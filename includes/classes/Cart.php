<?php
require_once __DIR__ . '/../../vendor/autoload.php';

class Cart {
    private $stripe;
    private $secretKey;
    
    public function __construct() {

        // Ensure Stripe constant exists before trying to use it
        if (defined('STRIPE_SECRET_KEY')) {
            // If using Composer:
            // require_once __DIR__ . '/../../vendor/autoload.php';
            
            \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
        }
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
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
    
    public function getCartCount($amount) {
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