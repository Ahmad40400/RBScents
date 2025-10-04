<?php
require 'db.php';
require 'helpers.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$is_admin = is_admin();

// Get user orders
if ($is_admin) {
    // Admin can see all orders
    $stmt = $pdo->prepare("
        SELECT o.*, u.name as user_name, u.email,
               COUNT(oi.id) as item_count,
               SUM(oi.quantity) as total_quantity
        FROM orders o 
        JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        GROUP BY o.id 
        ORDER BY o.created_at DESC
    ");
    $stmt->execute();
} else {
    // Regular users see only their orders
    $stmt = $pdo->prepare("
        SELECT o.*, 
               COUNT(oi.id) as item_count,
               SUM(oi.quantity) as total_quantity
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        WHERE o.user_id = ?
        GROUP BY o.id 
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$user_id]);
}

$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - RBScents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .orders-table {
            min-width: 600px;
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }
        
        .order-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        /* Mobile specific styles */
        @media (max-width: 768px) {
            .container {
                padding-left: 10px;
                padding-right: 10px;
            }
            
            .admin-card {
                padding: 1rem;
            }
            
            .table th, .table td {
                padding: 0.75rem 0.5rem;
                font-size: 0.9rem;
            }
            
            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.8rem;
            }
            
            .order-actions {
                flex-direction: column;
                gap: 0.25rem;
            }
        }
        
        @media (max-width: 576px) {
            .table th, .table td {
                padding: 0.5rem 0.25rem;
                font-size: 0.85rem;
            }
            
            .status-badge {
                font-size: 0.75rem;
                padding: 0.3rem 0.6rem;
            }
            
            h2 {
                font-size: 1.5rem;
            }
        }
        
        /* Hover effects */
        .table tbody tr:hover {
            background-color: rgba(139, 90, 43, 0.05);
        }
        
        /* Status colors */
        .badge.bg-success { background-color: #28a745 !important; }
        .badge.bg-warning { background-color: #ffc107 !important; color: #000 !important; }
        .badge.bg-info { background-color: #17a2b8 !important; }
        .badge.bg-primary { background-color: #007bff !important; }
        .badge.bg-secondary { background-color: #6c757d !important; }
        .badge.bg-danger { background-color: #dc3545 !important; }
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
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><?= $is_admin ? 'All Orders' : 'My Orders' ?></h2>
                    <?php if ($is_admin): ?>
                        <a href="admin-orders.php" class="btn btn-outline btn-sm">
                            <i class="fas fa-cog me-1"></i>Manage Orders
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="admin-card">
                    <?php if (empty($orders)): ?>
                        <div class="empty-state">
                            <i class="fas fa-box-open text-muted"></i>
                            <h3>No Orders Found</h3>
                            <p class="text-muted mb-4">
                                <?= $is_admin ? 'There are no orders in the system yet.' : 'You haven\'t placed any orders yet.' ?>
                            </p>
                            <a href="products.php" class="btn btn-primary">
                                <i class="fas fa-shopping-bag me-2"></i>
                                Start Shopping
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped orders-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <?php if ($is_admin): ?>
                                            <th>Customer</th>
                                        <?php endif; ?>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>
                                                <strong>#<?= e($order['id']) ?></strong>
                                            </td>
                                            <?php if ($is_admin): ?>
                                                <td>
                                                    <div>
                                                        <strong><?= e($order['user_name']) ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?= e($order['email']) ?></small>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
                                            <td>
                                                <?= e(date('M j, Y', strtotime($order['created_at']))) ?>
                                                <br>
                                                <small class="text-muted"><?= e(date('g:i A', strtotime($order['created_at']))) ?></small>
                                            </td>
                                            <td>
                                                <?= e($order['item_count']) ?> items
                                                <br>
                                                <small class="text-muted"><?= e($order['total_quantity']) ?> total</small>
                                            </td>
                                            <td>
                                                <strong>Rs<?= number_format($order['total_amount'], 2) ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge status-badge bg-<?= 
                                                    $order['status'] === 'completed' ? 'success' : 
                                                    ($order['status'] === 'pending' ? 'warning' : 
                                                    ($order['status'] === 'confirmed' ? 'info' : 
                                                    ($order['status'] === 'shipped' ? 'primary' : 
                                                    ($order['status'] === 'delivered' ? 'secondary' : 
                                                    ($order['status'] === 'cancelled' ? 'danger' : 'dark'))))) 
                                                ?>">
                                                    <?= e(ucfirst($order['status'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    <?= e(ucwords(str_replace('_', ' ', $order['payment_method'] ?? 'cash_on_delivery'))) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="order-actions">
                                                    <a href="order-details.php?id=<?= e($order['id']) ?>" 
                                                       class="btn btn-sm btn-outline" 
                                                       title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                        <span class="d-none d-md-inline">View</span>
                                                    </a>
                                                    <?php if ($is_admin): ?>
                                                        <a href="admin-orders.php?edit=<?= e($order['id']) ?>" 
                                                           class="btn btn-sm btn-outline" 
                                                           title="Manage Order">
                                                            <i class="fas fa-edit"></i>
                                                            <span class="d-none d-md-inline">Manage</span>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Orders Summary -->
                        <div class="mt-4 p-3 bg-light rounded">
                            <div class="row text-center">
                                <div class="col-md-3 mb-2 mb-md-0">
                                    <strong>Total Orders:</strong> <?= e(count($orders)) ?>
                                </div>
                                <div class="col-md-3 mb-2 mb-md-0">
                                    <strong>Total Amount:</strong> Rs<?= number_format(array_sum(array_column($orders, 'total_amount')), 2) ?>
                                </div>
                                <div class="col-md-3 mb-2 mb-md-0">
                                    <strong>Pending:</strong> <?= e(count(array_filter($orders, function($order) { return $order['status'] === 'pending'; }))) ?>
                                </div>
                                <div class="col-md-3">
                                    <strong>Completed:</strong> <?= e(count(array_filter($orders, function($order) { return $order['status'] === 'completed'; }))) ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
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
</body>
</html>