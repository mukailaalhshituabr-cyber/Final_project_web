<?php
require_once '../includes/classes/Database.php';
require_once '../includes/classes/Address.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$address = new Address();
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'delete':
            if (isset($_GET['id'])) {
                $result = $address->deleteAddress($_GET['id'], $userId);
                echo json_encode(['success' => $result]);
            }
            break;
            
        case 'set_default':
            if (isset($_GET['id'])) {
                $result = $address->setDefaultAddress($_GET['id'], $userId);
                echo json_encode(['success' => $result]);
            }
            break;
    }
}
?>