<?php
// PaymentProcessor.php

class PaymentProcessor {
    private $conn;
    private $orderId;
    private $amount;
    private $paymentMethod;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function setOrderDetails($orderId, $amount, $paymentMethod) {
        $this->orderId = $orderId;
        $this->amount = $amount;
        $this->paymentMethod = $paymentMethod;
    }
    
    public function processPayment($paymentData) {
        try {
            // Start transaction
            $this->conn->begin_transaction();
            
            // Get payment method ID
            $stmt = $this->conn->prepare("SELECT id FROM payment_methods WHERE code = ?");
            $stmt->bind_param("s", $this->paymentMethod);
            $stmt->execute();
            $result = $stmt->get_result();
            $paymentMethodId = $result->fetch_assoc()['id'];
            
            // Initialize payment status
            $status = 'pending';
            $responseCode = '';
            $responseMessage = '';
            $gatewayResponse = null;
            
            // Process based on payment method
            switch($this->paymentMethod) {
                case 'cod':
                    $status = 'pending';
                    $responseMessage = 'COD order placed successfully';
                    break;
                    
                case 'upi':
                    $validationResult = $this->validateUPIPayment($paymentData);
                    if ($validationResult['success']) {
                        $status = 'completed';
                        $responseMessage = 'UPI payment successful';
                        $gatewayResponse = json_encode([
                            'upi_id' => $paymentData['upi_id'],
                            'timestamp' => date('Y-m-d H:i:s')
                        ]);
                    } else {
                        throw new Exception($validationResult['message']);
                    }
                    break;
                    
                case 'card':
                    $validationResult = $this->validateCardPayment($paymentData);
                    if ($validationResult['success']) {
                        $status = 'completed';
                        $responseMessage = 'Card payment successful';
                        $gatewayResponse = json_encode([
                            'card_number' => substr($paymentData['card_number'], -4),
                            'card_type' => $this->detectCardType($paymentData['card_number']),
                            'timestamp' => date('Y-m-d H:i:s')
                        ]);
                    } else {
                        throw new Exception($validationResult['message']);
                    }
                    break;
                    
                default:
                    throw new Exception('Invalid payment method');
            }
            
            // Create transaction record
            $transactionId = $this->generateTransactionId();
            $stmt = $this->conn->prepare("
                INSERT INTO payment_transactions 
                (order_id, payment_method_id, transaction_id, amount, status, 
                response_code, response_message, gateway_response)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param(
                "iisdssss",
                $this->orderId,
                $paymentMethodId,
                $transactionId,
                $this->amount,
                $status,
                $responseCode,
                $responseMessage,
                $gatewayResponse
            );
            $stmt->execute();
            
            // Record status history
            $paymentTransactionId = $this->conn->insert_id;
            $stmt = $this->conn->prepare("
                INSERT INTO payment_status_history 
                (payment_transaction_id, previous_status, new_status, notes)
                VALUES (?, NULL, ?, ?)
            ");
            $stmt->bind_param("iss", $paymentTransactionId, $status, $responseMessage);
            $stmt->execute();
            
            // Update order status
            $orderStatus = ($status === 'completed') ? 'processing' : 'pending_payment';
            $stmt = $this->conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $orderStatus, $this->orderId);
            $stmt->execute();
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'status' => $status,
                'message' => $responseMessage
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function validateUPIPayment($paymentData) {
        // Validate UPI ID format
        if (!preg_match('/^[\w.-]+@[\w.-]+$/', $paymentData['upi_id'])) {
            return [
                'success' => false,
                'message' => 'Invalid UPI ID format'
            ];
        }
        
        // In a real implementation, you would:
        // 1. Connect to UPI payment gateway
        // 2. Initiate payment request
        // 3. Verify payment status
        
        return [
            'success' => true,
            'message' => 'UPI payment validated'
        ];
    }
    
    private function validateCardPayment($paymentData) {
        // Validate card number
        if (!preg_match('/^[0-9]{16}$/', str_replace(' ', '', $paymentData['card_number']))) {
            return [
                'success' => false,
                'message' => 'Invalid card number'
            ];
        }
        
        // Validate expiry date
        if (!preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $paymentData['card_expiry'])) {
            return [
                'success' => false,
                'message' => 'Invalid expiry date'
            ];
        }
        
        // Validate CVV
        if (!preg_match('/^[0-9]{3,4}$/', $paymentData['card_cvv'])) {
            return [
                'success' => false,
                'message' => 'Invalid CVV'
            ];
        }
        
        // In a real implementation, you would:
        // 1. Connect to payment gateway
        // 2. Tokenize card details
        // 3. Process payment
        // 4. Handle response
        
        return [
            'success' => true,
            'message' => 'Card payment validated'
        ];
    }
    
    private function detectCardType($cardNumber) {
        // Remove spaces and non-numeric characters
        $cardNumber = preg_replace('/\D/', '', $cardNumber);
        
        // Detect card type based on IIN ranges
        if (preg_match('/^4/', $cardNumber)) {
            return 'Visa';
        } elseif (preg_match('/^5[1-5]/', $cardNumber)) {
            return 'Mastercard';
        } elseif (preg_match('/^3[47]/', $cardNumber)) {
            return 'American Express';
        } else {
            return 'Unknown';
        }
    }
    
    private function generateTransactionId() {
        return uniqid('TXN') . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 4);
    }
}
<?php
// In your checkout processing code

require_once 'PaymentProcessor.php';

// When processing the order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // ... [previous validation code] ...

    if (empty($errors)) {
        try {
            $paymentProcessor = new PaymentProcessor($conn);
            $paymentProcessor->setOrderDetails($orderId, $totalAmount, $_POST['payment_method']);
            
            // Prepare payment data based on method
            $paymentData = [];
            switch($_POST['payment_method']) {
                case 'card':
                    $paymentData = [
                        'card_number' => $_POST['card_number'],
                        'card_expiry' => $_POST['card_expiry'],
                        'card_cvv' => $_POST['card_cvv']
                    ];
                    break;
                    
                case 'upi':
                    $paymentData = [
                        'upi_id' => $_POST['upi_id']
                    ];
                    break;
                    
                case 'cod':
                    $paymentData = [
                        'delivery_address' => $fullAddress
                    ];
                    break;
            }
            
            $paymentResult = $paymentProcessor->processPayment($paymentData);
            
            if ($paymentResult['success']) {
                // Clear cart and show success message
                unset($_SESSION['cart']);
                $success = "Order placed successfully! Transaction ID: " . $paymentResult['transaction_id'];
                
                // Redirect to success page
                $_SESSION['order_success'] = [
                    'order_id' => $orderId,
                    'transaction_id' => $paymentResult['transaction_id'],
                    'status' => $paymentResult['status'],
                    'message' => $paymentResult['message']
                ];
                header('Location: order-success.php');
                exit();
            } else {
                $errors[] = "Payment failed: " . $paymentResult['message'];
            }
            
        } catch (Exception $e) {
            $errors[] = "An error occurred: " . $e->getMessage();
        }
    }
}
?>