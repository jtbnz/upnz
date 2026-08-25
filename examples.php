<?php
require_once 'auth.php';
require_once 'config/db.php';

// Get all images from examples directory with proper ordering
function getExampleImages() {
    $imageDir = 'images/examples/';
    $db = Database::getInstance();
    
    // Sync database with actual files
    $db->syncWithDirectory($imageDir);
    
    // Get ordered images from database
    $orderedImages = $db->getOrderedImages();
    
    // Build full paths
    $images = [];
    foreach ($orderedImages as $imageData) {
        $fullPath = $imageDir . $imageData['filename'];
        if (file_exists($fullPath)) {
            $images[] = $fullPath;
        }
    }
    
    return $images;
}

$images = getExampleImages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examples | V D Wood Upholstery</title>
    <meta name="description" content="Browse our extensive gallery of upholstery examples and completed projects.">
    
    <!-- Favicon -->
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="icon" href="/images/logo/vdwood-icon-192.png" type="image/png" sizes="192x192">
    <link rel="apple-touch-icon" href="/images/logo/vdwood-icon-180.png">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- CSS Stylesheets -->
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/examples.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="index.html" class="logo" style="display: flex; align-items: center;">
                    <img src="images/logo/vdwood-logo-black-320.png" alt="V D Wood Upholstery Logo" style="height: 40px; margin-right: 10px;">
                    V D Wood&nbsp;<span>Upholstery</span>
                </a>
                
                <ul class="nav-menu">
                    <li class="nav-item"><a href="index.html" class="nav-link">Home</a></li>
                    <li class="nav-item"><a href="about.html" class="nav-link">About</a></li>
                    <li class="nav-item"><a href="services.html" class="nav-link">Services</a></li>
                    <li class="nav-item"><a href="gallery.html" class="nav-link">Gallery</a></li>
                    <li class="nav-item"><a href="examples.php" class="nav-link active">Examples</a></li>
                    <li class="nav-item social-icons">
                        <a href="https://www.facebook.com/vdwoodupholstery/" target="_blank" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://www.instagram.com/vdwoodupholstery/" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="https://wa.me/6421588380" target="_blank" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    </li>
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
                <h1 class="page-title">Examples Gallery</h1>
                <p class="page-subtitle">Explore our comprehensive collection of upholstery projects and transformations</p>
                
                <!-- Login/Logout Section -->
                <div class="auth-section">
                    <?php if (isLoggedIn()): ?>
                        <div class="user-info">
                            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['user']); ?>!</span>
                            <a href="?action=logout" class="btn btn-secondary">Logout</a>
                            <?php if (isAdmin()): ?>
                                <a href="admin.php" class="btn">Admin Panel</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- The login button is now a subtle link in the footer -->
                    <?php endif; ?>
                </div>
            </section>

            <!-- Gallery Section -->
            <section class="examples-gallery">
                <?php if (isAdmin()): ?>
                    <!-- Admin Controls -->
                    <div class="admin-controls">
                        <button id="reorderToggle" class="btn btn-secondary">
                            <i class="fas fa-arrows-alt"></i> Enable Reordering
                        </button>
                        <span class="reorder-hint" style="display: none;">
                            <i class="fas fa-info-circle"></i> Drag images to reorder. Changes save automatically.
                        </span>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($images)): ?>
                    <div class="no-images">
                        <i class="fas fa-images"></i>
                        <h3>No Examples Available</h3>
                        <p>Check back soon for our latest upholstery examples!</p>
                        <?php if (isAdmin()): ?>
                            <a href="admin.php" class="btn">Upload Images</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="gallery-grid" id="galleryGrid">
                        <?php foreach ($images as $index => $image): ?>
                            <?php $filename = basename($image); ?>
                            <div class="gallery-item" data-filename="<?php echo htmlspecialchars($filename); ?>" data-index="<?php echo $index; ?>" onclick="openLightbox(<?php echo $index; ?>)">
                                <?php if (isAdmin()): ?>
                                    <div class="drag-handle" style="display: none;">
                                        <i class="fas fa-grip-vertical"></i>
                                    </div>
                                <?php endif; ?>
                                <img src="<?php echo htmlspecialchars($image); ?>" alt="Upholstery example" class="gallery-image">
                                <div class="gallery-overlay">
                                    <i class="fas fa-search-plus"></i>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Admin Login</h2>
            <?php if (isset($login_error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($login_error); ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" autocomplete="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn">Login</button>
            </form>
        </div>
    </div>

    <!-- Image Lightbox -->
    <div id="lightbox" class="lightbox">
        <span class="lightbox-close">&times;</span>
        <img id="lightbox-image" src="" alt="Gallery image">
        <div class="lightbox-nav">
            <button id="prevBtn" class="nav-btn"><i class="fas fa-chevron-left"></i></button>
            <button id="nextBtn" class="nav-btn"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-about">
                    <div class="footer-logo">V D Wood <span>Upholstery</span></div>
                    <p>Quality upholstery services with attention to detail and craftsmanship. Bringing new life to your furniture since 2010.</p>
                </div>
                
                <div class="footer-links">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.html">Home</a></li>
                        <li><a href="about.html">About</a></li>
                        <li><a href="services.html">Services</a></li>
                        <li><a href="gallery.html">Gallery</a></li>
                        <li><a href="examples.php">Examples</a></li>
                        <li><a href="contact.html">Contact</a></li>
                    </ul>
                </div>
                
                <div class="footer-links">
                    <h4>Services</h4>
                    <ul>
                        <li><a href="services.html">Residential Upholstery</a></li>
                        <li><a href="services.html">Commercial Upholstery</a></li>
                        <li><a href="services.html">Custom Upholstery</a></li>
                        <li><a href="services.html">Furniture Restoration</a></li>
                        <li><a href="services.html">Fabric Selection</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <img src="images/logo/vdwood-logo-black-320.png" alt="V D Wood Upholstery Logo" style="height: 50px; margin: 1rem auto;">
                <p>&copy; 2025 V D Wood Upholstery. All Rights Reserved.</p>
                <?php if (!isLoggedIn()): ?>
                <div class="admin-link" style="margin-top: 10px;">
                    <a href="#" id="loginBtn" style="color: #666; font-size: 0.8rem; text-decoration: none; opacity: 0.7;">Admin</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="js/main.js"></script>
    <script src="js/examples.js"></script>
</body>
</html>
