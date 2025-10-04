<?php
require 'db.php';
require 'helpers.php';

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';
require __DIR__ . '/PHPMailer/Exception.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get cart items
$stmt = $pdo->prepare("
    SELECT c.*, p.name, p.brand, p.price, p.image, p.in_stock 
    FROM cart c 
    JOIN perfumes p ON c.perfume_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

if (empty($cart_items)) {
    header('Location: cart.php');
    exit;
}

$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = 10.00;
$tax = $subtotal * 0.1;
$total = $subtotal + $shipping + $tax;

$err = '';
$success = '';

// Function to send order confirmation email
function sendOrderConfirmationEmail($user_email, $user_name, $order_id, $order_total, $order_items) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'aaallliii77992186@gmail.com';  // Your Gmail
        $mail->Password   = 'omke jjrt lrfy fehu';     // Your App password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Sender & recipient
        $mail->setFrom('aaallliii77992186@gmail.com', 'RBScents');
        $mail->addAddress($user_email, $user_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Order Confirmation - RBScents';
        
        // Build email content
        $items_html = '';
        foreach ($order_items as $item) {
            $items_html .= "<tr>
                <td>{$item['name']} ({$item['brand']})</td>
                <td>{$item['quantity']}</td>
                <td>Rs{$item['price']}</td>
                <td>Rs" . number_format($item['price'] * $item['quantity'], 2) . "</td>
            </tr>";
        }
        
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #8B5A2B; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background: #f9f9f9; }
                    .order-details { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
                    .footer { text-align: center; padding: 20px; color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>RBScents</h1>
                        <h2>Order Confirmation</h2>
                    </div>
                    <div class='content'>
                        <p>Dear {$user_name},</p>
                        <p>Thank you for your order! We're excited to let you know that we've received your order and it is being processed.</p>
                        
                        <div class='order-details'>
                            <h3>Order Details</h3>
                            <p><strong>Order ID:</strong> #{$order_id}</p>
                            <p><strong>Order Total:</strong> Rs{$order_total}</p>
                            <p><strong>Payment Method:</strong> Cash on Delivery</p>
                            
                            <h4>Order Items:</h4>
                            <table>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                                {$items_html}
                            </table>
                        </div>
                        
                        <p><strong>Delivery Information:</strong><br>
                        Your order will be delivered within 3-5 business days. You will pay the total amount in cash when you receive your order.</p>
                        
                        <p>You can track your order status by logging into your account on our website.</p>
                        
                        <p>If you have any questions, please don't hesitate to contact us.</p>
                    </div>
                    <div class='footer'>
                        <p>Thank you for choosing RBScents!</p>
                        <p>© 2024 RBScents. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        $mail->AltBody = "Order Confirmation\n\nDear {$user_name},\n\nThank you for your order #{$order_id}.\nTotal: Rs{$order_total}\n\nWe will process your order shortly.\n\nThank you for choosing RBScents!";

        return $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $err = 'Invalid CSRF token';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $zip = trim($_POST['zip'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $instructions = trim($_POST['instructions'] ?? '');

        if (!$name || !$email || !$address || !$city || !$zip || !$phone) {
            $err = 'Please fill in all required fields';
        } else {
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // Create shipping address string
                $shipping_address = "$address, $city, $zip";
                if (!empty($instructions)) {
                    $shipping_address .= " - Instructions: $instructions";
                }
                
                // Create order with all required fields
                $stmt = $pdo->prepare("
                    INSERT INTO orders (user_id, total_amount, shipping_address, payment_method, customer_phone, status) 
                    VALUES (?, ?, ?, 'cash_on_delivery', ?, 'pending')
                ");
                $stmt->execute([$user_id, $total, $shipping_address, $phone]);
                $order_id = $pdo->lastInsertId();
                
                // Add order items
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, perfume_id, quantity, price)
                    VALUES (?, ?, ?, ?)
                ");
                
                foreach ($cart_items as $item) {
                    $stmt->execute([$order_id, $item['perfume_id'], $item['quantity'], $item['price']]);
                    
                    // Update perfume stock
                    $update_stmt = $pdo->prepare("
                        UPDATE perfumes SET in_stock = in_stock - ? WHERE id = ?
                    ");
                    $update_stmt->execute([$item['quantity'], $item['perfume_id']]);
                }
                
                // Clear cart
                $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$user_id]);
                
                $pdo->commit();
                
                // Send order confirmation email
                $user_info = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
                $user_info->execute([$user_id]);
                $user_data = $user_info->fetch();
                
                if (sendOrderConfirmationEmail($user_data['email'], $user_data['name'], $order_id, number_format($total, 2), $cart_items)) {
                    $success = "Order placed successfully! Order ID: #$order_id. Confirmation email sent.";
                } else {
                    $success = "Order placed successfully! Order ID: #$order_id. (Email notification failed)";
                }
                
                // Redirect to order confirmation
                header("Location: order-confirmation.php?id=$order_id");
                exit;
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $err = 'Failed to place order. Please try again.';
                error_log("Order Error: " . $e->getMessage());
            }
        }
    }
}

// Get user info
$user_stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - RBScents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .payment-method {
            background: rgba(139, 90, 43, 0.05);
            border: 2px solid var(--primary);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .payment-method.selected {
            background: rgba(139, 90, 43, 0.1);
        }
        
        .payment-icon {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .payment-info {
            background: var(--light);
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .form-control:invalid {
            border-color: #dc3545;
        }
        
        .phone-input {
            direction: ltr;
            text-align: left;
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
                <a href="cart.php" class="btn btn-outline btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Back to Cart
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-5 mt-5">
        <h2 class="text-center mb-4">Checkout</h2>
        
        <?php if ($err): ?>
            <div class="alert alert-danger"><?= e($err) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="admin-card mb-4">
                    <h4 class="mb-4">Shipping Information</h4>
                    <form method="post" id="checkoutForm">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?= e($user['name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= e($user['email'] ?? '') ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Phone Number *</label>
                            <input type="tel" name="phone" id="phone" class="form-control phone-input" 
                                   placeholder="Enter your phone number" required>
                            <small class="text-muted">Please enter your phone number for delivery updates</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address *</label>
                            <input type="text" name="address" class="form-control" 
                                   placeholder="Street address" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">City *</label>
                                <input type="text" name="city" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ZIP Code *</label>
                                <input type="text" name="zip" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Delivery Instructions (Optional)</label>
                            <textarea name="instructions" class="form-control" rows="3" 
                                      placeholder="Any special delivery instructions..."></textarea>
                        </div>
                </div>
                
                <div class="admin-card">
                    <h4 class="mb-4">Payment Method</h4>
                    
                    <div class="payment-method selected">
                        <div class="text-center">
                            <div class="payment-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <h5>Cash on Delivery</h5>
                            <p class="text-muted">Pay when you receive your order</p>
                        </div>
                        
                        <div class="payment-info">
                            <h6><i class="fas fa-info-circle me-2 text-primary"></i>How it works:</h6>
                            <ul class="small mb-0">
                                <li>Place your order without any upfront payment</li>
                                <li>Our delivery executive will bring your order to your doorstep</li>
                                <li>Pay the total amount in cash when you receive your order</li>
                                <li>Get instant confirmation and receipt</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-shield-alt me-2"></i>
                        <strong>Secure & Convenient:</strong> Your order is confirmed immediately. No payment information required.
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="admin-card">
                    <h4 class="mb-4">Order Summary</h4>
                    
                    <?php foreach ($cart_items as $item): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="mb-0"><?= e($item['name']) ?></h6>
                                <small class="text-muted">Qty: <?= e($item['quantity']) ?></small>
                            </div>
                            <span>Rs<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                    
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span>Rs<?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping:</span>
                        <span>Rs<?= number_format($shipping, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Tax:</span>
                        <span>Rs<?= number_format($tax, 2) ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-4">
                        <strong>Total Amount:</strong>
                        <strong>Rs<?= number_format($total, 2) ?></strong>
                    </div>
                    
                    <div class="alert alert-warning mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <small>You will pay <strong>Rs<?= number_format($total, 2) ?></strong> in cash when your order is delivered.</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 btn-lg">
                        <i class="fas fa-check-circle me-2"></i>Confirm Order
                    </button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            <i class="fas fa-lock me-1"></i>Your order is secure
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const phoneInput = document.getElementById('phone');
            
            // Simple phone number input - no formatting restrictions
            phoneInput.addEventListener('input', function(e) {
                // Allow any phone number format
                // Remove any length restrictions
                if (e.target.value.length > 20) {
                    e.target.value = e.target.value.substring(0, 20);
                }
            });
            
            // Form validation - only check if phone field is not empty
            const form = document.getElementById('checkoutForm');
            form.addEventListener('submit', function(e) {
                const phone = document.getElementById('phone').value;
                
                if (!phone.trim()) {
                    e.preventDefault();
                    alert('Please enter your phone number');
                    document.getElementById('phone').focus();
                }
            });
        });
    </script>
</body>
</html>