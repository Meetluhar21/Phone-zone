<?php
require_once 'config.php';
$pageTitle = 'Home';

// Featured products
$featured = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_featured = 1 AND p.is_active = 1 LIMIT 6")->fetchAll();

// All categories with product count
$cats = $pdo->query("SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1 GROUP BY c.id")->fetchAll();

// Latest products
$latest = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_active = 1 ORDER BY p.created_at DESC LIMIT 4")->fetchAll();

include 'includes/header.php';
?>

<!-- HERO -->
<section style="background: linear-gradient(135deg, #0a0a0f 0%, #12121a 50%, #0f0f1a 100%); padding: 80px 24px; position:relative; overflow:hidden;">
    <div style="position:absolute;top:-100px;right:-100px;width:500px;height:500px;background:radial-gradient(circle, rgba(108,99,255,0.15) 0%, transparent 70%);pointer-events:none;"></div>
    <div style="position:absolute;bottom:-80px;left:-80px;width:400px;height:400px;background:radial-gradient(circle, rgba(255,107,107,0.1) 0%, transparent 70%);pointer-events:none;"></div>

    <div style="max-width:1280px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center;">
        <div>
            <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(108,99,255,0.15);border:1px solid rgba(108,99,255,0.3);border-radius:20px;padding:6px 16px;font-size:0.8rem;font-weight:600;color:var(--accent);margin-bottom:24px;">
                🔥 New Arrivals 2024
            </div>
            <h1 style="font-family:var(--font-head);font-size:clamp(2.5rem,5vw,4rem);font-weight:800;line-height:1.1;letter-spacing:-0.03em;margin-bottom:20px;">
                Next-Gen<br>
                <span style="color:var(--accent);">Smartphones</span><br>
                Are Here
            </h1>
            <p style="color:var(--text2);font-size:1.05rem;max-width:460px;margin-bottom:36px;line-height:1.7;">
                Discover the latest flagship phones, budget gems, and everything in between. Unbeatable prices, authentic products.
            </p>
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <a href="shop.php" class="btn btn-primary" style="padding:14px 28px;font-size:1rem;">
                    🛍️ Shop Now
                </a>
                <a href="shop.php?category=flagship" class="btn btn-outline" style="padding:14px 28px;font-size:1rem;">
                    View Flagship →
                </a>
            </div>
            <div style="display:flex;gap:32px;margin-top:40px;">
                <div>
                    <div style="font-family:var(--font-head);font-size:1.75rem;font-weight:800;color:var(--text);">500+</div>
                    <div style="font-size:0.8rem;color:var(--text3);">Products</div>
                </div>
                <div>
                    <div style="font-family:var(--font-head);font-size:1.75rem;font-weight:800;color:var(--text);">50+</div>
                    <div style="font-size:0.8rem;color:var(--text3);">Brands</div>
                </div>
                <div>
                    <div style="font-family:var(--font-head);font-size:1.75rem;font-weight:800;color:var(--text);">10K+</div>
                    <div style="font-size:0.8rem;color:var(--text3);">Customers</div>
                </div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;position:relative;">
            <?php foreach(array_slice($featured, 0, 4) as $i => $p): ?>
            <a href="product.php?id=<?= $p['id'] ?>" class="card" style="transform: <?= $i % 2 ? 'translateY(20px)' : '' ?>">
                <div style="padding:16px;text-align:center;">
                    <div style="font-size:0.7rem;color:var(--accent);font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:4px;"><?= sanitize($p['brand']) ?></div>
                    <div style="font-family:var(--font-head);font-size:0.9rem;font-weight:700;margin-bottom:8px;line-height:1.3;"><?= sanitize($p['name']) ?></div>
                    <div style="font-size:1.1rem;font-weight:700;color:var(--accent);"><?= formatPrice($p['price']) ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CATEGORIES -->
<section class="page-wrap">
    <div class="section-title">Browse Categories</div>
    <div class="section-sub">Find exactly what you're looking for</div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;">
        <a href="shop.php" class="card" style="padding:20px;text-align:center;cursor:pointer;">
            <div style="font-size:2rem;margin-bottom:8px;">📱</div>
            <div style="font-family:var(--font-head);font-weight:700;font-size:0.9rem;">All Phones</div>
            <div style="font-size:0.75rem;color:var(--text3);margin-top:2px;">Browse all</div>
        </a>
        <?php foreach($cats as $cat): ?>
        <a href="shop.php?category=<?= urlencode($cat['slug']) ?>" class="card" style="padding:20px;text-align:center;cursor:pointer;">
            <div style="font-size:2rem;margin-bottom:8px;"><?= $cat['icon'] ?></div>
            <div style="font-family:var(--font-head);font-weight:700;font-size:0.9rem;"><?= sanitize($cat['name']) ?></div>
            <div style="font-size:0.75rem;color:var(--text3);margin-top:2px;"><?= $cat['product_count'] ?> phones</div>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- FEATURED PRODUCTS -->
<section class="page-wrap" style="padding-top:0;">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:24px;">
        <div>
            <div class="section-title">Featured Phones</div>
            <div class="section-sub" style="margin-bottom:0;">Hand-picked top performers</div>
        </div>
        <a href="shop.php?featured=1" class="btn btn-outline btn-sm">View All →</a>
    </div>

    <div class="products-grid">
        <?php foreach($featured as $p):
            $discount = $p['original_price'] > 0 ? round((1 - $p['price']/$p['original_price'])*100) : 0;
        ?>
        <div class="card product-card" onclick="location.href='product.php?id=<?= $p['id'] ?>'">
            <div style="position:relative;">
                <?php if($p['image_url']): ?>
                    <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="<?= sanitize($p['name']) ?>" class="product-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                    <div class="product-img-placeholder" style="display:none;">📱</div>
                <?php else: ?>
                    <div class="product-img-placeholder">📱</div>
                <?php endif; ?>
                <?php if($discount > 5): ?>
                    <div style="position:absolute;top:10px;right:10px;background:var(--accent2);color:white;border-radius:6px;padding:3px 8px;font-size:0.75rem;font-weight:700;">-<?= $discount ?>%</div>
                <?php endif; ?>
                <div style="position:absolute;top:10px;left:10px;"><span class="badge badge-featured">⭐ Featured</span></div>
            </div>
            <div class="product-body">
                <div class="product-brand"><?= sanitize($p['brand']) ?></div>
                <div class="product-name"><?= sanitize($p['name']) ?></div>
                <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                    <span class="product-price"><?= formatPrice($p['price']) ?></span>
                    <?php if($p['original_price'] > $p['price']): ?>
                        <span class="product-original"><?= formatPrice($p['original_price']) ?></span>
                        <?php if($discount > 0): ?><span class="product-discount">Save <?= $discount ?>%</span><?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="product-actions">
                <form action="cart.php" method="POST" style="flex:1;">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                    <input type="hidden" name="redirect" value="index.php">
                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;" onclick="event.stopPropagation()">
                        🛒 Add to Cart
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- PROMO BANNER -->
<section style="padding:0 24px;margin-bottom:60px;">
    <div style="max-width:1280px;margin:0 auto;background:linear-gradient(135deg,var(--accent),#9b8eff);border-radius:20px;padding:48px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px;">
        <div>
            <div style="font-family:var(--font-head);font-size:2rem;font-weight:800;color:white;margin-bottom:8px;">Free Delivery on Orders ₹999+</div>
            <div style="color:rgba(255,255,255,0.8);font-size:1rem;">Shop now and get fast delivery across India</div>
        </div>
        <a href="shop.php" class="btn" style="background:white;color:var(--accent);padding:14px 28px;font-size:1rem;">Shop Now →</a>
    </div>
</section>



<?php include 'includes/footer.php'; ?>
