<?php
require 'db.php';
require 'helpers.php';

// Get filters
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$brand = $_GET['brand'] ?? '';

// Build query
$sql = "SELECT * FROM perfumes WHERE 1=1";
$params = [];

if ($category) {
    $sql .= " AND category = ?";
    $params[] = $category;
}

if ($brand) {
    $sql .= " AND brand LIKE ?";
    $params[] = "%$brand%";
}

if ($search) {
    $sql .= " AND (name LIKE ? OR description LIKE ? OR brand LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$perfumes = $stmt->fetchAll();

// Get unique brands for filter
$brands = $pdo->query("SELECT DISTINCT brand FROM perfumes ORDER BY brand")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Perfumes - RBScents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-gem me-2"></i>RBScents
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
              <ul class="navbar-nav me-auto">
    <li class="nav-item"><a href="index.php" class="nav-link">Home</a></li>
    <li class="nav-item"><a href="products.php" class="nav-link">Perfumes</a></li>
    <?php if (!is_admin()): ?>
        <li class="nav-item"><a href="about.php" class="nav-link">About</a></li>
        <li class="nav-item"><a href="contact.php" class="nav-link">Contact</a></li>
    <?php endif; ?>
    <?php if (is_admin()): ?>
        <li class="nav-item"><a href="admin.php" class="nav-link">Admin</a></li>
    <?php endif; ?>
</ul>
                
                <div class="d-flex align-items-center">
                    <?php if (is_logged_in()): ?>
                        <a href="cart.php" class="btn btn-outline me-2">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-badge">Cart</span>
                        </a>
                        <div class="dropdown">
                            <button class="btn p-0 d-flex align-items-center dropdown-toggle" data-bs-toggle="dropdown">
                                <div class="bg-primary text-white rounded-circle me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                    <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                                </div>
                                <span><?= e($_SESSION['user_name'] ?? 'User') ?></span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="orders.php"><i class="fas fa-box me-2"></i>My Orders</a></li>
                                <?php if (is_admin()): ?>
                                    <li><a class="dropdown-item" href="add-product.php"><i class="fas fa-plus me-2"></i>Add Perfume</a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary btn-sm me-2">Login</a>
                        <a href="register.php" class="btn btn-outline btn-sm">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-5 mt-5">
        <h2 class="text-center mb-4">Our Perfume Collection</h2>
        
        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="admin-card">
                    <form method="get" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-control">
                                <option value="">All Categories</option>
                                <option value="Men" <?= $category === 'Men' ? 'selected' : '' ?>>Men</option>
                                <option value="Women" <?= $category === 'Women' ? 'selected' : '' ?>>Women</option>
                                <option value="Unisex" <?= $category === 'Unisex' ? 'selected' : '' ?>>Unisex</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Brand</label>
                            <select name="brand" class="form-control">
                                <option value="">All Brands</option>
                                <?php foreach ($brands as $brand_item): ?>
                                    <option value="<?= e($brand_item['brand']) ?>" <?= $brand === $brand_item['brand'] ? 'selected' : '' ?>>
                                        <?= e($brand_item['brand']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Search perfumes..." value="<?= e($search) ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="perfume-grid">
            <?php if (empty($perfumes)): ?>
                <div class="text-center py-5 col-12">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h3>No perfumes found</h3>
                    <p class="text-muted">Try adjusting your search filters</p>
                    <a href="products.php" class="btn btn-primary">Clear Filters</a>
                </div>
            <?php else: ?>
                <?php foreach ($perfumes as $perfume): ?>
                    <div class="perfume-card">
                        <!-- Replace existing image line with: -->
<img src="<?= 
    (!empty($perfume['image_filename']) && file_exists('uploads/' . $perfume['image_filename'])) 
    ? 'uploads/' . e($perfume['image_filename']) 
    : (e($perfume['image']) ?: 'https://images.unsplash.com/photo-1547887537-6158d64c35b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80')
?>" class="perfume-image" alt="<?= e($perfume['name']) ?>">
                            
                        <div class="perfume-info">
                            <h3 class="perfume-name"><?= e($perfume['name']) ?></h3>
                            <p class="perfume-brand"><?= e($perfume['brand']) ?></p>
                            <p class="perfume-price">Rs<?= e($perfume['price']) ?></p>
                            <div class="d-flex gap-2">
                                <a href="product-details.php?id=<?= $perfume['id'] ?>" class="btn btn-primary flex-grow-1">View Details</a>
                                <?php if (is_logged_in()): ?>
                                    <a href="add-to-cart.php?id=<?= $perfume['id'] ?>" class="btn btn-outline">
                                        <i class="fas fa-cart-plus"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="navbar-brand text-white">RBScents</h5>
                    <p>Discover the essence of luxury with our premium fragrance collection.</p>
                </div>
                <div class="col-md-2 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="products.php" class="text-white">Perfumes</a></li>
                        <li><a href="about.php" class="text-white">About</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5>Customer Service</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white">Shipping Info</a></li>
                        <li><a href="#" class="text-white">Returns</a></li>
                        <li><a href="contact.php" class="text-white">Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Connect</h5>
                    <div class="d-flex gap-3 mt-3">
                        <a href="#" class="text-white"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-twitter fa-lg"></i></a>
                    </div>
                </div>
            </div>
            <hr>
            <p class="text-center mb-0">&copy; 2024 RBScents. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>