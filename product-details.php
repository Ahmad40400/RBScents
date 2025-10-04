<?php
require 'db.php';
require 'helpers.php';

$perfume_id = $_GET['id'] ?? 0;

if (!$perfume_id) {
    header('Location: products.php');
    exit;
}

// Get perfume details
$stmt = $pdo->prepare("SELECT * FROM perfumes WHERE id = ?");
$stmt->execute([$perfume_id]);
$perfume = $stmt->fetch();

if (!$perfume) {
    header('Location: products.php');
    exit;
}

// Get related perfumes (same brand)
$stmt = $pdo->prepare("SELECT * FROM perfumes WHERE brand = ? AND id != ? ORDER BY created_at DESC LIMIT 4");
$stmt->execute([$perfume['brand'], $perfume_id]);
$related_perfumes = $stmt->fetchAll();

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart']) && is_logged_in()) {
    $quantity = (int)$_POST['quantity'] ?? 1;
    
    if ($quantity > 0 && $quantity <= $perfume['in_stock']) {
        // Check if already in cart
        $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND perfume_id = ?");
        $stmt->execute([$_SESSION['user_id'], $perfume_id]);
        $existing_item = $stmt->fetch();
        
        if ($existing_item) {
            // Update quantity
            $new_quantity = $existing_item['quantity'] + $quantity;
            if ($new_quantity <= $perfume['in_stock']) {
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND perfume_id = ?");
                $stmt->execute([$new_quantity, $_SESSION['user_id'], $perfume_id]);
                $success = 'Cart updated successfully!';
            } else {
                $error = 'Not enough stock available';
            }
        } else {
            // Add new item
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, perfume_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $perfume_id, $quantity]);
            $success = 'Perfume added to cart!';
        }
    } else {
        $error = 'Invalid quantity or insufficient stock';
    }
}

// YAHAN ADD KARO - Handle delete product (Admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product']) && is_admin()) {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $error = 'Invalid CSRF token';
    } else {
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // First delete from cart
            $stmt = $pdo->prepare("DELETE FROM cart WHERE perfume_id = ?");
            $stmt->execute([$perfume_id]);
            
            // Also delete from order_items if exists
            $stmt = $pdo->prepare("DELETE FROM order_items WHERE perfume_id = ?");
            $stmt->execute([$perfume_id]);
            
            // Then delete the product
            $stmt = $pdo->prepare("DELETE FROM perfumes WHERE id = ?");
            if ($stmt->execute([$perfume_id])) {
                $pdo->commit();
                $_SESSION['success'] = 'Product deleted successfully!';
                header('Location: products.php');
                exit;
            } else {
                throw new Exception('Failed to delete product');
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to delete product: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($perfume['name']) ?> - RBScents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .product-image {
            width: 100%;
            height: 500px;
            object-fit: cover;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .product-info {
            padding-left: 2rem;
        }
        
        .product-title {
            font-size: 2.5rem;
            font-weight: 300;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .product-brand {
            font-size: 1.2rem;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 1rem;
        }
        
        .product-price {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }
        
        .product-description {
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--text);
            margin-bottom: 2rem;
        }
        
        .stock-info {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .stock-badge {
            background: var(--light);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .stock-badge.in-stock {
            background: rgba(40, 167, 69, 0.1);
            color: #155724;
        }
        
        .stock-badge.low-stock {
            background: rgba(255, 193, 7, 0.1);
            color: #856404;
        }
        
        .stock-badge.out-of-stock {
            background: rgba(220, 53, 69, 0.1);
            color: #721c24;
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .quantity-input {
            width: 80px;
            text-align: center;
            font-weight: 600;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .btn-add-cart {
            flex: 2;
        }
        
        .btn-wishlist {
            flex: 1;
            background: var(--light);
            color: var(--text);
            border: 1px solid rgba(139, 90, 43, 0.2);
        }
        
        .btn-wishlist:hover {
            background: rgba(139, 90, 43, 0.1);
        }

        /* Admin buttons styles */
        .admin-actions {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid var(--secondary);
        }
        
        .admin-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .btn-edit {
            background: var(--secondary);
            color: var(--dark);
            border: 1px solid var(--secondary);
        }
        
        .btn-edit:hover {
            background: #B89446;
            color: var(--dark);
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
            border: 1px solid #dc3545;
        }
        
        .btn-delete:hover {
            background: #c82333;
            color: white;
        }
        
        .product-meta {
            border-top: 1px solid rgba(139, 90, 43, 0.1);
            padding-top: 1.5rem;
        }
        
        .meta-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(139, 90, 43, 0.05);
        }
        
        .meta-label {
            font-weight: 600;
            color: var(--text);
        }
        
        .meta-value {
            color: var(--primary);
        }
        
        .related-products {
            margin-top: 4rem;
        }
        
        .section-title {
            font-size: 1.8rem;
            font-weight: 300;
            color: var(--dark);
            margin-bottom: 2rem;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .product-info {
                padding-left: 0;
                margin-top: 2rem;
            }
            
            .product-title {
                font-size: 2rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .admin-buttons {
                flex-direction: column;
            }
        }
    </style>
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
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="products.php">Perfumes</a></li>
                <li class="breadcrumb-item active"><?= e($perfume['name']) ?></li>
            </ol>
        </nav>
        
        <!-- Product Details -->
        <div class="row mb-5">
            <div class="col-md-6">
                <img src="<?= 
                    (!empty($perfume['image_filename']) && file_exists('uploads/' . $perfume['image_filename'])) 
                    ? 'uploads/' . e($perfume['image_filename']) 
                    : (e($perfume['image']) ?: 'https://images.unsplash.com/photo-1547887537-6158d64c35b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80')
                ?>" class="product-image" alt="<?= e($perfume['name']) ?>">
            </div>
            
            <div class="col-md-6">
                <div class="product-info">
                    <h1 class="product-title"><?= e($perfume['name']) ?></h1>
                    <p class="product-brand"><?= e($perfume['brand']) ?></p>
                    <p class="product-price">Rs<?= e($perfume['price']) ?></p>
                    
                    <div class="stock-info">
                        <?php if ($perfume['in_stock'] > 10): ?>
                            <span class="stock-badge in-stock">
                                <i class="fas fa-check-circle me-1"></i>In Stock
                            </span>
                        <?php elseif ($perfume['in_stock'] > 0): ?>
                            <span class="stock-badge low-stock">
                                <i class="fas fa-exclamation-triangle me-1"></i>Only <?= e($perfume['in_stock']) ?> left
                            </span>
                        <?php else: ?>
                            <span class="stock-badge out-of-stock">
                                <i class="fas fa-times-circle me-1"></i>Out of Stock
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <p class="product-description">
                        <?= e($perfume['description'] ?: 'Experience the luxury of this exquisite fragrance. Crafted with premium ingredients and meticulous attention to detail, this perfume offers a sophisticated scent that lasts throughout the day.') ?>
                    </p>
                    
                    <?php if (is_logged_in()): ?>
                        <form method="post">
                            <div class="quantity-selector">
                                <label for="quantity" class="form-label fw-bold">Quantity:</label>
                                <input type="number" id="quantity" name="quantity" class="form-control quantity-input" 
                                       value="1" min="1" max="<?= e($perfume['in_stock']) ?>" 
                                       <?= $perfume['in_stock'] == 0 ? 'disabled' : '' ?>>
                            </div>
                            
                            <?php if (isset($success)): ?>
                                <div class="alert alert-success"><?= e($success) ?></div>
                            <?php endif; ?>
                            
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger"><?= e($error) ?></div>
                            <?php endif; ?>
                            
                            <div class="action-buttons">
                                <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg btn-add-cart"
                                        <?= $perfume['in_stock'] == 0 ? 'disabled' : '' ?>>
                                    <i class="fas fa-shopping-cart me-2"></i>
                                    <?= $perfume['in_stock'] > 0 ? 'Add to Cart' : 'Out of Stock' ?>
                                </button>
                                <button type="button" class="btn btn-wishlist btn-lg">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <a href="login.php" class="alert-link">Login</a> to add this item to your cart.
                        </div>
                    <?php endif; ?>
                    
                    <!-- Admin Actions Section -->
                    <?php if (is_admin()): ?>
                        <div class="admin-actions">
                            <h5 class="text-secondary mb-3">
                                <i class="fas fa-cog me-2"></i>Admin Actions
                            </h5>
                            <div class="admin-buttons">
                                <a href="edit-product.php?id=<?= $perfume['id'] ?>" class="btn btn-edit">
                                    <i class="fas fa-edit me-2"></i>Edit Product
                                </a>
                                <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this product? This action cannot be undone.');">
                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                    <button type="submit" name="delete_product" class="btn btn-delete w-100">
                                        <i class="fas fa-trash me-2"></i>Delete Product
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="product-meta">
                        <div class="meta-item">
                            <span class="meta-label">Category:</span>
                            <span class="meta-value"><?= e($perfume['category'] ?: 'Unisex') ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Brand:</span>
                            <span class="meta-value"><?= e($perfume['brand']) ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Availability:</span>
                            <span class="meta-value"><?= e($perfume['in_stock']) ?> in stock</span>
                        </div>
                        <?php if (is_admin()): ?>
                            <div class="meta-item">
                                <span class="meta-label">Product ID:</span>
                                <span class="meta-value">#<?= e($perfume['id']) ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Created:</span>
                                <span class="meta-value"><?= e(date('M j, Y', strtotime($perfume['created_at']))) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
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
    <script>
        // Quantity selector functionality
        document.addEventListener('DOMContentLoaded', function() {
            const quantityInput = document.getElementById('quantity');
            if (quantityInput) {
                // Ensure quantity stays within bounds
                quantityInput.addEventListener('change', function() {
                    const max = parseInt(this.getAttribute('max'));
                    const min = parseInt(this.getAttribute('min'));
                    let value = parseInt(this.value);
                    
                    if (isNaN(value) || value < min) {
                        this.value = min;
                    } else if (value > max) {
                        this.value = max;
                    }
                });
            }
            
            // Wishlist button animation
            const wishlistBtn = document.querySelector('.btn-wishlist');
            if (wishlistBtn) {
                wishlistBtn.addEventListener('click', function() {
                    this.classList.toggle('active');
                    if (this.classList.contains('active')) {
                        this.innerHTML = '<i class="fas fa-heart text-danger"></i>';
                    } else {
                        this.innerHTML = '<i class="fas fa-heart"></i>';
                    }
                });
            }
        });
    </script>
</body>
</html>