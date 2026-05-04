<?php
session_start();
require_once '../../config/database.php';
require_once '../helpers.php';
require_once '../../config/MqttClient.php';

global $conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = postInt('id');

    if ($id > 0) {
        $orderCheck = $conn->prepare("SELECT order_code FROM orders WHERE id = ?");
        $orderCheck->bind_param("i", $id);
        $orderCheck->execute();
        $orderResult = $orderCheck->get_result()->fetch_assoc();
        $orderCheck->close();
        $orderCode = $orderResult['order_code'] ?? '';

        $stmt = $conn->prepare('DELETE FROM order_status_logs WHERE order_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('DELETE FROM order_items WHERE order_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('DELETE FROM orders WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        if ($orderCode) {
            MqttPublisher::publishOrder($orderCode, 'deleted', 'admin');
        }
    }

    header('Location: ../../public/admin/dashboard.php');
    exit;
}
?>