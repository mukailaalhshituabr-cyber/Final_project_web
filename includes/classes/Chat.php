<?php
require_once 'includes/config/database.php';

class Chat {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function sendMessage($senderId, $receiverId, $message, $orderId = null, $attachment = null) {
        $this->db->query("INSERT INTO messages (sender_id, receiver_id, order_id, message, attachment) 
                         VALUES (:sender_id, :receiver_id, :order_id, :message, :attachment)");
        
        $this->db->bind(':sender_id', $senderId);
        $this->db->bind(':receiver_id', $receiverId);
        $this->db->bind(':order_id', $orderId);
        $this->db->bind(':message', $message);
        $this->db->bind(':attachment', $attachment);
        
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }
    
    public function getMessages($userId1, $userId2, $limit = 50) {
        $this->db->query("
            SELECT m.*, 
                   s.profile_pic as sender_pic,
                   r.profile_pic as receiver_pic
            FROM messages m
            LEFT JOIN users s ON m.sender_id = s.id
            LEFT JOIN users r ON m.receiver_id = r.id
            WHERE (m.sender_id = :user1 AND m.receiver_id = :user2)
               OR (m.sender_id = :user2 AND m.receiver_id = :user1)
            ORDER BY m.created_at DESC
            LIMIT :limit
        ");
        
        $this->db->bind(':user1', $userId1);
        $this->db->bind(':user2', $userId2);
        $this->db->bind(':limit', $limit);
        
        $messages = $this->db->resultSet();
        
        // Reverse to show oldest first
        return array_reverse($messages);
    }
    
    public function getConversations($userId) {
        $this->db->query("
            SELECT DISTINCT 
                CASE 
                    WHEN m.sender_id = :user_id THEN m.receiver_id
                    ELSE m.sender_id
                END as other_user_id,
                u.full_name,
                u.profile_pic,
                u.user_type,
                (SELECT message FROM messages 
                 WHERE (sender_id = :user_id AND receiver_id = other_user_id)
                    OR (sender_id = other_user_id AND receiver_id = :user_id)
                 ORDER BY created_at DESC LIMIT 1) as last_message,
                (SELECT created_at FROM messages 
                 WHERE (sender_id = :user_id AND receiver_id = other_user_id)
                    OR (sender_id = other_user_id AND receiver_id = :user_id)
                 ORDER BY created_at DESC LIMIT 1) as last_message_time,
                (SELECT COUNT(*) FROM messages 
                 WHERE sender_id = other_user_id 
                 AND receiver_id = :user_id 
                 AND is_read = 0) as unread_count,
                (SELECT last_active FROM user_sessions 
                 WHERE user_id = other_user_id 
                 ORDER BY last_active DESC LIMIT 1) > NOW() - INTERVAL 5 MINUTE as is_online
            FROM messages m
            LEFT JOIN users u ON u.id = CASE 
                WHEN m.sender_id = :user_id THEN m.receiver_id
                ELSE m.sender_id
            END
            WHERE m.sender_id = :user_id OR m.receiver_id = :user_id
            GROUP BY other_user_id, u.full_name, u.profile_pic, u.user_type
            ORDER BY last_message_time DESC
        ");
        
        $this->db->bind(':user_id', $userId);
        
        return $this->db->resultSet();
    }
    
    public function markAsRead($senderId, $receiverId) {
        $this->db->query("UPDATE messages SET is_read = 1 
                         WHERE sender_id = :sender_id AND receiver_id = :receiver_id AND is_read = 0");
        
        $this->db->bind(':sender_id', $senderId);
        $this->db->bind(':receiver_id', $receiverId);
        
        return $this->db->execute();
    }
    
    public function getUnreadCounts($userId) {
        $this->db->query("
            SELECT sender_id, COUNT(*) as count 
            FROM messages 
            WHERE receiver_id = :user_id AND is_read = 0
            GROUP BY sender_id
        ");
        
        $this->db->bind(':user_id', $userId);
        
        $results = $this->db->resultSet();
        $counts = [];
        
        foreach ($results as $result) {
            $counts[$result['sender_id']] = $result['count'];
        }
        
        return $counts;
    }
    
    public function searchUsers($query, $excludeId = null) {
        $sql = "
            SELECT id, username, email, full_name, profile_pic, user_type 
            FROM users 
                        WHERE (username LIKE :query OR email LIKE :query OR full_name LIKE :query) 
            AND id != :exclude_id
            AND user_type IN ('tailor', 'customer')
            ORDER BY full_name
            LIMIT 10
        ";
        
        $this->db->query($sql);
        $this->db->bind(':query', "%$query%");
        $this->db->bind(':exclude_id', $excludeId ?: 0);
        
        return $this->db->resultSet();
    }
    
    public function updateTypingStatus($userId, $receiverId, $isTyping = true) {
        $this->db->query("
            INSERT INTO typing_status (user_id, receiver_id, is_typing, last_update) 
            VALUES (:user_id, :receiver_id, :is_typing, NOW())
            ON DUPLICATE KEY UPDATE 
            is_typing = :is_typing, 
            last_update = NOW()
        ");
        
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':receiver_id', $receiverId);
        $this->db->bind(':is_typing', $isTyping);
        
        return $this->db->execute();
    }
    
    public function getTypingStatus($userId, $receiverId) {
        $this->db->query("
            SELECT is_typing 
            FROM typing_status 
            WHERE user_id = :user_id 
            AND receiver_id = :receiver_id 
            AND last_update > NOW() - INTERVAL 2 SECOND
        ");
        
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':receiver_id', $receiverId);
        
        $result = $this->db->single();
        return $result ? $result['is_typing'] : false;
    }
    
    public function deleteConversation($userId1, $userId2) {
        $this->db->query("
            DELETE FROM messages 
            WHERE (sender_id = :user1 AND receiver_id = :user2)
               OR (sender_id = :user2 AND receiver_id = :user1)
        ");
        
        $this->db->bind(':user1', $userId1);
        $this->db->bind(':user2', $userId2);
        
        return $this->db->execute();
    }
    
    public function getUnreadMessageCount($userId) {
        $this->db->query("
            SELECT COUNT(*) as count 
            FROM messages 
            WHERE receiver_id = :user_id 
            AND is_read = 0
        ");
        
        $this->db->bind(':user_id', $userId);
        
        $result = $this->db->single();
        return $result['count'] ?? 0;
    }
}
?>