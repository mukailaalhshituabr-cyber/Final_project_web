                // ============================================
        // PRODUCTS PAGE JAVASCRIPT
        // ============================================

        $(document).ready(function() {
            initProductFilters();
            initProductGrid();
            initProductSearch();
            initWishlistActions();
            initQuickView();
            initProductComparison();
            initImageZoom();
            initReviewSystem();
        });

        function initProductFilters() {
            // Price range slider
            if ($('#priceRange').length) {
                const priceSlider = document.getElementById('priceRange');
                const minPriceInput = $('#minPrice');
                const maxPriceInput = $('#maxPrice');
                const priceDisplay = $('#priceValue');
                
                const minPrice = parseInt(minPriceInput.val()) || 0;
                const maxPrice = parseInt(maxPriceInput.val()) || 1000;
                
                noUiSlider.create(priceSlider, {
                    start: [minPrice, maxPrice],
                    connect: true,
                    range: {
                        'min': 0,
                        'max': 1000
                    },
                    step: 10
                });
                
                priceSlider.noUiSlider.on('update', function(values) {
                    const min = Math.round(values[0]);
                    const max = Math.round(values[1]);
                    
                    minPriceInput.val(min);
                    maxPriceInput.val(max);
                    priceDisplay.text(`$${min} - $${max}`);
                });
            }
            
            // Category filter
            $(document).on('change', '#categoryFilter', function() {
                applyFilters();
            });
            
            // Size filter
            $(document).on('change', '.size-filter', function() {
                applyFilters();
            });
            
            // Color filter
            $(document).on('change', '.color-filter', function() {
                applyFilters();
            });
            
            // Sort by
            $(document).on('change', '#sortBy', function() {
                applyFilters();
            });
            
            // Apply filters button
            $(document).on('click', '#applyFilters', function() {
                applyFilters();
            });
            
            // Clear filters
            $(document).on('click', '#clearFilters', function() {
                $('.filter-input').val('');
                $('.filter-select').val('');
                $('.filter-checkbox').prop('checked', false);
                
                if (priceSlider) {
                    priceSlider.noUiSlider.set([0, 1000]);
                }
                
                applyFilters();
            });
            
            // Toggle filter sidebar on mobile
            $(document).on('click', '#toggleFilters', function() {
                $('#filterSidebar').toggleClass('show');
                $('.filter-overlay').toggleClass('show');
            });
            
            $(document).on('click', '.filter-overlay', function() {
                $('#filterSidebar').removeClass('show');
                $('.filter-overlay').removeClass('show');
            });
        }

        function applyFilters() {
            const filters = {
                category: $('#categoryFilter').val(),
                min_price: $('#minPrice').val(),
                max_price: $('#maxPrice').val(),
                size: $('.size-filter:checked').map(function() {
                    return $(this).val();
                }).get(),
                color: $('.color-filter:checked').map(function() {
                    return $(this).val();
                }).get(),
                sort_by: $('#sortBy').val(),
                search: $('#searchInput').val()
            };
            
            // Update URL without page reload
            const urlParams = new URLSearchParams(window.location.search);
            Object.keys(filters).forEach(key => {
                if (filters[key]) {
                    if (Array.isArray(filters[key])) {
                        urlParams.set(key, filters[key].join(','));
                    } else {
                        urlParams.set(key, filters[key]);
                    }
                } else {
                    urlParams.delete(key);
                }
            });
            
            const newUrl = window.location.pathname + '?' + urlParams.toString();
            window.history.replaceState({}, '', newUrl);
            
            // Load filtered products
            loadFilteredProducts(filters);
        }

        function loadFilteredProducts(filters) {
            showLoading('Loading products...');
            
            $.ajax({
                url: 'api/products.php?action=filter',
                method: 'GET',
                data: filters,
                success: function(response) {
                    if (response.success) {
                        $('#productGrid').html(response.html);
                        $('#productCount').text(response.count + ' products found');
                        initProductGrid();
                    }
                },
                complete: function() {
                    hideLoading();
                }
            });
        }

        function initProductGrid() {
            // Lazy load images
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
                    $(this).find('.product-actions').stop(true, true).fadeIn(200);
                    $(this).addClass('hover');
                },
                function() {
                    $(this).find('.product-actions').stop(true, true).fadeOut(200);
                    $(this).removeClass('hover');
                }
            );
            
            // Quick add to cart
            $(document).on('click', '.quick-add-to-cart', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const productId = $(this).data('product-id');
                const productCard = $(this).closest('.product-card');
                
                // Show loading state
                const button = $(this);
                const originalHtml = button.html();
                button.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span>'
                );
                
                $.ajax({
                    url: 'api/cart.php?action=add',
                    method: 'POST',
                    data: { product_id: productId, quantity: 1 },
                    success: function(response) {
                        if (response.success) {
                            // Show success animation
                            productCard.addClass('added-to-cart');
                            setTimeout(() => {
                                productCard.removeClass('added-to-cart');
                            }, 1000);
                            
                            // Update cart count
                            updateCartCount(response.cart_count);
                            showNotification('Added to cart!', 'success');
                        }
                    },
                    complete: function() {
                        button.prop('disabled', false).html(originalHtml);
                    }
                });
            });
        }

        function initProductSearch() {
            let searchTimeout;
            
            $('#searchInput').on('keyup', function() {
                const searchTerm = $(this).val().trim();
                
                clearTimeout(searchTimeout);
                
                if (searchTerm.length >= 2) {
                    searchTimeout = setTimeout(function() {
                        searchProducts(searchTerm);
                    }, 300);
                } else if (searchTerm.length === 0) {
                    applyFilters(); // Reload all products
                }
            });
            
            // Clear search
            $(document).on('click', '#clearSearch', function() {
                $('#searchInput').val('');
                applyFilters();
            });
        }

        function searchProducts(searchTerm) {
            $.ajax({
                url: 'api/products.php?action=search',
                method: 'GET',
                data: { q: searchTerm },
                success: function(response) {
                    if (response.success) {
                        $('#productGrid').html(response.html);
                        $('#productCount').text(response.count + ' products found');
                        initProductGrid();
                    }
                }
            });
        }

        function initWishlistActions() {
            // Toggle wishlist
            $(document).on('click', '.toggle-wishlist', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const button = $(this);
                const productId = button.data('product-id');
                const isInWishlist = button.hasClass('in-wishlist');
                
                $.ajax({
                    url: 'api/wishlist.php?action=' + (isInWishlist ? 'remove' : 'add'),
                    method: 'POST',
                    data: { product_id: productId },
                    success: function(response) {
                        if (response.success) {
                            if (isInWishlist) {
                                button.removeClass('in-wishlist btn-danger').addClass('btn-outline-danger');
                                button.find('i').removeClass('fas').addClass('far');
                                button.attr('title', 'Add to wishlist');
                            } else {
                                button.removeClass('btn-outline-danger').addClass('in-wishlist btn-danger');
                                button.find('i').removeClass('far').addClass('fas');
                                button.attr('title', 'Remove from wishlist');
                            }
                            
                            // Update wishlist count
                            updateWishlistCount(response.wishlist_count);
                            showNotification(response.message, 'success');
                        }
                    }
                });
            });
        }

        function updateWishlistCount(count) {
            $('.wishlist-count').text(count).toggle(count > 0);
        }

        function initQuickView() {
            $(document).on('click', '.quick-view', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const productId = $(this).data('product-id');
                
                $.ajax({
                    url: 'api/products.php?action=quick_view',
                    method: 'GET',
                    data: { id: productId },
                    success: function(response) {
                        if (response.success) {
                            $('#quickViewModal .modal-body').html(response.html);
                            $('#quickViewModal').modal('show');
                            
                            // Initialize product actions in modal
                            initProductActionsInModal();
                        }
                    }
                });
            });
        }

        function initProductActionsInModal() {
            // Re-initialize wishlist and cart buttons inside modal
            $('.toggle-wishlist, .add-to-cart').off('click').on('click', function(e) {
                e.preventDefault();
                
                const button = $(this);
                const productId = button.data('product-id');
                const action = button.hasClass('toggle-wishlist') ? 'wishlist' : 'cart';
                
                if (action === 'wishlist') {
                    const isInWishlist = button.hasClass('in-wishlist');
                    
                    $.ajax({
                        url: 'api/wishlist.php?action=' + (isInWishlist ? 'remove' : 'add'),
                        method: 'POST',
                        data: { product_id: productId },
                        success: function(response) {
                            if (response.success) {
                                if (isInWishlist) {
                                    button.removeClass('in-wishlist btn-danger').addClass('btn-outline-danger');
                                    button.find('i').removeClass('fas').addClass('far');
                                } else {
                                    button.removeClass('btn-outline-danger').addClass('in-wishlist btn-danger');
                                    button.find('i').removeClass('far').addClass('fas');
                                }
                                updateWishlistCount(response.wishlist_count);
                            }
                        }
                    });
                } else {
                    // Add to cart from modal
                    $.ajax({
                        url: 'api/cart.php?action=add',
                        method: 'POST',
                        data: { product_id: productId, quantity: 1 },
                        success: function(response) {
                            if (response.success) {
                                updateCartCount(response.cart_count);
                                showNotification('Added to cart!', 'success');
                                $('#quickViewModal').modal('hide');
                            }
                        }
                    });
                }
            });
        }

        function initProductComparison() {
            const compareProducts = [];
            const maxCompare = 4;
            
            // Add to comparison
            $(document).on('click', '.compare-product', function(e) {
                e.preventDefault();
                const productId = $(this).data('product-id');
                
                if (compareProducts.includes(productId)) {
                    // Remove from comparison
                    const index = compareProducts.indexOf(productId);
                    compareProducts.splice(index, 1);
                    $(this).removeClass('active').find('i').removeClass('fa-exchange-alt').addClass('fa-plus');
                } else {
                    // Add to comparison
                    if (compareProducts.length >= maxCompare) {
                        showNotification(`Maximum ${maxCompare} products can be compared`, 'warning');
                        return;
                    }
                    compareProducts.push(productId);
                    $(this).addClass('active').find('i').removeClass('fa-plus').addClass('fa-exchange-alt');
                }
                
                updateCompareButton();
            });
            
            // Compare button
            $(document).on('click', '#compareProducts', function(e) {
                e.preventDefault();
                if (compareProducts.length < 2) {
                    showNotification('Select at least 2 products to compare', 'warning');
                    return;
                }
                
                window.location.href = '?page=compare&ids=' + compareProducts.join(',');
            });
            
            function updateCompareButton() {
                const compareBtn = $('#compareProducts');
                const count = compareProducts.length;
                
                compareBtn.find('.count').text(count);
                compareBtn.toggleClass('disabled', count < 2);
            }
        }

        function initImageZoom() {
            // Product image zoom on hover
            $('.product-main-image').hover(function() {
                $(this).addClass('zoomed');
            }, function() {
                $(this).removeClass('zoomed');
            });
            
            // Thumbnail navigation
            $(document).on('click', '.product-thumbnail', function() {
                const mainImage = $(this).closest('.product-images').find('.product-main-image');
                const newSrc = $(this).data('large-src') || $(this).find('img').attr('src');
                
                // Update main image
                mainImage.attr('src', newSrc);
                
                // Update active thumbnail
                $(this).siblings().removeClass('active');
                $(this).addClass('active');
            });
        }

        function initReviewSystem() {
            // Star rating
            $(document).on('mouseover', '.star-rating .star', function() {
                const rating = $(this).data('rating');
                highlightStars(rating);
            });
            
            $(document).on('mouseleave', '.star-rating', function() {
                const currentRating = $(this).find('input[name="rating"]').val() || 0;
                highlightStars(currentRating);
            });
            
            $(document).on('click', '.star-rating .star', function() {
                const rating = $(this).data('rating');
                const container = $(this).closest('.star-rating');
                
                container.find('input[name="rating"]').val(rating);
                highlightStars(rating);
            });
            
            // Submit review
            $(document).on('submit', '#reviewForm', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const productId = $(this).data('product-id');
                
                $.ajax({
                    url: 'api/reviews.php?action=submit',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                    if (response.success) {
                        $('#reviewsContainer').append(response.html);
                        $(this).data('offset', offset + response.count);
                        
                        if (!response.has_more) {
                            $(this).hide();
                        }
                    }
                }
            });
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

function loadReviews(productId) {
    $.ajax({
        url: 'api/reviews.php?action=get',
        method: 'GET',
        data: { product_id: productId },
        success: function(response) {
            if (response.success) {
                $('#reviewsContainer').html(response.html);
            }
        }
    });
}

// Infinite scroll for products
let isLoading = false;
let page = 1;
let hasMore = true;

function initInfiniteScroll() {
    if ($('#infiniteScrollTrigger').length === 0) return;
    
    const observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && !isLoading && hasMore) {
            loadMoreProducts();
        }
    });
    
    observer.observe(document.getElementById('infiniteScrollTrigger'));
}

function loadMoreProducts() {
    if (isLoading || !hasMore) return;
    
    isLoading = true;
    page++;
    
    // Show loading indicator
    $('#infiniteScrollTrigger').html(
        '<div class="text-center py-3">' +
        '<div class="spinner-border text-primary"></div>' +
        '<p class="mt-2">Loading more products...</p>' +
        '</div>'
    );
    
    const filters = {
        page: page,
        category: $('#categoryFilter').val(),
        min_price: $('#minPrice').val(),
        max_price: $('#maxPrice').val(),
        sort_by: $('#sortBy').val()
    };
    
    $.ajax({
        url: 'api/products.php?action=load_more',
        method: 'GET',
        data: filters,
        success: function(response) {
            if (response.success) {
                $('#productGrid').append(response.html);
                initProductGrid();
                hasMore = response.has_more;
                
                if (!response.has_more) {
                    $('#infiniteScrollTrigger').html(
                        '<div class="text-center py-3">' +
                        '<p class="text-muted">No more products to load</p>' +
                        '</div>'
                    );
                } else {
                    $('#infiniteScrollTrigger').html(
                        '<div class="text-center py-3">' +
                        '<p class="text-muted">Scroll for more products</p>' +
                        '</div>'
                    );
                }
            }
        },
        complete: function() {
            isLoading = false;
        }
    });
}

// Product grid/list view toggle
$(document).on('click', '.view-toggle', function(e) {
    e.preventDefault();
    
    const viewType = $(this).data('view');
    $('.view-toggle').removeClass('active');
    $(this).addClass('active');
    
    localStorage.setItem('product_view', viewType);
    
    if (viewType === 'list') {
        $('#productGrid').addClass('list-view').removeClass('grid-view');
    } else {
        $('#productGrid').addClass('grid-view').removeClass('list-view');
    }
});

// Load saved view preference
const savedView = localStorage.getItem('product_view') || 'grid';
if (savedView === 'list') {
    $('.view-toggle[data-view="list"]').click();
}

// Product share functionality
$(document).on('click', '.share-product', function(e) {
    e.preventDefault();
    
    const productId = $(this).data('product-id');
    const productUrl = window.location.origin + '/?page=product&id=' + productId;
    const productTitle = $(this).data('product-title') || 'Check out this product';
    
    if (navigator.share) {
        navigator.share({
            title: productTitle,
            text: 'I found this amazing product!',
            url: productUrl
        });
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(productUrl).then(() => {
            showNotification('Link copied to clipboard!', 'success');
        });
    }
});

// Initialize on page load
$(document).ready(function() {
    initInfiniteScroll();
});