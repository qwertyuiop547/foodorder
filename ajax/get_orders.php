<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

$public = isset($_GET['public']) && $_GET['public'] === '1';
$isLoggedIn = isset($_SESSION['user_id']);
$role = $isLoggedIn ? $_SESSION['role'] : null;
$user_id = $isLoggedIn ? $_SESSION['user_id'] : null;

if (!$public && (!$isLoggedIn || !in_array($role, ['admin', 'staff', 'customer']))) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$valid_statuses = ['all', 'pending', 'preparing', 'ready', 'completed'];

if (!in_array($status_filter, $valid_statuses)) {
    $status_filter = 'all';
}

if ($public || $role === 'customer') {
    if (!$public && $role === 'customer') {
        $sql = "SELECT 
                    orders.*,
                    GROUP_CONCAT(CONCAT(order_items.item_name, ' (x', order_items.quantity, ')') SEPARATOR '||') AS items
                FROM orders
                LEFT JOIN order_items ON orders.id = order_items.order_id
                WHERE orders.user_id = ?
                GROUP BY orders.id
                ORDER BY orders.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
    } else {
        $sql = "SELECT 
                    orders.*,
                    users.name AS customer_name,
                    GROUP_CONCAT(CONCAT(order_items.item_name, ' (x', order_items.quantity, ')') SEPARATOR '||') AS items
                FROM orders
                JOIN users ON orders.user_id = users.id
                LEFT JOIN order_items ON orders.id = order_items.order_id";
        
        if ($status_filter !== 'all') {
            $sql .= " WHERE orders.status = ?";
        }
        
        $sql .= " GROUP BY orders.id ORDER BY orders.created_at DESC";
        
        if ($status_filter !== 'all') {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $status_filter);
        } else {
            $stmt = $conn->prepare($sql);
        }
    }
} else {
    $sql = "SELECT 
                orders.*,
                users.name AS customer_name,
                GROUP_CONCAT(CONCAT(order_items.item_name, ' (x', order_items.quantity, ')') SEPARATOR '||') AS items
            FROM orders
            JOIN users ON orders.user_id = users.id
            LEFT JOIN order_items ON orders.id = order_items.order_id";
    
    if ($status_filter !== 'all') {
        $sql .= " WHERE orders.status = ?";
    }
    
    $sql .= " GROUP BY orders.id ORDER BY orders.created_at DESC";
    
    if ($status_filter !== 'all') {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $status_filter);
    } else {
        $stmt = $conn->prepare($sql);
    }
}

$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$counts = ['pending' => 0, 'preparing' => 0, 'ready' => 0, 'completed' => 0];
foreach ($orders as $o) {
    if (isset($counts[$o['status']])) {
        $counts[$o['status']]++;
    }
}

$response = [
    'success' => true,
    'orders' => $orders,
    'counts' => $counts,
    'timestamp' => time()
];

echo json_encode($response);
