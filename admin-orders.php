<?php
require 'db.php';
require 'helpers.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: index.php');
    exit;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $_SESSION['error'] = 'Invalid CSRF token';
    } else {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['status'];
        
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $order_id])) {
            $_SESSION['success'] = 'Order status updated successfully!';
        } else {
            $_SESSION['error'] = 'Failed to update order status. Please try again.';
        }
        
        header('Location: admin-orders.php');
        exit;
    }
}

// Filter orders by status if specified
$status_filter = $_GET['status'] ?? '';
if ($status_filter && in_array($status_filter, ['pending', 'confirmed', 'shipped', 'delivered', 'completed', 'cancelled'])) {
    $stmt = $pdo->prepare("
        SELECT o.*, u.name as user_name, u.email,
               COUNT(oi.id) as item_count,
               SUM(oi.quantity) as total_quantity
        FROM orders o 
        JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        WHERE o.status = ?
        GROUP BY o.id 
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$status_filter]);
    $orders = $stmt->fetchAll();
} else {
    // Get all orders with user information
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
    $orders = $stmt->fetchAll();
}

// Get statistics for the dashboard
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$completed_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - RBScents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .status-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }
        
        .order-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .stats-card i {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .filter-buttons {
            margin-bottom: 1.5rem;
        }
        
        .mobile-table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .mobile-table {
            min-width: 800px;
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
            
            .order-actions {
                flex-direction: column;
                gap: 0.25rem;
            }
            
            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.8rem;
            }
            
            .stats-card {
                padding: 1rem;
            }
            
            .stats-number {
                font-size: 1.5rem;
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
                <a href="admin.php" class="btn btn-outline btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Admin Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-5 mt-5">
        <h2 class="text-center mb-4">Manage Orders</h2>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= e($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= e($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Order Statistics -->
        <div class="row mb-5">
            <div class="col-md-3">
                <div class="stats-card">
                    <i class="fas fa-shopping-bag text-primary"></i>
                    <div class="stats-number"><?= e($total_orders) ?></div>
                    <div class="text-muted">Total Orders</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <i class="fas fa-clock text-warning"></i>
                    <div class="stats-number"><?= e($pending_orders) ?></div>
                    <div class="text-muted">Pending</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <i class="fas fa-check-circle text-success"></i>
                    <div class="stats-number"><?= e($completed_orders) ?></div>
                    <div class="text-muted">Completed</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <i class="fas fa-chart-line text-info"></i>
                    <div class="stats-number">Rs<?= number_format(array_sum(array_column($orders, 'total_amount')), 2) ?></div>
                    <div class="text-muted">Total Revenue</div>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="admin-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>All Orders</h4>
                <div class="filter-buttons">
                    <a href="admin-orders.php" class="btn btn-sm btn-outline <?= !isset($_GET['status']) ? 'active' : '' ?>">All</a>
                    <a href="admin-orders.php?status=pending" class="btn btn-sm btn-outline <?= ($_GET['status'] ?? '') === 'pending' ? 'active' : '' ?>">Pending</a>
                    <a href="admin-orders.php?status=completed" class="btn btn-sm btn-outline <?= ($_GET['status'] ?? '') === 'completed' ? 'active' : '' ?>">Completed</a>
                    <a href="admin-orders.php?status=cancelled" class="btn btn-sm btn-outline <?= ($_GET['status'] ?? '') === 'cancelled' ? 'active' : '' ?>">Cancelled</a>
                </div>
            </div>

            <?php if (empty($orders)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                    <h3>No orders found</h3>
                    <p class="text-muted">There are no orders in the system yet.</p>
                </div>
            <?php else: ?>
                <div class="mobile-table-container">
                    <table class="table table-striped mobile-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
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
                                    <td>
                                        <div>
                                            <strong><?= e($order['user_name']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= e($order['email']) ?></small>
                                        </div>
                                    </td>
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
                                        <strong>Rs<?= e($order['total_amount']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge status-badge bg-<?= 
                                            $order['status'] === 'completed' ? 'success' : 
                                            ($order['status'] === 'pending' ? 'warning' : 
                                            ($order['status'] === 'shipped' ? 'info' : 
                                            ($order['status'] === 'cancelled' ? 'danger' : 
                                            ($order['status'] === 'confirmed' ? 'primary' : 
                                            ($order['status'] === 'delivered' ? 'success' : 'secondary'))))) 
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
                                            </a>
                                            
                                            <!-- Status Update Form -->
                                            <form method="post" class="d-inline" id="statusForm<?= e($order['id']) ?>">
                                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="order_id" value="<?= e($order['id']) ?>">
                                                <input type="hidden" name="update_status" value="1">
                                                <input type="hidden" name="status" id="statusInput<?= e($order['id']) ?>" value="">
                                                
                                                <div class="dropdown d-inline">
                                                    <button class="btn btn-sm btn-outline dropdown-toggle" 
                                                            type="button" 
                                                            data-bs-toggle="dropdown"
                                                            title="Change Status">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item status-option <?= $order['status'] === 'pending' ? 'active' : '' ?>" 
                                                               href="#" 
                                                               data-status="pending"
                                                               data-order-id="<?= e($order['id']) ?>">
                                                                <i class="fas fa-clock me-2"></i>Pending
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item status-option <?= $order['status'] === 'confirmed' ? 'active' : '' ?>" 
                                                               href="#" 
                                                               data-status="confirmed"
                                                               data-order-id="<?= e($order['id']) ?>">
                                                                <i class="fas fa-check me-2"></i>Confirmed
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item status-option <?= $order['status'] === 'shipped' ? 'active' : '' ?>" 
                                                               href="#" 
                                                               data-status="shipped"
                                                               data-order-id="<?= e($order['id']) ?>">
                                                                <i class="fas fa-shipping-fast me-2"></i>Shipped
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item status-option <?= $order['status'] === 'delivered' ? 'active' : '' ?>" 
                                                               href="#" 
                                                               data-status="delivered"
                                                               data-order-id="<?= e($order['id']) ?>">
                                                                <i class="fas fa-box-open me-2"></i>Delivered
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item status-option <?= $order['status'] === 'completed' ? 'active' : '' ?>" 
                                                               href="#" 
                                                               data-status="completed"
                                                               data-order-id="<?= e($order['id']) ?>">
                                                                <i class="fas fa-flag me-2"></i>Completed
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item status-option text-danger <?= $order['status'] === 'cancelled' ? 'active' : '' ?>" 
                                                               href="#" 
                                                               data-status="cancelled"
                                                               data-order-id="<?= e($order['id']) ?>">
                                                                <i class="fas fa-times me-2"></i>Cancelled
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Orders Summary -->
                <div class="mt-4 p-3 bg-light rounded">
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Total Orders:</strong> <?= e($total_orders) ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Total Revenue:</strong> Rs<?= number_format(array_sum(array_column($orders, 'total_amount')), 2) ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Average Order:</strong> Rs<?= number_format($total_orders > 0 ? array_sum(array_column($orders, 'total_amount')) / $total_orders : 0, 2) ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle status change
        document.addEventListener('DOMContentLoaded', function() {
            const statusOptions = document.querySelectorAll('.status-option');
            
            statusOptions.forEach(option => {
                option.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const orderId = this.getAttribute('data-order-id');
                    const newStatus = this.getAttribute('data-status');
                    
                    // Set the status value in the form
                    const statusInput = document.getElementById('statusInput' + orderId);
                    if (statusInput) {
                        statusInput.value = newStatus;
                    }
                    
                    // Submit the form
                    const form = document.getElementById('statusForm' + orderId);
                    if (form) {
                        form.submit();
                    }
                });
            });
        });
    </script>
</body>
</html>