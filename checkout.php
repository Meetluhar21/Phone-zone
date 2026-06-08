<?php
require_once 'config.php';
$pageTitle = 'Checkout';

if (!isLoggedIn()) {
    flashMessage('error', 'Please login to checkout.');
    redirect('login.php');
}

// Get cart
$stmt = $pdo->prepare("SELECT c.quantity, p.id, p.name, p.brand, p.price, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    flashMessage('error', 'Your cart is empty.');
    redirect('cart.php');
}

$subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));
$shipping = $subtotal > 999 ? 0 : 99;
$total    = $subtotal + $shipping;

// Fetch user info
$user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$_SESSION['user_id']]);
$user = $user->fetch();

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = sanitize($_POST['address'] ?? '');
    $phone   = sanitize($_POST['phone'] ?? '');
    $payment = sanitize($_POST['payment'] ?? 'COD');
    $notes   = sanitize($_POST['notes'] ?? '');

    if (empty($address)) {
        flashMessage('error', 'Shipping address is required.');
        redirect('checkout.php');
    }

    // Validate stock
    foreach ($cartItems as $item) {
        if ($item['quantity'] > $item['stock']) {
            flashMessage('error', "Insufficient stock for {$item['name']}.");
            redirect('cart.php');
        }
    }

    try {
        $pdo->beginTransaction();

        // Create order
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, status, shipping_address, payment_method, notes) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$_SESSION['user_id'], $total, 'pending', $address, $payment, $notes]);
        $orderId = $pdo->lastInsertId();

        // Order items & update stock
        foreach ($cartItems as $item) {
            $ins = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES (?,?,?,?,?)");
            $ins->execute([$orderId, $item['id'], $item['name'], $item['quantity'], $item['price']]);

            $upd = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $upd->execute([$item['quantity'], $item['id']]);
        }

        // Clear cart
        $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$_SESSION['user_id']]);

        $pdo->commit();
        flashMessage('success', "Order #$orderId placed successfully! Thank you for shopping with PhoneZone.");
        redirect("orders.php");

    } catch (Exception $e) {
        $pdo->rollBack();
        flashMessage('error', 'Order failed. Please try again.');
        redirect('checkout.php');
    }
}

include 'includes/header.php';
?>

<div class="page-wrap">
    <div style="display:flex;gap:8px;align-items:center;color:var(--text3);font-size:0.875rem;margin-bottom:28px;">
        <a href="cart.php" style="color:var(--text3);">Cart</a> /
        <span style="color:var(--text2);">Checkout</span>
    </div>

    <div class="section-title">Checkout</div>

    <div style="display:grid;grid-template-columns:1fr 360px;gap:28px;align-items:flex-start;">

        <!-- SHIPPING FORM -->
        <form method="POST">
            <div class="card" style="padding:24px;margin-bottom:20px;">
                <div style="font-family:var(--font-head);font-size:1rem;font-weight:700;margin-bottom:20px;">📦 Shipping Details</div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Full Name</label>
                        <input type="text" value="<?= sanitize($user['name']) ?>" readonly style="background:var(--bg);">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Phone Number *</label>
                        <input type="text" name="phone" placeholder="10-digit mobile number" value="<?= sanitize($user['phone'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-group" style="margin-top:16px;">
                    <label>Email</label>
                    <input type="email" value="<?= sanitize($user['email']) ?>" readonly style="background:var(--bg);">
                </div>

                <div class="form-group">
                    <label>Shipping Address *</label>
                    <textarea name="address" rows="3" placeholder="House/Flat No., Street, Area, City, State, PIN Code" required><?= sanitize($user['address'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="card" style="padding:24px;margin-bottom:20px;">
                <div style="font-family:var(--font-head);font-size:1rem;font-weight:700;margin-bottom:20px;">💳 Payment Method</div>

                <?php $methods = ['COD' => '💵 Cash on Delivery', 'UPI' => '📲 UPI Payment', 'Card' => '💳 Debit/Credit Card', 'Netbanking' => '🏦 Net Banking']; ?>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <?php foreach($methods as $val => $label): ?>
                    <label style="display:flex;align-items:center;gap:12px;padding:14px;border:1px solid var(--border);border-radius:8px;cursor:pointer;transition:border-color 0.2s;" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="if(!this.querySelector('input').checked)this.style.borderColor='var(--border)'">
                        <input type="radio" name="payment" value="<?= $val ?>" <?= $val === 'COD' ? 'checked' : '' ?> style="accent-color:var(--accent);width:auto;" onchange="document.querySelectorAll('.pay-label').forEach(l=>l.style.borderColor='var(--border)');this.closest('label').style.borderColor='var(--accent)'">
                        <?= $label ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card" style="padding:24px;margin-bottom:20px;">
                <div style="font-family:var(--font-head);font-size:1rem;font-weight:700;margin-bottom:16px;">📝 Order Notes (optional)</div>
                <textarea name="notes" rows="2" placeholder="Special delivery instructions..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;font-size:1.05rem;padding:16px;">
                ✅ Place Order — <?= formatPrice($total) ?>
            </button>
        </form>

        <!-- ORDER SUMMARY -->
        <div class="card" style="padding:24px;position:sticky;top:80px;">
            <div style="font-family:var(--font-head);font-size:1.1rem;font-weight:700;margin-bottom:16px;">Order Summary</div>

            <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:20px;">
                <?php foreach($cartItems as $item): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:0.85rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= sanitize($item['name']) ?></div>
                        <div style="font-size:0.75rem;color:var(--text3);">x<?= $item['quantity'] ?> × <?= formatPrice($item['price']) ?></div>
                    </div>
                    <div style="font-size:0.9rem;font-weight:600;flex-shrink:0;"><?= formatPrice($item['price'] * $item['quantity']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div style="border-top:1px solid var(--border);padding-top:14px;display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;justify-content:space-between;color:var(--text2);font-size:0.875rem;">
                    <span>Subtotal</span><span><?= formatPrice($subtotal) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;color:var(--text2);font-size:0.875rem;">
                    <span>Shipping</span>
                    <span style="color:<?= $shipping === 0 ? 'var(--success)' : 'inherit' ?>"><?= $shipping === 0 ? 'FREE' : formatPrice($shipping) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:1.1rem;font-weight:700;padding-top:10px;border-top:1px solid var(--border);">
                    <span>Total</span><span style="color:var(--accent);"><?= formatPrice($total) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
