<?php
require 'db.php';
require 'helpers.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $err = 'Invalid CSRF token';
    } else {
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $pass = $_POST['password'] ?? '';
        
        if ($email && $pass) {
            $stmt = $pdo->prepare("SELECT id, name, password, is_admin FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($pass, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['is_admin'] = (bool)$user['is_admin'];
                $_SESSION['LAST_ACTIVITY'] = time();
                
                header('Location: index.php');
                exit;
            } else {
                $err = 'Invalid email or password';
            }
        } else {
            $err = 'Please provide valid email and password';
        }
    }
}

if (isset($_SESSION['session_expired'])) {
    $err = 'Your session has expired. Please login again.';
    unset($_SESSION['session_expired']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RBScents</title>
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
            max-width: 450px;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="brand-logo">
            <h1><i class="fas fa-gem me-2"></i>RBScents</h1>
        </div>
        
        <div class="auth-container">
            <h3 class="text-center mb-4">
                <i class="fas fa-sign-in-alt me-2"></i>Welcome Back
            </h3>
            
            <?php if ($err): ?>
                <div class="alert alert-danger"><?= e($err) ?></div>
            <?php endif; ?>
            
            <form method="post">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                
                <div class="mb-4">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>" placeholder="Enter your email">
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="Enter your password">
                    <div class="mt-2 text-end">
                        <small class="text-muted">
                            <a href="forgot-password.php" class="text-decoration-none">Forgot password?</a>
                        </small>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 mb-4 py-2">Login</button>
                
                <div class="text-center">
                    <span class="text-muted">Don't have an account?</span>
                    <a href="register.php" class="text-decoration-none fw-bold">Sign up now</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>