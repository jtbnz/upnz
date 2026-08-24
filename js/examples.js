// Examples Gallery JavaScript for Lightbox and Reordering Functionality
document.addEventListener('DOMContentLoaded', function() {
    const lightbox = document.getElementById('lightbox');
    const lightboxImage = document.getElementById('lightbox-image');
    const galleryGrid = document.getElementById('galleryGrid');
    const reorderToggle = document.getElementById('reorderToggle');
    const reorderHint = document.querySelector('.reorder-hint');
    
    let galleryImages = document.querySelectorAll('.gallery-image');
    let isReorderMode = false;
    let draggedElement = null;
    
    // If there are no gallery images or no lightbox element, do nothing.
    if (galleryImages.length === 0 || !lightbox) {
        return;
    }
    
    // Create an array of image sources directly from the rendered images in the DOM.
    let imageSources = Array.from(galleryImages).map(img => img.src);
    let currentImageIndex = 0;

    // Make the openLightbox function globally accessible for the onclick attribute.
    // It now accepts an index instead of a source string for better reliability.
    window.openLightbox = function(index) {
        currentImageIndex = index;
        if (lightboxImage && imageSources[currentImageIndex]) {
            lightboxImage.src = imageSources[currentImageIndex];
            lightbox.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    };

    function closeLightbox() {
        lightbox.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function showNextImage() {
        currentImageIndex = (currentImageIndex + 1) % imageSources.length;
        lightboxImage.src = imageSources[currentImageIndex];
    }

    function showPreviousImage() {
        currentImageIndex = (currentImageIndex - 1 + imageSources.length) % imageSources.length;
        lightboxImage.src = imageSources[currentImageIndex];
    }

    // Attach event listeners for lightbox controls.
    const closeBtn = lightbox.querySelector('.lightbox-close');
    const nextBtn = lightbox.querySelector('#nextBtn');
    const prevBtn = lightbox.querySelector('#prevBtn');

    if (closeBtn) closeBtn.addEventListener('click', closeLightbox);
    if (nextBtn) nextBtn.addEventListener('click', showNextImage);
    if (prevBtn) prevBtn.addEventListener('click', showPreviousImage);

    // Close lightbox when clicking on the background overlay.
    lightbox.addEventListener('click', function(e) {
        if (e.target === this) {
            closeLightbox();
        }
    });

    // Add keyboard navigation (Escape, Left Arrow, Right Arrow).
    document.addEventListener('keydown', function(e) {
        if (lightbox.style.display === 'flex') {
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowRight') showNextImage();
            if (e.key === 'ArrowLeft') showPreviousImage();
        }
    });
    
    // Reordering functionality (admin only)
    if (reorderToggle) {
        reorderToggle.addEventListener('click', toggleReorderMode);
    }
    
    function toggleReorderMode() {
        isReorderMode = !isReorderMode;
        
        if (isReorderMode) {
            enableReorderMode();
        } else {
            disableReorderMode();
        }
    }
    
    function enableReorderMode() {
        // Update button text and style
        reorderToggle.innerHTML = '<i class="fas fa-check"></i> Finish Reordering';
        reorderToggle.classList.remove('btn-secondary');
        reorderToggle.classList.add('btn-primary');
        
        // Show hint
        if (reorderHint) {
            reorderHint.style.display = 'inline-block';
        }
        
        // Make gallery items draggable
        const galleryItems = document.querySelectorAll('.gallery-item');
        galleryItems.forEach(item => {
            item.draggable = true;
            item.classList.add('draggable');
            
            // Show drag handles
            const dragHandle = item.querySelector('.drag-handle');
            if (dragHandle) {
                dragHandle.style.display = 'block';
            }
            
            // Disable lightbox click during reorder
            item.style.pointerEvents = 'auto';
            item.onclick = null;
            
            // Add drag event listeners
            item.addEventListener('dragstart', handleDragStart);
            item.addEventListener('dragover', handleDragOver);
            item.addEventListener('dragenter', handleDragEnter);
            item.addEventListener('dragleave', handleDragLeave);
            item.addEventListener('drop', handleDrop);
            item.addEventListener('dragend', handleDragEnd);
        });
        
        // Add visual feedback to grid
        galleryGrid.classList.add('reorder-mode');
    }
    
    function disableReorderMode() {
        // Update button text and style
        reorderToggle.innerHTML = '<i class="fas fa-arrows-alt"></i> Enable Reordering';
        reorderToggle.classList.remove('btn-primary');
        reorderToggle.classList.add('btn-secondary');
        
        // Hide hint
        if (reorderHint) {
            reorderHint.style.display = 'none';
        }
        
        // Restore gallery items
        const galleryItems = document.querySelectorAll('.gallery-item');
        galleryItems.forEach((item, index) => {
            item.draggable = false;
            item.classList.remove('draggable');
            
            // Hide drag handles
            const dragHandle = item.querySelector('.drag-handle');
            if (dragHandle) {
                dragHandle.style.display = 'none';
            }
            
            // Restore lightbox functionality
            item.onclick = () => openLightbox(index);
            
            // Remove drag event listeners
            item.removeEventListener('dragstart', handleDragStart);
            item.removeEventListener('dragover', handleDragOver);
            item.removeEventListener('dragenter', handleDragEnter);
            item.removeEventListener('dragleave', handleDragLeave);
            item.removeEventListener('drop', handleDrop);
            item.removeEventListener('dragend', handleDragEnd);
        });
        
        // Remove visual feedback from grid
        galleryGrid.classList.remove('reorder-mode');
        
        // Update image sources for lightbox
        updateImageSources();
    }
    
    function handleDragStart(e) {
        draggedElement = this;
        this.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', this.outerHTML);
    }
    
    function handleDragEnter(e) {
        e.preventDefault();
        if (this !== draggedElement) {
            this.classList.add('drag-over');
        }
    }
    
    function handleDragLeave(e) {
        // Only remove if we're actually leaving the element
        if (!this.contains(e.relatedTarget)) {
            this.classList.remove('drag-over');
        }
    }
    
    function handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    }
    
    function handleDrop(e) {
        e.preventDefault();
        
        if (this !== draggedElement) {
            // Get all gallery items
            const items = Array.from(galleryGrid.querySelectorAll('.gallery-item'));
            const draggedIndex = items.indexOf(draggedElement);
            const targetIndex = items.indexOf(this);
            
            // Reorder DOM elements
            if (draggedIndex < targetIndex) {
                this.parentNode.insertBefore(draggedElement, this.nextSibling);
            } else {
                this.parentNode.insertBefore(draggedElement, this);
            }
            
            // Save new order
            saveImageOrder();
        }
        
        // Remove visual feedback
        this.classList.remove('drag-over');
    }
    
    function handleDragEnd(e) {
        // Remove visual feedback from all items
        const galleryItems = document.querySelectorAll('.gallery-item');
        galleryItems.forEach(item => {
            item.classList.remove('dragging', 'drag-over');
        });
        
        draggedElement = null;
    }
    
    function saveImageOrder() {
        const galleryItems = document.querySelectorAll('.gallery-item');
        const orderData = [];
        
        galleryItems.forEach((item, index) => {
            const filename = item.getAttribute('data-filename');
            if (filename) {
                orderData.push({
                    filename: filename,
                    order: index + 1
                });
            }
        });
        
        // Send AJAX request to save order
        fetch('ajax/reorder.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order: orderData
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Image order saved successfully!', 'success');
            } else {
                showNotification('Failed to save image order: ' + (data.error || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error saving image order:', error);
            showNotification('Error saving image order. Please try again.', 'error');
        });
    }
    
    function updateImageSources() {
        // Update image sources array for lightbox after reordering
        galleryImages = document.querySelectorAll('.gallery-image');
        imageSources = Array.from(galleryImages).map(img => img.src);
    }
    
    function showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span>${message}</span>
        `;
        
        // Style the notification
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#d4edda' : '#f8d7da'};
            color: ${type === 'success' ? '#155724' : '#721c24'};
            border: 1px solid ${type === 'success' ? '#c3e6cb' : '#f5c6cb'};
            padding: 12px 16px;
            border-radius: 4px;
            z-index: 9999;
            max-width: 300px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateX(100%);
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        `;
        
        document.body.appendChild(notification);
        
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
});
