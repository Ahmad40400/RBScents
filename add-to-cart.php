<?php
require 'db.php';
require 'helpers.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$perfume_id = $_GET['id'] ?? 0; // Changed product_id to perfume_id
$quantity = $_GET['quantity'] ?? 1;

if (!$perfume_id) {
    header('Location: products.php');
    exit;
}

// Check if perfume exists and has stock
$stmt = $pdo->prepare("SELECT * FROM perfumes WHERE id = ? AND in_stock > 0");
$stmt->execute([$perfume_id]);
$perfume = $stmt->fetch();

if (!$perfume) {
    $_SESSION['error'] = 'Perfume not available or out of stock';
    header('Location: products.php');
    exit;
}

// Check if item already in cart
$stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND perfume_id = ?"); // Changed product_id to perfume_id
$stmt->execute([$_SESSION['user_id'], $perfume_id]);
$existing_item = $stmt->fetch();

if ($existing_item) {
    // Update quantity
    $new_quantity = $existing_item['quantity'] + $quantity;
    if ($new_quantity <= $perfume['in_stock']) {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND perfume_id = ?"); // Changed product_id to perfume_id
        $stmt->execute([$new_quantity, $_SESSION['user_id'], $perfume_id]);
        $_SESSION['success'] = 'Cart updated successfully!';
    } else {
        $_SESSION['error'] = 'Not enough stock available';
    }
} else {
    // Add new item
    if ($quantity <= $perfume['in_stock']) {
        $stmt = $pdo->prepare("INSERT INTO cart (user_id, perfume_id, quantity) VALUES (?, ?, ?)"); // Changed product_id to perfume_id
        $stmt->execute([$_SESSION['user_id'], $perfume_id, $quantity]);
        $_SESSION['success'] = 'Perfume added to cart!';
    } else {
        $_SESSION['error'] = 'Not enough stock available';
    }
}

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'products.php'));
exit;