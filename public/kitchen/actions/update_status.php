<?php
session_start();

require_once '../../../config/database.php';
require_once '../../../includes/helpers.php';
require_once '../../../config/MqttClient.php';

requiredRole(['staff', 'admin'], '../../login.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $new_status = isset($_POST['new_status']) ? trim($_POST['new_status']) : '';
    
    $allowed_statuses = ['pending', 'preparing', 'ready', 'completed'];
    
    if ($order_id > 0 && in_array($new_status, $allowed_statuses)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        
        $orderCheck = $conn->prepare("SELECT order_code, users.name FROM orders JOIN users ON orders.user_id = users.id WHERE orders.id = ?");
        $orderCheck->bind_param("i", $order_id);
        $orderCheck->execute();
        $orderResult = $orderCheck->get_result()->fetch_assoc();
        $orderCheck->close();

        if ($stmt->execute()) {
            MqttPublisher::publishOrder($orderResult['order_code'], $new_status, $orderResult['name']);
            
            $log_stmt = $conn->prepare("INSERT INTO order_status_logs (order_id, status, message) VALUES (?, ?, ?)");
            $message = "Order status updated to " . ucfirst($new_status) . " by kitchen staff";
            $log_stmt->bind_param("iss", $order_id, $new_status, $message);
            $log_stmt->execute();
            $log_stmt->close();
            
            setFlash("Order #$order_id marked as " . ucfirst($new_status), 'success');
        } else {
            setFlash("Failed to update order status", 'error');
        }
        
        $stmt->close();
    } else {
        setFlash("Invalid order or status", 'error');
    }
}

redirect('../index.php');
