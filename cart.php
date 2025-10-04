<?php
require 'db.php';
require 'helpers.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get cart items - UPDATED COLUMN NAMES
$stmt = $pdo->prepare("
    SELECT c.*, p.name, p.brand, p.price, p.image, p.image_filename, p.in_stock 
    FROM cart c 
    JOIN perfumes p ON c.perfume_id = p.id  -- Changed product_id to perfume_id
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_quantity'])) {
        $perfume_id = $_POST['perfume_id']; // Changed product_id to perfume_id
        $quantity = (int)$_POST['quantity'];
        
        if ($quantity > 0) {
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND perfume_id = ?"); // Changed product_id to perfume_id
            $stmt->execute([$quantity, $user_id, $perfume_id]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND perfume_id = ?"); // Changed product_id to perfume_id
            $stmt->execute([$user_id, $perfume_id]);
        }
        header('Location: cart.php');
        exit;
    }
    
    if (isset($_POST['remove_item'])) {
        $perfume_id = $_POST['perfume_id']; // Changed product_id to perfume_id
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND perfume_id = ?"); // Changed product_id to perfume_id
        $stmt->execute([$user_id, $perfume_id]);
        header('Location: cart.php');
        exit;
    }
    
    if (isset($_POST['checkout'])) {
        header('Location: checkout.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - RBScents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .cart-item-mobile {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid rgba(139, 90, 43, 0.1);
        }
        
        .cart-item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.5rem 0;
        }
        
        .quantity-btn {
            width: 35px;
            height: 35px;
            border: 1px solid #8B5A2B;
            background: white;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        .quantity-input {
            width: 50px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 0.25rem;
        }
        
        .stock-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 0.5rem;
            margin: 0.5rem 0;
            font-size: 0.8rem;
        }
        
        .cart-summary-mobile {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid rgba(139, 90, 43, 0.1);
            position: sticky;
            bottom: 0;
            background: white;
            margin-top: 1rem;
        }
        
        .empty-cart-mobile {
            text-align: center;
            padding: 3rem 1rem;
        }
        
        .empty-cart-mobile i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #8B5A2B;
        }
        
        /* Desktop styles */
        @media (min-width: 768px) {
            .cart-item-desktop {
                display: flex;
                align-items: center;
                padding: 1rem;
                border-bottom: 1px solid #eee;
            }
            
            .cart-item-image-desktop {
                width: 100px;
                height: 100px;
                object-fit: cover;
                border-radius: 8px;
                margin-right: 1rem;
            }
            
            .cart-summary-desktop {
                background: white;
                border-radius: 10px;
                padding: 1.5rem;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                border: 1px solid rgba(139, 90, 43, 0.1);
            }
        }
        
        /* Mobile specific styles */
        @media (max-width: 767px) {
            .container {
                padding-left: 10px;
                padding-right: 10px;
            }
            
            .cart-item-desktop {
                display: none;
            }
            
            h2 {
                font-size: 1.5rem;
                text-align: center;
            }
            
            .btn-lg {
                padding: 0.75rem 1rem;
                font-size: 1rem;
            }
            
            .quantity-controls {
                justify-content: center;
            }
        }
        
        /* Desktop specific styles */
        @media (min-width: 768px) {
            .cart-item-mobile {
                display: none;
            }
        }
        
        /* Action buttons */
        .action-btns {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .btn-remove {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            flex: 1;
        }
        
        .price-highlight {
            color: #8B5A2B;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .item-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .item-brand {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .auto-update-form {
            display: none;
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
                    <a href="cart.php" class="btn btn-outline me-2 position-relative">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= count($cart_items) ?>
                        </span>
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
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4 mt-5">
        <h2 class="text-center mb-4">Shopping Cart</h2>
        
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart-mobile">
                <i class="fas fa-shopping-cart"></i>
                <h3>Your cart is empty</h3>
                <p class="text-muted mb-4">Add some perfumes to your cart</p>
                <a href="products.php" class="btn btn-primary btn-lg">Browse Perfumes</a>
            </div>
        <?php else: ?>
            <div class="row">
                <!-- Cart Items - Mobile View -->
                <div class="col-12 d-md-none">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item-mobile">
                            <div class="d-flex align-items-start">
                                <img src="<?= 
                                    (!empty($item['image_filename']) && file_exists('uploads/' . $item['image_filename'])) 
                                    ? 'uploads/' . e($item['image_filename']) 
                                    : (e($item['image']) ?: 'https://images.unsplash.com/photo-1547887537-6158d64c35b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80')
                                ?>" class="cart-item-image me-3" alt="<?= e($item['name']) ?>">
                                <div class="flex-grow-1">
                                    <div class="item-name"><?= e($item['name']) ?></div>
                                    <div class="item-brand"><?= e($item['brand']) ?></div>
                                    <div class="price-highlight item-price" data-price="<?= e($item['price']) ?>">Rs<?= e($item['price']) ?></div>
                                    
                                    <?php if ($item['quantity'] > $item['in_stock']): ?>
                                        <div class="stock-warning">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            Only <?= e($item['in_stock']) ?> left in stock
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="quantity-controls">
                                        <div class="quantity-btn" data-action="decrease" data-perfume-id="<?= e($item['perfume_id']) ?>">-</div>
                                        <input type="number" name="quantity" value="<?= e($item['quantity']) ?>" 
                                               min="1" max="<?= e($item['in_stock']) ?>" class="quantity-input" 
                                               data-perfume-id="<?= e($item['perfume_id']) ?>" data-price="<?= e($item['price']) ?>">
                                        <div class="quantity-btn" data-action="increase" data-perfume-id="<?= e($item['perfume_id']) ?>">+</div>
                                    </div>
                                    
                                    <div class="action-btns">
                                        <form method="post" class="auto-update-form" id="update-form-<?= e($item['perfume_id']) ?>">
                                            <input type="hidden" name="perfume_id" value="<?= e($item['perfume_id']) ?>">
                                            <input type="hidden" name="quantity" id="quantity-<?= e($item['perfume_id']) ?>" value="<?= e($item['quantity']) ?>">
                                            <input type="hidden" name="update_quantity" value="1">
                                        </form>
                                        <form method="post" id="remove-form-<?= e($item['perfume_id']) ?>" class="d-inline w-100">
                                            <input type="hidden" name="perfume_id" value="<?= e($item['perfume_id']) ?>">
                                            <button type="submit" name="remove_item" class="btn-remove w-100">
                                                <i class="fas fa-trash me-1"></i>Remove
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Cart Items - Desktop View -->
                <div class="col-md-8 d-none d-md-block">
                    <div class="admin-card">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item-desktop">
                                <img src="<?= 
                                    (!empty($item['image_filename']) && file_exists('uploads/' . $item['image_filename'])) 
                                    ? 'uploads/' . e($item['image_filename']) 
                                    : (e($item['image']) ?: 'https://images.unsplash.com/photo-1547887537-6158d64c35b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80')
                                ?>" class="cart-item-image-desktop" alt="<?= e($item['name']) ?>">
                                
                                <div class="flex-grow-1">
                                    <h5 class="mb-1"><?= e($item['name']) ?></h5>
                                    <p class="text-muted mb-1"><?= e($item['brand']) ?></p>
                                    <p class="price-highlight mb-2 item-price" data-price="<?= e($item['price']) ?>">Rs<?= e($item['price']) ?></p>
                                    
                                    <?php if ($item['quantity'] > $item['in_stock']): ?>
                                        <div class="stock-warning mb-2">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            Only <?= e($item['in_stock']) ?> left in stock
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="text-end">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div class="quantity-btn" data-action="decrease" data-perfume-id="<?= e($item['perfume_id']) ?>">-</div>
                                        <input type="number" name="quantity" value="<?= e($item['quantity']) ?>" 
                                               min="1" max="<?= e($item['in_stock']) ?>" class="form-control quantity-input-desktop" 
                                               style="width: 80px;" data-perfume-id="<?= e($item['perfume_id']) ?>" data-price="<?= e($item['price']) ?>">
                                        <div class="quantity-btn" data-action="increase" data-perfume-id="<?= e($item['perfume_id']) ?>">+</div>
                                    </div>
                                    
                                    <form method="post" class="auto-update-form" id="update-form-desktop-<?= e($item['perfume_id']) ?>">
                                        <input type="hidden" name="perfume_id" value="<?= e($item['perfume_id']) ?>">
                                        <input type="hidden" name="quantity" id="quantity-desktop-<?= e($item['perfume_id']) ?>" value="<?= e($item['quantity']) ?>">
                                        <input type="hidden" name="update_quantity" value="1">
                                    </form>
                                    
                                    <form method="post">
                                        <input type="hidden" name="perfume_id" value="<?= e($item['perfume_id']) ?>">
                                        <button type="submit" name="remove_item" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash me-1"></i>Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Cart Summary -->
                <div class="col-md-4">
                    <div class="cart-summary-desktop d-none d-md-block">
                        <h4 class="mb-4">Order Summary</h4>
                        
                        <?php 
                        $subtotal = 0;
                        foreach ($cart_items as $item) {
                            $subtotal += $item['price'] * $item['quantity'];
                        }
                        $shipping = 10.00;
                        $tax = $subtotal * 0.1;
                        $total = $subtotal + $shipping + $tax;
                        ?>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>Rs<span id="subtotal"><?= number_format($subtotal, 2) ?></span></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping:</span>
                            <span>Rs<span id="shipping">10.00</span></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Tax:</span>
                            <span>Rs<span id="tax"><?= number_format($tax, 2) ?></span></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <strong>Total:</strong>
                            <strong>Rs<span id="total"><?= number_format($total, 2) ?></span></strong>
                        </div>
                        
                        <form method="post">
                            <button type="submit" name="checkout" class="btn btn-primary w-100 btn-lg mb-3">
                                Proceed to Checkout
                            </button>
                        </form>
                        
                        <a href="products.php" class="btn btn-outline w-100">
                            <i class="fas fa-arrow-left me-1"></i>Continue Shopping
                        </a>
                    </div>
                    
                    <!-- Mobile Cart Summary -->
                    <div class="cart-summary-mobile d-md-none">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>Rs<span id="subtotal-mobile"><?= number_format($subtotal, 2) ?></span></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping:</span>
                            <span>Rs<span id="shipping-mobile">10.00</span></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Tax:</span>
                            <span>Rs<span id="tax-mobile"><?= number_format($tax, 2) ?></span></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <strong>Total:</strong>
                            <strong>Rs<span id="total-mobile"><?= number_format($total, 2) ?></span></strong>
                        </div>
                        
                        <form method="post">
                            <button type="submit" name="checkout" class="btn btn-primary w-100 btn-lg mb-3">
                                Proceed to Checkout
                            </button>
                        </form>
                        
                        <a href="products.php" class="btn btn-outline w-100">
                            <i class="fas fa-arrow-left me-1"></i>Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer class="bg-dark text-white py-5 mt-5">
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
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize cart totals
            updateCartTotals();
            
            // Mobile and desktop quantity buttons functionality
            document.querySelectorAll('.quantity-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const action = this.getAttribute('data-action');
                    const perfumeId = this.getAttribute('data-perfume-id');
                    let input;
                    
                    // Find the corresponding input field
                    if (window.innerWidth < 768) {
                        // Mobile view
                        input = document.querySelector(`.quantity-input[data-perfume-id="${perfumeId}"]`);
                    } else {
                        // Desktop view
                        input = document.querySelector(`.quantity-input-desktop[data-perfume-id="${perfumeId}"]`);
                    }
                    
                    if (action === 'increase') {
                        input.stepUp();
                    } else {
                        input.stepDown();
                    }
                    
                    // Update the hidden form value
                    const hiddenInput = document.getElementById(`quantity-${perfumeId}`) || 
                                       document.getElementById(`quantity-desktop-${perfumeId}`);
                    if (hiddenInput) {
                        hiddenInput.value = input.value;
                    }
                    
                    // Update cart totals
                    updateCartTotals();
                    
                    // Auto-submit the update form after a short delay
                    setTimeout(() => {
                        const updateForm = document.getElementById(`update-form-${perfumeId}`) || 
                                         document.getElementById(`update-form-desktop-${perfumeId}`);
                        if (updateForm) {
                            updateForm.submit();
                        }
                    }, 500);
                });
            });
            
            // Update cart totals when quantity inputs change directly
            document.querySelectorAll('.quantity-input, .quantity-input-desktop').forEach(input => {
                input.addEventListener('change', function() {
                    const perfumeId = this.getAttribute('data-perfume-id');
                    
                    // Update the hidden form value
                    const hiddenInput = document.getElementById(`quantity-${perfumeId}`) || 
                                       document.getElementById(`quantity-desktop-${perfumeId}`);
                    if (hiddenInput) {
                        hiddenInput.value = this.value;
                    }
                    
                    // Update cart totals
                    updateCartTotals();
                    
                    // Auto-submit the update form after a short delay
                    setTimeout(() => {
                        const updateForm = document.getElementById(`update-form-${perfumeId}`) || 
                                         document.getElementById(`update-form-desktop-${perfumeId}`);
                        if (updateForm) {
                            updateForm.submit();
                        }
                    }, 500);
                });
            });
            
            function updateCartTotals() {
                let subtotal = 0;
                
                // Calculate subtotal from all items
                document.querySelectorAll('.quantity-input, .quantity-input-desktop').forEach(input => {
                    const quantity = parseInt(input.value) || 0;
                    const price = parseFloat(input.getAttribute('data-price')) || 0;
                    subtotal += quantity * price;
                });
                
                // Calculate tax and total
                const shipping = 10.00;
                const tax = subtotal * 0.1;
                const total = subtotal + shipping + tax;
                
                // Update display for both desktop and mobile
                document.getElementById('subtotal').textContent = subtotal.toFixed(2);
                document.getElementById('tax').textContent = tax.toFixed(2);
                document.getElementById('total').textContent = total.toFixed(2);
                
                // Update mobile display if exists
                const subtotalMobile = document.getElementById('subtotal-mobile');
                if (subtotalMobile) {
                    subtotalMobile.textContent = subtotal.toFixed(2);
                }
                
                const taxMobile = document.getElementById('tax-mobile');
                if (taxMobile) {
                    taxMobile.textContent = tax.toFixed(2);
                }
                
                const totalMobile = document.getElementById('total-mobile');
                if (totalMobile) {
                    totalMobile.textContent = total.toFixed(2);
                }
            }
        });
    </script>
</body>
</html>