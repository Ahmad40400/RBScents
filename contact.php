
<?php
require 'helpers.php';

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';
require __DIR__ . '/PHPMailer/Exception.php';

$err = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verify_csrf($_POST['csrf'] ?? '')) {
    $err = 'Invalid CSRF token';
  } else {
    $name = trim($_POST['name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (!$name || !$email || !$subject || !$message) {
      $err = 'Please fill in all fields';
    } else {
      // ✅ Send Email with PHPMailer
      $mail = new PHPMailer(true);
      try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'aaallliii77992186@gmail.com';  // 🔑 Your Gmail
        $mail->Password   = 'omke jjrt lrfy fehu';     // 🔑 App password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Sender & recipient
        $mail->setFrom($email, $name);  // User who filled form
        $mail->addAddress('m.ahmad20092003@gmail.com', 'Luxe Perfumes Admin'); // Where it will be sent
        $mail->addReplyTo($email, $name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = "
          <h3>New Contact Form Message - Luxe Perfumes</h3>
          <p><strong>Name:</strong> {$name}</p>
          <p><strong>Email:</strong> {$email}</p>
          <p><strong>Subject:</strong> {$subject}</p>
          <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>
        ";
        $mail->AltBody = "Name: {$name}\nEmail: {$email}\nSubject: {$subject}\nMessage:\n{$message}";

        $mail->send();
        $success = 'Thank you for your message! We will get back to you soon.';
        $_POST = []; // Clear form
      } catch (Exception $e) {
        $err = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
      }
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Contact Us - RBScents</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome@6.4.0/css/all.min.css" rel="stylesheet">
  <link href="style.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #8B5A2B;
      --secondary: #C9A96E;
      --dark: #2B1810;
      --light: #F8F5F0;
      --text: #333333;
    }
    
    .contact-card {
      background: white;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(139, 90, 43, 0.1);
      border: 1px solid rgba(139, 90, 43, 0.1);
      transition: all 0.3s ease;
    }
    
    .contact-card:hover {
      box-shadow: 0 8px 25px rgba(139, 90, 43, 0.15);
      transform: translateY(-5px);
    }
    
    .contact-icon {
      background: rgba(139, 90, 43, 0.1);
      color: var(--primary);
      width: 60px;
      height: 60px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
    }
    
    .contact-icon i {
      font-size: 1.5rem;
    }
    
    .btn-primary {
      background-color: var(--primary);
      border-color: var(--primary);
      color: white;
      padding: 0.75rem 1.5rem;
      border-radius: 8px;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
      background-color: #6B4423;
      border-color: #6B4423;
      transform: translateY(-2px);
    }
    
    .form-control:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 0.2rem rgba(139, 90, 43, 0.25);
    }
    
    .accordion-button:not(.collapsed) {
      background-color: rgba(139, 90, 43, 0.1);
      color: var(--primary);
    }
    
    .accordion-button:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 0.2rem rgba(139, 90, 43, 0.25);
    }
    
    .text-primary {
      color: var(--primary) !important;
    }
    
    h1, h2, h3, h4, h5 {
      color: var(--dark);
    }
    
    .card-title {
      color: var(--dark);
      font-weight: 600;
    }
    
    .alert-success {
      background-color: rgba(40, 167, 69, 0.1);
      border-color: rgba(40, 167, 69, 0.2);
      color: #155724;
    }
    
    .alert-danger {
      background-color: rgba(220, 53, 69, 0.1);
      border-color: rgba(220, 53, 69, 0.2);
      color: #721c24;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-gem me-2"></i>RBScents
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
              <ul class="navbar-nav me-auto">
    <li class="nav-item"><a href="index.php" class="nav-link">Home</a></li>
    <li class="nav-item"><a href="products.php" class="nav-link">Perfumes</a></li>
    <?php if (!is_admin()): ?>
        <li class="nav-item"><a href="about.php" class="nav-link">About</a></li>
        <li class="nav-item"><a href="contact.php" class="nav-link">Contact</a></li>
    <?php endif; ?>
    <?php if (is_admin()): ?>
        <li class="nav-item"><a href="admin.php" class="nav-link">Admin</a></li>
    <?php endif; ?>
</ul>
                
                <div class="d-flex align-items-center">
                    <?php if (is_logged_in()): ?>
                        <a href="cart.php" class="btn btn-outline me-2">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-badge">Cart</span>
                        </a>
                        <div class="dropdown">
                            <button class="btn p-0 d-flex align-items-center dropdown-toggle" data-bs-toggle="dropdown">
                                <div class="bg-primary text-white rounded-circle me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                    <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                                </div>
                                <span><?= e($_SESSION['user_name'] ?? 'User') ?></span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="orders.php"><i class="fas fa-box me-2"></i>My Orders</a></li>
                                <?php if (is_admin()): ?>
                                    <li><a class="dropdown-item" href="add-product.php"><i class="fas fa-plus me-2"></i>Add Perfume</a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary btn-sm me-2">Login</a>
                        <a href="register.php" class="btn btn-outline btn-sm">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

<div class="container py-5">
  <div class="row">
    <div class="col-lg-10 mx-auto">
      <h1 class="text-center mb-4">Contact RBScents</h1>
      <p class="text-center text-muted mb-5">Have questions about our fragrances? Need assistance with your order? We're here to help you find your perfect scent.</p>
      
      <?php if ($err): ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-circle me-2"></i><?= e($err) ?>
        </div>
      <?php endif; ?>
      
      <?php if ($success): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle me-2"></i><?= e($success) ?>
        </div>
      <?php endif; ?>
      
      <div class="row">
        <div class="col-md-8">
          <div class="card contact-card">
            <div class="card-body">
              <h3 class="card-title mb-4">Send us a Message</h3>
              <form method="post">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group mb-3">
                      <label class="form-label" for="name">Your Name</label>
                      <input type="text" class="form-control" id="name" name="name" placeholder="Enter your name" value="<?= e($_POST['name'] ?? '') ?>" required>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-3">
                      <label class="form-label" for="email">Email Address</label>
                      <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" value="<?= e($_POST['email'] ?? '') ?>" required>
                    </div>
                  </div>
                </div>
                
                <div class="form-group mb-3">
                  <label class="form-label" for="subject">Subject</label>
                  <input type="text" class="form-control" id="subject" name="subject" placeholder="What is this regarding?" value="<?= e($_POST['subject'] ?? '') ?>" required>
                </div>
                
                <div class="form-group mb-4">
                  <label class="form-label" for="message">Message</label>
                  <textarea class="form-control" id="message" name="message" rows="5" placeholder="How can we help you?" required><?= e($_POST['message'] ?? '') ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">
                  <i class="fas fa-paper-plane me-1"></i>Send Message
                </button>
              </form>
            </div>
          </div>
        </div>
        
        <div class="col-md-4">
          <div class="card contact-card mb-4">
            <div class="card-body text-center">
              <div class="contact-icon">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <h5 class="text-primary">Our Boutique</h5>
              <p class="text-muted">123 Fragrance Avenue<br>Luxury District, LC 12345</p>
            </div>
          </div>
          
          <div class="card contact-card mb-4">
            <div class="card-body text-center">
              <div class="contact-icon">
                <i class="fas fa-phone"></i>
              </div>
              <h5 class="text-primary">Call Us</h5>
              <p class="text-muted">+1 (555) 123-4567<br>Mon-Sat, 10am-8pm</p>
            </div>
          </div>
          
          <div class="card contact-card">
            <div class="card-body text-center">
              <div class="contact-icon">
                <i class="fas fa-envelope"></i>
              </div>
              <h5 class="text-primary">Email Us</h5>
              <p class="text-muted">info@rbscents.com<br>support@rbscents.com</p>
            </div>
          </div>
        </div>
      </div>
      
      <div class="card contact-card mt-5">
        <div class="card-body">
          <h3 class="card-title text-center mb-4">Frequently Asked Questions</h3>
          <div class="accordion" id="faqAccordion">
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingOne">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                  How do I choose the right perfume?
                </button>
              </h2>
              <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                  Choosing the right perfume depends on your personal preference and body chemistry. We recommend trying samples first and considering the occasion. Fresh scents are great for daytime, while richer, warmer fragrances work well for evenings. Our fragrance experts can help you find your perfect match.
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingTwo">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                  What is your return policy?
                </button>
              </h2>
              <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                  We offer a 30-day return policy for unopened and unused products. Due to hygiene reasons, we cannot accept returns on opened fragrance products unless they are defective. If you receive a damaged or defective item, please contact us within 7 days of delivery.
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingThree">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                  Do you offer international shipping?
                </button>
              </h2>
              <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                  Yes, we ship internationally to most countries. Shipping costs and delivery times vary depending on the destination. Some restrictions may apply to certain countries due to customs regulations. Please check our shipping information page for detailed rates and delivery estimates.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<footer class="bg-dark text-white py-5 mt-5">
  <div class="container">
    <div class="row">
      <div class="col-md-4 mb-4">
        <h5 class="navbar-brand text-white">RBScents</h5>
        <p>Discover the essence of luxury with our premium fragrance collection.</p>
      </div>
      <div class="col-md-2 mb-4">
        <h5>Quick Links</h5>
        <ul class="list-unstyled">
          <li><a href="index.php" class="text-white">Home</a></li>
          <li><a href="products.php" class="text-white">Perfumes</a></li>
          <li><a href="about.php" class="text-white">About Us</a></li>
          <li><a href="contact.php" class="text-white">Contact</a></li>
        </ul>
      </div>
      <div class="col-md-3 mb-4">
        <h5>Customer Service</h5>
        <ul class="list-unstyled">
          <li><a href="#" class="text-white">Shipping Info</a></li>
          <li><a href="#" class="text-white">Returns & Exchanges</a></li>
          <li><a href="#" class="text-white">Privacy Policy</a></li>
          <li><a href="#" class="text-white">Terms of Service</a></li>
        </ul>
      </div>
      <div class="col-md-3">
        <h5>Connect With Us</h5>
        <div class="d-flex gap-3 mt-3">
          <a href="#" class="text-white"><i class="fab fa-facebook fa-lg"></i></a>
          <a href="#" class="text-white"><i class="fab fa-twitter fa-lg"></i></a>
          <a href="#" class="text-white"><i class="fab fa-instagram fa-lg"></i></a>
          <a href="#" class="text-white"><i class="fab fa-pinterest fa-lg"></i></a>
        </div>
      </div>
    </div>
    <hr class="my-4">
    <p class="text-center mb-0">&copy; <?= date('Y') ?> RBScents. All rights reserved.</p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
