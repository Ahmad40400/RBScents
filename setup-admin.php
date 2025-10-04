<?php
require 'db.php';

// Create admin user with simple password
$name = "Admin User";
$email = "admin@luxeperfumes.com";
$password = "admin123"; // Simple password
$is_admin = true;

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if admin already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        echo "Admin user already exists!<br>";
    } else {
        // Insert admin user
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hashed_password, $is_admin]);
        echo "✅ Admin user created successfully!<br>";
    }
    
    echo "<strong>Admin Login Details:</strong><br>";
    echo "Email: <strong>$email</strong><br>";
    echo "Password: <strong>$password</strong><br><br>";
    
    echo '<a href="login.php" class="btn btn-primary">Go to Login</a>';
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Setup Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <div class="alert alert-info">
        <h4>Admin Setup Complete</h4>
    </div>
</body>
</html>