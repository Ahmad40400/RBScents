<?php
require 'db.php';
require 'helpers.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['id'] ?? 0;

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, u.name as user_name, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? AND (o.user_id = ? OR ?)
");
$is_admin = is_admin();
$stmt->execute([$order_id, $user_id, $is_admin]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Get order items - FIXED: Changed product_id to perfume_id
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.brand, p.image, p.image_filename 
    FROM order_items oi 
    JOIN perfumes p ON oi.perfume_id = p.id  -- Changed from product_id to perfume_id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

// Handle status update for admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status']) && is_admin()) {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $error = 'Invalid CSRF token';
    } else {
        $new_status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $order_id])) {
            $success = 'Order status updated successfully!';
            // Refresh order data
            $stmt = $pdo->prepare("SELECT o.*, u.name as user_name, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();
        } else {
            $error = 'Failed to update order status. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= e($order_id) ?> - RBScents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .order-header {
            border-bottom: 2px solid #8B5A2B;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        
        .order-item {
            border-bottom: 1px solid #eee;
            padding: 1rem 0;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .status-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
        
        .total-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        /* Mobile responsive table */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .order-table {
            min-width: 600px;
        }
        
        .table th {
            white-space: nowrap;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        /* Mobile optimized product column */
        .product-cell {
            min-width: 200px;
        }
        
        /* Mobile specific styles */
        @media (max-width: 768px) {
            .item-image {
                width: 60px;
                height: 60px;
            }
            
            .order-header h2 {
                font-size: 1.5rem;
            }
            
            .admin-actions .row {
                flex-direction: column;
            }
            
            .admin-actions .col-md-4 {
                margin-bottom: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            .container {
                padding-left: 10px;
                padding-right: 10px;
            }
            
            .admin-card {
                padding: 1rem;
            }
            
            .order-header {
                flex-direction: column;
                text-align: center;
            }
            
            .status-badge {
                margin-top: 0.5rem;
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
                <?php if (is_admin()): ?>
                    <a href="admin-orders.php" class="btn btn-outline btn-sm me-2">
                        <i class="fas fa-list me-1"></i>All Orders
                    </a>
                <?php endif; ?>
                <a href="orders.php" class="btn btn-outline btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Back to Orders
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-5 mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="admin-card">
                    <!-- Order Header -->
                    <div class="order-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <div>
                                <h2 class="mb-1">Order #<?= e($order['id']) ?></h2>
                                <p class="text-muted mb-0">
                                    Placed on <?= e(date('F j, Y, g:i A', strtotime($order['created_at']))) ?>
                                </p>
                            </div>
                            <span class="badge status-badge bg-<?= 
                                $order['status'] === 'completed' ? 'success' : 
                                ($order['status'] === 'pending' ? 'warning' : 
                                ($order['status'] === 'confirmed' ? 'info' : 
                                ($order['status'] === 'shipped' ? 'primary' : 
                                ($order['status'] === 'delivered' ? 'secondary' : 
                                ($order['status'] === 'cancelled' ? 'danger' : 'dark'))))) 
                            ?> fs-6">
                                <?= e(ucfirst($order['status'])) ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?= e($success) ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= e($error) ?></div>
                    <?php endif; ?>
                    
                    <div class="row mb-5">
                        <!-- Customer Information -->
                        <div class="col-md-6 mb-4">
                            <h5 class="mb-3">
                                <i class="fas fa-user me-2 text-primary"></i>Customer Information
                            </h5>
                            <div class="ps-3">
                                <p class="mb-1"><strong>Name:</strong> <?= e($order['user_name']) ?></p>
                                <p class="mb-1"><strong>Email:</strong> <?= e($order['email']) ?></p>
                                <?php if (!empty($order['customer_phone'])): ?>
                                    <p class="mb-0"><strong>Phone:</strong> <?= e($order['customer_phone']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Shipping Information -->
                        <div class="col-md-6 mb-4">
                            <h5 class="mb-3">
                                <i class="fas fa-truck me-2 text-primary"></i>Shipping Information
                            </h5>
                            <div class="ps-3">
                                <p class="mb-0"><?= nl2br(e($order['shipping_address'])) ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Items -->
                    <h5 class="mb-4">
                        <i class="fas fa-box me-2 text-primary"></i>Order Items
                    </h5>
                    
                    <?php if (empty($order_items)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No items found in this order.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table order-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $subtotal = 0;
                                    foreach ($order_items as $item): 
                                        $item_total = $item['price'] * $item['quantity'];
                                        $subtotal += $item_total;
                                    ?>
                                        <tr>
                                            <td class="product-cell">
                                                <div class="d-flex align-items-center">
                                                    <img src="<?= 
                                                        (!empty($item['image_filename']) && file_exists('uploads/' . $item['image_filename'])) 
                                                        ? 'uploads/' . e($item['image_filename']) 
                                                        : (e($item['image']) ?: 'https://images.unsplash.com/photo-1547887537-6158d64c35b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80')
                                                    ?>" class="item-image me-3" alt="<?= e($item['name']) ?>">
                                                    <div>
                                                        <div class="fw-bold"><?= e($item['name']) ?></div>
                                                        <small class="text-muted"><?= e($item['brand']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>Rs<?= e($item['price']) ?></td>
                                            <td><?= e($item['quantity']) ?></td>
                                            <td>Rs<?= number_format($item_total, 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Order Totals -->
                        <div class="total-section">
                            <div class="row">
                                <div class="col-md-6 offset-md-6">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Subtotal:</span>
                                        <span>Rs<?= number_format($subtotal, 2) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Shipping:</span>
                                        <span>Rs10.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-3">
                                        <span>Tax (10%):</span>
                                        <span>Rs<?= number_format($subtotal * 0.1, 2) ?></span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between mb-0">
                                        <strong>Total Amount:</strong>
                                        <strong>Rs<?= e($order['total_amount']) ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Admin Actions -->
                    <?php if (is_admin()): ?>
                        <div class="mt-5 p-4 bg-light rounded">
                            <h5 class="mb-3">
                                <i class="fas fa-cog me-2 text-primary"></i>Admin Actions
                            </h5>
                            <form method="post" class="row g-3 align-items-center">
                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Update Order Status:</label>
                                    <select name="status" class="form-control" required>
                                        <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="confirmed" <?= $order['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                        <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                        <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                        <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <button type="submit" name="update_status" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Status
                                    </button>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="text-muted small">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Current status: <strong><?= e(ucfirst($order['status'])) ?></strong>
                                    </div>
                                </div>
                            </form>
                            
                            <!-- Order Metadata -->
                            <div class="mt-4 pt-3 border-top">
                                <h6 class="mb-2">Order Metadata:</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <strong>Order ID:</strong> #<?= e($order['id']) ?>
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <strong>Payment Method:</strong> 
                                            <?= e(ucwords(str_replace('_', ' ', $order['payment_method'] ?? 'cash_on_delivery'))) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>