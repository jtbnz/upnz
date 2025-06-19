// Examples Gallery JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Known example images (fallback list)
    const exampleImages = [
        'images/examples/example_001.jpg',
        'images/examples/example_002.jpg',
        'images/examples/example_003.jpg'
    ];
    
    let currentImages = [];
    let currentImageIndex = 0;
    
    // Load gallery images
    function loadGalleryImages() {
        const galleryGrid = document.getElementById('gallery-grid');
        const noImagesDiv = document.getElementById('no-images');
        
        // Try to load images dynamically, fallback to known list
        loadImagesFromDirectory()
            .then(images => {
                if (images.length > 0) {
                    currentImages = images;
                    displayImages(images);
                    noImagesDiv.style.display = 'none';
                } else {
                    // Fallback to known images
                    return checkKnownImages();
                }
            })
            .catch(() => {
                // Fallback to known images
                checkKnownImages();
            });
    }
    
    // Try to load images from directory (works when served via HTTP)
    function loadImagesFromDirectory() {
        return new Promise((resolve, reject) => {
            // This approach works when the page is served via HTTP server
            // For local file access, we'll use the fallback
            if (window.location.protocol === 'file:') {
                reject('File protocol detected');
                return;
            }
            
            // Try to fetch a directory listing (this would need server support)
            // For now, we'll use the known images as fallback
            reject('Dynamic loading not available');
        });
    }
    
    // Check which known images actually exist
    function checkKnownImages() {
        const promises = exampleImages.map(src => {
            return new Promise((resolve) => {
                const img = new Image();
                img.onload = () => resolve(src);
                img.onerror = () => resolve(null);
                img.src = src;
            });
        });
        
        Promise.all(promises).then(results => {
            const validImages = results.filter(src => src !== null);
            currentImages = validImages;
            
            if (validImages.length > 0) {
                displayImages(validImages);
                document.getElementById('no-images').style.display = 'none';
            } else {
                document.getElementById('no-images').style.display = 'block';
            }
        });
    }
    
    // Display images in the gallery
    function displayImages(images) {
        const galleryGrid = document.getElementById('gallery-grid');
        galleryGrid.innerHTML = '';
        
        images.forEach((imageSrc, index) => {
            const galleryItem = document.createElement('div');
            galleryItem.className = 'gallery-item';
            
            galleryItem.innerHTML = `
                <img src="${imageSrc}" alt="Upholstery example ${index + 1}" class="gallery-image" onclick="openLightbox('${imageSrc}', ${index})">
                <div class="gallery-overlay">
                    <i class="fas fa-search-plus"></i>
                </div>
            `;
            
            galleryGrid.appendChild(galleryItem);
        });
    }
    
    // Lightbox functionality
    window.openLightbox = function(imageSrc, index = 0) {
        const lightbox = document.getElementById('lightbox');
        const lightboxImage = document.getElementById('lightbox-image');
        
        currentImageIndex = index;
        lightboxImage.src = imageSrc;
        lightbox.style.display = 'flex';
        
        // Prevent body scrolling when lightbox is open
        document.body.style.overflow = 'hidden';
    };
    
    function closeLightbox() {
        const lightbox = document.getElementById('lightbox');
        lightbox.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    function showPreviousImage() {
        if (currentImages.length > 1) {
            currentImageIndex = (currentImageIndex - 1 + currentImages.length) % currentImages.length;
            document.getElementById('lightbox-image').src = currentImages[currentImageIndex];
        }
    }
    
    function showNextImage() {
        if (currentImages.length > 1) {
            currentImageIndex = (currentImageIndex + 1) % currentImages.length;
            document.getElementById('lightbox-image').src = currentImages[currentImageIndex];
        }
    }
    
    // Event listeners for lightbox
    document.querySelector('.lightbox-close').addEventListener('click', closeLightbox);
    document.getElementById('prevBtn').addEventListener('click', showPreviousImage);
    document.getElementById('nextBtn').addEventListener('click', showNextImage);
    
    // Close lightbox when clicking outside the image
    document.getElementById('lightbox').addEventListener('click', function(e) {
        if (e.target === this) {
            closeLightbox();
        }
    });
    
    // Keyboard navigation for lightbox
    document.addEventListener('keydown', function(e) {
        const lightbox = document.getElementById('lightbox');
        if (lightbox.style.display === 'flex') {
            switch(e.key) {
                case 'Escape':
                    closeLightbox();
                    break;
                case 'ArrowLeft':
                    showPreviousImage();
                    break;
                case 'ArrowRight':
                    showNextImage();
                    break;
            }
        }
    });
    
    // Initialize gallery
    loadGalleryImages();
});
