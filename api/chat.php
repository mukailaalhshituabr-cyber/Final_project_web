<?php
require_once '../config.php';
require_once '../includes/classes/Chat.php';
require_once '../includes/classes/User.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Authentication required']);
    exit();
}

$chat = new Chat();
$user = new User();
$currentUserId = $_SESSION['user_id'];

// Handle different actions
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'send_message':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $receiverId = $_POST['receiver_id'] ?? 0;
            $message = $_POST['message'] ?? '';
            $orderId = $_POST['order_id'] ?? null;
            
            // Handle file upload
            $attachment = null;
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = UPLOAD_PATH . 'chat/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $filename = uniqid() . '_' . basename($_FILES['attachment']['name']);
                $targetFile = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFile)) {
                    $attachment = $filename;
                }
            }
            
            $messageId = $chat->sendMessage($currentUserId, $receiverId, $message, $orderId, $attachment);
            
            if ($messageId) {
                // Get the sent message
                $this->db->query("SELECT * FROM messages WHERE id = :id");
                $this->db->bind(':id', $messageId);
                $sentMessage = $this->db->single();
                
                echo json_encode([
                    'success' => true,
                    'message' => $sentMessage
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to send message']);
            }
        }
        break;
        
    case 'get_new_messages':
        if (isset($_GET['user_id']) && isset($_GET['last_message_id'])) {
            $otherUserId = $_GET['user_id'];
            $lastMessageId = $_GET['last_message_id'];
            
            $this->db->query("
                SELECT m.*, u.profile_pic as sender_pic
                FROM messages m
                LEFT JOIN users u ON m.sender_id = u.id
                WHERE ((m.sender_id = :current_user AND m.receiver_id = :other_user)
                    OR (m.sender_id = :other_user AND m.receiver_id = :current_user))
                AND m.id > :last_message_id
                ORDER BY m.created_at ASC
            ");
            
            $this->db->bind(':current_user', $currentUserId);
            $this->db->bind(':other_user', $otherUserId);
            $this->db->bind(':last_message_id', $lastMessageId);
            
            $messages = $this->db->resultSet();
            
            // Mark as read
            $chat->markAsRead($otherUserId, $currentUserId);
            
            echo json_encode(['success' => true, 'messages' => $messages]);
        }
        break;
        
    case 'search_users':
        if (isset($_GET['query'])) {
            $users = $chat->searchUsers($_GET['query'], $currentUserId);
            echo json_encode(['success' => true, 'users' => $users]);
        }
        break;
        
    case 'start_conversation':
        if (isset($_POST['receiver_id']) && isset($_POST['message'])) {
            $receiverId = $_POST['receiver_id'];
            $message = $_POST['message'];
            
            $messageId = $chat->sendMessage($currentUserId, $receiverId, $message);
            
            if ($messageId) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false]);
            }
        }
        break;
        
    case 'typing':
        if (isset($_POST['receiver_id'])) {
            $chat->updateTypingStatus($currentUserId, $_POST['receiver_id'], true);
            echo json_encode(['success' => true]);
        }
        break;
        
    case 'stop_typing':
        if (isset($_POST['receiver_id'])) {
            $chat->updateTypingStatus($currentUserId, $_POST['receiver_id'], false);
            echo json_encode(['success' => true]);
        }
        break;
        
    case 'check_typing':
        if (isset($_GET['user_id'])) {
            $isTyping = $chat->getTypingStatus($_GET['user_id'], $currentUserId);
            echo json_encode(['success' => true, 'typing' => $isTyping]);
        }
        break;
        
    case 'get_unread_count':
        $count = $chat->getUnreadMessageCount($currentUserId);
        echo json_encode(['success' => true, 'count' => $count]);
        break;
        
    case 'mark_read':
        if (isset($_POST['message_id'])) {
            $this->db->query("UPDATE messages SET is_read = 1 WHERE id = :id AND receiver_id = :user_id");
            $this->db->bind(':id', $_POST['message_id']);
            $this->db->bind(':user_id', $currentUserId);
            $this->db->execute();
            
            echo json_encode(['success' => true]);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>