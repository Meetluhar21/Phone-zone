<?php
require_once 'config.php';
$pageTitle = 'Shop';

// Filters
$search   = isset($_GET['search'])   ? trim($_GET['search'])   : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$brand    = isset($_GET['brand'])    ? trim($_GET['brand'])    : '';
$sort     = isset($_GET['sort'])     ? $_GET['sort']           : 'newest';
$featured = isset($_GET['featured']) ? 1 : 0;
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 12;
$offset   = ($page - 1) * $perPage;

// Build query
$where  = ["p.is_active = 1"];
$params = [];

if ($search) {
    $where[]  = "(p.name LIKE ? OR p.brand LIKE ? OR p.description LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if ($category) {
    $where[]  = "c.slug = ?";
    $params[] = $category;
}
if ($brand) {
    $where[]  = "p.brand = ?";
    $params[] = $brand;
}
if ($featured) {
    $where[]  = "p.is_featured = 1";
}

$whereStr = implode(' AND ', $where);

$orderBy = match($sort) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'name'       => 'p.name ASC',
    'featured'   => 'p.is_featured DESC, p.created_at DESC',
    default      => 'p.created_at DESC'
};

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE $whereStr");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE $whereStr ORDER BY $orderBy LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$products = $stmt->fetchAll();

// Brands for filter
$brands = $pdo->query("SELECT DISTINCT brand FROM products WHERE is_active = 1 ORDER BY brand")->fetchAll(PDO::FETCH_COLUMN);
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

include 'includes/header.php';
?>

<div class="page-wrap">
    <div style="display:flex;gap:28px;align-items:flex-start;">

        <!-- SIDEBAR FILTERS -->
        <aside style="width:220px;flex-shrink:0;position:sticky;top:80px;">
            <div style="font-family:var(--font-head);font-size:1rem;font-weight:700;margin-bottom:16px;">Filters</div>

            <form method="GET" id="filterForm">
                <?php if($search): ?>
                    <input type="hidden" name="search" value="<?= sanitize($search) ?>">
                <?php endif; ?>

                <!-- Categories -->
                <div style="margin-bottom:20px;">
                    <div style="font-size:0.8rem;font-weight:600;color:var(--text2);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:10px;">Category</div>
                    <a href="shop.php<?= $search ? '?search='.urlencode($search) : '' ?>" style="display:block;padding:6px 10px;border-radius:6px;font-size:0.875rem;color:<?= !$category ? 'var(--accent)' : 'var(--text2)' ?>;background:<?= !$category ? 'rgba(108,99,255,0.1)' : 'transparent' ?>;margin-bottom:2px;">All Phones</a>
                    <?php foreach($categories as $cat): ?>
                    <a href="shop.php?category=<?= urlencode($cat['slug']) ?><?= $search ? '&search='.urlencode($search) : '' ?>" style="display:block;padding:6px 10px;border-radius:6px;font-size:0.875rem;color:<?= $category === $cat['slug'] ? 'var(--accent)' : 'var(--text2)' ?>;background:<?= $category === $cat['slug'] ? 'rgba(108,99,255,0.1)' : 'transparent' ?>;margin-bottom:2px;">
                        <?= $cat['icon'] ?> <?= sanitize($cat['name']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- Brands -->
                <div style="margin-bottom:20px;">
                    <div style="font-size:0.8rem;font-weight:600;color:var(--text2);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:10px;">Brand</div>
                    <?php foreach($brands as $b): ?>
                    <label style="display:flex;align-items:center;gap:8px;padding:4px 0;cursor:pointer;font-size:0.875rem;color:var(--text2);">
                        <input type="radio" name="brand" value="<?= sanitize($b) ?>" <?= $brand === $b ? 'checked' : '' ?> onchange="this.form.submit()" style="width:auto;accent-color:var(--accent);">
                        <?= sanitize($b) ?>
                    </label>
                    <?php endforeach; ?>
                    <?php if($brand): ?>
                        <a href="shop.php<?= $category ? '?category='.urlencode($category) : '' ?>" style="font-size:0.8rem;color:var(--accent2);margin-top:4px;display:block;">✕ Clear brand</a>
                    <?php endif; ?>
                </div>

                <!-- Sort -->
                <div>
                    <div style="font-size:0.8rem;font-weight:600;color:var(--text2);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:10px;">Sort By</div>
                    <select name="sort" onchange="this.form.submit()" style="width:100%;padding:8px 12px;font-size:0.85rem;">
                        <option value="newest" <?= $sort==='newest' ? 'selected' : '' ?>>Newest First</option>
                        <option value="price_asc" <?= $sort==='price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_desc" <?= $sort==='price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="name" <?= $sort==='name' ? 'selected' : '' ?>>Name A–Z</option>
                        <option value="featured" <?= $sort==='featured' ? 'selected' : '' ?>>Featured First</option>
                    </select>
                </div>
                <?php if($category): ?><input type="hidden" name="category" value="<?= sanitize($category) ?>"><?php endif; ?>
                <?php if($brand): ?><input type="hidden" name="brand" value="<?= sanitize($brand) ?>"><?php endif; ?>
            </form>
        </aside>

        <!-- MAIN CONTENT -->
        <main style="flex:1;min-width:0;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
                <div>
                    <div class="section-title" style="font-size:1.4rem;">
                        <?php if($search): ?>
                            Results for "<?= sanitize($search) ?>"
                        <?php elseif($category): ?>
                            <?= sanitize(ucfirst($category)) ?> Phones
                        <?php elseif($featured): ?>
                            Featured Phones
                        <?php else: ?>
                            All Phones
                        <?php endif; ?>
                    </div>
                    <div style="color:var(--text3);font-size:0.875rem;"><?= $total ?> phone<?= $total !== 1 ? 's' : '' ?> found</div>
                </div>
                <?php if($search || $category || $brand || $featured): ?>
                    <a href="shop.php" class="btn btn-outline btn-sm">✕ Clear filters</a>
                <?php endif; ?>
            </div>

            <?php if(empty($products)): ?>
                <div style="text-align:center;padding:80px 20px;">
                    <div style="font-size:4rem;margin-bottom:16px;">📭</div>
                    <div style="font-family:var(--font-head);font-size:1.5rem;font-weight:700;margin-bottom:8px;">No phones found</div>
                    <div style="color:var(--text2);margin-bottom:24px;">Try adjusting your filters or search term</div>
                    <a href="shop.php" class="btn btn-primary">Browse all phones</a>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach($products as $p):
                        $discount = $p['original_price'] > 0 ? round((1 - $p['price']/$p['original_price'])*100) : 0;
                        $stockClass = $p['stock'] > 10 ? 'badge-stock' : 'badge-low';
                        $stockLabel = $p['stock'] > 10 ? 'In Stock' : ($p['stock'] > 0 ? 'Low Stock' : 'Out of Stock');
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
                            <?php if($p['is_featured']): ?>
                                <div style="position:absolute;top:10px;left:10px;"><span class="badge badge-featured">⭐</span></div>
                            <?php endif; ?>
                        </div>
                        <div class="product-body">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:2px;">
                                <div class="product-brand"><?= sanitize($p['brand']) ?></div>
                                <span class="badge <?= $stockClass ?>" style="font-size:0.65rem;"><?= $stockLabel ?></span>
                            </div>
                            <div class="product-name"><?= sanitize($p['name']) ?></div>
                            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                <span class="product-price"><?= formatPrice($p['price']) ?></span>
                                <?php if($p['original_price'] > $p['price']): ?>
                                    <span class="product-original"><?= formatPrice($p['original_price']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="product-actions" onclick="event.stopPropagation()">
                            <?php if($p['stock'] > 0): ?>
                            <form action="cart.php" method="POST" style="flex:1;">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="redirect" value="shop.php">
                                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">🛒 Add</button>
                            </form>
                            <?php else: ?>
                                <span style="color:var(--text3);font-size:0.85rem;padding:8px;">Out of Stock</span>
                            <?php endif; ?>
                            <a href="product.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">Details</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- PAGINATION -->
                <?php if($totalPages > 1): ?>
                <div class="pagination">
                    <?php if($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page-1])) ?>" class="page-btn">← Prev</a>
                    <?php endif; ?>
                    <?php for($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if($page < $totalPages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page+1])) ?>" class="page-btn">Next →</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
