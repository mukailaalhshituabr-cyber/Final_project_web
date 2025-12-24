<?php
class StripePayment {
    private $stripe;
    private $secretKey;
    
    public function __construct() {
        if (!defined('STRIPE_SECRET_KEY') || empty(STRIPE_SECRET_KEY)) {
            throw new Exception('Stripe secret key not configured');
        }
        
        $this->secretKey = STRIPE_SECRET_KEY;
        
        // Load Stripe library
        $autoloadPath = dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (!file_exists($autoloadPath)) {
            throw new Exception('Stripe PHP library not found. Please install via Composer.');
        }
        
        require_once $autoloadPath;
        \Stripe\Stripe::setApiKey($this->secretKey);
    }
    
    public function createPaymentIntent($amount, $currency = 'usd', $metadata = []) {
        try {
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $amount,
                'currency' => $currency,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => $metadata
            ]);
            
            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function confirmPayment($paymentIntentId, $paymentMethodId = null) {
        try {
            $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
            
            if ($paymentMethodId) {
                $paymentIntent->confirm([
                    'payment_method' => $paymentMethodId,
                    'return_url' => SITE_URL . '/pages/cart/payment-success.php'
                ]);
            } else {
                $paymentIntent->confirm();
            }
            
            return [
                'success' => true,
                'status' => $paymentIntent->status,
                'payment_intent' => $paymentIntent
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function getPaymentIntent($paymentIntentId) {
        try {
            return \Stripe\PaymentIntent::retrieve($paymentIntentId);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return null;
        }
    }
    
    public function createCustomer($email, $name, $metadata = []) {
        try {
            $customer = \Stripe\Customer::create([
                'email' => $email,
                'name' => $name,
                'metadata' => $metadata
            ]);
            
            return $customer->id;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log("Stripe customer creation error: " . $e->getMessage());
            return null;
        }
    }
    
    public function refundPayment($paymentIntentId, $amount = null) {
        try {
            $refundData = ['payment_intent' => $paymentIntentId];
            
            if ($amount !== null) {
                $refundData['amount'] = $amount;
            }
            
            $refund = \Stripe\Refund::create($refundData);
            
            return [
                'success' => true,
                'refund_id' => $refund->id,
                'status' => $refund->status
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function listCharges($customerId = null, $limit = 10) {
        try {
            $params = ['limit' => $limit];
            
            if ($customerId) {
                $params['customer'] = $customerId;
            }
            
            return \Stripe\Charge::all($params);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return null;
        }
    }
}
?>




<?php
/*require_once __DIR__ . '/../../vendor/autoload.php';

class StripePayment {
    private $stripe;
    private $secretKey;
    
    public function __construct() {
        $this->secretKey = STRIPE_SECRET_KEY;
        \Stripe\Stripe::setApiKey($this->secretKey);
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
    
    public function getClientSecret($amount) {
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
*/