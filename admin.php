<?php
require_once 'auth.php';
require_once 'config/db.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: examples.php');
    exit;
}

$message = '';
$error = '';
$uploadResults = [];

// Handle multiple file upload
if (isset($_POST['action']) && $_POST['action'] === 'upload' && isset($_FILES['images'])) {
    $uploadDir = 'images/examples/';
    // Map the image type the server detects to the extension we will store it
    // under. The browser-supplied MIME type and filename are never trusted:
    // both are attacker-controlled and were previously enough to land a .php
    // file in this web-executable directory.
    $allowedTypes = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_GIF  => 'gif',
        IMAGETYPE_WEBP => 'webp',
    ];
    $maxFileSize = 5 * 1024 * 1024; // 5MB

    $files = $_FILES['images'];
    $uploadCount = 0;
    $errorCount = 0;
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Handle multiple files
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $fileName = basename($files['name'][$i]);
            $fileSize = $files['size'][$i];
            $fileTmpName = $files['tmp_name'][$i];

            // Reject anything that did not arrive through an actual upload.
            if (!is_uploaded_file($fileTmpName)) {
                $uploadResults[] = ['file' => $fileName, 'status' => 'error', 'message' => 'Invalid upload'];
                $errorCount++;
                continue;
            }

            // Validate file size
            if ($fileSize > $maxFileSize) {
                $uploadResults[] = ['file' => $fileName, 'status' => 'error', 'message' => 'File too large (max 5MB)'];
                $errorCount++;
                continue;
            }

            // Validate the file type by inspecting the file itself, and take
            // the extension from what was detected rather than from the name
            // the browser sent.
            $imageInfo = @getimagesize($fileTmpName);
            if ($imageInfo === false || !isset($allowedTypes[$imageInfo[2]])) {
                $uploadResults[] = ['file' => $fileName, 'status' => 'error', 'message' => 'Invalid file type'];
                $errorCount++;
                continue;
            }

            // Generate unique filename
            $extension = $allowedTypes[$imageInfo[2]];
            $uniqueFilename = uniqid('example_') . '_' . time() . '.' . $extension;
            $uploadPath = $uploadDir . $uniqueFilename;

            // Move uploaded file
            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                chmod($uploadPath, 0644);

                // Add to database
                $db = Database::getInstance();
                $db->addImage($uniqueFilename);
                
                $uploadResults[] = ['file' => $fileName, 'status' => 'success', 'message' => 'Uploaded successfully'];
                $uploadCount++;
            } else {
                $uploadResults[] = ['file' => $fileName, 'status' => 'error', 'message' => 'Failed to upload'];
                $errorCount++;
            }
        } else {
            $uploadResults[] = ['file' => $files['name'][$i], 'status' => 'error', 'message' => 'Upload error: ' . $files['error'][$i]];
            $errorCount++;
        }
    }
    
    if ($uploadCount > 0) {
        $message = "Successfully uploaded {$uploadCount} image(s).";
        if ($errorCount > 0) {
            $message .= " {$errorCount} file(s) failed to upload.";
        }
    } else {
        $error = "Failed to upload all files. Check file types and sizes.";
    }
}

// Handle bulk file deletion
if (isset($_POST['action']) && $_POST['action'] === 'bulk_delete' && isset($_POST['filenames'])) {
    $filenames = $_POST['filenames'];
    $deleteCount = 0;
    $failCount = 0;
    $db = Database::getInstance();
    
    foreach ($filenames as $filename) {
        $filename = basename($filename); // Security: prevent directory traversal
        $filePath = 'images/examples/' . $filename;
        
        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                // Remove from database
                $db->removeImage($filename);
                $deleteCount++;
            } else {
                $failCount++;
            }
        } else {
            $failCount++;
        }
    }
    
    if ($deleteCount > 0) {
        $message = "Successfully deleted {$deleteCount} image(s).";
        if ($failCount > 0) {
            $message .= " {$failCount} file(s) failed to delete.";
        }
    } else {
        $error = "Failed to delete any files.";
    }
}

// Handle single file deletion (backward compatibility)
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['filename'])) {
    $filename = basename($_POST['filename']); // Security: prevent directory traversal
    $filePath = 'images/examples/' . $filename;
    
    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            // Remove from database
            $db = Database::getInstance();
            $db->removeImage($filename);
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
    <title>Admin Panel | V D Wood Upholstery</title>
    
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
    <link rel="stylesheet" href="css/admin.css">
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
                <h2>Upload Images</h2>
                <form method="POST" enctype="multipart/form-data" class="upload-form" id="uploadForm">
                    <input type="hidden" name="action" value="upload">
                    
                    <div class="upload-area" id="uploadArea">
                        <div class="upload-content">
                            <i class="fas fa-cloud-upload-alt upload-icon"></i>
                            <p class="upload-main-text">Drag and drop images here, or click to select</p>
                            <p class="upload-info">Supported formats: JPG, PNG, GIF, WebP (Max 5MB each) • Multiple files supported</p>
                        </div>
                        <input type="file" name="images[]" id="imageInput" accept="image/*" multiple>
                    </div>
                    
                    <div class="upload-preview-container" id="uploadPreviewContainer" style="display: none;">
                        <h3>Selected Files:</h3>
                        <div class="upload-preview-grid" id="uploadPreviewGrid"></div>
                        <div class="upload-actions">
                            <button type="button" class="btn btn-secondary" id="clearFiles">
                                <i class="fas fa-times"></i> Clear All
                            </button>
                            <button type="submit" class="btn upload-btn" id="uploadBtn">
                                <i class="fas fa-upload"></i> Upload <span id="fileCount">0</span> File(s)
                            </button>
                        </div>
                    </div>
                </form>
                
                <!-- Upload Results -->
                <?php if (!empty($uploadResults)): ?>
                    <div class="upload-results">
                        <h3>Upload Results:</h3>
                        <?php foreach ($uploadResults as $result): ?>
                            <div class="upload-result-item <?php echo $result['status']; ?>">
                                <i class="fas <?php echo $result['status'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                                <span class="file-name"><?php echo htmlspecialchars($result['file']); ?></span>
                                <span class="result-message"><?php echo htmlspecialchars($result['message']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Images Management -->
            <section class="images-section">
                <div class="images-header">
                    <h2>Manage Images (<?php echo count($images); ?> total)</h2>
                    <div class="images-controls">
                        <div class="view-controls">
                            <button class="btn btn-small" id="selectAllBtn" onclick="selectAllImages()">
                                <i class="fas fa-check-square"></i> Select All
                            </button>
                            <button class="btn btn-small" id="deselectAllBtn" onclick="deselectAllImages()" style="display: none;">
                                <i class="fas fa-square"></i> Deselect All
                            </button>
                        </div>
                        <div class="bulk-actions" id="bulkActions" style="display: none;">
                            <span class="selected-count" id="selectedCount">0 selected</span>
                            <button class="btn btn-danger" onclick="confirmBulkDelete()">
                                <i class="fas fa-trash"></i> Delete Selected
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php if (empty($images)): ?>
                    <div class="no-images">
                        <i class="fas fa-images"></i>
                        <p>No images uploaded yet. Upload your first images above!</p>
                    </div>
                <?php else: ?>
                    <div class="images-grid" id="imagesGrid">
                        <?php foreach ($images as $image): ?>
                            <div class="image-card" data-filename="<?php echo htmlspecialchars($image['filename']); ?>">
                                <div class="image-checkbox">
                                    <input type="checkbox" class="image-select" value="<?php echo htmlspecialchars($image['filename']); ?>" onchange="updateSelection()">
                                </div>
                                
                                <div class="image-preview">
                                    <img src="<?php echo htmlspecialchars($image['path']); ?>" alt="Example image" loading="lazy">
                                    <div class="image-overlay">
                                        <button class="btn-icon view-btn" onclick="viewImage('<?php echo htmlspecialchars($image['path']); ?>')" title="View full size">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-icon delete-btn" onclick="confirmDelete('<?php echo htmlspecialchars($image['filename']); ?>')" title="Delete image">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="image-info">
                                    <p class="image-name" title="<?php echo htmlspecialchars($image['filename']); ?>">
                                        <?php echo htmlspecialchars(strlen($image['filename']) > 20 ? substr($image['filename'], 0, 17) . '...' : $image['filename']); ?>
                                    </p>
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
            <p id="deleteMessage">Are you sure you want to delete this image? This action cannot be undone.</p>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <form method="POST" style="display: inline;" id="deleteForm">
                    <input type="hidden" name="action" value="delete" id="deleteAction">
                    <input type="hidden" name="filename" id="deleteFilename">
                    <button type="submit" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bulk Delete Modal -->
    <div id="bulkDeleteModal" class="modal">
        <div class="modal-content">
            <h3>Confirm Bulk Deletion</h3>
            <p id="bulkDeleteMessage">Are you sure you want to delete the selected images? This action cannot be undone.</p>
            <div class="selected-files-preview" id="selectedFilesPreview"></div>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeBulkDeleteModal()">Cancel</button>
                <form method="POST" style="display: inline;" id="bulkDeleteForm">
                    <input type="hidden" name="action" value="bulk_delete">
                    <div id="bulkDeleteFilenames"></div>
                    <button type="submit" class="btn btn-danger" id="confirmBulkDeleteBtn">
                        <i class="fas fa-trash"></i> Delete <span id="bulkDeleteCount">0</span> Images
                    </button>
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
                    <div class="footer-logo">V D Wood <span>Upholstery</span></div>
                    <p>Quality upholstery services with attention to detail and craftsmanship.</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 V D Wood Upholstery. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="js/main.js"></script>
    <script src="js/admin.js"></script>
</body>
</html>