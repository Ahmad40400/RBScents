<?php
require 'db.php';
require 'helpers.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: index.php');
    exit;
}

// Get statistics
$users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$products_count = $pdo->query("SELECT COUNT(*) FROM perfumes")->fetchColumn();
$orders_count = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

// Get recent orders
$recent_orders = $pdo->query("SELECT o.*, u.name as user_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5")->fetchAll();

// Get low stock products
$low_stock = $pdo->query("SELECT * FROM perfumes WHERE in_stock < 10 ORDER BY in_stock ASC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - RBScents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .mobile-table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .mobile-table {
            min-width: 600px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding-left: 10px;
                padding-right: 10px;
            }
            
            .admin-card {
                padding: 1rem;
            }
            
            h2 {
                font-size: 1.5rem;
            }
            
            .table th, .table td {
                padding: 0.75rem 0.5rem;
                font-size: 0.9rem;
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-gem me-2"></i>RBScents
            </a>
            <div>
                <a href="index.php" class="btn btn-outline btn-sm">
                    <i class="fas fa-home me-1"></i>Back to Site
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-5 mt-5">
        <h2 class="text-center mb-5">Admin Dashboard</h2>
        
        <!-- Statistics Cards -->
        <div class="row mb-5">
            <div class="col-md-4 mb-4">
                <div class="admin-card">
                    <i class="fas fa-users fa-3x text-primary mb-3"></i>
                    <h3><?= e($users_count) ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="admin-card">
                    <i class="fas fa-wine-bottle fa-3x text-primary mb-3"></i>
                    <h3><?= e($products_count) ?></h3>
                    <p>Total Products</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="admin-card">
                    <i class="fas fa-shopping-bag fa-3x text-primary mb-3"></i>
                    <h3><?= e($orders_count) ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Quick Actions -->
            <div class="col-md-4 mb-4">
                <div class="admin-card">
                    <h4 class="mb-4">Quick Actions</h4>
                    <div class="d-grid gap-2">
                        <a href="add-product.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add New Perfume
                        </a>
                        <a href="products.php" class="btn btn-outline">
                            <i class="fas fa-edit me-2"></i>Manage Products
                        </a>
                        <a href="admin-orders.php" class="btn btn-outline">
                            <i class="fas fa-list me-2"></i>View All Orders
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="col-md-8">
                <div class="admin-card">
                    <h4 class="mb-4">Recent Orders</h4>
                    <?php if (empty($recent_orders)): ?>
                        <p class="text-muted">No orders yet.</p>
                    <?php else: ?>
                        <div class="mobile-table-container">
                            <table class="table mobile-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td>#<?= e($order['id']) ?></td>
                                            <td><?= e($order['user_name']) ?></td>
                                            <td>Rs<?= e($order['total_amount']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $order['status'] === 'completed' ? 'success' : 'warning' ?>">
                                                    <?= e($order['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= e(date('M j, Y', strtotime($order['created_at']))) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Low Stock Alert -->
                <?php if (!empty($low_stock)): ?>
                    <div class="admin-card mt-4">
                        <h4 class="mb-4 text-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>Low Stock Alert
                        </h4>
                        <div class="mobile-table-container">
                            <table class="table mobile-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Brand</th>
                                        <th>Stock</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($low_stock as $product): ?>
                                        <tr>
                                            <td><?= e($product['name']) ?></td>
                                            <td><?= e($product['brand']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $product['in_stock'] < 5 ? 'danger' : 'warning' ?>">
                                                    <?= e($product['in_stock']) ?> left
                                                </span>
                                            </td>
                                            <td>
                                                <a href="edit-product.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-outline">
                                                    Restock
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>