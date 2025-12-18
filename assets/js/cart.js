// ============================================
// CART FUNCTIONALITY JAVASCRIPT
// ============================================

$(document).ready(function() {
    initCartPage();
    initCheckoutProcess();
    initPaymentMethods();
    initAddressSelection();
    initCouponCodes();
});

function initCartPage() {
    // Update cart totals on quantity change
    $(document).on('change', '.cart-item-quantity', function() {
        updateCartItem($(this).data('item-id'), $(this).val());
    });
    
    // Remove item from cart
    $(document).on('click', '.remove-cart-item', function(e) {
        e.preventDefault();
        const itemId = $(this).data('item-id');
        removeCartItem(itemId);
    });
    
    // Move to wishlist
    $(document).on('click', '.move-to-wishlist', function(e) {
        e.preventDefault();
        const itemId = $(this).data('item-id');
        moveToWishlist(itemId);
    });
    
    // Clear entire cart
    $(document).on('click', '#clearCart', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to clear your entire cart?')) {
            clearCart();
        }
    });
    
    // Update cart totals initially
    updateCartTotals();
}

function updateCartItem(itemId, quantity) {
    if (quantity < 1) {
        removeCartItem(itemId);
        return;
    }
    
    $.ajax({
        url: 'api/cart.php?action=update',
        method: 'POST',
        data: {
            item_id: itemId,
            quantity: quantity
        },
        success: function(response) {
            if (response.success) {
                updateCartDisplay(response);
                showNotification('Cart updated successfully', 'success');
            }
        }
    });
}

function removeCartItem(itemId) {
    if (confirm('Remove this item from cart?')) {
        $.ajax({
            url: 'api/cart.php?action=remove',
            method: 'POST',
            data: { item_id: itemId },
            success: function(response) {
                if (response.success) {
                    $(`.cart-item[data-id="${itemId}"]`).fadeOut(300, function() {
                        $(this).remove();
                        updateCartDisplay(response);
                        checkEmptyCart();
                    });
                    showNotification('Item removed from cart', 'info');
                }
            }
        });
    }
}

function moveToWishlist(itemId) {
    $.ajax({
        url: 'api/wishlist.php?action=add_from_cart',
        method: 'POST',
        data: { item_id: itemId },
        success: function(response) {
            if (response.success) {
                removeCartItem(itemId);
                showNotification('Item moved to wishlist', 'success');
                updateWishlistCount(response.wishlist_count);
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
                $('.cart-item').fadeOut(300, function() {
                    $(this).remove();
                    checkEmptyCart();
                });
                updateCartDisplay(response);
                showNotification('Cart cleared', 'info');
            }
        }
    });
}

function updateCartDisplay(response) {
    // Update individual item totals
    if (response.items) {
        response.items.forEach(function(item) {
            $(`.cart-item[data-id="${item.id}"] .item-total`).text('$' + item.total);
        });
    }
    
    // Update cart totals
    updateCartTotals(response.totals);
    
    // Update cart count in navbar
    updateCartCount(response.cart_count);
}

function updateCartTotals(totals) {
    if (totals) {
        $('#subtotal').text('$' + totals.subtotal);
        $('#shipping').text('$' + totals.shipping);
        $('#tax').text('$' + totals.tax);
        $('#total').text('$' + totals.total);
        
        if (totals.discount > 0) {
            $('#discount-row').show();
            $('#discount').text('-$' + totals.discount);
        } else {
            $('#discount-row').hide();
        }
    }
}

function checkEmptyCart() {
    if ($('.cart-item').length === 0) {
        $('.cart-items-container').html(`
            <div class="empty-cart text-center py-5">
                <i class="fas fa-shopping-cart fa-4x text-muted mb-4"></i>
                <h3>Your cart is empty</h3>
                <p class="text-muted mb-4">Looks like you haven't added any items to your cart yet.</p>
                <a href="?page=products" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag me-2"></i> Start Shopping
                </a>
            </div>
        `);
        $('.cart-actions, .checkout-section').hide();
    }
}

function initCheckoutProcess() {
    // Proceed to checkout
    $(document).on('click', '#proceedToCheckout', function(e) {
        e.preventDefault();
        
        // Validate cart has items
        if ($('.cart-item').length === 0) {
            showNotification('Your cart is empty', 'warning');
            return;
        }
        
        window.location.href = '?page=checkout';
    });
    
    // Continue shopping
    $(document).on('click', '#continueShopping', function(e) {
        e.preventDefault();
        window.location.href = '?page=products';
    });
}

function initPaymentMethods() {
    // Payment method selection
    $(document).on('change', 'input[name="payment_method"]', function() {
        const method = $(this).val();
        showPaymentDetails(method);
    });
    
    // Card payment form
    $(document).on('click', '#saveCard', function() {
        if (!validateCardForm()) {
            return false;
        }
        // Save card logic here
    });
}

function showPaymentDetails(method) {
    $('.payment-details').hide();
    $(`#${method}-details`).show();
    
    // Highlight selected method
    $('.payment-method-card').removeClass('selected');
    $(`.payment-method-card[data-method="${method}"]`).addClass('selected');
}

function validateCardForm() {
    const cardNumber = $('#card_number').val().replace(/\s/g, '');
    const expiry = $('#card_expiry').val();
    const cvv = $('#card_cvv').val();
    const name = $('#card_name').val();
    
    let isValid = true;
    
    // Validate card number
    if (!/^\d{16}$/.test(cardNumber)) {
        showInputError('card_number', 'Please enter a valid 16-digit card number');
        isValid = false;
    } else {
        clearInputError('card_number');
    }
    
    // Validate expiry date
    if (!/^\d{2}\/\d{2}$/.test(expiry)) {
        showInputError('card_expiry', 'Please enter expiry date in MM/YY format');
        isValid = false;
    } else {
        clearInputError('card_expiry');
    }
    
    // Validate CVV
    if (!/^\d{3,4}$/.test(cvv)) {
        showInputError('card_cvv', 'Please enter a valid CVV');
        isValid = false;
    } else {
        clearInputError('card_cvv');
    }
    
    // Validate name
    if (!name.trim()) {
        showInputError('card_name', 'Please enter name on card');
        isValid = false;
    } else {
        clearInputError('card_name');
    }
    
    return isValid;
}

function initAddressSelection() {
    // Add new address
    $(document).on('click', '#addNewAddress', function() {
        $('#addressModal').modal('show');
    });
    
    // Select address
    $(document).on('change', 'input[name="shipping_address"]', function() {
        const addressId = $(this).val();
        if (addressId === 'new') {
            $('#addressModal').modal('show');
        } else {
            loadAddressDetails(addressId);
        }
    });
    
    // Save address
    $(document).on('submit', '#addressForm', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'api/address.php?action=save',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#addressModal').modal('hide');
                    showNotification('Address saved successfully', 'success');
                    loadAddressList();
                }
            }
        });
    });
}

function loadAddressDetails(addressId) {
    $.ajax({
        url: 'api/address.php?action=get',
        method: 'GET',
        data: { id: addressId },
        success: function(response) {
            if (response.success) {
                $('#addressPreview').html(response.html);
            }
        }
    });
}

function loadAddressList() {
    $.ajax({
        url: 'api/address.php?action=list',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                $('#addressList').html(response.html);
            }
        }
    });
}

function initCouponCodes() {
    // Apply coupon
    $(document).on('click', '#applyCoupon', function() {
        const couponCode = $('#coupon_code').val().trim();
        
        if (!couponCode) {
            showNotification('Please enter a coupon code', 'warning');
            return;
        }
        
        $.ajax({
            url: 'api/cart.php?action=apply_coupon',
            method: 'POST',
            data: { coupon_code: couponCode },
            success: function(response) {
                if (response.success) {
                    updateCartDisplay(response);
                    showNotification('Coupon applied successfully', 'success');
                    $('#couponApplied').show().find('.coupon-code').text(couponCode);
                    $('#applyCoupon').hide();
                    $('#removeCoupon').show();
                } else {
                    showNotification(response.message || 'Invalid coupon code', 'danger');
                }
            }
        });
    });
    
    // Remove coupon
    $(document).on('click', '#removeCoupon', function() {
        $.ajax({
            url: 'api/cart.php?action=remove_coupon',
            method: 'POST',
            success: function(response) {
                if (response.success) {
                    updateCartDisplay(response);
                    showNotification('Coupon removed', 'info');
                    $('#couponApplied').hide();
                    $('#applyCoupon').show();
                    $('#removeCoupon').hide();
                    $('#coupon_code').val('');
                }
            }
        });
    });
}

// Place order
$(document).on('click', '#placeOrder', function(e) {
    e.preventDefault();
    
    // Validate required fields
    if (!validateCheckoutForm()) {
        showNotification('Please fill in all required fields', 'warning');
        return;
    }
    
    const button = $(this);
    const originalText = button.html();
    button.prop('disabled', true).html(
        '<span class="spinner-border spinner-border-sm me-2"></span>Processing...'
    );
    
    const orderData = {
        shipping_address_id: $('input[name="shipping_address"]:checked').val(),
        billing_address_id: $('input[name="billing_address_same"]:checked').val() ? 'same' : $('input[name="billing_address"]:checked').val(),
        payment_method: $('input[name="payment_method"]:checked').val(),
        notes: $('#order_notes').val()
    };
    
    // Add payment details if needed
    if (orderData.payment_method === 'card') {
        orderData.card_number = $('#card_number').val();
        orderData.card_expiry = $('#card_expiry').val();
        orderData.card_cvv = $('#card_cvv').val();
        orderData.card_name = $('#card_name').val();
    }
    
    $.ajax({
        url: 'api/orders.php?action=create',
        method: 'POST',
        data: orderData,
        success: function(response) {
            if (response.success) {
                showNotification('Order placed successfully!', 'success');
                setTimeout(function() {
                    window.location.href = '?page=order-confirmation&id=' + response.order_id;
                }, 1500);
            } else {
                showNotification(response.message || 'Failed to place order', 'danger');
                button.prop('disabled', false).html(originalText);
            }
        },
        error: function() {
            showNotification('Network error. Please try again.', 'danger');
            button.prop('disabled', false).html(originalText);
        }
    });
});

function validateCheckoutForm() {
    let isValid = true;
    
    // Check shipping address
    if (!$('input[name="shipping_address"]:checked').length) {
        isValid = false;
    }
    
    // Check payment method
    if (!$('input[name="payment_method"]:checked').length) {
        isValid = false;
    }
    
    // Validate payment details if card selected
    if ($('input[name="payment_method"]:checked').val() === 'card') {
        isValid = isValid && validateCardForm();
    }
    
    return isValid;
}

// Real-time shipping calculator
function calculateShipping(zipCode) {
    if (!zipCode || zipCode.length < 5) return;
    
    $.ajax({
        url: 'api/shipping.php?action=calculate',
        method: 'POST',
        data: { zip_code: zipCode },
        success: function(response) {
            if (response.success) {
                $('#shippingOptions').html(response.html);
                updateCartTotals(response.totals);
            }
        }
    });
}

// Initialize shipping calculator
$('#shipping_zip').on('blur', function() {
    calculateShipping($(this).val());
});