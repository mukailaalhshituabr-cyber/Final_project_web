<?php
require_once '../../config.php';
require_once '../../includes/classes/Database.php';
require_once '../../includes/classes/Chat.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'customer') {
    header('Location: ../auth/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$chat = new Chat();

// Get conversations
$conversations = $chat->getConversations($userId);

// Get messages for specific conversation
$conversationUserId = $_GET['user_id'] ?? null;
$messages = [];
$otherUser = null;

if ($conversationUserId) {
    $db = Database::getInstance();
    $db->query("SELECT * FROM users WHERE id = :id");
    $db->bind(':id', $conversationUserId);
    $otherUser = $db->single();
    
    $messages = $chat->getMessages($userId, $conversationUserId);
}

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiverId = $_POST['receiver_id'];
    $messageText = $_POST['message'];
    
    if ($chat->sendMessage($userId, $receiverId, $messageText)) {
        header("Location: messages.php?user_id=$receiverId");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Clothing Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .chat-container {
            height: 600px;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .conversations-sidebar {
            border-right: 1px solid #dee2e6;
            height: 100%;
            overflow-y: auto;
        }
        
        .chat-area {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }
        
        .message-input {
            border-top: 1px solid #dee2e6;
            padding: 1rem;
        }
        
        .conversation-item {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .conversation-item:hover, .conversation-item.active {
            background-color: #f8f9fa;
        }
        
        .message-bubble {
            max-width: 70%;
            padding: 0.75rem 1rem;
            border-radius: 15px;
            margin-bottom: 0.5rem;
        }
        
        .message-sent {
            background-color: #007bff;
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 5px;
        }
        
        .message-received {
            background-color: #e9ecef;
            color: #333;
            margin-right: auto;
            border-bottom-left-radius: 5px;
        }
        
        .unread-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #007bff;
        }
    </style>
</head>
<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Messages</li>
                    </ol>
                </nav>
                
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Messages</h4>
                    </div>
                    
                    <div class="card-body p-0">
                        <div class="chat-container">
                            <div class="row h-100">
                                <!-- Conversations List -->
                                <div class="col-md-4 h-100 p-0">
                                    <div class="conversations-sidebar">
                                        <?php if (!empty($conversations)): ?>
                                            <?php foreach ($conversations as $conv): ?>
                                                <a href="messages.php?user_id=<?php echo $conv['user_id']; ?>" 
                                                   class="text-decoration-none text-dark">
                                                    <div class="conversation-item <?php echo $conversationUserId == $conv['user_id'] ? 'active' : ''; ?>">
                                                        <div class="d-flex align-items-center">
                                                            <div class="position-relative">
                                                                <img src="../../assets/images/avatars/<?php echo $conv['profile_pic'] ?: 'default.jpg'; ?>" 
                                                                     class="rounded-circle me-3" 
                                                                     width="45" 
                                                                     height="45">
                                                                <?php if ($conv['unread_count'] > 0): ?>
                                                                    <span class="unread-dot position-absolute top-0 end-0"></span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <div class="d-flex justify-content-between">
                                                                    <h6 class="mb-0"><?php echo htmlspecialchars($conv['full_name']); ?></h6>
                                                                    <small class="text-muted"><?php echo date('h:i A', strtotime($conv['last_message_time'])); ?></small>
                                                                </div>
                                                                <p class="text-muted small mb-0 text-truncate">
                                                                    <?php echo htmlspecialchars($conv['last_message']); ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </a>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="p-4 text-center">
                                                <i class="bi bi-chat display-6 text-muted mb-3"></i>
                                                <h6>No conversations</h6>
                                                <p class="small text-muted">Start a conversation with a tailor</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Chat Area -->
                                <div class="col-md-8 h-100 p-0">
                                    <?php if ($conversationUserId && $otherUser): ?>
                                        <div class="chat-area">
                                            <!-- Chat Header -->
                                            <div class="border-bottom p-3 bg-light">
                                                <div class="d-flex align-items-center">
                                                    <img src="../../assets/images/avatars/<?php echo $otherUser['profile_pic'] ?: 'default.jpg'; ?>" 
                                                         class="rounded-circle me-3" 
                                                         width="45" 
                                                         height="45">
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($otherUser['full_name']); ?></h6>
                                                        <small class="text-muted">Tailor</small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Messages -->
                                            <div class="messages-container" id="messagesContainer">
                                                <?php if (!empty($messages)): ?>
                                                    <?php foreach ($messages as $msg): ?>
                                                        <div class="message-bubble <?php echo $msg['sender_id'] == $userId ? 'message-sent' : 'message-received'; ?>">
                                                            <?php echo htmlspecialchars($msg['message']); ?>
                                                            <div class="small text-end mt-1">
                                                                <?php echo date('h:i A', strtotime($msg['created_at'])); ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Message Input -->
                                            <div class="message-input">
                                                <form method="POST" id="messageForm">
                                                    <input type="hidden" name="receiver_id" value="<?php echo $conversationUserId; ?>">
                                                    <div class="input-group">
                                                        <input type="text" 
                                                               class="form-control" 
                                                               name="message" 
                                                               placeholder="Type your message..." 
                                                               required>
                                                        <button class="btn btn-primary" type="submit" name="send_message">
                                                            <i class="bi bi-send"></i>
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="h-100 d-flex align-items-center justify-content-center">
                                            <div class="text-center">
                                                <i class="bi bi-chat-left-text display-6 text-muted mb-3"></i>
                                                <h5>Select a conversation</h5>
                                                <p class="text-muted">Choose a conversation from the list</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Scroll to bottom of messages
        function scrollToBottom() {
            const container = document.getElementById('messagesContainer');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }
        
        // Auto-refresh messages
        function refreshMessages() {
            if (<?php echo $conversationUserId ? 'true' : 'false'; ?>) {
                fetch('../../api/chat.php?action=get_messages&user_id=<?php echo $conversationUserId; ?>')
                    .then(response => response.json())
                    .then(data => {
                        if (data.messages) {
                            // Update messages display
                            // In a real app, you would update the messages
                        }
                    });
            }
        }
        
        // Initial setup
        document.addEventListener('DOMContentLoaded', function() {
            scrollToBottom();
            
            // Refresh every 5 seconds
            setInterval(refreshMessages, 5000);
            
            // Form submission
            const form = document.getElementById('messageForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const input = this.querySelector('input[name="message"]');
                    if (!input.value.trim()) {
                        e.preventDefault();
                        input.focus();
                    }
                });
            }
        });
    </script>
</body>
</html>


