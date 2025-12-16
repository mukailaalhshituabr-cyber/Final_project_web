// ============================================
// MAIN JAVASCRIPT FILE - Global Functions
// ============================================

$(document).ready(function() {
    // Initialize Bootstrap components
    initBootstrapComponents();
    
    // Initialize global event handlers
    initGlobalEvents();
    
    // Initialize AJAX setup
    initAjaxSetup();
    
    // Initialize password toggles
    initPasswordToggles();
    
    // Initialize form validations
    initFormValidations();
    
    // Initialize cart functionality
    initCart();
    
    // Initialize notifications
    initNotifications();
    
    // Initialize file upload previews
    initFileUploads();
    
    // Initialize search functionality
    initSearch();
    
    // Initialize quantity controls
    initQuantityControls();
    
    // Initialize rating system
    initRatingSystem();
});

// ============================================
// BOOTSTRAP COMPONENTS INITIALIZATION
// ============================================
function initBootstrapComponents() {
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip({
        trigger: 'hover',
        placement: 'top'
    });
    
    // Initialize popovers
    $('[data-bs-toggle="popover"]').popover();
    
    // Initialize toast notifications
    $('.toast').toast({
        delay: 3000,
        animation: true
    });
    
    // Show toast if exists
    $('.toast').toast('show');
    
    // Initialize modals
    $('.modal').on('shown.bs.modal', function() {
        $(this).find('[autofocus]').focus();
    });
}

// ============================================
// GLOBAL EVENT HANDLERS
// ============================================
function initGlobalEvents() {
    // Confirm on delete actions
    $(document).on('click', '.confirm-delete', function(e) {
        e.preventDefault();
        const message = $(this).data('confirm') || 'Are you sure you want to delete this item?';
        const url = $(this).attr('href');
        
        if (confirm(message)) {
            window.location.href = url;
        }
    });
    
    // Confirm on logout
    $(document).on('click', '.logout-link', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = url;
        }
    });
    
    // Smooth scrolling for anchor links
    $(document).on('click', 'a[href^="#"]', function(e) {
        e.preventDefault();
        const target = $(this.getAttribute('href'));
        if (target.length) {
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 80
            }, 1000);
        }
    });
    
    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        $('.alert:not(.alert-permanent)').fadeOut('slow', function() {
            $(this).remove();
        });
    }, 5000);
    
    // Close alert on click
    $(document).on('click', '.alert .btn-close', function() {
        $(this).closest('.alert').fadeOut('slow', function() {
            $(this).remove();
        });
    });
    
    // Toggle mobile menu
    $(document).on('click', '.mobile-menu-toggle', function() {
        $('.mobile-menu').toggleClass('show');
    });
    
    // Close mobile menu when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.mobile-menu, .mobile-menu-toggle').length) {
            $('.mobile-menu').removeClass('show');
        }
    });
}

// ============================================
// AJAX SETUP
// ============================================
function initAjaxSetup() {
    // Set up AJAX defaults
    $.ajaxSetup({
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        beforeSend: function(xhr) {
            // Add loading indicator
            showLoading();
            
            // Get CSRF token from meta tag
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            if (csrfToken) {
                xhr.setRequestHeader('X-CSRF-Token', csrfToken);
            }
        },
        complete: function() {
            // Hide loading indicator
            hideLoading();
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error);
            showNotification('An error occurred. Please try again.', 'danger');
        }
    });
}

// ============================================
// PASSWORD TOGGLE FUNCTIONALITY
// ============================================
function initPasswordToggles() {
    $(document).on('click', '.password-toggle', function() {
        const input = $(this).closest('.input-group').find('input');
        const icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
            $(this).attr('title', 'Hide password');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
            $(this).attr('title', 'Show password');
        }
        
        // Update tooltip
        $(this).tooltip('hide').tooltip('show');
    });
}

// ============================================
// FORM VALIDATIONS
// ============================================
function initFormValidations() {
    // Email validation
    $.validator.addMethod('validEmail', function(value) {
        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        return emailRegex.test(value);
    }, 'Please enter a valid email address.');
    
    // Phone validation
    $.validator.addMethod('validPhone', function(value) {
        if (value === '') return true; // Optional field
        const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
        return phoneRegex.test(value);
    }, 'Please enter a valid phone number.');
    
    // Password strength
    $.validator.addMethod('strongPassword', function(value) {
        if (value === '') return true;
        return value.length >= 6;
    }, 'Password must be at least 6 characters.');
    
    // Initialize validation on forms with class 'validate-form'
    $('.validate-form').each(function() {
        $(this).validate({
            rules: {
                email: {
                    required: true,
                    email: true,
                    validEmail: true
                },
                password: {
                    required: true,
                    minlength: 6,
                    strongPassword: true
                },
                confirm_password: {
                    required: true,
                    equalTo: '#password'
                },
                phone: {
                    validPhone: true
                }
            },
            messages: {
                email: {
                    required: 'Please enter your email address',
                    email: 'Please enter a valid email address'
                },
                password: {
                    required: 'Please enter a password',
                    minlength: 'Password must be at least 6 characters'
                },
                confirm_password: {
                    required: 'Please confirm your password',
                    equalTo: 'Passwords do not match'
                }
            },
            errorElement: 'div',
            errorClass: 'invalid-feedback',
            highlight: function(element) {
                $(element).addClass('is-invalid').removeClass('is-valid');
            },
            unhighlight: function(element) {
                $(element).removeClass('is-invalid').addClass('is-valid');
            },
            errorPlacement: function(error, element) {
                if (element.parent('.input-group').length) {
                    error.insertAfter(element.parent());
                } else {
                    error.insertAfter(element);
                }
            },
            submitHandler: function(form) {
                // Disable submit button to prevent double submission
                $(form).find('button[type="submit"]').prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...'
                );
                form.submit();
            }
        });
    });
}

// ============================================
// CART FUNCTIONALITY
// ============================================
function initCart() {
    // Add to cart
    $(document).on('click', '.add-to-cart', function(e) {
        e.preventDefault();
        
        const productId = $(this).data('product-id');
        const quantity = $(this).data('quantity') || 1;
        
        addToCart(productId, quantity);
    });
    
    // Update cart quantity
    $(document).on('change', '.cart-quantity', function() {
        const cartItemId = $(this).data('cart-item-id');
        const quantity = $(this).val();
        
        updateCartItem(cartItemId, quantity);
    });
    
    // Remove from cart
    $(document).on('click', '.remove-from-cart', function(e) {
        e.preventDefault();
        
        const cartItemId = $(this).data('cart-item-id');
        
        if (confirm('Are you sure you want to remove this item from your cart?')) {
            removeFromCart(cartItemId);
        }
    });
    
    // Clear cart
    $(document).on('click', '.clear-cart', function(e) {
        e.preventDefault();
        
        if (confirm('Are you sure you want to clear your entire cart?')) {
            clearCart();
        }
    });
}

function addToCart(productId, quantity = 1) {
    $.ajax({
        url: 'api/cart.php?action=add',
        method: 'POST',
        data: {
            product_id: productId,
            quantity: quantity
        },
        success: function(response) {
            if (response.success) {
                updateCartCount(response.cart_count);
                updateCartTotal(response.cart_total);
                showNotification(response.message || 'Item added to cart!', 'success');
                
                // Update cart modal if open
                if ($('#cartModal').length) {
                    loadCartModal();
                }
            } else {
                showNotification(response.message || 'Failed to add item to cart', 'danger');
            }
        }
    });
}

function updateCartItem(cartItemId, quantity) {
    if (quantity < 1) {
        removeFromCart(cartItemId);
        return;
    }
    
    $.ajax({
        url: 'api/cart.php?action=update',
        method: 'POST',
        data: {
            cart_item_id: cartItemId,
            quantity: quantity
        },
        success: function(response) {
            if (response.success) {
                updateCartCount(response.cart_count);
                updateCartTotal(response.cart_total);
                showNotification('Cart updated!', 'success');
                
                // Update specific item total
                $(`.cart-item[data-id="${cartItemId}"] .item-total`).text('$' + response.item_total);
            }
        }
    });
}

function removeFromCart(cartItemId) {
    $.ajax({
        url: 'api/cart.php?action=remove',
        method: 'POST',
        data: {
            cart_item_id: cartItemId
        },
        success: function(response) {
            if (response.success) {
                updateCartCount(response.cart_count);
                updateCartTotal(response.cart_total);
                showNotification('Item removed from cart', 'info');
                
                // Remove item from DOM
                $(`.cart-item[data-id="${cartItemId}"]`).fadeOut(300, function() {
                    $(this).remove();
                    
                    // Check if cart is empty
                    if (response.cart_count === 0) {
                        $('.cart-items-container').html(
                            '<div class="empty-cart text-center py-5">' +
                            '<i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>' +
                            '<h5>Your cart is empty</h5>' +
                            '<p>Add some items to get started!</p>' +
                            '</div>'
                        );
                    }
                });
            }
        }
    });
}

function clearCart() {
    $.ajax({
        url: 'api/cart.php?action=clear',
        method: 'POST',
        success: function(response) {
            if (response.success) {
                updateCartCount(0);
                updateCartTotal('0.00');
                showNotification('Cart cleared', 'info');
                
                // Clear cart items
                $('.cart-items-container').html(
                    '<div class="empty-cart text-center py-5">' +
                    '<i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>' +
                    '<h5>Your cart is empty</h5>' +
                    '<p>Add some items to get started!</p>' +
                    '</div>'
                );
            }
        }
    });
}

function updateCartCount(count) {
    $('.cart-count').text(count).toggle(count > 0);
    
    // Update badge
    $('.cart-badge').text(count).toggle(count > 0);
}

function updateCartTotal(total) {
    $('.cart-total').text('$' + total);
}

function loadCartModal() {
    $.ajax({
        url: 'api/cart.php?action=get',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                $('#cartModal .modal-body').html(response.html);
            }
        }
    });
}

// ============================================
// NOTIFICATION SYSTEM
// ============================================
function initNotifications() {
    // Check for URL messages
    const urlParams = new URLSearchParams(window.location.search);
    const message = urlParams.get('message');
    const messageType = urlParams.get('type') || 'info';
    
    if (message) {
        showNotification(decodeURIComponent(message), messageType);
        
        // Clean URL (remove message parameters)
        cleanUrl();
    }
}

function showNotification(message, type = 'info', duration = 5000) {
    // Remove existing notifications
    $('.custom-notification').remove();
    
    // Create notification element
    const notification = $(
        '<div class="custom-notification alert alert-' + type + ' alert-dismissible fade show">' +
        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
        message +
        '</div>'
    );
    
    // Add to page
    $('body').append(notification);
    
    // Position it
    notification.css({
        position: 'fixed',
        top: '20px',
        right: '20px',
        zIndex: '9999',
        maxWidth: '400px',
        boxShadow: '0 4px 12px rgba(0,0,0,0.15)'
    });
    
    // Auto remove after duration
    setTimeout(function() {
        notification.alert('close');
    }, duration);
    
    // Add click to dismiss
    notification.on('click', function() {
        $(this).alert('close');
    });
}

function cleanUrl() {
    const url = new URL(window.location);
    url.searchParams.delete('message');
    url.searchParams.delete('type');
    window.history.replaceState({}, document.title, url.toString());
}

// ============================================
// FILE UPLOAD PREVIEWS
// ============================================
function initFileUploads() {
    $(document).on('change', '.file-upload', function() {
        const input = this;
        const previewContainer = $(this).data('preview');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                if (previewContainer) {
                    $(previewContainer).html(
                        '<img src="' + e.target.result + '" class="img-fluid rounded" style="max-height: 200px;">' +
                        '<button type="button" class="btn btn-sm btn-danger mt-2 remove-image">Remove</button>'
                    );
                }
            };
            
            reader.readAsDataURL(input.files[0]);
        }
    });
    
    $(document).on('click', '.remove-image', function() {
        const previewContainer = $(this).closest('.image-preview-container');
        const fileInput = $('.file-upload[data-preview="#' + previewContainer.attr('id') + '"]');
        
        previewContainer.html(
            '<div class="text-center text-muted py-4">' +
            '<i class="fas fa-image fa-3x mb-3"></i>' +
            '<p>No image selected</p>' +
            '</div>'
        );
        
        // Reset file input
        fileInput.val('');
    });
}

// ============================================
// SEARCH FUNCTIONALITY
// ============================================
function initSearch() {
    // Debounce search function
    let searchTimeout;
    
    $(document).on('keyup', '.live-search', function() {
        const searchTerm = $(this).val().trim();
        const searchUrl = $(this).data('search-url');
        
        clearTimeout(searchTimeout);
        
        if (searchTerm.length >= 2) {
            searchTimeout = setTimeout(function() {
                performSearch(searchTerm, searchUrl);
            }, 300);
        } else {
            clearSearchResults();
        }
    });
    
    // Clear search on escape
    $(document).on('keydown', '.live-search', function(e) {
        if (e.key === 'Escape') {
            clearSearchResults();
            $(this).val('');
        }
    });
    
    // Close search results when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.search-container, .live-search-results').length) {
            clearSearchResults();
        }
    });
}

function performSearch(term, url) {
    $.ajax({
        url: url,
        method: 'GET',
        data: { q: term },
        success: function(response) {
            if (response.success) {
                displaySearchResults(response.results, response.html);
            }
        }
    });
}

function displaySearchResults(results, html) {
    $('.live-search-results').html(html).show();
}

function clearSearchResults() {
    $('.live-search-results').html('').hide();
}

// ============================================
// QUANTITY CONTROLS
// ============================================
function initQuantityControls() {
    $(document).on('click', '.quantity-btn', function() {
        const input = $(this).closest('.quantity-control').find('.quantity-input');
        let value = parseInt(input.val()) || 0;
        const min = parseInt(input.attr('min')) || 1;
        const max = parseInt(input.attr('max')) || 999;
        
        if ($(this).hasClass('quantity-minus')) {
            value = Math.max(min, value - 1);
        } else if ($(this).hasClass('quantity-plus')) {
            value = Math.min(max, value + 1);
        }
        
        input.val(value).trigger('change');
    });
    
    $(document).on('change', '.quantity-input', function() {
        let value = parseInt($(this).val()) || 0;
        const min = parseInt($(this).attr('min')) || 1;
        const max = parseInt($(this).attr('max')) || 999;
        
        if (value < min) {
            value = min;
        } else if (value > max) {
            value = max;
        }
        
        $(this).val(value);
    });
}

// ============================================
// RATING SYSTEM
// ============================================
function initRatingSystem() {
    $(document).on('mouseover', '.star-rating .star', function() {
        const rating = $(this).data('rating');
        highlightStars(rating);
    });
    
    $(document).on('mouseleave', '.star-rating', function() {
        const currentRating = $(this).find('input[type="hidden"]').val() || 0;
        highlightStars(currentRating);
    });
    
    $(document).on('click', '.star-rating .star', function() {
        const rating = $(this).data('rating');
        const container = $(this).closest('.star-rating');
        
        container.find('input[type="hidden"]').val(rating);
        highlightStars(rating);
        
        // Trigger change event
        container.find('input[type="hidden"]').trigger('change');
    });
}

function highlightStars(rating) {
    $('.star-rating .star').each(function() {
        const starRating = $(this).data('rating');
        if (starRating <= rating) {
            $(this).addClass('active');
        } else {
            $(this).removeClass('active');
        }
    });
}

// ============================================
// LOADING INDICATORS
// ============================================
function showLoading(message = 'Loading...') {
    // Remove existing loader
    $('.global-loader').remove();
    
    // Create loader
    const loader = $(
        '<div class="global-loader">' +
        '<div class="loader-backdrop"></div>' +
        '<div class="loader-content">' +
        '<div class="spinner-border text-primary" role="status"></div>' +
        '<p class="mt-3">' + message + '</p>' +
        '</div>' +
        '</div>'
    );
    
    $('body').append(loader);
}

function hideLoading() {
    $('.global-loader').fadeOut(300, function() {
        $(this).remove();
    });
}

// ============================================
// UTILITY FUNCTIONS
// ============================================
function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function truncateText(text, maxLength = 100) {
    if (text.length <= maxLength) return text;
    return text.substr(0, maxLength) + '...';
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// ============================================
// FORM SUBMISSION HANDLERS
// ============================================
$(document).on('submit', 'form', function(e) {
    // Check for required fields
    const requiredFields = $(this).find('[required]');
    let valid = true;
    
    requiredFields.each(function() {
        if (!$(this).val().trim()) {
            valid = false;
            $(this).addClass('is-invalid');
            $(this).closest('.form-group').find('.invalid-feedback').text('This field is required');
        }
    });
    
    if (!valid) {
        e.preventDefault();
        showNotification('Please fill in all required fields', 'warning');
    }
});

// ============================================
// PAGE SPECIFIC INITIALIZATIONS
// ============================================
// Check current page and run specific initializations
const currentPage = window.location.pathname.split('/').pop().split('?')[0];

switch(currentPage) {
    case 'products':
    case 'index.php':
        if (window.location.search.includes('page=products')) {
            initProductFilters();
            initProductGrid();
        }
        break;
    case 'cart':
        initCheckoutProcess();
        break;
    case 'dashboard':
        initDashboardCharts();
        break;
}

function initProductFilters() {
    // Price range slider
    if ($('#priceRange').length) {
        const priceSlider = document.getElementById('priceRange');
        const priceOutput = document.getElementById('priceValue');
        
        noUiSlider.create(priceSlider, {
            start: [0, 1000],
            connect: true,
            range: {
                'min': 0,
                'max': 1000
            },
            step: 10
        });
        
        priceSlider.noUiSlider.on('update', function(values) {
            const minPrice = parseInt(values[0]);
            const maxPrice = parseInt(values[1]);
            priceOutput.textContent = `$${minPrice} - $${maxPrice}`;
            
            // Update hidden inputs
            $('#minPrice').val(minPrice);
            $('#maxPrice').val(maxPrice);
        });
    }
    
    // Apply filters
    $(document).on('click', '#applyFilters', function() {
        const filters = {
            category: $('#categoryFilter').val(),
            minPrice: $('#minPrice').val(),
            maxPrice: $('#maxPrice').val(),
            sortBy: $('#sortBy').val()
        };
        
        $.ajax({
            url: 'api/products.php?action=filter',
            method: 'GET',
            data: filters,
            success: function(response) {
                if (response.success) {
                    $('#productGrid').html(response.html);
                }
            }
        });
    });
    
    // Clear filters
    $(document).on('click', '#clearFilters', function() {
        $('#categoryFilter').val('');
        $('#sortBy').val('newest');
        
        if (priceSlider) {
            priceSlider.noUiSlider.set([0, 1000]);
        }
        
        $('#applyFilters').click();
    });
}

function initProductGrid() {
    // Lazy loading images
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img.lazy-load');
        
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy-load');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach((img) => imageObserver.observe(img));
    }
    
    // Product card hover effects
    $('.product-card').hover(
        function() {
            $(this).find('.product-actions').fadeIn(200);
        },
        function() {
            $(this).find('.product-actions').fadeOut(200);
        }
    );
}

function initCheckoutProcess() {
    // Address selection
    $(document).on('change', 'input[name="shipping_address"]', function() {
        const addressId = $(this).val();
        loadAddressDetails(addressId);
    });
    
    // Payment method selection
    $(document).on('change', 'input[name="payment_method"]', function() {
        const method = $(this).val();
        showPaymentDetails(method);
    });
    
    // Place order
    $(document).on('click', '#placeOrder', function() {
        const orderData = {
            shipping_address_id: $('input[name="shipping_address"]:checked').val(),
            payment_method: $('input[name="payment_method"]:checked').val(),
            notes: $('#orderNotes').val()
        };
        
        $.ajax({
            url: 'api/orders.php?action=create',
            method: 'POST',
            data: orderData,
            success: function(response) {
                if (response.success) {
                    window.location.href = '?page=order-confirmation&id=' + response.order_id;
                } else {
                    showNotification(response.message, 'danger');
                }
            }
        });
    });
}

function initDashboardCharts() {
    // Only initialize if charts are needed
    if ($('#salesChart').length && typeof Chart !== 'undefined') {
        const ctx = document.getElementById('salesChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Sales',
                    data: [12, 19, 3, 5, 2, 3],
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    borderWidth: 2,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
}

// ============================================
// WISHLIST FUNCTIONALITY
// ============================================
$(document).on('click', '.add-to-wishlist', function(e) {
    e.preventDefault();
    
    const productId = $(this).data('product-id');
    const isInWishlist = $(this).hasClass('in-wishlist');
    
    $.ajax({
        url: 'api/wishlist.php?action=' + (isInWishlist ? 'remove' : 'add'),
        method: 'POST',
        data: { product_id: productId },
        success: function(response) {
            if (response.success) {
                const button = $(`.add-to-wishlist[data-product-id="${productId}"]`);
                
                if (isInWishlist) {
                    button.removeClass('in-wishlist btn-danger').addClass('btn-outline-danger');
                    button.find('i').removeClass('fas').addClass('far');
                    button.attr('title', 'Add to wishlist');
                } else {
                    button.removeClass('btn-outline-danger').addClass('in-wishlist btn-danger');
                    button.find('i').removeClass('far').addClass('fas');
                    button.attr('title', 'Remove from wishlist');
                }
                
                showNotification(response.message, 'success');
                
                // Update wishlist count
                if (response.wishlist_count !== undefined) {
                    $('.wishlist-count').text(response.wishlist_count).toggle(response.wishlist_count > 0);
                }
            }
        }
    });
});

// ============================================
// INITIALIZATION COMPLETE
// ============================================
console.log('Main JavaScript initialized successfully');