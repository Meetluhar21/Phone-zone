<?php
require_once 'config.php';
$pageTitle = 'Cart';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action'] ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);
    $qty       = max(1, (int)($_POST['quantity'] ?? 1));
    $redirect  = $_POST['redirect'] ?? 'cart.php';

    if (!isLoggedIn()) {
        flashMessage('error', 'Please login to manage your cart.');
        redirect('login.php');
    }

    // Verify user exists in database
    if (!userExists($pdo, $_SESSION['user_id'])) {
        $_SESSION = [];
        flashMessage('error', 'Session invalid. Please login again.');
        redirect('login.php');
    }

    if ($action === 'add' && $productId) {
        // Check product exists and in stock
        $p = $pdo->prepare("SELECT id, stock, name FROM products WHERE id = ? AND is_active = 1");
        $p->execute([$productId]);
        $prod = $p->fetch();
        if (!$prod) {
            flashMessage('error', 'Product not found.');
        } elseif ($prod['stock'] < 1) {
            flashMessage('error', 'Sorry, this product is out of stock.');
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?,?,?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
                $stmt->execute([$_SESSION['user_id'], $productId, $qty, $qty]);
                flashMessage('success', sanitize($prod['name']) . ' added to cart!');
            } catch (PDOException $e) {
                flashMessage('error', 'Failed to add item to cart. Please try again.');
            }
        }
        redirect($redirect);

    } elseif ($action === 'update' && $productId) {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$qty, $_SESSION['user_id'], $productId]);
        redirect('cart.php');

    } elseif ($action === 'remove' && $productId) {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $productId]);
        flashMessage('success', 'Item removed from cart.');
        redirect('cart.php');

    } elseif ($action === 'clear') {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        flashMessage('success', 'Cart cleared.');
        redirect('cart.php');
    }
}

// Fetch cart items
$cartItems = [];
$subtotal = 0;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT c.quantity, p.id, p.name, p.brand, p.price, p.stock, p.image_url FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cartItems = $stmt->fetchAll();
    foreach ($cartItems as $item) $subtotal += $item['price'] * $item['quantity'];
}

$shipping = $subtotal > 999 ? 0 : 99;
$total = $subtotal + $shipping;

include 'includes/header.php';
?>

<div class="page-wrap">
    <div class="section-title">Shopping Cart</div>

    <?php if(!isLoggedIn()): ?>
        <div style="text-align:center;padding:60px 20px;">
            <div style="font-size:4rem;margin-bottom:16px;">🔒</div>
            <div style="font-family:var(--font-head);font-size:1.5rem;font-weight:700;margin-bottom:8px;">Please login</div>
            <div style="color:var(--text2);margin-bottom:24px;">You need to be logged in to view your cart</div>
            <a href="login.php" class="btn btn-primary">Login</a>
        </div>
    <?php elseif(empty($cartItems)): ?>
        <div style="text-align:center;padding:60px 20px;">
            <div style="font-size:5rem;margin-bottom:16px;">🛒</div>
            <div style="font-family:var(--font-head);font-size:1.5rem;font-weight:700;margin-bottom:8px;">Your cart is empty</div>
            <div style="color:var(--text2);margin-bottom:24px;">Add some awesome phones to get started!</div>
            <a href="shop.php" class="btn btn-primary">Browse Phones</a>
        </div>
    <?php else: ?>
        <div style="display:grid;grid-template-columns:1fr 340px;gap:28px;align-items:flex-start;">

            <!-- CART ITEMS -->
            <div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                    <div style="color:var(--text2);font-size:0.9rem;"><?= count($cartItems) ?> item<?= count($cartItems) !== 1 ? 's' : '' ?></div>
                    <form method="POST">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn btn-outline btn-sm" onclick="return confirm('Clear cart?')">🗑️ Clear Cart</button>
                    </form>
                </div>

                <div style="display:flex;flex-direction:column;gap:12px;">
                    <?php foreach($cartItems as $item): ?>
                    <div class="card" style="padding:16px;display:flex;gap:16px;align-items:center;">
                        <div style="width:80px;height:80px;background:var(--surface2);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:2rem;flex-shrink:0;">
                            <?php if($item['image_url']): ?>
                                <img src="<?= htmlspecialchars($item['image_url']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:8px;" onerror="this.style.display='none';this.parentElement.textContent='📱'">
                            <?php else: ?>
                                📱
                            <?php endif; ?>
                        </div>

                        <div style="flex:1;min-width:0;">
                            <div style="font-size:0.75rem;color:var(--accent);font-weight:600;"><?= sanitize($item['brand']) ?></div>
                            <a href="product.php?id=<?= $item['id'] ?>" style="font-family:var(--font-head);font-weight:700;font-size:0.95rem;display:block;margin-bottom:4px;"><?= sanitize($item['name']) ?></a>
                            <div style="color:var(--text2);font-size:0.9rem;"><?= formatPrice($item['price']) ?> each</div>
                        </div>

                        <div style="display:flex;align-items:center;gap:16px;flex-shrink:0;">
                            <form method="POST" style="display:flex;align-items:center;gap:6px;">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                <div style="display:flex;align-items:center;background:var(--surface2);border:1px solid var(--border);border-radius:8px;overflow:hidden;">
                                    <button type="submit" name="quantity" value="<?= max(1,$item['quantity']-1) ?>" style="background:none;border:none;color:var(--text);padding:6px 12px;cursor:pointer;">−</button>
                                    <span style="padding:0 8px;font-weight:600;min-width:24px;text-align:center;"><?= $item['quantity'] ?></span>
                                    <button type="submit" name="quantity" value="<?= min($item['stock'],$item['quantity']+1) ?>" style="background:none;border:none;color:var(--text);padding:6px 12px;cursor:pointer;">+</button>
                                </div>
                            </form>

                            <div style="font-weight:700;font-size:1rem;min-width:80px;text-align:right;"><?= formatPrice($item['price'] * $item['quantity']) ?></div>

                            <form method="POST">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                <button type="submit" style="background:none;border:none;color:var(--accent2);cursor:pointer;font-size:1.1rem;padding:4px;">✕</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ORDER SUMMARY -->
            <div class="card" style="padding:24px;position:sticky;top:80px;">
                <div style="font-family:var(--font-head);font-size:1.2rem;font-weight:700;margin-bottom:20px;">Order Summary</div>

                <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:20px;">
                    <div style="display:flex;justify-content:space-between;color:var(--text2);font-size:0.9rem;">
                        <span>Subtotal (<?= count($cartItems) ?> items)</span>
                        <span><?= formatPrice($subtotal) ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;color:var(--text2);font-size:0.9rem;">
                        <span>Shipping</span>
                        <span style="color:<?= $shipping === 0 ? 'var(--success)' : 'inherit' ?>">
                            <?= $shipping === 0 ? 'FREE' : formatPrice($shipping) ?>
                        </span>
                    </div>
                    <?php if($shipping > 0): ?>
                    <div style="font-size:0.8rem;color:var(--text3);background:var(--surface2);border-radius:6px;padding:8px 10px;">
                        Add <?= formatPrice(999 - $subtotal) ?> more for free shipping
                    </div>
                    <?php endif; ?>
                    <div style="border-top:1px solid var(--border);padding-top:12px;display:flex;justify-content:space-between;font-size:1.1rem;font-weight:700;">
                        <span>Total</span>
                        <span style="color:var(--accent);"><?= formatPrice($total) ?></span>
                    </div>
                </div>

                <a href="checkout.php" class="btn btn-primary" style="width:100%;justify-content:center;font-size:1rem;padding:14px;">
                    Proceed to Checkout →
                </a>
                <a href="shop.php" class="btn btn-outline" style="width:100%;justify-content:center;margin-top:10px;">
                    Continue Shopping
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
