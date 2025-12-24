<?php
class Address {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getUserAddresses($userId) {
        $this->db->query("SELECT * FROM addresses WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC");
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }
    
    public function getAddressById($addressId, $userId = null) {
        $sql = "SELECT * FROM addresses WHERE id = :id";
        if ($userId) {
            $sql .= " AND user_id = :user_id";
        }
        
        $this->db->query($sql);
        $this->db->bind(':id', $addressId);
        
        if ($userId) {
            $this->db->bind(':user_id', $userId);
        }
        
        return $this->db->single();
    }
    
    public function addAddress($userId, $addressData) {
        try {
            if (isset($addressData['is_default']) && $addressData['is_default']) {
                $this->clearDefaultAddress($userId);
            }
            
            $this->db->query("INSERT INTO addresses (
                user_id, label, full_name, phone, address_line1, address_line2,
                city, state, country, postal_code, is_default, created_at
            ) VALUES (
                :user_id, :label, :full_name, :phone, :address_line1, :address_line2,
                :city, :state, :country, :postal_code, :is_default, NOW()
            )");
            
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':label', $addressData['label']);
            $this->db->bind(':full_name', $addressData['full_name']);
            $this->db->bind(':phone', $addressData['phone']);
            $this->db->bind(':address_line1', $addressData['address_line1']);
            $this->db->bind(':address_line2', $addressData['address_line2'] ?? '');
            $this->db->bind(':city', $addressData['city']);
            $this->db->bind(':state', $addressData['state']);
            $this->db->bind(':country', $addressData['country']);
            $this->db->bind(':postal_code', $addressData['postal_code']);
            $this->db->bind(':is_default', $addressData['is_default'] ?? 0);
            
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Address add error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateAddress($addressId, $userId, $addressData) {
        try {
            if (isset($addressData['is_default']) && $addressData['is_default']) {
                $this->clearDefaultAddress($userId);
            }
            
            $this->db->query("UPDATE addresses SET
                label = :label,
                full_name = :full_name,
                phone = :phone,
                address_line1 = :address_line1,
                address_line2 = :address_line2,
                city = :city,
                state = :state,
                country = :country,
                postal_code = :postal_code,
                is_default = :is_default,
                updated_at = NOW()
            WHERE id = :id AND user_id = :user_id");
            
            $this->db->bind(':id', $addressId);
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':label', $addressData['label']);
            $this->db->bind(':full_name', $addressData['full_name']);
            $this->db->bind(':phone', $addressData['phone']);
            $this->db->bind(':address_line1', $addressData['address_line1']);
            $this->db->bind(':address_line2', $addressData['address_line2'] ?? '');
            $this->db->bind(':city', $addressData['city']);
            $this->db->bind(':state', $addressData['state']);
            $this->db->bind(':country', $addressData['country']);
            $this->db->bind(':postal_code', $addressData['postal_code']);
            $this->db->bind(':is_default', $addressData['is_default'] ?? 0);
            
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Address update error: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteAddress($addressId, $userId) {
        try {
            $address = $this->getAddressById($addressId, $userId);
            $wasDefault = $address['is_default'] ?? false;
            
            $this->db->query("DELETE FROM addresses WHERE id = :id AND user_id = :user_id");
            $this->db->bind(':id', $addressId);
            $this->db->bind(':user_id', $userId);
            
            $result = $this->db->execute();
            
            if ($result && $wasDefault) {
                $this->setNewDefaultAddress($userId);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Address delete error: " . $e->getMessage());
            return false;
        }
    }
    
    public function setDefaultAddress($addressId, $userId) {
        try {
            $this->clearDefaultAddress($userId);
            
            $this->db->query("UPDATE addresses SET is_default = 1, updated_at = NOW() 
                            WHERE id = :id AND user_id = :user_id");
            $this->db->bind(':id', $addressId);
            $this->db->bind(':user_id', $userId);
            
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Set default address error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getDefaultAddress($userId) {
        $this->db->query("SELECT * FROM addresses WHERE user_id = :user_id AND is_default = 1 LIMIT 1");
        $this->db->bind(':user_id', $userId);
        return $this->db->single();
    }
    
    private function clearDefaultAddress($userId) {
        $this->db->query("UPDATE addresses SET is_default = 0 WHERE user_id = :user_id");
        $this->db->bind(':user_id', $userId);
        return $this->db->execute();
    }
    
    private function setNewDefaultAddress($userId) {
        $this->db->query("SELECT id FROM addresses WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1");
        $this->db->bind(':user_id', $userId);
        $address = $this->db->single();
        
        if ($address) {
            return $this->setDefaultAddress($address['id'], $userId);
        }
        
        return false;
    }
    
    public function countUserAddresses($userId) {
        $this->db->query("SELECT COUNT(*) as count FROM addresses WHERE user_id = :user_id");
        $this->db->bind(':user_id', $userId);
        $result = $this->db->single();
        return $result['count'] ?? 0;
    }
    
    public function validateAddress($addressData) {
        $errors = [];
        
        if (empty($addressData['label'])) {
            $errors[] = 'Address label is required';
        }
        
        if (empty($addressData['full_name'])) {
            $errors[] = 'Full name is required';
        }
        
        if (empty($addressData['phone'])) {
            $errors[] = 'Phone number is required';
        }
        
        if (empty($addressData['address_line1'])) {
            $errors[] = 'Address line 1 is required';
        }
        
        if (empty($addressData['city'])) {
            $errors[] = 'City is required';
        }
        
        if (empty($addressData['state'])) {
            $errors[] = 'State is required';
        }
        
        if (empty($addressData['country'])) {
            $errors[] = 'Country is required';
        }
        
        if (empty($addressData['postal_code'])) {
            $errors[] = 'Postal code is required';
        }
        
        return $errors;
    }
    
    public function formatAddress($address) {
        if (!$address) return '';
        
        $parts = [];
        if (!empty($address['full_name'])) $parts[] = $address['full_name'];
        if (!empty($address['address_line1'])) $parts[] = $address['address_line1'];
        if (!empty($address['address_line2'])) $parts[] = $address['address_line2'];
        if (!empty($address['city'])) $parts[] = $address['city'];
        if (!empty($address['state'])) $parts[] = $address['state'];
        if (!empty($address['postal_code'])) $parts[] = $address['postal_code'];
        if (!empty($address['country'])) $parts[] = $address['country'];
        
        return implode(', ', $parts);
    }
}
?>




<?php
/*class Address {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    
    public function getUserAddresses($userId) {
        $this->db->query("SELECT * FROM addresses WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC");
        $this->db->bind(':user_id', $userId);
        return $this->db->resultSet();
    }
    
    public function getAddressById($addressId, $userId = null) {
        $sql = "SELECT * FROM addresses WHERE id = :id";
        if ($userId) {
            $sql .= " AND user_id = :user_id";
        }
        
        $this->db->query($sql);
        $this->db->bind(':id', $addressId);
        
        if ($userId) {
            $this->db->bind(':user_id', $userId);
        }
        
        return $this->db->single();
    }
    
    public function addAddress($userId, $addressData) {
        try {
            // If this is set as default, remove default from other addresses
            if (isset($addressData['is_default']) && $addressData['is_default']) {
                $this->clearDefaultAddress($userId);
            }
            
            $this->db->query("INSERT INTO addresses (
                user_id, label, full_name, phone, address_line1, address_line2,
                city, state, country, postal_code, is_default
            ) VALUES (
                :user_id, :label, :full_name, :phone, :address_line1, :address_line2,
                :city, :state, :country, :postal_code, :is_default
            )");
            
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':label', $addressData['label']);
            $this->db->bind(':full_name', $addressData['full_name']);
            $this->db->bind(':phone', $addressData['phone']);
            $this->db->bind(':address_line1', $addressData['address_line1']);
            $this->db->bind(':address_line2', $addressData['address_line2']);
            $this->db->bind(':city', $addressData['city']);
            $this->db->bind(':state', $addressData['state']);
            $this->db->bind(':country', $addressData['country']);
            $this->db->bind(':postal_code', $addressData['postal_code']);
            $this->db->bind(':is_default', $addressData['is_default'] ?? 0);
            
            return $this->db->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    
    
    public function updateAddress($addressId, $userId, $addressData) {
        try {
            // If this is set as default, remove default from other addresses
            if (isset($addressData['is_default']) && $addressData['is_default']) {
                $this->clearDefaultAddress($userId);
            }
            
            $this->db->query("UPDATE addresses SET
                label = :label,
                full_name = :full_name,
                phone = :phone,
                address_line1 = :address_line1,
                address_line2 = :address_line2,
                city = :city,
                state = :state,
                country = :country,
                postal_code = :postal_code,
                is_default = :is_default,
                updated_at = NOW()
            WHERE id = :id AND user_id = :user_id");
            
            $this->db->bind(':id', $addressId);
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':label', $addressData['label']);
            $this->db->bind(':full_name', $addressData['full_name']);
            $this->db->bind(':phone', $addressData['phone']);
            $this->db->bind(':address_line1', $addressData['address_line1']);
            $this->db->bind(':address_line2', $addressData['address_line2']);
            $this->db->bind(':city', $addressData['city']);
            $this->db->bind(':state', $addressData['state']);
            $this->db->bind(':country', $addressData['country']);
            $this->db->bind(':postal_code', $addressData['postal_code']);
            $this->db->bind(':is_default', $addressData['is_default'] ?? 0);
            
            return $this->db->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    
    
    public function deleteAddress($addressId, $userId) {
        try {
            // Check if this is the default address
            $address = $this->getAddressById($addressId, $userId);
            $wasDefault = $address['is_default'] ?? false;
            
            $this->db->query("DELETE FROM addresses WHERE id = :id AND user_id = :user_id");
            $this->db->bind(':id', $addressId);
            $this->db->bind(':user_id', $userId);
            
            $result = $this->db->execute();
            
            // If we deleted the default address, set another one as default
            if ($result && $wasDefault) {
                $this->setNewDefaultAddress($userId);
            }
            
            return $result;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function setDefaultAddress($addressId, $userId) {
        try {
            // Remove default from all addresses
            $this->clearDefaultAddress($userId);
            
            // Set this address as default
            $this->db->query("UPDATE addresses SET is_default = 1 WHERE id = :id AND user_id = :user_id");
            $this->db->bind(':id', $addressId);
            $this->db->bind(':user_id', $userId);
            
            return $this->db->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    
    
    public function getDefaultAddress($userId) {
        $this->db->query("SELECT * FROM addresses WHERE user_id = :user_id AND is_default = 1 LIMIT 1");
        $this->db->bind(':user_id', $userId);
        return $this->db->single();
    }
    
   
    private function clearDefaultAddress($userId) {
        $this->db->query("UPDATE addresses SET is_default = 0 WHERE user_id = :user_id");
        $this->db->bind(':user_id', $userId);
        return $this->db->execute();
    }
    
    
    private function setNewDefaultAddress($userId) {
        $this->db->query("SELECT id FROM addresses WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1");
        $this->db->bind(':user_id', $userId);
        $address = $this->db->single();
        
        if ($address) {
            return $this->setDefaultAddress($address['id'], $userId);
        }
        
        return false;
    }
    
    
    public function countUserAddresses($userId) {
        $this->db->query("SELECT COUNT(*) as count FROM addresses WHERE user_id = :user_id");
        $this->db->bind(':user_id', $userId);
        $result = $this->db->single();
        return $result['count'] ?? 0;
    }
    
    
    public function validateAddress($addressData) {
        $errors = [];
        
        if (empty($addressData['label'])) {
            $errors[] = 'Address label is required';
        }
        
        if (empty($addressData['full_name'])) {
            $errors[] = 'Full name is required';
        }
        
        if (empty($addressData['phone'])) {
            $errors[] = 'Phone number is required';
        }
        
        if (empty($addressData['address_line1'])) {
            $errors[] = 'Address line 1 is required';
        }
        
        if (empty($addressData['city'])) {
            $errors[] = 'City is required';
        }
        
        if (empty($addressData['state'])) {
            $errors[] = 'State is required';
        }
        
        if (empty($addressData['country'])) {
            $errors[] = 'Country is required';
        }
        
        if (empty($addressData['postal_code'])) {
            $errors[] = 'Postal code is required';
        }
        
        return $errors;
    }
   
    public function formatAddress($address) {
        if (!$address) return '';
        
        $parts = [];
        if (!empty($address['full_name'])) $parts[] = $address['full_name'];
        if (!empty($address['address_line1'])) $parts[] = $address['address_line1'];
        if (!empty($address['address_line2'])) $parts[] = $address['address_line2'];
        if (!empty($address['city'])) $parts[] = $address['city'];
        if (!empty($address['state'])) $parts[] = $address['state'];
        if (!empty($address['postal_code'])) $parts[] = $address['postal_code'];
        if (!empty($address['country'])) $parts[] = $address['country'];
        
        return implode(', ', $parts);
    }
}
?>
*/