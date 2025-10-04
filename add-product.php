<?php
require 'db.php';
require 'helpers.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: index.php');
    exit;
}

$err = '';
$success = '';

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $err = 'Invalid CSRF token';
    } else {
        $name = trim($_POST['name'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = trim($_POST['price'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $in_stock = (int)($_POST['in_stock'] ?? 0);
        $image_url = filter_var(trim($_POST['image'] ?? ''), FILTER_SANITIZE_URL);
        
        $image_filename = '';

        if (!$name || !$brand || !$price) {
            $err = 'Please fill in all required fields';
        } else {
            // Handle file upload
            if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['image_upload'];
                
                // Validate file type
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                $file_type = mime_content_type($file['tmp_name']);
                
                if (!in_array($file_type, $allowed_types)) {
                    $err = 'Only JPG, JPEG, PNG, GIF, and WebP images are allowed.';
                } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
                    $err = 'Image size must be less than 5MB.';
                } else {
                    // Generate unique filename
                    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $image_filename = uniqid() . '_' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $image_filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                        // File uploaded successfully
                        $success = 'Image uploaded successfully! ';
                    } else {
                        $err = 'Failed to upload image. Please try again.';
                    }
                }
            }
            
            if (!$err) {
                // Insert into database
                $stmt = $pdo->prepare("INSERT INTO perfumes (name, brand, description, price, image, image_filename, category, in_stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                
                if ($stmt->execute([$name, $brand, $description, $price, $image_url, $image_filename, $category, $in_stock])) {
                    $success .= 'Perfume added successfully!';
                    $_POST = []; // Clear form
                    $_FILES = []; // Clear files
                } else {
                    $err = 'Failed to add perfume. Please try again.';
                    // Delete uploaded file if database insert failed
                    if ($image_filename && file_exists($upload_path)) {
                        unlink($upload_path);
                    }
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
    <title>Add Perfume - RBScents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .upload-area {
            border: 2px dashed #8B5A2B;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            background: rgba(139, 90, 43, 0.05);
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }
        
        .upload-area:hover {
            background: rgba(139, 90, 43, 0.1);
            border-color: #6B4423;
        }
        
        .upload-area i {
            font-size: 3rem;
            color: #8B5A2B;
            margin-bottom: 1rem;
        }
        
        .upload-area.dragover {
            background: rgba(139, 90, 43, 0.15);
            border-color: #6B4423;
        }
        
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 10px;
            margin-top: 1rem;
            display: none;
        }
        
        .file-input {
            display: none;
        }
        
        .upload-text {
            color: #8B5A2B;
            font-weight: 500;
        }
        
        .upload-hint {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .tab-content {
            margin-top: 1rem;
        }
        
        .nav-tabs .nav-link {
            color: #8B5A2B;
            border: 1px solid transparent;
        }
        
        .nav-tabs .nav-link.active {
            color: #6B4423;
            background: rgba(139, 90, 43, 0.1);
            border-color: #8B5A2B;
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
        <div class="auth-container">
            <h3 class="text-center mb-4">Add New Perfume</h3>
            
            <?php if ($err): ?>
                <div class="alert alert-danger"><?= e($err) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Perfume Name *</label>
                            <input type="text" name="name" class="form-control" required value="<?= e($_POST['name'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Brand *</label>
                            <input type="text" name="brand" class="form-control" required value="<?= e($_POST['brand'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= e($_POST['description'] ?? '') ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Price *</label>
                            <input type="number" step="0.01" name="price" class="form-control" required value="<?= e($_POST['price'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-control">
                                <option value="Men">Men</option>
                                <option value="Women">Women</option>
                                <option value="Unisex">Unisex</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Stock Quantity</label>
                            <input type="number" name="in_stock" class="form-control" value="<?= e($_POST['in_stock'] ?? 0) ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Image Upload Section -->
                <div class="mb-4">
                    <label class="form-label fw-bold mb-3">Product Image</label>
                    
                    <ul class="nav nav-tabs" id="imageTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload" type="button" role="tab">
                                <i class="fas fa-upload me-1"></i>Upload Image
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="url-tab" data-bs-toggle="tab" data-bs-target="#url" type="button" role="tab">
                                <i class="fas fa-link me-1"></i>Image URL
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="imageTabContent">
                        <div class="tab-pane fade show active" id="upload" role="tabpanel">
                            <div class="upload-area" id="uploadArea">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <div class="upload-text">Click to upload or drag and drop</div>
                                <div class="upload-hint">PNG, JPG, GIF, WebP up to 5MB</div>
                                <input type="file" name="image_upload" id="image_upload" class="file-input" accept="image/*">
                                <img id="imagePreview" class="image-preview" alt="Image preview">
                            </div>
                            <div id="fileName" class="text-muted small"></div>
                        </div>
                        
                        <div class="tab-pane fade" id="url" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label">Image URL</label>
                                <input type="url" name="image" class="form-control" value="<?= e($_POST['image'] ?? '') ?>" placeholder="https://example.com/image.jpg">
                                <small class="text-muted">Leave empty to use default image</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-plus me-2"></i>Add Perfume
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('image_upload');
            const imagePreview = document.getElementById('imagePreview');
            const fileName = document.getElementById('fileName');
            
            // Click to select file
            uploadArea.addEventListener('click', function() {
                fileInput.click();
            });
            
            // File selection handler
            fileInput.addEventListener('change', function(e) {
                if (e.target.files.length > 0) {
                    const file = e.target.files[0];
                    fileName.textContent = 'Selected file: ' + file.name;
                    
                    // Show preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        imagePreview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // Drag and drop functionality
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                uploadArea.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, unhighlight, false);
            });
            
            function highlight() {
                uploadArea.classList.add('dragover');
            }
            
            function unhighlight() {
                uploadArea.classList.remove('dragover');
            }
            
            // Handle dropped files
            uploadArea.addEventListener('drop', function(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                
                if (files.length > 0) {
                    fileInput.files = files;
                    
                    // Trigger change event
                    const event = new Event('change');
                    fileInput.dispatchEvent(event);
                }
            });
        });
    </script>
</body>
</html>