/**
 * Enhanced Admin Panel JavaScript
 * Handles multi-file upload, drag & drop, bulk selection, and image management
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const uploadArea = document.getElementById('uploadArea');
    const imageInput = document.getElementById('imageInput');
    const uploadPreviewContainer = document.getElementById('uploadPreviewContainer');
    const uploadPreviewGrid = document.getElementById('uploadPreviewGrid');
    const uploadForm = document.getElementById('uploadForm');
    const clearFilesBtn = document.getElementById('clearFiles');
    const uploadBtn = document.getElementById('uploadBtn');
    const fileCountSpan = document.getElementById('fileCount');
    
    // Modal elements
    const deleteModal = document.getElementById('deleteModal');
    const bulkDeleteModal = document.getElementById('bulkDeleteModal');
    const viewModal = document.getElementById('viewModal');
    const viewImage = document.getElementById('viewImage');
    
    // Selection elements
    const selectAllBtn = document.getElementById('selectAllBtn');
    const deselectAllBtn = document.getElementById('deselectAllBtn');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    
    // File handling variables
    let selectedFiles = [];
    let selectedImages = new Set();
    
    /**
     * Define all global functions first
     */
    function defineGlobalFunctions() {
        /**
         * Update selection state
         */
        window.updateSelection = function() {
            selectedImages.clear();
            
            document.querySelectorAll('.image-select:checked').forEach(checkbox => {
                selectedImages.add(checkbox.value);
                checkbox.closest('.image-card').classList.add('selected');
            });
            
            document.querySelectorAll('.image-select:not(:checked)').forEach(checkbox => {
                checkbox.closest('.image-card').classList.remove('selected');
            });
            
            updateSelectionUI();
        };
        
        /**
         * Select all images
         */
        window.selectAllImages = function() {
            document.querySelectorAll('.image-select').forEach(checkbox => {
                checkbox.checked = true;
            });
            updateSelection();
        };
        
        /**
         * Deselect all images
         */
        window.deselectAllImages = function() {
            document.querySelectorAll('.image-select').forEach(checkbox => {
                checkbox.checked = false;
            });
            updateSelection();
        };
        
        /**
         * View image function
         */
        window.viewImage = function(imagePath) {
            if (viewImage && viewModal) {
                viewImage.src = imagePath;
                viewModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        };
        
        /**
         * Close view modal
         */
        window.closeViewModal = function() {
            if (viewModal) {
                viewModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        };
        
        /**
         * Confirm delete function
         */
        window.confirmDelete = function(filename) {
            const deleteFilename = document.getElementById('deleteFilename');
            const deleteMessage = document.getElementById('deleteMessage');
            
            if (deleteFilename) {
                deleteFilename.value = filename;
            }
            
            if (deleteMessage) {
                deleteMessage.textContent = `Are you sure you want to delete "${filename}"? This action cannot be undone.`;
            }
            
            if (deleteModal) {
                deleteModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        };
        
        /**
         * Close delete modal
         */
        window.closeDeleteModal = function() {
            if (deleteModal) {
                deleteModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        };
        
        /**
         * Confirm bulk delete
         */
        window.confirmBulkDelete = function() {
            if (selectedImages.size === 0) {
                showNotification('Please select images to delete.', 'error');
                return;
            }
            
            const bulkDeleteMessage = document.getElementById('bulkDeleteMessage');
            const selectedFilesPreview = document.getElementById('selectedFilesPreview');
            const bulkDeleteCount = document.getElementById('bulkDeleteCount');
            const bulkDeleteFilenames = document.getElementById('bulkDeleteFilenames');
            
            if (bulkDeleteMessage) {
                bulkDeleteMessage.textContent = `Are you sure you want to delete ${selectedImages.size} image(s)? This action cannot be undone.`;
            }
            
            if (bulkDeleteCount) {
                bulkDeleteCount.textContent = selectedImages.size;
            }
            
            // Show selected files
            if (selectedFilesPreview) {
                selectedFilesPreview.innerHTML = '';
                selectedImages.forEach(filename => {
                    const item = document.createElement('div');
                    item.className = 'selected-file-item';
                    item.textContent = filename;
                    selectedFilesPreview.appendChild(item);
                });
            }
            
            // Create hidden inputs for filenames
            if (bulkDeleteFilenames) {
                bulkDeleteFilenames.innerHTML = '';
                selectedImages.forEach(filename => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'filenames[]';
                    input.value = filename;
                    bulkDeleteFilenames.appendChild(input);
                });
            }
            
            if (bulkDeleteModal) {
                bulkDeleteModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        };
        
        /**
         * Close bulk delete modal
         */
        window.closeBulkDeleteModal = function() {
            if (bulkDeleteModal) {
                bulkDeleteModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        };
    }
    
    // Define global functions first
    defineGlobalFunctions();
    
    // Initialize functionality
    initializeUpload();
    initializeSelection();
    initializeModals();
    initializeMessages();
    
    /**
     * Initialize upload functionality
     */
    function initializeUpload() {
        if (!uploadArea || !imageInput) return;
        
        // Click to select files
        uploadArea.addEventListener('click', function(e) {
            if (e.target === uploadArea || e.target.closest('.upload-content')) {
                imageInput.click();
            }
        });
        
        // File input change
        imageInput.addEventListener('change', handleFileSelect);
        
        // Drag and drop events
        uploadArea.addEventListener('dragover', handleDragOver);
        uploadArea.addEventListener('dragleave', handleDragLeave);
        uploadArea.addEventListener('drop', handleDrop);
        
        // Clear files button
        if (clearFilesBtn) {
            clearFilesBtn.addEventListener('click', clearSelectedFiles);
        }
        
        // Form submission
        if (uploadForm) {
            uploadForm.addEventListener('submit', handleFormSubmit);
        }
    }
    
    /**
     * Handle file selection
     */
    function handleFileSelect(event) {
        const files = Array.from(event.target.files);
        processFiles(files);
    }
    
    /**
     * Handle drag over
     */
    function handleDragOver(e) {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    }
    
    /**
     * Handle drag leave
     */
    function handleDragLeave(e) {
        e.preventDefault();
        if (!uploadArea.contains(e.relatedTarget)) {
            uploadArea.classList.remove('dragover');
        }
    }
    
    /**
     * Handle file drop
     */
    function handleDrop(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        
        const files = Array.from(e.dataTransfer.files);
        processFiles(files);
    }
    
    /**
     * Process selected files
     */
    function processFiles(files) {
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        const maxFileSize = 5 * 1024 * 1024; // 5MB
        
        selectedFiles = []; // Reset selected files
        
        files.forEach(file => {
            // Validate file type
            if (!allowedTypes.includes(file.type)) {
                showNotification(`Invalid file type: ${file.name}`, 'error');
                return;
            }
            
            // Validate file size
            if (file.size > maxFileSize) {
                showNotification(`File too large: ${file.name} (max 5MB)`, 'error');
                return;
            }
            
            selectedFiles.push(file);
        });
        
        if (selectedFiles.length > 0) {
            displayFilePreview();
            updateUploadButton();
        }
    }
    
    /**
     * Display file preview
     */
    function displayFilePreview() {
        uploadPreviewGrid.innerHTML = '';
        
        selectedFiles.forEach((file, index) => {
            const previewItem = document.createElement('div');
            previewItem.className = 'upload-preview-item';
            
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.alt = file.name;
            
            const info = document.createElement('div');
            info.className = 'preview-info';
            
            const name = document.createElement('div');
            name.className = 'preview-name';
            name.textContent = file.name.length > 15 ? file.name.substring(0, 12) + '...' : file.name;
            name.title = file.name;
            
            const size = document.createElement('div');
            size.className = 'preview-size';
            size.textContent = formatFileSize(file.size);
            
            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-file';
            removeBtn.type = 'button';
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            removeBtn.onclick = () => removeFile(index);
            
            info.appendChild(name);
            info.appendChild(size);
            
            previewItem.appendChild(img);
            previewItem.appendChild(info);
            previewItem.appendChild(removeBtn);
            
            uploadPreviewGrid.appendChild(previewItem);
        });
        
        uploadPreviewContainer.style.display = 'block';
    }
    
    /**
     * Remove file from selection
     */
    function removeFile(index) {
        selectedFiles.splice(index, 1);
        
        if (selectedFiles.length === 0) {
            uploadPreviewContainer.style.display = 'none';
            imageInput.value = '';
        } else {
            displayFilePreview();
        }
        
        updateUploadButton();
    }
    
    /**
     * Clear all selected files
     */
    function clearSelectedFiles() {
        selectedFiles = [];
        uploadPreviewContainer.style.display = 'none';
        imageInput.value = '';
        updateUploadButton();
    }
    
    /**
     * Update upload button
     */
    function updateUploadButton() {
        if (fileCountSpan) {
            fileCountSpan.textContent = selectedFiles.length;
        }
        
        if (uploadBtn) {
            uploadBtn.disabled = selectedFiles.length === 0;
        }
    }
    
    /**
     * Handle form submission
     */
    function handleFormSubmit(e) {
        if (selectedFiles.length === 0) {
            e.preventDefault();
            showNotification('Please select at least one file to upload.', 'error');
            return;
        }
        
        // Create FormData with selected files
        const formData = new FormData();
        formData.append('action', 'upload');
        
        selectedFiles.forEach(file => {
            formData.append('images[]', file);
        });
        
        // Update form data
        const existingInputs = uploadForm.querySelectorAll('input[name="images[]"]');
        existingInputs.forEach(input => input.remove());
        
        selectedFiles.forEach(file => {
            const input = document.createElement('input');
            input.type = 'file';
            input.name = 'images[]';
            input.style.display = 'none';
            
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            input.files = dataTransfer.files;
            
            uploadForm.appendChild(input);
        });
        
        // Show loading state
        uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        uploadBtn.disabled = true;
    }
    
    
    /**
     * Initialize selection functionality
     */
    function initializeSelection() {
        // Add event listeners to existing checkboxes
        document.querySelectorAll('.image-select').forEach(checkbox => {
            checkbox.addEventListener('change', updateSelection);
        });
        
        // Select/Deselect all buttons
        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', selectAllImages);
        }
        
        if (deselectAllBtn) {
            deselectAllBtn.addEventListener('click', deselectAllImages);
        }
    }
    
    /**
     * Update selection UI
     */
    function updateSelectionUI() {
        const count = selectedImages.size;
        const totalImages = document.querySelectorAll('.image-select').length;
        
        if (selectedCount) {
            selectedCount.textContent = `${count} selected`;
        }
        
        if (bulkActions) {
            bulkActions.style.display = count > 0 ? 'flex' : 'none';
        }
        
        if (selectAllBtn && deselectAllBtn) {
            if (count === 0) {
                selectAllBtn.style.display = 'inline-block';
                deselectAllBtn.style.display = 'none';
            } else if (count === totalImages) {
                selectAllBtn.style.display = 'none';
                deselectAllBtn.style.display = 'inline-block';
            } else {
                selectAllBtn.style.display = 'inline-block';
                deselectAllBtn.style.display = 'inline-block';
            }
        }
    }
    
    
    /**
     * Initialize modals
     */
    function initializeModals() {
        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === deleteModal) {
                window.closeDeleteModal();
            }
            if (event.target === bulkDeleteModal) {
                window.closeBulkDeleteModal();
            }
            if (event.target === viewModal) {
                window.closeViewModal();
            }
        });
        
        // Keyboard navigation
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                if (deleteModal && deleteModal.style.display === 'block') {
                    window.closeDeleteModal();
                }
                if (bulkDeleteModal && bulkDeleteModal.style.display === 'block') {
                    window.closeBulkDeleteModal();
                }
                if (viewModal && viewModal.style.display === 'block') {
                    window.closeViewModal();
                }
            }
        });
    }
    
    /**
     * Initialize message handling
     */
    function initializeMessages() {
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
    }
    
    /**
     * Show notification
     */
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="fas ${type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
            ${message}
        `;
        
        document.body.appendChild(notification);
        
        // Position and show
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'error' ? '#f8d7da' : '#d4edda'};
            color: ${type === 'error' ? '#721c24' : '#155724'};
            border: 1px solid ${type === 'error' ? '#f5c6cb' : '#c3e6cb'};
            padding: 12px 16px;
            border-radius: 4px;
            z-index: 9999;
            max-width: 300px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;
        
        // Slide in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Auto remove
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
    
    /**
     * Format file size
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    /**
     * Initialize lazy loading for images
     */
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
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        // Clean up any ongoing uploads or operations
        const uploadingElements = document.querySelectorAll('.uploading');
        uploadingElements.forEach(function(element) {
            element.classList.remove('uploading');
        });
        
        // Revoke object URLs to prevent memory leaks
        selectedFiles.forEach(file => {
            if (file.preview) {
                URL.revokeObjectURL(file.preview);
            }
        });
    });
    
    // Initialize selection state on page load
    updateSelection();
});