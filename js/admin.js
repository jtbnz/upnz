/**
 * Admin Panel JavaScript
 * Handles file upload, drag & drop, image management, and modals
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const uploadArea = document.getElementById('uploadArea');
    const imageInput = document.getElementById('imageInput');
    const uploadPreview = document.getElementById('uploadPreview');
    const previewImage = document.getElementById('previewImage');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const uploadForm = document.querySelector('.upload-form');
    
    // Modal elements
    const deleteModal = document.getElementById('deleteModal');
    const viewModal = document.getElementById('viewModal');
    const viewImage = document.getElementById('viewImage');
    const deleteFilename = document.getElementById('deleteFilename');
    
    // File upload handling
    if (imageInput) {
        imageInput.addEventListener('change', handleFileSelect);
    }
    
    // Drag and drop functionality
    if (uploadArea) {
        uploadArea.addEventListener('click', function() {
            imageInput.click();
        });
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                imageInput.files = files;
                handleFileSelect({ target: { files: files } });
            }
        });
    }
    
    // Handle file selection
    function handleFileSelect(event) {
        const file = event.target.files[0];
        
        if (file) {
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('Please select a valid image file (JPG, PNG, GIF, or WebP).');
                return;
            }
            
            // Validate file size (5MB)
            const maxSize = 5 * 1024 * 1024;
            if (file.size > maxSize) {
                alert('File size must be less than 5MB.');
                return;
            }
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                uploadPreview.style.display = 'flex';
            };
            reader.readAsDataURL(file);
        }
    }
    
    // Format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Form submission with loading state
    if (uploadForm) {
        uploadForm.addEventListener('submit', function() {
            const submitBtn = uploadForm.querySelector('.upload-btn');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
                submitBtn.disabled = true;
                uploadForm.classList.add('uploading');
            }
        });
    }
    
    // View image function
    window.viewImage = function(imagePath) {
        viewImage.src = imagePath;
        viewModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    };
    
    // Close view modal
    window.closeViewModal = function() {
        viewModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    };
    
    // Confirm delete function
    window.confirmDelete = function(filename) {
        deleteFilename.value = filename;
        deleteModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    };
    
    // Close delete modal
    window.closeDeleteModal = function() {
        deleteModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    };
    
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === deleteModal) {
            closeDeleteModal();
        }
        if (event.target === viewModal) {
            closeViewModal();
        }
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            if (deleteModal.style.display === 'block') {
                closeDeleteModal();
            }
            if (viewModal.style.display === 'block') {
                closeViewModal();
            }
        }
    });
    
    // Auto-hide messages after 5 seconds
    const messages = document.querySelectorAll('.success-message, .error-message');
    messages.forEach(function(message) {
        setTimeout(function() {
            message.style.opacity = '0';
            setTimeout(function() {
                message.style.display = 'none';
            }, 300);
        }, 5000);
    });
    
    // Image lazy loading for better performance
    function setupLazyLoading() {
        const images = document.querySelectorAll('.image-preview img');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            imageObserver.unobserve(img);
                        }
                    }
                });
            });
            
            images.forEach(function(img) {
                if (img.dataset.src) {
                    imageObserver.observe(img);
                }
            });
        }
    }
    
    // Initialize lazy loading
    setupLazyLoading();
    
    // Prevent form resubmission on page refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    
    // Add confirmation for bulk actions (if implemented in future)
    function confirmBulkAction(action, count) {
        const actionText = action === 'delete' ? 'delete' : action;
        return confirm(`Are you sure you want to ${actionText} ${count} image(s)? This action cannot be undone.`);
    }
    
    // File input validation on change
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Additional client-side validation
                const img = new Image();
                img.onload = function() {
                    // Image loaded successfully
                    console.log(`Image dimensions: ${this.width}x${this.height}`);
                };
                img.onerror = function() {
                    alert('Invalid image file. Please select a valid image.');
                    imageInput.value = '';
                    uploadPreview.style.display = 'none';
                };
                img.src = URL.createObjectURL(file);
            }
        });
    }
    
    // Add tooltips for better UX
    function addTooltips() {
        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        
        tooltipElements.forEach(function(element) {
            element.addEventListener('mouseenter', function() {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = this.getAttribute('data-tooltip');
                document.body.appendChild(tooltip);
                
                const rect = this.getBoundingClientRect();
                tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
                tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
            });
            
            element.addEventListener('mouseleave', function() {
                const tooltip = document.querySelector('.tooltip');
                if (tooltip) {
                    tooltip.remove();
                }
            });
        });
    }
    
    // Initialize tooltips
    addTooltips();
    
    // Progress indicator for uploads (if needed for large files)
    function showUploadProgress(percent) {
        const progressBar = document.querySelector('.upload-progress');
        if (progressBar) {
            progressBar.style.width = percent + '%';
        }
    }
    
    // Cleanup function for when page is unloaded
    window.addEventListener('beforeunload', function() {
        // Clean up any ongoing uploads or operations
        const uploadingElements = document.querySelectorAll('.uploading');
        uploadingElements.forEach(function(element) {
            element.classList.remove('uploading');
        });
    });
});

// CSS for tooltips (if not in CSS file)
const tooltipCSS = `
.tooltip {
    position: absolute;
    background-color: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    z-index: 9999;
    pointer-events: none;
}
`;

// Add tooltip CSS if not already present
if (!document.querySelector('#tooltip-styles')) {
    const style = document.createElement('style');
    style.id = 'tooltip-styles';
    style.textContent = tooltipCSS;
    document.head.appendChild(style);
}