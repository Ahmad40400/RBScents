<?php
require 'db.php';
require 'helpers.php';

// Get featured perfumes
$stmt = $pdo->query("SELECT * FROM perfumes ORDER BY created_at DESC LIMIT 8");
$featured_perfumes = $stmt->fetchAll();

// Check if user is logged in
$is_logged_in = is_logged_in();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luxe Perfumes - RBScents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Custom styles for the login popup */
        .login-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .login-popup {
            background: white;
            border-radius: 10px;
            padding: 30px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            text-align: center;
        }
        
        .login-popup h3 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .login-popup p {
            margin-bottom: 25px;
            color: #666;
        }
        
        .login-popup .btn {
            margin: 5px;
        }
        
        .hidden {
            display: none;
        }
        
        /* Disable interaction with page content when popup is active */
        body.popup-active {
            overflow: hidden;
            pointer-events: none;
        }
        
        body.popup-active .login-popup-overlay {
            pointer-events: auto;
        }
    </style>
</head>
<body <?php if (!$is_logged_in): ?>class="popup-active"<?php endif; ?>>
    <!-- Login Popup -->
    <?php if (!$is_logged_in): ?>
    <div class="login-popup-overlay" id="loginPopup">
        <div class="login-popup">
            <h3>Welcome to RBScents</h3>
            <p>Please login to explore our premium fragrance collection and enjoy a personalized shopping experience.</p>
            <div class="d-flex justify-content-center flex-wrap">
                <a href="login.php" class="btn btn-primary">Login</a>
                <a href="register.php" class="btn btn-outline-primary">Sign Up</a>
            </div>
            <p class="mt-3 small text-muted">You need to login to continue using the site</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Navigation -->
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
         <li><a class="dropdown-item" href="orders.php"><i class="fas fa-box me-2"></i>My Orders</a></li>
    <?php endif; ?>
    <?php if (is_admin()): ?>
        <li class="nav-item"><a href="admin.php" class="nav-link">Admin</a></li>
    <?php endif; ?>
</ul>
                
                <div class="d-flex align-items-center">
                    <?php if ($is_logged_in): ?>
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1 class="floating">Discover Your Signature Scent</h1>
            <p>Experience luxury fragrances that tell your story</p>
            <a href="products.php" class="btn btn-gold btn-lg pulse">Explore Collection</a>
        </div>
    </section>

    <!-- Featured Perfumes -->
    <section class="container py-5">
        <h2 class="text-center mb-5">Featured Fragrances</h2>
        <div class="perfume-grid">
            <?php if (empty($featured_perfumes)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-wind fa-3x text-muted mb-3"></i>
                    <h3>No perfumes available</h3>
                    <p class="text-muted">Check back soon for our latest collection</p>
                </div>
            <?php else: ?>
                <?php foreach ($featured_perfumes as $perfume): ?>
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
                                <?php if ($is_logged_in): ?>
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
        
        <div class="text-center mt-4">
            <a href="products.php" class="btn btn-outline">View All Perfumes</a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="bg-light py-5">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <div class="admin-card">
                        <i class="fas fa-truck fa-3x text-primary mb-3"></i>
                        <h4>Free Shipping</h4>
                        <p class="text-muted">On all orders over $50</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="admin-card">
                        <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                        <h4>Quality Guarantee</h4>
                        <p class="text-muted">100% authentic fragrances</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="admin-card">
                        <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                        <h4>24/7 Support</h4>
                        <p class="text-muted">Expert customer service</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

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
    <script>
        // Prevent closing the popup
        document.addEventListener('DOMContentLoaded', function() {
            const popup = document.getElementById('loginPopup');
            if (popup) {
                // Disable clicks outside the popup
                popup.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
                
                // Prevent right-click context menu
                document.addEventListener('contextmenu', function(e) {
                    e.preventDefault();
                    return false;
                });
                
                // Disable keyboard shortcuts that might close the popup
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' || e.key === 'F12' || 
                        (e.ctrlKey && (e.key === 'w' || e.key === 'n' || e.key === 't'))) {
                        e.preventDefault();
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>