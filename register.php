<?php
require 'db.php';
require 'helpers.php';

// Include PHPMailer files
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$err = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $err = 'Invalid CSRF token';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (!$name || !$email || !$password) {
            $err = 'Please fill in all required fields';
        } elseif ($password !== $confirm_password) {
            $err = 'Passwords do not match';
        } elseif (strlen($password) < 6) {
            $err = 'Password must be at least 6 characters long';
        } else {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $err = 'Email already registered';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                
                if ($stmt->execute([$name, $email, $hashed_password])) {
                    // Send welcome email
                    $mail = new PHPMailer(true);
                    try {
                        // Server settings
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = ''; // Your Gmail
                        $mail->Password   = '';         // Your Gmail App Password
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;
                        $mail->SMTPDebug  = 0; // Set to 2 for detailed debugging
                        
                        // SSL/TLS configuration for better compatibility
                        $mail->SMTPOptions = array(
                            'ssl' => array(
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                                'allow_self_signed' => true
                            )
                        );
                        
                        // Timeout settings
                        $mail->Timeout = 30;
                        
                        // Character set
                        $mail->CharSet = 'UTF-8';

                        // Recipients
                        $mail->setFrom('', 'RBScents');
                        $mail->addAddress($email, $name);
                        $mail->addReplyTo('', 'RBScents');

                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Welcome to RBScents!';
                        $mail->Body    = "
                            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                                <h2 style='color: #C9A96E; text-align: center;'>Welcome to RBScents!</h2>
                                <p>Hi <strong>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</strong>,</p>
                                <p>Thank you for registering at <strong>RBScents</strong>.</p>
                                <p>We're excited to have you on board! 🎉</p>
                                <p>You can now login to your account and explore our exclusive collection of scents.</p>
                                <br>
                                <p>Best regards,<br><strong>RBScents Team</strong></p>
                                <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                                <p style='font-size: 12px; color: #666; text-align: center;'>
                                    If you did not create this account, please ignore this email.
                                </p>
                            </div>
                        ";
                        
                        // Plain text version for non-HTML email clients
                        $mail->AltBody = "Welcome to RBScents!\n\n" .
                                        "Hi $name,\n\n" .
                                        "Thank you for registering at RBScents.\n\n" .
                                        "We're excited to have you on board! 🎉\n\n" .
                                        "You can now login to your account and explore our exclusive collection of scents.\n\n" .
                                        "Best regards,\nRBScents Team\n\n" .
                                        "If you did not create this account, please ignore this email.";

                        if ($mail->send()) {
                            $success = "Registration successful! A welcome email has been sent to your email address.";
                            $_POST = []; // Clear form
                        } else {
                            throw new Exception('Mailer failed to send');
                        }
                    } catch (Exception $e) {
                        // Log the error for debugging
                        error_log("PHPMailer Error: " . $e->getMessage());
                        error_log("PHPMailer Debug: " . $mail->ErrorInfo);
                        
                        // Try fallback to basic mail() function
                        $fallback_sent = false;
                        $subject = 'Welcome to RBScents!';
                        $message = "Hi $name,\n\nThank you for registering at RBScents.\nWe're excited to have you on board! 🎉\n\nYou can now login to your account and explore our exclusive collection of scents.\n\nBest regards,\nRBScents Team";
                        $headers = "From: RBScents <noreply@rbscents.com>\r\n";
                        $headers .= "Reply-To: aaallliii77992186@gmail.com\r\n";
                        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                        
                        if (mail($email, $subject, $message, $headers)) {
                            $success = "Registration successful! Welcome email sent (fallback method).";
                            $fallback_sent = true;
                        } else {
                            $success = "Registration successful, but welcome email could not be sent. You can still login to your account.";
                        }
                        
                        $_POST = []; // Clear form
                    }
                } else {
                    $err = 'Registration failed. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - RBScents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .auth-container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            padding: 2.5rem;
            max-width: 500px;
            width: 100%;
            color: white;
        }
        
        .auth-container h3 {
            color: white;
            font-weight: 300;
            letter-spacing: 1px;
            margin-bottom: 1.5rem;
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            color: white;
            padding: 0.75rem 1rem;
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.15);
            color: white;
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .form-label {
            color: white;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .btn-primary {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            color: white;
            font-weight: 500;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .text-muted {
            color: rgba(255, 255, 255, 0.7) !important;
        }
        
        .text-decoration-none {
            color: rgba(255, 255, 255, 0.9);
        }
        
        .text-decoration-none:hover {
            color: white;
            text-decoration: underline !important;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            color: white;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            color: white;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }
        
        body {
            background: linear-gradient(rgba(43, 24, 16, 0.85), rgba(67, 37, 25, 0.9)), 
                        url('https://images.unsplash.com/photo-1547887537-6158d64c35b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .brand-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .brand-logo h1 {
            color: white;
            font-weight: 300;
            font-size: 2.5rem;
            letter-spacing: 2px;
        }
        
        .brand-logo i {
            color: #C9A96E;
        }
        
        .password-requirements {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .alert-warning {
            background: rgba(255, 193, 7, 0.2);
            color: white;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="brand-logo">
            <h1><i class="fas fa-gem me-2"></i>RBScents</h1>
        </div>
        
        <div class="auth-container">
            <h3 class="text-center mb-4">
                <i class="fas fa-user-plus me-2"></i>Create Account
            </h3>
            
            <?php if ($err): ?>
                <div class="alert alert-danger"><?= e($err) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>
            
            <form method="post">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" required value="<?= e($_POST['name'] ?? '') ?>" placeholder="Enter your full name">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>" placeholder="Enter your email">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="Create a password">
                    <div class="password-requirements mt-1">
                        Must be at least 6 characters long
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required placeholder="Confirm your password">
                </div>
                
                <button type="submit" class="btn btn-primary w-100 mb-4 py-2">Create Account</button>
                
                <div class="text-center">
                    <span class="text-muted">Already have an account?</span>
                    <a href="login.php" class="text-decoration-none fw-bold">Login here</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
