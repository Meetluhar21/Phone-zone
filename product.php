<?php
require_once 'config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('shop.php');

$stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.is_active = 1");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    flashMessage('error', 'Product not found.');
    redirect('shop.php');
}

$pageTitle = $product['name'];

// Specs
$specs = json_decode($product['specs'] ?? '{}', true) ?: [];

// Reviews
$reviews = $pdo->prepare("SELECT r.*, u.name as user_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
$reviews->execute([$id]);
$reviews = $reviews->fetchAll();
$avgRating = count($reviews) ? round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1) : 0;


// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'review') {
    if (!isLoggedIn()) {
        flashMessage('error', 'Please login to leave a review.');
        redirect("product.php?id=$id");
    }
    $rating = (int)$_POST['rating'];
    $comment = sanitize($_POST['comment'] ?? '');
    if ($rating < 1 || $rating > 5) {
        flashMessage('error', 'Please select a rating.');
    } else {
        // Check if already reviewed
        $check = $pdo->prepare("SELECT id FROM reviews WHERE product_id = ? AND user_id = ?");
        $check->execute([$id, $_SESSION['user_id']]);
        if ($check->fetch()) {
            flashMessage('error', 'You have already reviewed this product.');
        } else {
            $ins = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?,?,?,?)");
            $ins->execute([$id, $_SESSION['user_id'], $rating, $comment]);
            flashMessage('success', 'Review submitted!');
        }
    }
    redirect("product.php?id=$id");
}

include 'includes/header.php';
?>

<div class="page-wrap">
    <!-- BREADCRUMB -->
    <div style="display:flex;gap:8px;align-items:center;color:var(--text3);font-size:0.875rem;margin-bottom:28px;">
        <a href="index.php" style="color:var(--text3);">Home</a> /
        <a href="shop.php" style="color:var(--text3);">Shop</a> /
        <?php if($product['category_name']): ?>
            <a href="shop.php?category=<?= urlencode($product['category_slug']) ?>" style="color:var(--text3);"><?= sanitize($product['category_name']) ?></a> /
        <?php endif; ?>
        <span style="color:var(--text2);"><?= sanitize($product['name']) ?></span>
    </div>

    <!-- PRODUCT MAIN -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:48px;margin-bottom:60px;">
        <!-- IMAGE -->
        <div>
            <div class="card" style="padding:0;overflow:hidden;">
                <?php if($product['image_url']): ?>
                    <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= sanitize($product['name']) ?>" style="width:100%;aspect-ratio:4/3;object-fit:cover;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                    <div style="display:none;width:100%;aspect-ratio:4/3;align-items:center;justify-content:center;font-size:6rem;background:var(--surface2);">📱</div>
                <?php else: ?>
                    <div style="width:100%;aspect-ratio:4/3;display:flex;align-items:center;justify-content:center;font-size:8rem;background:var(--surface2);">📱</div>
                <?php endif; ?>
            </div>

            <?php if($avgRating > 0): ?>
            <div style="display:flex;align-items:center;gap:12px;margin-top:16px;padding:12px 16px;background:var(--surface);border-radius:var(--radius);border:1px solid var(--border);">
                <div style="font-size:1.5rem;font-weight:700;color:var(--accent3);"><?= $avgRating ?></div>
                <div>
                    <div style="color:var(--accent3);font-size:1.1rem;"><?= str_repeat('★', round($avgRating)) ?><?= str_repeat('☆', 5-round($avgRating)) ?></div>
                    <div style="font-size:0.8rem;color:var(--text3);"><?= count($reviews) ?> review<?= count($reviews) !== 1 ? 's' : '' ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- DETAILS -->
        <div>
            <?php if($product['is_featured']): ?>
                <span class="badge badge-featured" style="margin-bottom:12px;display:inline-block;">⭐ Featured</span>
            <?php endif; ?>

            <div style="font-size:0.85rem;color:var(--accent);font-weight:600;text-transform:uppercase;letter-spacing:0.08em;"><?= sanitize($product['brand']) ?></div>
            <h1 style="font-family:var(--font-head);font-size:2rem;font-weight:800;line-height:1.2;margin:8px 0 16px;"><?= sanitize($product['name']) ?></h1>

            <div style="display:flex;align-items:baseline;gap:12px;margin-bottom:20px;">
                <div style="font-size:2rem;font-weight:700;color:var(--text);"><?= formatPrice($product['price']) ?></div>
                <?php if($product['original_price'] > $product['price']): ?>
                    <div style="font-size:1.2rem;color:var(--text3);text-decoration:line-through;"><?= formatPrice($product['original_price']) ?></div>
                    <?php $d = round((1-$product['price']/$product['original_price'])*100); ?>
                    <div style="background:rgba(74,222,128,0.15);color:var(--success);border-radius:6px;padding:3px 10px;font-size:0.875rem;font-weight:700;">Save <?= $d ?>%</div>
                <?php endif; ?>
            </div>

            <p style="color:var(--text2);line-height:1.8;margin-bottom:24px;"><?= nl2br(sanitize($product['description'])) ?></p>

            <!-- STOCK -->
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:24px;">
                <?php if($product['stock'] > 10): ?>
                    <span class="badge badge-stock">✓ In Stock</span>
                    <span style="color:var(--text3);font-size:0.85rem;"><?= $product['stock'] ?> units available</span>
                <?php elseif($product['stock'] > 0): ?>
                    <span class="badge badge-low">⚡ Only <?= $product['stock'] ?> left</span>
                <?php else: ?>
                    <span class="badge" style="background:rgba(100,100,100,0.2);color:var(--text3);">Out of Stock</span>
                <?php endif; ?>
            </div>

            <!-- ADD TO CART -->
            <?php if($product['stock'] > 0): ?>
            <form action="cart.php" method="POST" style="display:flex;gap:10px;margin-bottom:20px;">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <input type="hidden" name="redirect" value="product.php?id=<?= $product['id'] ?>">
                <div style="display:flex;align-items:center;background:var(--surface2);border:1px solid var(--border);border-radius:8px;overflow:hidden;">
                    <button type="button" onclick="changeQty(-1)" style="background:none;border:none;color:var(--text);padding:10px 14px;cursor:pointer;font-size:1.1rem;">−</button>
                    <input type="number" name="quantity" id="qty" value="1" min="1" max="<?= $product['stock'] ?>" style="width:50px;border:none;background:transparent;text-align:center;color:var(--text);font-size:1rem;padding:0;">
                    <button type="button" onclick="changeQty(1)" style="background:none;border:none;color:var(--text);padding:10px 14px;cursor:pointer;font-size:1.1rem;">+</button>
                </div>
                <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center;font-size:1rem;padding:12px;">
                    🛒 Add to Cart
                </button>
            </form>
            <?php else: ?>
                <div class="btn" style="background:var(--surface2);color:var(--text3);cursor:not-allowed;margin-bottom:20px;">Out of Stock</div>
            <?php endif; ?>

            <!-- SPECS -->
            <?php if(!empty($specs)): ?>
            <div class="card" style="padding:20px;">
                <div style="font-family:var(--font-head);font-size:1rem;font-weight:700;margin-bottom:14px;">Key Specs</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <?php foreach($specs as $key => $val): ?>
                    <div style="background:var(--surface2);border-radius:8px;padding:10px 12px;">
                        <div style="font-size:0.7rem;color:var(--text3);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:2px;"><?= sanitize(ucfirst($key)) ?></div>
                        <div style="font-size:0.875rem;font-weight:600;color:var(--text);"><?= sanitize($val) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- REVIEWS -->
    <div style="margin-bottom:60px;">
        <div class="section-title" style="margin-bottom:20px;">Customer Reviews</div>

        <!-- Submit Review -->
        <?php if(isLoggedIn()): ?>
        <div class="card" style="padding:24px;margin-bottom:24px;">
            <div style="font-weight:600;margin-bottom:14px;">Write a Review</div>
            <form method="POST">
                <input type="hidden" name="action" value="review">
                <div class="form-group">
                    <label>Rating</label>
                    <div style="display:flex;gap:8px;">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                        <label style="cursor:pointer;">
                            <input type="radio" name="rating" value="<?= $i ?>" style="display:none;" required>
                            <span style="font-size:1.5rem;" onmouseover="highlightStars(<?= $i ?>)" onclick="selectStar(<?= $i ?>)" class="star" data-val="<?= $i ?>">☆</span>
                        </label>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label>Comment (optional)</label>
                    <textarea name="comment" rows="3" placeholder="Share your experience..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Submit Review</button>
            </form>
        </div>
        <?php else: ?>
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px;margin-bottom:24px;color:var(--text2);">
            <a href="login.php" style="color:var(--accent);">Login</a> to leave a review
        </div>
        <?php endif; ?>

        <!-- Reviews List -->
        <?php if(empty($reviews)): ?>
            <div style="color:var(--text3);font-size:0.95rem;">No reviews yet. Be the first!</div>
        <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:14px;">
                <?php foreach($reviews as $r): ?>
                <div class="card" style="padding:20px;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                        <div>
                            <span style="font-weight:600;"><?= sanitize($r['user_name']) ?></span>
                            <span style="color:var(--accent3);margin-left:10px;"><?= str_repeat('★', $r['rating']) ?><?= str_repeat('☆', 5-$r['rating']) ?></span>
                        </div>
                        <span style="font-size:0.8rem;color:var(--text3);"><?= date('M j, Y', strtotime($r['created_at'])) ?></span>
                    </div>
                    <?php if($r['comment']): ?>
                        <p style="color:var(--text2);font-size:0.9rem;"><?= nl2br(sanitize($r['comment'])) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- RELATED PRODUCTS -->
    <?php if(!empty($related)): ?>
    <div>
        <div class="section-title" style="margin-bottom:20px;">Related Phones</div>
        <div class="products-grid" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr));">
            <?php foreach($related as $p): ?>
            <div class="card product-card" onclick="location.href='product.php?id=<?= $p['id'] ?>'">
                <div class="product-img-placeholder">📱</div>
                <div class="product-body">
                    <div class="product-brand"><?= sanitize($p['brand']) ?></div>
                    <div class="product-name"><?= sanitize($p['name']) ?></div>
                    <span class="product-price"><?= formatPrice($p['price']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function changeQty(delta) {
    const q = document.getElementById('qty');
    const val = parseInt(q.value) + delta;
    q.value = Math.max(1, Math.min(<?= $product['stock'] ?>, val));
}
function highlightStars(n) {
    document.querySelectorAll('.star').forEach(s => {
        s.textContent = parseInt(s.dataset.val) <= n ? '★' : '☆';
        s.style.color = parseInt(s.dataset.val) <= n ? '#ffd93d' : '';
    });
}
function selectStar(n) {
    document.querySelectorAll('.star').forEach(s => {
        s.style.color = parseInt(s.dataset.val) <= n ? '#ffd93d' : '';
    });
}
</script>

<?php include 'includes/footer.php'; ?>
