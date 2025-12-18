<?php
/*require_once '../../config.php';
require_once '../../includes/classes/Database.php';
require_once '../../includes/classes/Chat.php';
require_once '../../includes/classes/User.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'tailor') {
    header('Location: ../auth/login.php');
    exit();
}

$tailorId = $_SESSION['user_id'];
$chat = new Chat();
$user = new User();

// Get conversation with specific user
$conversationUserId = $_GET['user_id'] ?? null;

// Get all conversations
$conversations = $chat->getConversations($tailorId);

// Get messages for specific conversation
$messages = [];
$otherUser = null;

if ($conversationUserId) {
    $messages = $chat->getMessages($tailorId, $conversationUserId);
    $otherUser = $user->getUserById($conversationUserId);
    
    // Mark messages as read
    $chat->markAsRead($tailorId, $conversationUserId);
}

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiverId = $_POST['receiver_id'];
    $message = $_POST['message'];
    
    if ($chat->sendMessage($tailorId, $receiverId, $message)) {
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
    <title>Messages - Tailor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .chat-container {
            height: calc(100vh - 200px);
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #dee2e6;
        }
        
        .conversations-list {
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
            overflow-y: auto;
        }
        
        .chat-messages {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }
        
        .message-input-container {
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
            background-color: #e9ecef;
        }
        
        .message-bubble {
            max-width: 70%;
            padding: 0.75rem 1rem;
            border-radius: 15px;
            margin-bottom: 0.5rem;
            position: relative;
        }
        
        .message-sent {
            background-color: #007bff;
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 5px;
        }
        
        .message-received {
            background-color: #e9ecef;
            color: #333;
            align-self: flex-start;
            border-bottom-left-radius: 5px;
        }
        
        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.25rem;
        }
        
        .unread-badge {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #007bff;
        }
    </style>
</head>
<body>
    <?php include '../../includes/components/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Messages</h4>
                    </div>
                    <div class="card-body p-0">
                        <div class="chat-container">
                            <div class="row h-100">
                                <!-- Conversations List -->
                                <div class="col-md-4 h-100">
                                    <div class="conversations-list h-100">
                                        <div class="p-3 border-bottom">
                                            <input type="text" class="form-control" placeholder="Search conversations...">
                                        </div>
                                        <?php if (!empty($conversations)): ?>
                                            <?php foreach ($conversations as $conv): ?>
                                                <a href="messages.php?user_id=<?php echo $conv['user_id']; ?>" 
                                                   class="text-decoration-none text-dark">
                                                    <div class="conversation-item <?php echo $conversationUserId == $conv['user_id'] ? 'active' : ''; ?>">
                                                        <div class="d-flex align-items-center">
                                                            <div class="position-relative">
                                                                <img src="../../assets/images/avatars/<?php echo $conv['profile_pic'] ?: 'default.jpg'; ?>" 
                                                                     class="rounded-circle me-3" 
                                                                     width="50" 
                                                                     height="50"
                                                                     alt="<?php echo htmlspecialchars($conv['full_name']); ?>">
                                                                <?php if ($conv['unread_count'] > 0): ?>
                                                                    <span class="unread-badge position-absolute top-0 end-0"></span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <div class="d-flex justify-content-between align-items-start">
                                                                    <div>
                                                                        <h6 class="mb-0"><?php echo htmlspecialchars($conv['full_name']); ?></h6>
                                                                        <p class="text-muted mb-0 small text-truncate" style="max-width: 150px;">
                                                                            <?php echo htmlspecialchars($conv['last_message']); ?>
                                                                        </p>
                                                                    </div>
                                                                    <div class="text-end">
                                                                        <small class="text-muted"><?php echo date('h:i A', strtotime($conv['last_message_time'])); ?></small>
                                                                        <?php if ($conv['unread_count'] > 0): ?>
                                                                            <span class="badge bg-primary rounded-pill"><?php echo $conv['unread_count']; ?></span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </a>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="p-4 text-center">
                                                <i class="bi bi-chat display-6 text-muted mb-3"></i>
                                                <h5>No conversations yet</h5>
                                                <p class="text-muted">When customers message you, conversations will appear here.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Chat Area -->
                                <div class="col-md-8 h-100">
                                    <?php if ($conversationUserId && $otherUser): ?>
                                        <div class="chat-messages h-100">
                                            <!-- Chat Header -->
                                            <div class="border-bottom p-3">
                                                <div class="d-flex align-items-center">
                                                    <img src="../../assets/images/avatars/<?php echo $otherUser['profile_pic'] ?: 'default.jpg'; ?>" 
                                                         class="rounded-circle me-3" 
                                                         width="50" 
                                                         height="50"
                                                         alt="<?php echo htmlspecialchars($otherUser['full_name']); ?>">
                                                    <div>
                                                        <h5 class="mb-0"><?php echo htmlspecialchars($otherUser['full_name']); ?></h5>
                                                        <small class="text-muted">Active now</small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Messages -->
                                            <div class="messages-container" id="messagesContainer">
                                                <?php if (!empty($messages)): ?>
                                                    <?php foreach ($messages as $msg): ?>
                                                        <div class="message-bubble <?php echo $msg['sender_id'] == $tailorId ? 'message-sent' : 'message-received'; ?>">
                                                            <?php echo htmlspecialchars($msg['message']); ?>
                                                            <div class="message-time">
                                                                <?php echo date('h:i A', strtotime($msg['created_at'])); ?>
                                                                <?php if ($msg['sender_id'] == $tailorId && $msg['is_read']): ?>
                                                                    <i class="bi bi-check-all ms-1"></i>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <div class="text-center my-5">
                                                        <i class="bi bi-chat-text display-6 text-muted mb-3"></i>
                                                        <p class="text-muted">No messages yet. Start the conversation!</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Message Input -->
                                            <div class="message-input-container">
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
                                                <p class="text-muted">Choose a conversation from the list to start messaging</p>
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
        // Auto-scroll to bottom of messages
        function scrollToBottom() {
            const container = document.getElementById('messagesContainer');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }
        
        // Scroll to bottom on page load
        document.addEventListener('DOMContentLoaded', function() {
            scrollToBottom();
            
            // Auto-refresh messages every 5 seconds
            if (<?php echo $conversationUserId ? 'true' : 'false'; ?>) {
                setInterval(function() {
                    // In a real application, you would make an AJAX call here
                    console.log('Refreshing messages...');
                }, 5000);
            }
            
            // Message form submission
            const messageForm = document.getElementById('messageForm');
            if (messageForm) {
                messageForm.addEventListener('submit', function(e) {
                    const input = this.querySelector('input[name="message"]');
                    if (input.value.trim() === '') {
                        e.preventDefault();
                        input.focus();
                    }
                });
            }
        });
    </script>
</body>
</html>

<?php
*/


require_once '../config.php';
require_once '../includes/classes/Chat.php';
require_once '../includes/classes/User.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$chat = new Chat();
$user = new User();

$currentUserId = $_SESSION['user_id'];
$conversations = $chat->getConversations($currentUserId);

// Get selected conversation
$selectedUserId = $_GET['user_id'] ?? null;
$messages = [];
$selectedUser = null;

if ($selectedUserId) {
    $messages = $chat->getMessages($currentUserId, $selectedUserId);
    $selectedUser = $user->getUserById($selectedUserId);
    // Mark messages as read
    $chat->markAsRead($selectedUserId, $currentUserId);
}

// Get unread counts
$unreadCounts = $chat->getUnreadCounts($currentUserId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --message-sent: linear-gradient(135deg, #667eea, #764ba2);
            --message-received: #f1f3f5;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea20 0%, #764ba220 100%);
            min-height: 100vh;
            margin: 0;
        }
        
        .chat-container {
            height: calc(100vh - 100px);
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
            margin: 20px auto;
        }
        
        /* Conversations Sidebar */
        .conversations-sidebar {
            border-right: 1px solid #e5e7eb;
            height: 100%;
            overflow-y: auto;
            background: #f8f9fa;
        }
        
        .conversations-header {
            background: var(--primary-gradient);
            color: white;
            padding: 1.5rem;
        }
        
        .conversation-item {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .conversation-item:hover {
            background: white;
        }
        
        .conversation-item.active {
            background: white;
            border-left: 4px solid #667eea;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .user-avatar.online::after {
            content: '';
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 10px;
            height: 10px;
            background: #43e97b;
            border-radius: 50%;
            border: 2px solid white;
        }
        
        .conversation-info {
            flex: 1;
        }
        
        .unread-badge {
            background: #ef4444;
            color: white;
            border-radius: 10px;
            padding: 2px 8px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        /* Chat Area */
        .chat-area {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .chat-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            background: white;
        }
        
        .messages-container {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            background: url('data:image/svg+xml,<svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M0 0h20v20H0z" fill="none"/><path d="M1 1h1v1H1zM3 3h1v1H3zM5 5h1v1H5zM7 7h1v1H7zM9 9h1v1H9zM11 11h1v1H11zM13 13h1v1H13zM15 15h1v1H15zM17 17h1v1H17z" fill="%23e5e7eb"/></svg>');
        }
        
        .message {
            max-width: 70%;
            margin-bottom: 1rem;
            animation: messageIn 0.3s ease;
        }
        
        @keyframes messageIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message.sent {
            margin-left: auto;
        }
        
        .message-content {
            padding: 12px 16px;
            border-radius: 18px;
            position: relative;
            word-wrap: break-word;
        }
        
        .message.sent .message-content {
            background: var(--message-sent);
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        .message.received .message-content {
            background: var(--message-received);
            color: #333;
            border-bottom-left-radius: 4px;
        }
        
        .message-time {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 4px;
            text-align: right;
        }
        
        .message.received .message-time {
            text-align: left;
        }
        
        .chat-input-container {
            padding: 1rem;
            border-top: 1px solid #e5e7eb;
            background: white;
        }
        
        .message-input {
            border: 2px solid #e5e7eb;
            border-radius: 25px;
            padding: 12px 20px;
            resize: none;
            transition: all 0.3s ease;
        }
        
        .message-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .send-button {
            background: var(--primary-gradient);
            border: none;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            position: absolute;
            right: 10px;
            bottom: 10px;
        }
        
        .send-button:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .attachment-button {
            position: absolute;
            left: 10px;
            bottom: 10px;
            background: transparent;
            border: none;
            color: #6b7280;
            font-size: 1.2rem;
        }
        
        .no-conversation {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            padding: 2rem;
            color: #6b7280;
        }
        
        .no-conversation-icon {
            font-size: 4rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }
        
        /* Online status */
        .online-status {
            font-size: 0.75rem;
            color: #43e97b;
            font-weight: 500;
        }
        
        .offline-status {
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        /* Typing indicator */
        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 8px 16px;
            background: var(--message-received);
            border-radius: 18px;
            width: fit-content;
            margin-bottom: 1rem;
        }
        
        .typing-indicator span {
            width: 8px;
            height: 8px;
            background: #6b7280;
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }
        
        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes typing {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        
        /* Scrollbar styling */
        .messages-container::-webkit-scrollbar {
            width: 6px;
        }
        
        .messages-container::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .messages-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .conversations-sidebar {
                position: absolute;
                width: 100%;
                height: 100%;
                z-index: 1000;
                display: none;
            }
            
            .conversations-sidebar.active {
                display: block;
            }
            
            .toggle-conversations {
                display: block;
            }
        }
        
        .toggle-conversations {
            display: none;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1001;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <?php include '../includes/components/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Toggle Button for Mobile -->
            <button class="toggle-conversations" id="toggleConversations">
                <i class="bi bi-chat-dots"></i>
            </button>
            
            <!-- Conversations Sidebar -->
            <div class="col-md-4 col-lg-3 p-0 conversations-sidebar active" id="conversationsSidebar">
                <div class="conversations-header">
                    <h4 class="fw-bold mb-0">Messages</h4>
                    <p class="mb-0 small">Chat with tailors and customers</p>
                </div>
                
                <div class="conversations-list">
                    <?php if (!empty($conversations)): ?>
                        <?php foreach ($conversations as $conv): ?>
                            <a href="?user_id=<?php echo $conv['other_user_id']; ?>" 
                               class="text-decoration-none text-dark">
                                <div class="conversation-item <?php echo $selectedUserId == $conv['other_user_id'] ? 'active' : ''; ?>">
                                    <div class="position-relative">
                                        <img src="<?php echo SITE_URL; ?>/assets/images/avatars/<?php echo $conv['profile_pic'] ?: 'default.jpg'; ?>" 
                                             class="user-avatar <?php echo $conv['is_online'] ? 'online' : ''; ?>">
                                    </div>
                                    <div class="conversation-info">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($conv['full_name']); ?></h6>
                                            <small class="text-muted"><?php echo date('h:i A', strtotime($conv['last_message_time'])); ?></small>
                                        </div>
                                        <p class="text-muted mb-1 text-truncate" style="max-width: 200px;">
                                            <?php echo htmlspecialchars($conv['last_message']); ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="small <?php echo $conv['is_online'] ? 'online-status' : 'offline-status'; ?>">
                                                <?php echo $conv['is_online'] ? 'Online' : 'Offline'; ?>
                                            </span>
                                            <?php if ($conv['unread_count'] > 0): ?>
                                                <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-chat-dots display-4 text-muted mb-3"></i>
                            <p class="text-muted">No conversations yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Chat Area -->
            <div class="col-md-8 col-lg-9 p-0">
                <div class="chat-area">
                    <?php if ($selectedUser): ?>
                        <!-- Chat Header -->
                        <div class="chat-header d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <img src="<?php echo SITE_URL; ?>/assets/images/avatars/<?php echo $selectedUser['profile_pic'] ?: 'default.jpg'; ?>" 
                                     class="user-avatar <?php echo $selectedUser['is_online'] ? 'online' : ''; ?>">
                                <div>
                                    <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($selectedUser['full_name']); ?></h5>
                                    <small class="<?php echo $selectedUser['is_online'] ? 'online-status' : 'offline-status'; ?>">
                                        <?php echo $selectedUser['is_online'] ? 'Online' : 'Last seen 2h ago'; ?>
                                    </small>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-telephone"></i>
                                </button>
                                <button class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-info-circle"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Messages Container -->
                        <div class="messages-container" id="messagesContainer">
                            <?php if (!empty($messages)): ?>
                                <?php foreach ($messages as $message): ?>
                                    <div class="message <?php echo $message['sender_id'] == $currentUserId ? 'sent' : 'received'; ?>">
                                        <div class="message-content">
                                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                            <?php if ($message['attachment']): ?>
                                                <div class="mt-2">
                                                    <img src="<?php echo SITE_URL; ?>/assets/uploads/chat/<?php echo $message['attachment']; ?>" 
                                                         class="img-fluid rounded" 
                                                         style="max-width: 200px;">
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="message-time">
                                            <?php echo date('h:i A', strtotime($message['created_at'])); ?>
                                            <?php if ($message['sender_id'] == $currentUserId && $message['is_read']): ?>
                                                <i class="bi bi-check2-all text-primary ms-1"></i>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <p class="text-muted">No messages yet. Start the conversation!</p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Typing Indicator (hidden by default) -->
                            <div class="typing-indicator" id="typingIndicator" style="display: none;">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                        </div>
                        
                        <!-- Chat Input -->
                        <div class="chat-input-container position-relative">
                            <form id="messageForm" method="POST" enctype="multipart/form-data">
                                <div class="position-relative">
                                    <textarea class="form-control message-input" 
                                              id="messageInput" 
                                              name="message" 
                                              rows="1" 
                                              placeholder="Type your message..."
                                              required></textarea>
                                    
                                    <input type="file" 
                                           id="fileInput" 
                                           name="attachment" 
                                           accept="image/*" 
                                           style="display: none;">
                                    
                                    <button type="button" class="attachment-button" id="attachButton">
                                        <i class="bi bi-paperclip"></i>
                                    </button>
                                    
                                    <button type="submit" class="send-button" id="sendButton">
                                        <i class="bi bi-send"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <!-- No Conversation Selected -->
                        <div class="no-conversation">
                            <i class="bi bi-chat-dots no-conversation-icon"></i>
                            <h4 class="fw-bold mb-3">Select a conversation</h4>
                            <p class="text-muted mb-4">Choose a conversation from the sidebar or start a new one</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                                <i class="bi bi-plus-circle me-2"></i> New Message
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- New Message Modal -->
    <div class="modal fade" id="newMessageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="newConversationForm">
                        <div class="mb-3">
                            <label class="form-label">Search User</label>
                            <input type="text" class="form-control" id="userSearch" placeholder="Start typing name...">
                            <div id="searchResults" class="mt-2"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" id="initialMessage" rows="3" placeholder="Type your message..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="startConversation">Send Message</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            const currentUserId = <?php echo $currentUserId; ?>;
            const selectedUserId = <?php echo $selectedUserId ?? 'null'; ?>;
            
            // Auto-resize textarea
            $('#messageInput').on('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
            
            // Send message
            $('#messageForm').on('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('receiver_id', selectedUserId);
                formData.append('action', 'send_message');
                
                $.ajax({
                    url: '../api/chat.php',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            // Add message to chat
                            addMessage(response.message);
                            
                            // Clear input
                            $('#messageInput').val('');
                            $('#messageInput').css('height', 'auto');
                            
                            // Scroll to bottom
                            scrollToBottom();
                        }
                    }
                });
            });
            
            // Load new messages
            function loadNewMessages() {
                if (!selectedUserId) return;
                
                $.ajax({
                    url: '../api/chat.php',
                    method: 'GET',
                    data: {
                        action: 'get_new_messages',
                        user_id: selectedUserId,
                        last_message_id: getLastMessageId()
                    },
                    success: function(response) {
                        if (response.messages.length > 0) {
                            response.messages.forEach(function(message) {
                                addMessage(message);
                            });
                            scrollToBottom();
                        }
                    }
                });
            }
            
            // Add message to chat
            function addMessage(message) {
                const messageClass = message.sender_id == currentUserId ? 'sent' : 'received';
                const messageHtml = `
                    <div class="message ${messageClass}" data-id="${message.id}">
                        <div class="message-content">
                            ${escapeHtml(message.message)}
                            ${message.attachment ? 
                                `<div class="mt-2">
                                    <img src="${SITE_URL}/assets/uploads/chat/${message.attachment}" 
                                         class="img-fluid rounded" 
                                         style="max-width: 200px;">
                                </div>` : ''}
                        </div>
                        <div class="message-time">
                            ${formatTime(message.created_at)}
                            ${message.sender_id == currentUserId && message.is_read ? 
                                '<i class="bi bi-check2-all text-primary ms-1"></i>' : ''}
                        </div>
                    </div>
                `;
                
                $('#messagesContainer').append(messageHtml);
                animateMessage($('#messagesContainer .message:last-child'));
            }
            
            // Helper functions
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML.replace(/\n/g, '<br>');
            }
            
            function formatTime(timeString) {
                const date = new Date(timeString);
                return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            }
            
            function getLastMessageId() {
                const lastMessage = $('#messagesContainer .message:last-child');
                return lastMessage.length ? lastMessage.data('id') : 0;
            }
            
            function animateMessage(element) {
                element.css({
                    opacity: 0,
                    transform: 'translateY(20px)'
                });
                
                setTimeout(() => {
                    element.css({
                        opacity: 1,
                        transform: 'translateY(0)',
                        transition: 'all 0.3s ease'
                    });
                }, 10);
            }
            
            function scrollToBottom() {
                const container = $('#messagesContainer');
                container.scrollTop(container[0].scrollHeight);
            }
            
            // Auto-scroll to bottom on load
            scrollToBottom();
            
            // Check for new messages every 3 seconds
            if (selectedUserId) {
                setInterval(loadNewMessages, 3000);
            }
            
            // Search users for new conversation
            $('#userSearch').on('input', function() {
                const query = $(this).val();
                if (query.length > 2) {
                    $.ajax({
                        url: '../api/chat.php',
                        method: 'GET',
                        data: { action: 'search_users', query: query },
                        success: function(response) {
                            let html = '';
                            response.users.forEach(function(user) {
                                html += `
                                    <div class="user-search-result p-2 border-bottom" 
                                         data-id="${user.id}" 
                                         style="cursor: pointer;">
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="${SITE_URL}/assets/images/avatars/${user.profile_pic || 'default.jpg'}" 
                                                 class="rounded-circle" 
                                                 width="40" 
                                                 height="40">
                                            <div>
                                                <h6 class="mb-0">${user.full_name}</h6>
                                                <small class="text-muted">${user.user_type}</small>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                            $('#searchResults').html(html);
                        }
                    });
                }
            });
            
            // Select user from search results
            $(document).on('click', '.user-search-result', function() {
                const userId = $(this).data('id');
                const userName = $(this).find('h6').text();
                
                $('#userSearch').val(userName);
                $('#searchResults').html('');
                
                // Store selected user ID
                $(this).data('selected-user-id', userId);
            });
            
            // Start new conversation
            $('#startConversation').on('click', function() {
                const userId = $('.user-search-result[data-selected-user-id]').data('selected-user-id');
                const message = $('#initialMessage').val();
                
                if (!userId || !message) {
                    alert('Please select a user and enter a message');
                    return;
                }
                
                $.ajax({
                    url: '../api/chat.php',
                    method: 'POST',
                    data: {
                        action: 'start_conversation',
                        receiver_id: userId,
                        message: message
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.href = `?user_id=${userId}`;
                        }
                    }
                });
            });
            
            // Toggle conversations sidebar on mobile
            $('#toggleConversations').on('click', function() {
                $('#conversationsSidebar').toggleClass('active');
            });
            
            // Handle file attachment
            $('#attachButton').on('click', function() {
                $('#fileInput').click();
            });
            
            $('#fileInput').on('change', function() {
                if (this.files.length > 0) {
                    $('#messageInput').val($('#messageInput').val() + ' [Attachment]');
                }
            });
            
            // Typing indicator
            let typingTimer;
            $('#messageInput').on('input', function() {
                clearTimeout(typingTimer);
                
                // Show typing indicator
                $.ajax({
                    url: '../api/chat.php',
                    method: 'POST',
                    data: {
                        action: 'typing',
                        receiver_id: selectedUserId
                    }
                });
                
                // Hide typing indicator after 2 seconds of inactivity
                typingTimer = setTimeout(function() {
                    $.ajax({
                        url: '../api/chat.php',
                        method: 'POST',
                        data: {
                            action: 'stop_typing',
                            receiver_id: selectedUserId
                        }
                    });
                }, 2000);
            });
            
            // Check if other user is typing
            function checkTyping() {
                if (!selectedUserId) return;
                
                $.ajax({
                    url: '../api/chat.php',
                    method: 'GET',
                    data: {
                        action: 'check_typing',
                        user_id: selectedUserId
                    },
                    success: function(response) {
                        if (response.typing) {
                            $('#typingIndicator').show();
                        } else {
                            $('#typingIndicator').hide();
                        }
                    }
                });
            }
            
            if (selectedUserId) {
                setInterval(checkTyping, 1000);
            }
        });
        
        // Initialize SITE_URL constant for JavaScript
        const SITE_URL = '<?php echo SITE_URL; ?>';
    </script>
</body>
</html>
*/