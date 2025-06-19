<?php
require_once 'auth.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: examples.php');
    exit;
}

$message = '';
$error = '';

// Handle file upload
if ($_POST['action'] === 'upload' && isset($_FILES['image'])) {
    $uploadDir = 'images/examples/';
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    
    $file = $_FILES['image'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        // Validate file type
        if (!in_array($file['type'], $allowedTypes)) {
            $error = 'Invalid file type. Please upload JPG, PNG, GIF, or WebP images only.';
        }
        // Validate file size
        elseif ($file['size'] > $maxFileSize) {
            $error = 'File too large. Maximum size is 5MB.';
        }
        else {
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('example_') . '.' . $extension;
            $uploadPath = $uploadDir . $filename;
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $message = 'Image uploaded successfully!';
            } else {
                $error = 'Failed to upload image. Please try again.';
            }
        }
    } else {
        $error = 'Upload error: ' . $file['error'];
    }
}

// Handle file deletion
if ($_POST['action'] === 'delete' && isset($_POST['filename'])) {
    $filename = basename($_POST['filename']); // Security: prevent directory traversal
    $filePath = 'images/examples/' . $filename;
    
    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            $message = 'Image deleted successfully!';
        } else {
            $error = 'Failed to delete image.';
        }
    } else {
        $error = 'Image not found.';
    }
}

// Get all images from examples directory
function getExampleImages() {
    $images = [];
    $imageDir = 'images/examples/';
    
    if (is_dir($imageDir)) {
        $files = scandir($imageDir);
        foreach ($files as $file) {
            if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $images[] = [
                    'filename' => $file,
                    'path' => $imageDir . $file,
                    'size' => filesize($imageDir . $file),
                    'modified' => filemtime($imageDir . $file)
                ];
            }
        }
    }
    
    // Sort by modification time (newest first)
    usort($images, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
    
    return $images;
}

$images = getExampleImages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | Vaughans Upholstery</title>
    
    <!-- Favicon -->
    <link rel="icon" href="images/logo.svg" type="image/svg+xml">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- CSS Stylesheets -->
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="index.html" class="logo">Vaughans <span>Upholstery</span></a>
                
                <ul class="nav-menu">
                    <li class="nav-item"><a href="index.html" class="nav-link">Home</a></li>
                    <li class="nav-item"><a href="about.html" class="nav-link">About</a></li>
                    <li class="nav-item"><a href="services.html" class="nav-link">Services</a></li>
                    <li class="nav-item"><a href="gallery.html" class="nav-link">Gallery</a></li>
                    <li class="nav-item"><a href="examples.php" class="nav-link">Examples</a></li>
                    <li class="nav-item"><a href="contact.html" class="nav-link">Contact</a></li>
                </ul>
                
                <div class="hamburger">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Page Header -->
            <section class="page-header">
                <h1 class="page-title">Admin Panel</h1>
                <p class="page-subtitle">Manage example gallery images</p>
                
                <div class="admin-nav">
                    <a href="examples.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Examples
                    </a>
                    <a href="?action=logout" class="btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </section>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Upload Section -->
            <section class="upload-section">
                <h2>Upload New Image</h2>
                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <input type="hidden" name="action" value="upload">
                    
                    <div class="upload-area" id="uploadArea">
                        <div class="upload-content">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Drag and drop an image here, or click to select</p>
                            <p class="upload-info">Supported formats: JPG, PNG, GIF, WebP (Max 5MB)</p>
                        </div>
                        <input type="file" name="image" id="imageInput" accept="image/*" required>
                    </div>
                    
                    <div class="upload-preview" id="uploadPreview" style="display: none;">
                        <img id="previewImage" src="" alt="Preview">
                        <div class="preview-info">
                            <p id="fileName"></p>
                            <p id="fileSize"></p>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn upload-btn">
                        <i class="fas fa-upload"></i> Upload Image
                    </button>
                </form>
            </section>

            <!-- Images Management -->
            <section class="images-section">
                <h2>Manage Images (<?php echo count($images); ?> total)</h2>
                
                <?php if (empty($images)): ?>
                    <div class="no-images">
                        <i class="fas fa-images"></i>
                        <p>No images uploaded yet. Upload your first image above!</p>
                    </div>
                <?php else: ?>
                    <div class="images-grid">
                        <?php foreach ($images as $image): ?>
                            <div class="image-card">
                                <div class="image-preview">
                                    <img src="<?php echo htmlspecialchars($image['path']); ?>" alt="Example image">
                                    <div class="image-overlay">
                                        <button class="btn-icon view-btn" onclick="viewImage('<?php echo htmlspecialchars($image['path']); ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-icon delete-btn" onclick="confirmDelete('<?php echo htmlspecialchars($image['filename']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="image-info">
                                    <p class="image-name"><?php echo htmlspecialchars($image['filename']); ?></p>
                                    <p class="image-details">
                                        <?php echo number_format($image['size'] / 1024, 1); ?> KB • 
                                        <?php echo date('M j, Y', $image['modified']); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3>Confirm Deletion</h3>
            <p>Are you sure you want to delete this image? This action cannot be undone.</p>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="filename" id="deleteFilename">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Image View Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content modal-large">
            <span class="close" onclick="closeViewModal()">&times;</span>
            <img id="viewImage" src="" alt="Full size image">
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-about">
                    <div class="footer-logo">Vaughans <span>Upholstery</span></div>
                    <p>Quality upholstery services with attention to detail and craftsmanship.</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 Vaughans Upholstery. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="js/main.js"></script>
    <script src="js/admin.js"></script>
</body>
</html>