<?php
require_once 'config.php';
$pageTitle = 'My Orders';

if (!isLoggedIn()) {
    flashMessage('error', 'Please login to view your orders.');
    redirect('login.php');
}

$orders = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$orders->execute([$_SESSION['user_id']]);
$orders = $orders->fetchAll();

$statusColors = [
    'pending'    => '#ffd93d',
    'processing' => '#6c63ff',
    'shipped'    => '#4ab6ff',
    'delivered'  => '#4ade80',
    'cancelled'  => '#ff6b6b',
];

include 'includes/header.php';
?>

<div class="page-wrap">
    <div class="section-title">My Orders</div>
    <div class="section-sub">Track and manage your orders</div>

    <?php if(empty($orders)): ?>
        <div style="text-align:center;padding:60px 20px;">
            <div style="font-size:4rem;margin-bottom:16px;">📦</div>
            <div style="font-family:var(--font-head);font-size:1.5rem;font-weight:700;margin-bottom:8px;">No orders yet</div>
            <div style="color:var(--text2);margin-bottom:24px;">Start shopping to see your orders here</div>
            <a href="shop.php" class="btn btn-primary">Shop Now</a>
        </div>
    <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:16px;">
            <?php foreach($orders as $order):
                $items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
                $items->execute([$order['id']]);
                $items = $items->fetchAll();
                $color = $statusColors[$order['status']] ?? '#9090b0';
            ?>
            <div class="card" style="overflow:hidden;">
                <!-- Order Header -->
                <div style="padding:16px 20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;border-bottom:1px solid var(--border);">
                    <div>
                        <div style="font-family:var(--font-head);font-size:1rem;font-weight:700;">Order #<?= $order['id'] ?></div>
                        <div style="font-size:0.8rem;color:var(--text3);"><?= date('M j, Y g:i A', strtotime($order['created_at'])) ?></div>
                    </div>
                    <div style="display:flex;align-items:center;gap:16px;">
                        <span style="background:<?= $color ?>22;color:<?= $color ?>;border:1px solid <?= $color ?>44;border-radius:20px;padding:4px 14px;font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;">
                            <?= ucfirst($order['status']) ?>
                        </span>
                        <div style="font-size:1.1rem;font-weight:700;color:var(--accent);"><?= formatPrice($order['total']) ?></div>
                    </div>
                </div>

                <!-- Order Items -->
                <div style="padding:16px 20px;">
                    <?php foreach($items as $item): ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border);">
                        <div>
                            <div style="font-size:0.9rem;font-weight:600;"><?= sanitize($item['product_name']) ?></div>
                            <div style="font-size:0.8rem;color:var(--text3);">Qty: <?= $item['quantity'] ?> × <?= formatPrice($item['price']) ?></div>
                        </div>
                        <div style="font-weight:600;"><?= formatPrice($item['price'] * $item['quantity']) ?></div>
                    </div>
                    <?php endforeach; ?>

                    <div style="display:flex;justify-content:space-between;margin-top:12px;padding-top:4px;flex-wrap:wrap;gap:8px;">
                        <div style="font-size:0.85rem;color:var(--text2);">
                            <strong>Payment:</strong> <?= sanitize($order['payment_method']) ?> &nbsp;|&nbsp;
                            <strong>Ship to:</strong> <?= sanitize(substr($order['shipping_address'], 0, 60)) ?>...
                        </div>
                        <?php if($order['status'] === 'pending'): ?>
                            <form method="POST" action="cancel_order.php">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Cancel this order?')">Cancel Order</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
