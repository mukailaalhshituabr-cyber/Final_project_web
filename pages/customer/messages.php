<?php
require_once dirname(__DIR__, 2) . '/config.php';
require_once ROOT_PATH . '/includes/classes/Database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit();
}

$db = Database::getInstance();
$userId = $_SESSION['user_id'];

// Get conversations
$db->query("
    SELECT c.*, 
           CASE 
               WHEN c.user1_id = :user_id THEN u2.full_name
               ELSE u1.full_name
           END as other_user_name,
           CASE 
               WHEN c.user1_id = :user_id THEN u2.profile_pic
               ELSE u1.profile_pic
           END as other_user_avatar,
           m.message as last_message_text,
           m.message_type,
           m.created_at as last_message_time
    FROM conversations c
    LEFT JOIN users u1 ON c.user1_id = u1.id
    LEFT JOIN users u2 ON c.user2_id = u2.id
    LEFT JOIN messages m ON c.last_message_id = m.id
    WHERE (c.user1_id = :user_id OR c.user2_id = :user_id)
    ORDER BY c.last_message_at DESC
");
$db->bind(':user_id', $userId);
$conversations = $db->resultSet();

// Get specific conversation
$conversationId = $_GET['conversation_id'] ?? null;
$otherUserId = $_GET['user_id'] ?? null;
$currentConversation = null;
$messages = [];

if ($conversationId) {
    // Get conversation details
    $db->query("
        SELECT c.*, 
               u1.full_name as user1_name,
               u2.full_name as user2_name,
               u1.profile_pic as user1_avatar,
               u2.profile_pic as user2_avatar
        FROM conversations c
        JOIN users u1 ON c.user1_id = u1.id
        JOIN users u2 ON c.user2_id = u2.id
        WHERE c.id = :conversation_id
    ");
    $db->bind(':conversation_id', $conversationId);
    $currentConversation = $db->single();
    
    // Get messages
    $db->query("
        SELECT m.*, u.full_name as sender_name, u.profile_pic as sender_avatar
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.conversation_id = :conversation_id
        ORDER BY m.created_at ASC
    ");
    $db->bind(':conversation_id', $conversationId);
    $messages = $db->resultSet();
    
    // Mark messages as read
    $db->query("
        UPDATE messages 
        SET is_read = 1, read_at = NOW() 
        WHERE conversation_id = :conversation_id 
        AND receiver_id = :user_id 
        AND is_read = 0
    ");
    $db->bind(':conversation_id', $conversationId);
    $db->bind(':user_id', $userId);
    $db->execute();
} elseif ($otherUserId) {
    // Check if conversation exists
    $db->query("
        SELECT id FROM conversations 
        WHERE (user1_id = :user1_id AND user2_id = :user2_id) 
        OR (user1_id = :user2_id AND user2_id = :user1_id)
    ");
    $db->bind(':user1_id', $userId);
    $db->bind(':user2_id', $otherUserId);
    $existing = $db->single();
    
    if ($existing) {
        header('Location: messages.php?conversation_id=' . $existing['id']);
        exit();
    }
}

// Handle new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $conversationId = $_POST['conversation_id'] ?? null;
    $receiverId = $_POST['receiver_id'] ?? null;
    $message = trim($_POST['message']);
    $messageType = 'text';
    
    if ($message && ($conversationId || $receiverId)) {
        try {
            $db->beginTransaction();
            
            if (!$conversationId && $receiverId) {
                // Create new conversation
                $user1 = min($userId, $receiverId);
                $user2 = max($userId, $receiverId);
                
                $db->query("
                    INSERT INTO conversations (user1_id, user2_id, last_message_at) 
                    VALUES (:user1_id, :user2_id, NOW())
                ");
                $db->bind(':user1_id', $user1);
                $db->bind(':user2_id', $user2);
                $db->execute();
                
                $conversationId = $db->lastInsertId();
            }
            
            // Insert message
            $db->query("
                INSERT INTO messages (conversation_id, sender_id, receiver_id, message_type, message) 
                VALUES (:conversation_id, :sender_id, :receiver_id, :message_type, :message)
            ");
            $db->bind(':conversation_id', $conversationId);
            $db->bind(':sender_id', $userId);
            $db->bind(':receiver_id', $receiverId);
            $db->bind(':message_type', $messageType);
            $db->bind(':message', $message);
            $db->execute();
            
            $messageId = $db->lastInsertId();
            
            // Update conversation
            $db->query("
                UPDATE conversations 
                SET last_message_id = :message_id, 
                    last_message_at = NOW(),
                    unread_count_user1 = CASE 
                        WHEN user1_id = :receiver_id THEN unread_count_user1 + 1 
                        ELSE unread_count_user1 
                    END,
                    unread_count_user2 = CASE 
                        WHEN user2_id = :receiver_id THEN unread_count_user2 + 1 
                        ELSE unread_count_user2 
                    END
                WHERE id = :conversation_id
            ");
            $db->bind(':message_id', $messageId);
            $db->bind(':receiver_id', $receiverId);
            $db->bind(':conversation_id', $conversationId);
            $db->execute();
            
            $db->commit();
            
            header('Location: messages.php?conversation_id=' . $conversationId);
            exit();
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Failed to send message: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .messages-container {
            height: calc(100vh - 150px);
            max-height: 800px;
        }
        .conversations-sidebar {
            border-right: 1px solid #e9ecef;
            height: 100%;
            overflow-y: auto;
        }
        .conversation-item {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .conversation-item:hover {
            background-color: #f8f9fa;
        }
        .conversation-item.active {
            background-color: #e7f1ff;
            border-right: 3px solid #667eea;
        }
        .conversation-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        .message-area {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .messages-header {
            border-bottom: 1px solid #e9ecef;
            padding: 1rem;
            background: #f8f9fa;
        }
        .messages-list {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }
        .message-input-area {
            border-top: 1px solid #e9ecef;
            padding: 1rem;
            background: #f8f9fa;
        }
        .message-bubble {
            max-width: 70%;
            padding: 0.75rem 1rem;
            border-radius: 15px;
            margin-bottom: 0.5rem;
            position: relative;
        }
        .message-sent {
            background: #667eea;
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 5px;
        }
        .message-received {
            background: #e9ecef;
            color: #333;
            margin-right: auto;
            border-bottom-left-radius: 5px;
        }
        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.25rem;
        }
        .unread-badge {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #667eea;
            display: inline-block;
            margin-right: 0.5rem;
        }
        .empty-conversation {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }
        .typing-indicator {
            display: flex;
            align-items: center;
            color: #6c757d;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        .typing-dots {
            display: flex;
            margin-left: 0.5rem;
        }
        .typing-dots span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #6c757d;
            margin: 0 2px;
            animation: typing 1.4s infinite;
        }
        .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
        .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-5px); }
        }
        
        @media (max-width: 768px) {
            .conversations-sidebar {
                height: 300px;
                border-right: none;
                border-bottom: 1px solid #e9ecef;
            }
            .messages-container {
                height: auto;
                max-height: none;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <h1 class="h3 fw-bold mb-4">Messages</h1>
            </div>
        </div>
        
        <div class="row messages-container bg-white rounded shadow-sm overflow-hidden">
            <!-- Conversations Sidebar -->
            <div class="col-md-4 col-lg-3 p-0 conversations-sidebar">
                <div class="p-3 border-bottom">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" placeholder="Search conversations...">
                    </div>
                </div>
                
                <?php if (!empty($conversations)): ?>
                    <?php foreach ($conversations as $conv): 
                        $isActive = $currentConversation && $currentConversation['id'] == $conv['id'];
                        $unreadCount = ($userId == $conv['user1_id']) ? $conv['unread_count_user1'] : $conv['unread_count_user2'];
                    ?>
                    <div class="conversation-item <?php echo $isActive ? 'active' : ''; ?>" 
                         onclick="window.location.href='messages.php?conversation_id=<?php echo $conv['id']; ?>'">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo ASSETS_URL . 'images/avatars/' . ($conv['other_user_avatar'] ?: 'default.jpg'); ?>" 
                                 class="conversation-avatar me-3"
                                 alt="<?php echo htmlspecialchars($conv['other_user_name']); ?>"
                                 onerror="this.src='<?php echo ASSETS_URL; ?>images/avatars/default.jpg'">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($conv['other_user_name']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo $conv['last_message_time'] ? date('h:i A', strtotime($conv['last_message_time'])) : ''; ?>
                                    </small>
                                </div>
                                <p class="mb-0 text-truncate" style="max-width: 200px;">
                                    <?php if ($conv['last_message_text']): ?>
                                        <?php if ($conv['message_type'] == 'image'): ?>
                                            <i class="bi bi-image me-1"></i>Photo
                                        <?php elseif ($conv['message_type'] == 'file'): ?>
                                            <i class="bi bi-file me-1"></i>File
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($conv['last_message_text']); ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">No messages yet</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <?php if ($unreadCount > 0): ?>
                            <span class="badge bg-primary rounded-pill ms-2"><?php echo $unreadCount; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-conversation">
                        <i class="bi bi-chat display-4 mb-3"></i>
                        <h5>No conversations</h5>
                        <p class="text-muted">Start a conversation with a tailor</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Messages Area -->
            <div class="col-md-8 col-lg-9 p-0 message-area">
                <?php if ($currentConversation): 
                    $otherUserId = ($currentConversation['user1_id'] == $userId) ? $currentConversation['user2_id'] : $currentConversation['user1_id'];
                    $otherUserName = ($currentConversation['user1_id'] == $userId) ? $currentConversation['user2_name'] : $currentConversation['user1_name'];
                    $otherUserAvatar = ($currentConversation['user1_id'] == $userId) ? $currentConversation['user2_avatar'] : $currentConversation['user1_avatar'];
                ?>
                    <!-- Messages Header -->
                    <div class="messages-header">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo ASSETS_URL . 'images/avatars/' . ($otherUserAvatar ?: 'default.jpg'); ?>" 
                                 class="rounded-circle me-3" 
                                 width="50" 
                                 height="50"
                                 alt="<?php echo htmlspecialchars($otherUserName); ?>"
                                 onerror="this.src='<?php echo ASSETS_URL; ?>images/avatars/default.jpg'">
                            <div>
                                <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($otherUserName); ?></h5>
                                <small class="text-muted">
                                    <?php echo ($currentConversation['last_message_at'] && $currentConversation['last_message_at'] != '0000-00-00 00:00:00') 
                                        ? 'Last seen ' . date('h:i A', strtotime($currentConversation['last_message_at'])) 
                                        : 'Active recently'; ?>
                                </small>
                            </div>
                            <div class="ms-auto">
                                <button class="btn btn-outline-secondary btn-sm me-2">
                                    <i class="bi bi-telephone"></i>
                                </button>
                                <button class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Messages List -->
                    <div class="messages-list" id="messagesList">
                        <?php if (!empty($messages)): ?>
                            <?php foreach ($messages as $msg): 
                                $isSent = $msg['sender_id'] == $userId;
                            ?>
                            <div class="d-flex mb-3 <?php echo $isSent ? 'justify-content-end' : 'justify-content-start'; ?>">
                                <div class="message-bubble <?php echo $isSent ? 'message-sent' : 'message-received'; ?>">
                                    <?php if ($msg['message_type'] == 'image'): ?>
                                        <img src="<?php echo htmlspecialchars($msg['file_url']); ?>" 
                                             class="img-fluid rounded"
                                             alt="Image"
                                             style="max-width: 300px;">
                                    <?php elseif ($msg['message_type'] == 'file'): ?>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-file-earmark-text fs-4 me-2"></i>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($msg['file_name']); ?></div>
                                                <small><?php echo format_file_size($msg['file_size']); ?></small>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                    <?php endif; ?>
                                    <div class="message-time text-end">
                                        <?php echo date('h:i A', strtotime($msg['created_at'])); ?>
                                        <?php if ($isSent): ?>
                                            <i class="bi bi-check2-all ms-1 <?php echo $msg['is_read'] ? 'text-info' : 'text-muted'; ?>"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-conversation">
                                <i class="bi bi-chat-text display-4 mb-3"></i>
                                <h5>No messages yet</h5>
                                <p class="text-muted">Send a message to start the conversation</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Message Input -->
                    <div class="message-input-area">
                        <form method="POST" action="" id="messageForm">
                            <input type="hidden" name="conversation_id" value="<?php echo $currentConversation['id']; ?>">
                            <input type="hidden" name="receiver_id" value="<?php echo $otherUserId; ?>">
                            
                            <div class="d-flex align-items-center">
                                <button type="button" class="btn btn-outline-secondary me-2" onclick="toggleAttachment()">
                                    <i class="bi bi-paperclip"></i>
                                </button>
                                <div class="flex-grow-1 position-relative">
                                    <textarea name="message" id="messageInput" 
                                              class="form-control" 
                                              rows="1" 
                                              placeholder="Type your message..."
                                              style="resize: none;"></textarea>
                                    <div id="attachmentArea" class="mt-2 d-none">
                                        <input type="file" id="fileInput" class="form-control form-control-sm" accept="image/*,.pdf,.doc,.docx">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary ms-2">
                                    <i class="bi bi-send"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="empty-conversation h-100 d-flex flex-column align-items-center justify-content-center">
                        <i class="bi bi-chat-left-text display-1 mb-3 text-muted"></i>
                        <h4>Select a conversation</h4>
                        <p class="text-muted">Choose a conversation from the list to start messaging</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../../includes/components/footer.php'; ?>

    <script>
        // Auto-resize textarea
        const textarea = document.getElementById('messageInput');
        if (textarea) {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });
        }
        
        // Scroll to bottom of messages
        const messagesList = document.getElementById('messagesList');
        if (messagesList) {
            messagesList.scrollTop = messagesList.scrollHeight;
        }
        
        // Toggle attachment area
        function toggleAttachment() {
            const attachmentArea = document.getElementById('attachmentArea');
            attachmentArea.classList.toggle('d-none');
        }
        
        // Handle file upload
        const fileInput = document.getElementById('fileInput');
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const formData = new FormData();
                    formData.append('file', this.files[0]);
                    formData.append('conversation_id', document.querySelector('input[name="conversation_id"]').value);
                    formData.append('receiver_id', document.querySelector('input[name="receiver_id"]').value);
                    
                    fetch('upload-message-file.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + data.error);
                        }
                    });
                }
            });
        }
        
        // Auto-refresh messages every 10 seconds
        setInterval(() => {
            if (window.location.search.includes('conversation_id')) {
                fetch(window.location.href)
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newMessages = doc.querySelector('#messagesList');
                        if (newMessages) {
                            document.getElementById('messagesList').innerHTML = newMessages.innerHTML;
                            document.getElementById('messagesList').scrollTop = document.getElementById('messagesList').scrollHeight;
                        }
                    });
            }
        }, 10000);
        
        // Enter key to send message
        if (textarea) {
            textarea.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    document.getElementById('messageForm').submit();
                }
            });
        }
    </script>
    
    <?php
    function format_file_size($bytes) {
        if ($bytes == 0) return '0 Bytes';
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
    ?>
</body>
</html>