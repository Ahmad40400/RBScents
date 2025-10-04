<?php
require 'db.php';
require 'helpers.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user_name = $_SESSION['user_name'] ?? 'User';

// Handle logout confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_logout'])) {
        // Destroy all session data
        session_unset();
        session_destroy();
        
        // Start a new session for the logout message
        session_start();
        $_SESSION['success'] = "You have been successfully logged out. We hope to see you again soon!";
        
        // Redirect to login page
        header('Location: login.php');
        exit;
    } else {
        // User canceled logout
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - RBScents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .logout-container {
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
            text-align: center;
        }
        
        .logout-container h2 {
            color: white;
            font-weight: 300;
            letter-spacing: 1px;
            margin-bottom: 1.5rem;
        }
        
        .btn {
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-danger {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: white;
        }
        
        .btn-danger:hover {
            background: rgba(220, 53, 69, 0.3);
            transform: translateY(-2px);
        }
        
        .btn-outline {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
        }
        
        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
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
        
        .user-avatar {
            width: 80px;
            height: 80px;
            background: rgba(201, 169, 110, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .user-avatar span {
            font-size: 2rem;
            color: white;
            font-weight: 600;
        }
        
        .logout-message {
            margin-bottom: 2rem;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="brand-logo">
            <h1><i class="fas fa-gem me-2"></i>RBScents</h1>
        </div>
        
        <div class="logout-container">
            <div class="user-avatar">
                <span><?= strtoupper(substr($user_name, 0, 1)) ?></span>
            </div>
            
            <h2>Logout Confirmation</h2>
            
            <div class="logout-message">
                <p>Hello, <strong><?= e($user_name) ?></strong>!</p>
                <p>Are you sure you want to logout from your account?</p>
            </div>
            
            <form method="post" class="d-flex gap-3 justify-content-center">
                <button type="submit" name="confirm_logout" value="1" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt me-2"></i>Yes, Logout
                </button>
                <button type="submit" name="cancel_logout" value="1" class="btn btn-outline">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>