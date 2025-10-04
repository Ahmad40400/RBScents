<?php
require 'db.php';
require 'helpers.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$order_id = $_GET['id'] ?? 0;

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, u.name as user_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - RBScents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-gem me-2"></i>RBScents
            </a>
        </div>
    </nav>

    <div class="container py-5 mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <div class="admin-card">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success fa-5x mb-4"></i>
                        <h1 class="text-success">Order Confirmed!</h1>
                        <p class="lead">Thank you for your purchase, <?= e($order['user_name']) ?>!</p>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6 mx-auto">
                            <div class="p-4 bg-light rounded">
                                <h5>Order Details</h5>
                                <p><strong>Order Number:</strong> #<?= e($order['id']) ?></p>
                                <p><strong>Total Amount:</strong> Rs<?= e($order['total_amount']) ?></p>
                                <p><strong>Order Date:</strong> <?= e(date('F j, Y', strtotime($order['created_at']))) ?></p>
                                <p><strong>Status:</strong> <span class="badge bg-warning"><?= e(ucfirst($order['status'])) ?></span></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <p>We've sent a confirmation email to your registered email address.</p>
                        <p class="text-muted">You can track your order status in your account page.</p>
                    </div>
                    
                    <div class="d-flex gap-3 justify-content-center">
                        <a href="orders.php" class="btn btn-primary">
                            <i class="fas fa-box me-2"></i>View My Orders
                        </a>
                        <a href="products.php" class="btn btn-outline">
                            <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>