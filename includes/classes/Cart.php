<?php
class Cart {
    private $stripe;
    private $secretKey;
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Initialize Stripe if available
        if (defined('STRIPE_SECRET_KEY') && !empty(STRIPE_SECRET_KEY)) {
            $this->secretKey = STRIPE_SECRET_KEY;
            
            // Check for Stripe autoloader
            $autoloadPath = dirname(__DIR__, 2) . '/vendor/autoload.php';
            if (file_exists($autoloadPath)) {
                require_once $autoloadPath;
                \Stripe\Stripe::setApiKey($this->secretKey);
            }
        }
    }
    
    public function getCartItems() {
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            return [];
        }
        return $_SESSION['cart'];
    }
    
    public function addToCart($productId, $quantity = 1, $size = null, $color = null, $measurements = []) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        $cartItem = [
            'product_id' => $productId,
            'quantity' => $quantity,
            'size' => $size,
            'color' => $color,
            'measurements' => $measurements,
            'added_at' => time()
        ];
        
        $_SESSION['cart'][] = $cartItem;
        return true;
    }
    
    public function updateCartItem($index, $quantity, $size = null, $color = null, $measurements = []) {
        if (!isset($_SESSION['cart'][$index])) {
            return false;
        }
        
        $_SESSION['cart'][$index]['quantity'] = $quantity;
        if ($size !== null) $_SESSION['cart'][$index]['size'] = $size;
        if ($color !== null) $_SESSION['cart'][$index]['color'] = $color;
        if (!empty($measurements)) $_SESSION['cart'][$index]['measurements'] = $measurements;
        
        return true;
    }
    
    public function removeFromCart($index) {
        if (!isset($_SESSION['cart'][$index])) {
            return false;
        }
        
        array_splice($_SESSION['cart'], $index, 1);
        return true;
    }
    
    public function clearCart() {
        $_SESSION['cart'] = [];
        return true;
    }
    
    public function getCartCount() {
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            return 0;
        }
        return count($_SESSION['cart']);
    }
    
    public function getCartTotal($products) {
        $total = 0;
        if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
            return $total;
        }
        
        foreach ($_SESSION['cart'] as $item) {
            $productId = $item['product_id'];
            if (isset($products[$productId])) {
                $total += $products[$productId]['price'] * $item['quantity'];
            }
        }
        
        return $total;
    }
    
    public function processPayment($data) {
        try {
            if (!class_exists('\Stripe\PaymentIntent')) {
                return [
                    'success' => false,
                    'message' => 'Stripe payment system not available'
                ];
            }
            
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'payment_method' => $data['payment_method'],
                'confirmation_method' => 'manual',
                'confirm' => true,
                'metadata' => [
                    'order_id' => $data['order_id'],
                    'customer_id' => $_SESSION['user_id']
                ]
            ]);
            
            return [
                'success' => true,
                'transaction_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret
            ];
            
        } catch (\Stripe\Exception\CardException $e) {
            return [
                'success' => false,
                'message' => $e->getError()->message
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ];
        }
    }
    
    public function createCustomer($email, $name) {
        try {
            if (!class_exists('\Stripe\Customer')) {
                return null;
            }
            
            $customer = \Stripe\Customer::create([
                'email' => $email,
                'name' => $name,
                'metadata' => [
                    'user_id' => $_SESSION['user_id']
                ]
            ]);
            
            return $customer->id;
        } catch (Exception $e) {
            error_log("Stripe customer creation error: " . $e->getMessage());
            return null;
        }
    }
}
?>










<?php
/*    // Force an absolute path to the vendor folder
    $autoloadPath = dirname(__DIR__, 2) . '/vendor/autoload.php';

    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
    } else {
        // This will help you see the EXACT path PHP is looking for in your error logs
        error_log("CRITICAL: Autoload not found at: " . $autoloadPath);
    }
    class Cart {
        private $stripe;
        private $secretKey;
        
        public function __construct() {

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Check if Stripe was actually loaded by the autoloader
            if (class_exists('\Stripe\Stripe')) {
                if (defined('STRIPE_SECRET_KEY') && !empty(STRIPE_SECRET_KEY)) {
                    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
                }
            }
        }
        
        public function processPayment($data) {
            try {
                // Create PaymentIntent
                $paymentIntent = \Stripe\PaymentIntent::create([
                    'amount' => $data['amount'],
                    'currency' => $data['currency'],
                    'payment_method' => $data['payment_method'],
                    'confirmation_method' => 'manual',
                    'confirm' => true,
                    'return_url' => SITE_URL . '/pages/cart/payment-success.php',
                    'metadata' => [
                        'order_id' => $data['order_id'],
                        'customer_id' => $_SESSION['user_id']
                    ]
                ]);
                
                return [
                    'success' => true,
                    'transaction_id' => $paymentIntent->id,
                    'client_secret' => $paymentIntent->client_secret
                ];
                
            } catch (\Stripe\Exception\CardException $e) {
                return [
                    'success' => false,
                    'message' => $e->getError()->message
                ];
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Payment processing failed. Please try again.'
                ];
            }
        }
        
        public function getCartCount() {
            // The cart count should come from your session, not from Stripe
            if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                return count($_SESSION['cart']);
            }
            return 0;
        }
        
        
        public function createCustomer($email, $name) {
            try {
                $customer = \Stripe\Customer::create([
                    'email' => $email,
                    'name' => $name,
                    'metadata' => [
                        'user_id' => $_SESSION['user_id']
                    ]
                ]);
                
                return $customer->id;
            } catch (Exception $e) {
                return null;
            }
        }
        
        public function refundPayment($paymentIntentId) {
            try {
                $refund = \Stripe\Refund::create([
                    'payment_intent' => $paymentIntentId
                ]);
                
                return [
                    'success' => true,
                    'refund_id' => $refund->id
                ];
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }
    }
?>
*/