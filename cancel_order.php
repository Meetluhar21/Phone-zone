<?php
require_once 'config.php';

if (!isLoggedIn()) redirect('login.php');

$orderId = (int)($_POST['order_id'] ?? 0);
if ($orderId) {
    // Only allow cancellation of own pending orders
    $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute([$orderId, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$orderId]);
        flashMessage('success', "Order #$orderId has been cancelled.");
    } else {
        flashMessage('error', 'Order cannot be cancelled.');
    }
}
redirect('orders.php');
