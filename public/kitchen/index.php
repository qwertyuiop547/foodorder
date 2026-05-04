<?php
session_start();

require_once '../../config/database.php';
require_once '../../includes/helpers.php';

requiredRole(['staff', 'admin'], '../login.php');

global $conn;

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

$sql = "SELECT 
            orders.*,
            users.name AS customer_name,
            GROUP_CONCAT(CONCAT(order_items.item_name, ' (x', order_items.quantity, ')') SEPARATOR '||') AS items
        FROM orders
        JOIN users ON orders.user_id = users.id
        LEFT JOIN order_items ON orders.id = order_items.order_id";

if ($status_filter !== 'all') {
    $sql .= " WHERE orders.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}

$sql .= " GROUP BY orders.id ORDER BY orders.created_at DESC";

$orders = getAll($conn, $sql);

$pending_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'"))['count'];
$preparing_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE status = 'preparing'"))['count'];
$ready_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE status = 'ready'"))['count'];
$completed_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE status = 'completed'"))['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Panel - FoodPulse</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/base.css">
    <link rel="stylesheet" href="../../assets/css/toast.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="kitchen-body">
    <div class="kitchen-container">
        <div class="kitchen-header">
            <h1>Kitchen Orders</h1>
            <a href="actions/logout.php" class="logout-link">Logout</a>
        </div>

        <?php include '../../template/alerts.php'; ?>

        <div class="kitchen-stats" id="statsContainer">
            <div class="stat-box">Pending: <strong id="stat-pending"><?= $pending_count; ?></strong></div>
            <div class="stat-box">Preparing: <strong id="stat-preparing"><?= $preparing_count; ?></strong></div>
            <div class="stat-box">Ready: <strong id="stat-ready"><?= $ready_count; ?></strong></div>
            <div class="stat-box">Completed: <strong id="stat-completed"><?= $completed_count; ?></strong></div>
        </div>

        <div class="filter-links" id="filterLinks">
            <a href="#" data-status="all" class="<?= $status_filter === 'all' ? 'active' : '' ?>">All</a>
            <a href="#" data-status="pending" class="<?= $status_filter === 'pending' ? 'active' : '' ?>">Pending</a>
            <a href="#" data-status="preparing" class="<?= $status_filter === 'preparing' ? 'active' : '' ?>">Preparing</a>
            <a href="#" data-status="ready" class="<?= $status_filter === 'ready' ? 'active' : '' ?>">Ready</a>
            <a href="#" data-status="completed" class="<?= $status_filter === 'completed' ? 'active' : '' ?>">Completed</a>
        </div>

        <div class="orders-list" id="ordersList">
            <?php if (!empty($orders)): ?>
                <?php foreach ($orders as $order): ?>
                    <?php 
                    $items = $order['items'] ? explode('||', $order['items']) : [];
                    $nextStatus = '';
                    $nextLabel = '';
                    $showButton = false;
                    
                    if ($order['status'] === 'pending') {
                        $nextStatus = 'preparing';
                        $nextLabel = 'Start';
                        $showButton = true;
                    } elseif ($order['status'] === 'preparing') {
                        $nextStatus = 'ready';
                        $nextLabel = 'Ready';
                        $showButton = true;
                    } elseif ($order['status'] === 'ready') {
                        $nextStatus = 'completed';
                        $nextLabel = 'Done';
                        $showButton = true;
                    }
                    ?>
                    <div class="order-item" data-order-id="<?= $order['id'] ?>" data-status="<?= $order['status'] ?>">
                        <div class="order-top">
                            <span class="order-code"><?= htmlspecialchars($order['order_code']); ?></span>
                            <span class="order-status <?= $order['status']; ?>"><?= $order['status']; ?></span>
                        </div>
                        <div class="order-customer">Customer: <?= htmlspecialchars($order['customer_name']); ?></div>
                        <div class="order-details">
                            <strong>Items:</strong>
                            <ul id="items-list-<?= $order['id'] ?>">
                                <?php foreach ($items as $item): ?>
                                    <?php if (!empty($item)): ?>
                                    <li><?= htmlspecialchars($item); ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="order-meta">
                            <span>Total: ₱<?= number_format($order['total_amount'], 2); ?></span>
                            <span>Time: <?= date('h:i A', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div class="order-actions" id="actions-<?= $order['id'] ?>">
                            <?php if ($showButton): ?>
                                <button type="button" class="btn-action status-btn" data-id="<?= $order['id'] ?>" data-status="<?= $nextStatus ?>">
                                    <?= $nextLabel ?>
                                </button>
                            <?php else: ?>
                                <span class="done-text">Done</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-orders">No orders found</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="../../assets/js/ajax.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let currentStatus = '<?= $status_filter ?>';
            const ordersList = document.getElementById('ordersList');
            
            function renderOrders(orders, counts) {
                if (!orders || orders.length === 0) {
                    ordersList.innerHTML = '<p class="no-orders">No orders found</p>';
                    updateStats(counts);
                    return;
                }

                ordersList.innerHTML = orders.map(order => {
                    const items = order.items ? order.items.split('||') : [];
                    const nextStatus = AJAX.getNextStatus(order.status);
                    const showButton = nextStatus !== null;
                    
                    return `
                        <div class="order-item" data-order-id="${order.id}" data-status="${order.status}">
                            <div class="order-top">
                                <span class="order-code">${AJAX.formatText(order.order_code)}</span>
                                <span class="order-status ${order.status}">${order.status}</span>
                            </div>
                            <div class="order-customer">Customer: ${AJAX.formatText(order.customer_name)}</div>
                            <div class="order-details">
                                <strong>Items:</strong>
                                <ul>
                                    ${items.map(item => item ? `<li>${AJAX.formatText(item)}</li>` : '').join('')}
                                </ul>
                            </div>
                            <div class="order-meta">
                                <span>Total: ₱${parseFloat(order.total_amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}</span>
                                <span>Time: ${AJAX.formatTime(order.created_at)}</span>
                            </div>
                            <div class="order-actions" id="actions-${order.id}">
                                ${showButton ? `<button type="button" class="btn-action status-btn" data-id="${order.id}" data-status="${nextStatus.next}">${nextStatus.label}</button>` : '<span class="done-text">Done</span>'}
                            </div>
                        </div>
                    `;
                }).join('');

                updateStats(counts);
                attachStatusHandlers();
            }

            function updateStats(counts) {
                if (counts) {
                    document.getElementById('stat-pending').textContent = counts.pending || 0;
                    document.getElementById('stat-preparing').textContent = counts.preparing || 0;
                    document.getElementById('stat-ready').textContent = counts.ready || 0;
                    document.getElementById('stat-completed').textContent = counts.completed || 0;
                }
            }

            function attachStatusHandlers() {
                document.querySelectorAll('.status-btn').forEach(btn => {
                    btn.addEventListener('click', async function() {
                        const orderId = this.dataset.id;
                        const newStatus = this.dataset.status;
                        const orderItem = this.closest('.order-item');
                        
                        orderItem.classList.add('updating');
                        this.disabled = true;
                        
                        const result = await AJAX.updateOrderStatus(orderId, newStatus);
                        
                        if (result.success) {
                            orderItem.classList.add('updated');
                            
                            const nextStatus = AJAX.getNextStatus(newStatus);
                            
                            orderItem.dataset.status = newStatus;
                            orderItem.querySelector('.order-status').textContent = newStatus;
                            orderItem.querySelector('.order-status').className = `order-status ${newStatus}`;
                            
                            const actionsDiv = document.getElementById(`actions-${orderId}`);
                            if (nextStatus) {
                                actionsDiv.innerHTML = `<button type="button" class="btn-action status-btn" data-id="${orderId}" data-status="${nextStatus.next}">${nextStatus.label}</button>`;
                                attachStatusHandlers();
                            } else {
                                actionsDiv.innerHTML = '<span class="done-text">Done</span>';
                            }
                            
                            
                            
                            setTimeout(() => {
                                orderItem.classList.remove('updating', 'updated');
                                if (currentStatus !== 'all' && newStatus !== currentStatus) {
                                    orderItem.style.opacity = '0';
                                    setTimeout(() => orderItem.remove(), 300);
                                }
                            }, 500);
                            
                            const orders = await AJAX.getOrders(currentStatus);
                            if (currentStatus !== 'all') {
                                renderOrders(orders.orders, orders.counts);
                            } else {
                                updateStats(orders.counts);
                            }
                        } else {
                            orderItem.classList.remove('updating');
                            this.disabled = false;
                            AJAX.showToast(result.error || 'Failed to update status', 'error');
                        }
                    });
                });
            }

            document.getElementById('filterLinks').addEventListener('click', async function(e) {
                if (e.target.tagName === 'A') {
                    e.preventDefault();
                    currentStatus = e.target.dataset.status;
                    
                    document.querySelectorAll('#filterLinks a').forEach(a => a.classList.remove('active'));
                    e.target.classList.add('active');
                    
                    const result = await AJAX.getOrders(currentStatus);
                    renderOrders(result.orders, result.counts);
                }
            });

            

            AJAX.startAutoRefresh(async function(orders) {
                if (orders.success) {
                    if (currentStatus !== 'all') {
                        const filtered = orders.orders.filter(o => o.status === currentStatus);
                        renderOrders(filtered, orders.counts);
                    } else {
                        renderOrders(orders.orders, orders.counts);
                    }
                }
            }, 5000);

            attachStatusHandlers();
        });
    </script>
</body>
</html>
