<?php
// Determine current year for copyright
$currentYear = date('Y');
$siteName = defined('SITE_NAME') ? SITE_NAME : 'Clothing Marketplace';
?>

<footer class="footer mt-auto bg-dark text-white">
    <!-- Newsletter Section -->
    <div class="newsletter-section py-5" style="background: rgba(255,255,255,0.05);">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h4 class="fw-bold mb-2">Stay Updated</h4>
                    <p class="text-muted mb-0">Subscribe to our newsletter for the latest fashion trends and exclusive offers.</p>
                </div>
                <div class="col-lg-6">
                    <form id="newsletterForm" class="newsletter-form">
                        <div class="input-group">
                            <input type="email" 
                                   id="newsletterEmail"
                                   class="form-control form-control-lg" 
                                   placeholder="Enter your email address" 
                                   required
                                   style="border-radius: 8px 0 0 8px; border: none;">
                            <button class="btn btn-primary btn-lg" type="submit" 
                                    style="border-radius: 0 8px 8px 0;">
                                Subscribe
                            </button>
                        </div>
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" id="newsletterConsent" required>
                            <label class="form-check-label text-muted small" for="newsletterConsent">
                                I agree to receive marketing emails. You can unsubscribe at any time.
                            </label>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Footer -->
    <div class="container py-5">
        <div class="row">
            <!-- Brand & About Section -->
            <div class="col-lg-4 col-md-6 mb-5">
                <div class="footer-brand">
                    <h3 class="fw-bold mb-3">
                        <i class="bi bi-shop me-2"></i><?php echo htmlspecialchars($siteName); ?>
                    </h3>
                    <p class="text-muted mb-4">
                        Connecting customers with expert tailors for custom-made clothing. 
                        Quality fashion, perfect fit, delivered to your doorstep.
                    </p>
                    <div class="d-flex gap-3">
                        <div class="feature-icon" data-bs-toggle="tooltip" title="Secure Payment">
                            <i class="bi bi-shield-check fs-5"></i>
                            <span class="ms-2 small">Secure</span>
                        </div>
                        <div class="feature-icon" data-bs-toggle="tooltip" title="Quality Guarantee">
                            <i class="bi bi-award fs-5"></i>
                            <span class="ms-2 small">Quality</span>
                        </div>
                        <div class="feature-icon" data-bs-toggle="tooltip" title="Fast Delivery">
                            <i class="bi bi-truck fs-5"></i>
                            <span class="ms-2 small">Fast Delivery</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 mb-5">
                <h6 class="fw-bold mb-4 text-uppercase small">Quick Links</h6>
                <ul class="list-unstyled">
                    <li class="mb-3">
                        <a href="<?php echo SITE_URL; ?>" class="text-decoration-none text-muted footer-link">
                            <i class="bi bi-chevron-right me-2"></i>Home
                        </a>
                    </li>
                    <li class="mb-3">
                        <a href="<?php echo SITE_URL; ?>/pages/products/" class="text-decoration-none text-muted footer-link">
                            <i class="bi bi-chevron-right me-2"></i>Shop
                        </a>
                    </li>
                    <li class="mb-3">
                        <a href="<?php echo SITE_URL; ?>/pages/tailor/browse.php" class="text-decoration-none text-muted footer-link">
                            <i class="bi bi-chevron-right me-2"></i>Find Tailors
                        </a>
                    </li>
                    <li class="mb-3">
                        <a href="<?php echo SITE_URL; ?>/pages/how-it-works.php" class="text-decoration-none text-muted footer-link">
                            <i class="bi bi-chevron-right me-2"></i>How It Works
                        </a>
                    </li>
                    <li class="mb-3">
                        <a href="<?php echo SITE_URL; ?>/pages/contact.php" class="text-decoration-none text-muted footer-link">
                            <i class="bi bi-chevron-right me-2"></i>Contact Us
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Customer Support -->
            <div class="col-lg-3 col-md-6 mb-5">
                <h6 class="fw-bold mb-4 text-uppercase small">Support</h6>
                <ul class="list-unstyled">
                    <li class="mb-3">
                        <a href="<?php echo SITE_URL; ?>/pages/help/faq.php" class="text-decoration-none text-muted footer-link">
                            <i class="bi bi-chevron-right me-2"></i>FAQ
                        </a>
                    </li>
                    <li class="mb-3">
                        <a href="<?php echo SITE_URL; ?>/pages/help/shipping.php" class="text-decoration-none text-muted footer-link">
                            <i class="bi bi-chevron-right me-2"></i>Shipping & Delivery
                        </a>
                    </li>
                    <li class="mb-3">
                        <a href="<?php echo SITE_URL; ?>/pages/help/returns.php" class="text-decoration-none text-muted footer-link">
                            <i class="bi bi-chevron-right me-2"></i>Returns & Refunds
                        </a>
                    </li>
                    <li class="mb-3">
                        <a href="<?php echo SITE_URL; ?>/pages/help/privacy.php" class="text-decoration-none text-muted footer-link">
                            <i class="bi bi-chevron-right me-2"></i>Privacy Policy
                        </a>
                    </li>
                    <li class="mb-3">
                        <a href="<?php echo SITE_URL; ?>/pages/help/terms.php" class="text-decoration-none text-muted footer-link">
                            <i class="bi bi-chevron-right me-2"></i>Terms of Service
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Social Media & Contact -->
            <div class="col-lg-3 col-md-6 mb-5">
                <h6 class="fw-bold mb-4 text-uppercase small">Connect With Us</h6>
                
                <!-- Social Media Links -->
                <div class="mb-4">
                    <p class="text-muted mb-3">Follow us on social media:</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="https://facebook.com" target="_blank" class="social-icon">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="https://instagram.com" target="_blank" class="social-icon">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a href="https://twitter.com" target="_blank" class="social-icon">
                            <i class="bi bi-twitter"></i>
                        </a>
                        <a href="https://pinterest.com" target="_blank" class="social-icon">
                            <i class="bi bi-pinterest"></i>
                        </a>
                        <a href="https://youtube.com" target="_blank" class="social-icon">
                            <i class="bi bi-youtube"></i>
                        </a>
                        <a href="https://linkedin.com" target="_blank" class="social-icon">
                            <i class="bi bi-linkedin"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Contact Info -->
                <div>
                    <p class="text-muted mb-3">
                        <i class="bi bi-envelope me-2"></i>
                        <a href="mailto:support@<?php echo $_SERVER['HTTP_HOST'] ?? 'example.com'; ?>" 
                           class="text-decoration-none text-muted footer-link">
                            support@<?php echo $_SERVER['HTTP_HOST'] ?? 'example.com'; ?>
                        </a>
                    </p>
                    <p class="text-muted mb-3">
                        <i class="bi bi-telephone me-2"></i>
                        <a href="tel:+2348012345678" class="text-decoration-none text-muted footer-link">
                            +234 801 234 5678
                        </a>
                    </p>
                    <p class="text-muted mb-0">
                        <i class="bi bi-clock me-2"></i>
                        Mon-Fri: 9AM-6PM (WAT)
                    </p>
                </div>
            </div>
        </div>
        
        <hr class="my-4 border-secondary">
        
        <!-- Bottom Row -->
        <div class="row align-items-center">
            <div class="col-md-6 mb-3 mb-md-0">
                <p class="mb-0 text-muted">
                    &copy; <?php echo $currentYear; ?> <?php echo htmlspecialchars($siteName); ?>. All rights reserved.
                </p>
            </div>
            <div class="col-md-6">
                <div class="d-flex flex-column flex-md-row justify-content-md-end gap-4 align-items-center">
                    <!-- Payment Methods -->
                    <div class="d-flex gap-3 align-items-center">
                        <span class="text-muted small">We accept:</span>
                        <div class="d-flex gap-2">
                            <i class="bi bi-credit-card text-muted fs-5" title="Credit Cards"></i>
                            <i class="bi bi-paypal text-muted fs-5" title="PayPal"></i>
                            <i class="bi bi-bank text-muted fs-5" title="Bank Transfer"></i>
                            <i class="bi bi-currency-exchange text-muted fs-5" title="Mobile Money"></i>
                        </div>
                    </div>
                    
                    <!-- Back to Top -->
                    <button onclick="scrollToTop()" class="btn btn-sm btn-outline-light">
                        <i class="bi bi-arrow-up me-1"></i> Back to Top
                    </button>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- JavaScript -->
<script>
// Back to top function
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Newsletter subscription
document.getElementById('newsletterForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const email = document.getElementById('newsletterEmail').value;
    const consent = document.getElementById('newsletterConsent').checked;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address.');
        return false;
    }
    
    if (!consent) {
        alert('Please agree to receive marketing emails.');
        return false;
    }
    
    // Simulate AJAX submission
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Subscribing...';
    submitBtn.disabled = true;
    
    setTimeout(() => {
        alert('Thank you for subscribing to our newsletter!');
        document.getElementById('newsletterEmail').value = '';
        document.getElementById('newsletterConsent').checked = false;
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 1500);
    
    return false;
});
</script>

<style>
.footer {
    background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
    position: relative;
    overflow: hidden;
}

.footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2, #d53f8c);
}

.newsletter-section {
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.footer-brand h3 {
    font-size: 1.75rem;
    color: #fff;
}

.feature-icon {
    display: flex;
    align-items: center;
    padding: 0.5rem 1rem;
    background: rgba(255,255,255,0.05);
    border-radius: 8px;
    transition: all 0.3s ease;
    cursor: default;
}

.feature-icon:hover {
    background: rgba(255,255,255,0.1);
    transform: translateY(-2px);
}

.feature-icon i {
    color: #667eea;
}

.footer-link {
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
}

.footer-link:hover {
    color: #fff !important;
    transform: translateX(5px);
    text-decoration: none;
}

.footer-link i {
    font-size: 0.8rem;
    transition: all 0.3s ease;
}

.footer-link:hover i {
    color: #667eea;
}

.social-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.05);
    border-radius: 50%;
    color: #cbd5e0;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    text-decoration: none;
}

.social-icon:hover {
    background: #667eea;
    color: white;
    transform: translateY(-3px) scale(1.1);
}

.newsletter-form .form-control {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    color: white;
}

.newsletter-form .form-control:focus {
    background: rgba(255,255,255,0.15);
    border-color: #667eea;
    box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
    color: white;
}

.newsletter-form .form-control::placeholder {
    color: rgba(255,255,255,0.6);
}

.form-check-input:checked {
    background-color: #667eea;
    border-color: #667eea;
}

.form-check-label {
    font-size: 0.875rem;
}

.btn-outline-light:hover {
    background: rgba(255,255,255,0.1);
}

@media (max-width: 768px) {
    .footer {
        text-align: center;
    }
    
    .feature-icon {
        justify-content: center;
    }
    
    .footer-link {
        justify-content: center;
    }
    
    .footer-link i {
        display: none;
    }
}
</style>




<?php
/*// Determine current year for copyright
$currentYear = date('Y');
// Site name from config (if available)
$siteName = defined('SITE_NAME') ? SITE_NAME : 'Clothing Marketplace';
?>

<footer class="footer mt-auto py-5 bg-dark text-white">
    <div class="container">
        <div class="row">
            <!-- Brand & About Section -->
            <div class="col-lg-4 col-md-6 mb-4">
                <h5 class="fw-bold mb-3">
                    <i class="bi bi-shop me-2"></i><?php echo htmlspecialchars($siteName); ?>
                </h5>
                <p class="text-muted mb-4">
                    Connecting customers with expert tailors for custom-made clothing. 
                    Quality fashion, perfect fit, delivered to your doorstep.
                </p>
                <div class="d-flex gap-3">
                    <a href="#" class="text-white text-decoration-none" data-bs-toggle="tooltip" title="Secure Payment">
                        <i class="bi bi-shield-check fs-5"></i>
                    </a>
                    <a href="#" class="text-white text-decoration-none" data-bs-toggle="tooltip" title="Quality Guarantee">
                        <i class="bi bi-award fs-5"></i>
                    </a>
                    <a href="#" class="text-white text-decoration-none" data-bs-toggle="tooltip" title="Fast Delivery">
                        <i class="bi bi-truck fs-5"></i>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="fw-bold mb-3">Quick Links</h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="/index.php" class="text-decoration-none text-muted hover-white">
                            <i class="bi bi-house-door me-2"></i>Home
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/pages/tailor/browse.php" class="text-decoration-none text-muted hover-white">
                            <i class="bi bi-person-badge me-2"></i>Find Tailors
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/pages/how-it-works.php" class="text-decoration-none text-muted hover-white">
                            <i class="bi bi-question-circle me-2"></i>How It Works
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/pages/pricing.php" class="text-decoration-none text-muted hover-white">
                            <i class="bi bi-tag me-2"></i>Pricing
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/pages/contact.php" class="text-decoration-none text-muted hover-white">
                            <i class="bi bi-envelope me-2"></i>Contact Us
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Customer Support -->
            <div class="col-lg-3 col-md-6 mb-4">
                <h6 class="fw-bold mb-3">Customer Support</h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="/pages/help/faq.php" class="text-decoration-none text-muted hover-white">
                            <i class="bi bi-question-lg me-2"></i>FAQ
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/pages/help/shipping.php" class="text-decoration-none text-muted hover-white">
                            <i class="bi bi-truck me-2"></i>Shipping & Delivery
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/pages/help/returns.php" class="text-decoration-none text-muted hover-white">
                            <i class="bi bi-arrow-return-left me-2"></i>Returns & Refunds
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/pages/help/privacy.php" class="text-decoration-none text-muted hover-white">
                            <i class="bi bi-shield-lock me-2"></i>Privacy Policy
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/pages/help/terms.php" class="text-decoration-none text-muted hover-white">
                            <i class="bi bi-file-text me-2"></i>Terms of Service
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Social Media & Contact -->
            <div class="col-lg-3 col-md-6 mb-4">
                <h6 class="fw-bold mb-3">Connect With Us</h6>
                
                <!-- Social Media Links -->
                <div class="mb-4">
                    <p class="text-muted mb-3">Follow us on social media:</p>
                    <div class="d-flex gap-3">
                        <!-- Facebook -->
                        <a href="https://facebook.com/yourpage" 
                           target="_blank" 
                           class="social-icon bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                           style="width: 40px; height: 40px;"
                           data-bs-toggle="tooltip" title="Follow on Facebook">
                            <i class="bi bi-facebook fs-5"></i>
                        </a>
                        
                        <!-- Instagram -->
                        <a href="https://instagram.com/yourprofile" 
                           target="_blank" 
                           class="social-icon bg-instagram text-white rounded-circle d-flex align-items-center justify-content-center"
                           style="width: 40px; height: 40px; background: linear-gradient(45deg, #405DE6, #5851DB, #833AB4, #C13584, #E1306C, #FD1D1D);"
                           data-bs-toggle="tooltip" title="Follow on Instagram">
                            <i class="bi bi-instagram fs-5"></i>
                        </a>
                        
                        <!-- Twitter -->
                        <a href="https://twitter.com/yourhandle" 
                           target="_blank" 
                           class="social-icon bg-info text-white rounded-circle d-flex align-items-center justify-content-center"
                           style="width: 40px; height: 40px;"
                           data-bs-toggle="tooltip" title="Follow on Twitter">
                            <i class="bi bi-twitter fs-5"></i>
                        </a>
                        
                        <!-- Pinterest -->
                        <a href="https://pinterest.com/yourprofile" 
                           target="_blank" 
                           class="social-icon bg-danger text-white rounded-circle d-flex align-items-center justify-content-center"
                           style="width: 40px; height: 40px;"
                           data-bs-toggle="tooltip" title="Follow on Pinterest">
                            <i class="bi bi-pinterest fs-5"></i>
                        </a>
                        
                        <!-- YouTube -->
                        <a href="https://youtube.com/yourchannel" 
                           target="_blank" 
                           class="social-icon bg-danger text-white rounded-circle d-flex align-items-center justify-content-center"
                           style="width: 40px; height: 40px;"
                           data-bs-toggle="tooltip" title="Subscribe on YouTube">
                            <i class="bi bi-youtube fs-5"></i>
                        </a>
                        
                        <!-- LinkedIn -->
                        <a href="https://linkedin.com/company/yourcompany" 
                           target="_blank" 
                           class="social-icon bg-linkedin text-white rounded-circle d-flex align-items-center justify-content-center"
                           style="width: 40px; height: 40px; background-color: #0077B5;"
                           data-bs-toggle="tooltip" title="Follow on LinkedIn">
                            <i class="bi bi-linkedin fs-5"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Contact Info -->
                <div>
                    <p class="text-muted mb-2">
                        <i class="bi bi-envelope me-2"></i>
                        <a href="mailto:support@<?php echo isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'example.com'; ?>" 
                           class="text-decoration-none text-muted hover-white">
                            support@<?php echo isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'example.com'; ?>
                        </a>
                    </p>
                    <p class="text-muted mb-2">
                        <i class="bi bi-telephone me-2"></i>
                        <a href="tel:+1234567890" class="text-decoration-none text-muted hover-white">
                            +1 (234) 567-890
                        </a>
                    </p>
                    <p class="text-muted">
                        <i class="bi bi-clock me-2"></i>
                        Mon-Fri: 9AM-6PM
                    </p>
                </div>
            </div>
        </div>
        
        <hr class="my-4 border-secondary">
        
        <!-- Bottom Row -->
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0 text-muted">
                    &copy; <?php echo $currentYear; ?> <?php echo htmlspecialchars($siteName); ?>. All rights reserved.
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="d-flex justify-content-md-end gap-4">
                    <!-- Payment Methods -->
                    <div class="d-flex gap-2 align-items-center">
                        <span class="text-muted small me-2">We accept:</span>
                        <i class="bi bi-credit-card text-muted" title="Credit Cards"></i>
                        <i class="bi bi-paypal text-muted" title="PayPal"></i>
                        <i class="bi bi-bank text-muted" title="Bank Transfer"></i>
                    </div>
                    
                    <!-- Language/Currency Selector (Optional) -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" 
                                data-bs-toggle="dropdown">
                            <i class="bi bi-globe me-1"></i>English
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">English</a></li>
                            <li><a class="dropdown-item" href="#">French</a></li>
                            <li><a class="dropdown-item" href="#">Spanish</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Back to Top Button -->
        <button onclick="scrollToTop()" id="backToTop" class="btn btn-primary rounded-circle position-fixed bottom-3 end-3" 
                style="width: 50px; height: 50px; display: none; z-index: 1000;">
            <i class="bi bi-arrow-up"></i>
        </button>


        <!-- Newsletter Subscription -->
        <div class="col-lg-3 col-md-6 mb-4">
            <h6 class="fw-bold mb-3">Newsletter</h6>
            <p class="text-muted small mb-3">
                Subscribe to get updates on new tailors, promotions, and fashion tips.
            </p>
            <form onsubmit="return subscribeNewsletter()" class="newsletter-form">
                <div class="input-group mb-3">
                    <input type="email" 
                        id="newsletterEmail"
                        class="form-control form-control-sm" 
                        placeholder="Your email address" 
                        required>
                    <button class="btn btn-primary btn-sm" type="submit">
                        <i class="bi bi-send"></i>
                    </button>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="newsletterConsent" required>
                    <label class="form-check-label text-muted small" for="newsletterConsent">
                        I agree to receive marketing emails
                    </label>
                </div>
            </form>
        </div>
    </div>
</footer>

<!-- JavaScript for footer functionality -->
<script>
// Back to top button
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Show/hide back to top button
window.onscroll = function() {
    const backToTopBtn = document.getElementById('backToTop');
    if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
        backToTopBtn.style.display = 'block';
    } else {
        backToTopBtn.style.display = 'none';
    }
};

// Tooltip initialization
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Hover effect for links
document.addEventListener('DOMContentLoaded', function() {
    const links = document.querySelectorAll('.hover-white');
    links.forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.classList.add('text-white');
        });
        link.addEventListener('mouseleave', function() {
            this.classList.remove('text-white');
        });
    });
});

// Newsletter subscription (optional)
function subscribeNewsletter() {
    const email = document.getElementById('newsletterEmail').value;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address.');
        return false;
    }
    
    // In a real application, you would send this to your server
    alert('Thank you for subscribing to our newsletter!');
    document.getElementById('newsletterEmail').value = '';
    return false;
}
</script>

<style>
.footer {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%) !important;
}

.hover-white {
    transition: color 0.3s ease;
}

.hover-white:hover {
    color: white !important;
    text-decoration: underline !important;
}

.social-icon {
    transition: all 0.3s ease;
    text-decoration: none;
}

.social-icon:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

#backToTop {
    transition: all 0.3s ease;
}

#backToTop:hover {
    transform: scale(1.1);
}

.bg-linkedin {
    background-color: #0077B5 !important;
}

@media (max-width: 768px) {
    .footer {
        text-align: center;
    }
    
    .footer .text-md-end {
        text-align: center !important;
    }
    
    #backToTop {
        bottom: 20px;
        right: 20px;
        width: 45px;
        height: 45px;
    }
}
</style>
*/