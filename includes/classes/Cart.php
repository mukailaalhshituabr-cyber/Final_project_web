<?php
    // Force an absolute path to the vendor folder
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
        /*public function getCartCount($amount) {
            try {
                $paymentIntent = \Stripe\PaymentIntent::create([
                    'amount' => $amount,
                    'currency' => 'usd',
                    'automatic_payment_methods' => [
                        'enabled' => true,
                    ],
                ]);
                
                return $paymentIntent->client_secret;
            } catch (Exception $e) {
                return null;
            }
        }*/
        
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