<?php
/**
 * Heal2Rise - Donation Processing API
 * Handles donation processing and payment gateway integration
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/config.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    $data = $_POST;
}

// Validate required fields
if (!isset($data['amount']) || empty($data['amount'])) {
    sendJSONResponse(false, 'Donation amount is required');
}

if (!isset($data['payment_method']) || empty($data['payment_method'])) {
    sendJSONResponse(false, 'Payment method is required');
}

// Process amount
$amount = $data['amount'];
if ($amount === 'custom' && isset($data['custom_amount'])) {
    $amount = floatval($data['custom_amount']);
} else {
    $amount = floatval($amount);
}

if ($amount < 100) {
    sendJSONResponse(false, 'Minimum donation amount is ₨ 100');
}

// Sanitize inputs
$donationType = isset($data['donation_type']) ? sanitizeInput($data['donation_type']) : 'platform';
$ngoId = isset($data['ngo_id']) ? intval($data['ngo_id']) : null;
$donorName = isset($data['donor_name']) ? sanitizeInput($data['donor_name']) : 'Anonymous';
$donorEmail = isset($data['donor_email']) ? sanitizeInput($data['donor_email']) : null;
$donorPhone = isset($data['donor_phone']) ? sanitizeInput($data['donor_phone']) : null;
$paymentMethod = sanitizeInput($data['payment_method']);
$message = isset($data['message']) ? sanitizeInput($data['message']) : null;
$isAnonymous = isset($data['is_anonymous']) ? true : false;

if ($isAnonymous) {
    $donorName = 'Anonymous';
}

$database = new Database();
$db = $database->getConnection();

try {
    // Generate transaction ID
    $transactionId = 'DON' . time() . rand(1000, 9999);
    
    // Insert donation record
    $query = "INSERT INTO donations (donor_name, donor_email, donor_phone, ngo_id, amount, currency, 
              payment_method, transaction_id, payment_status, message, is_anonymous, donation_date) 
              VALUES (:donor_name, :donor_email, :donor_phone, :ngo_id, :amount, 'PKR', 
              :payment_method, :transaction_id, 'pending', :message, :is_anonymous, NOW())";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':donor_name', $donorName);
    $stmt->bindParam(':donor_email', $donorEmail);
    $stmt->bindParam(':donor_phone', $donorPhone);
    $stmt->bindParam(':ngo_id', $ngoId);
    $stmt->bindParam(':amount', $amount);
    $stmt->bindParam(':payment_method', $paymentMethod);
    $stmt->bindParam(':transaction_id', $transactionId);
    $stmt->bindParam(':message', $message);
    $stmt->bindParam(':is_anonymous', $isAnonymous, PDO::PARAM_BOOL);
    
    if ($stmt->execute()) {
        $donationId = $db->lastInsertId();
        
        // Process payment based on method
        $paymentResponse = processPayment($paymentMethod, $amount, $transactionId, $donorEmail);
        
        if ($paymentResponse['success']) {
            // Update donation status
            $updateQuery = "UPDATE donations SET payment_status = 'completed' WHERE id = :id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':id', $donationId);
            $updateStmt->execute();
            
            // Send confirmation email
            if ($donorEmail) {
                $subject = "Thank you for your donation - " . APP_NAME;
                $emailMessage = "<h2>Thank you for your generosity!</h2>
                               <p>Dear $donorName,</p>
                               <p>We have received your donation of <strong>₨ " . number_format($amount, 2) . "</strong>.</p>
                               <p>Transaction ID: $transactionId</p>
                               <p>Your contribution will help us provide counseling and support to those in need.</p>
                               <p>Best regards,<br>" . APP_NAME . " Team</p>";
                
                @sendEmail($donorEmail, $subject, $emailMessage);
            }
            
            // Notify admins
            $adminSubject = "New Donation Received";
            $adminMessage = "<h2>New Donation</h2>
                            <ul>
                                <li>Amount: ₨ " . number_format($amount, 2) . "</li>
                                <li>Donor: $donorName</li>
                                <li>Payment Method: $paymentMethod</li>
                                <li>Transaction ID: $transactionId</li>
                            </ul>";
            
            @sendEmail(ADMIN_EMAIL, $adminSubject, $adminMessage);
            
            sendJSONResponse(true, 'Donation processed successfully! Thank you for your generosity.', [
                'donation_id' => $donationId,
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'payment_url' => $paymentResponse['payment_url'] ?? null
            ]);
        } else {
            // Update donation status to failed
            $updateQuery = "UPDATE donations SET payment_status = 'failed' WHERE id = :id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':id', $donationId);
            $updateStmt->execute();
            
            sendJSONResponse(false, 'Payment processing failed: ' . $paymentResponse['message']);
        }
    } else {
        sendJSONResponse(false, 'Failed to process donation. Please try again.');
    }
} catch (PDOException $e) {
    sendJSONResponse(false, 'Database error: ' . $e->getMessage());
}

/**
 * Process payment through selected payment gateway
 */
function processPayment($method, $amount, $transactionId, $email) {
    // This is a demo implementation
    // In production, integrate with actual payment gateways
    
    switch ($method) {
        case 'credit_card':
        case 'debit_card':
            // Integrate with payment gateway (e.g., Stripe, PayFast)
            return [
                'success' => true,
                'message' => 'Card payment processed',
                'payment_url' => 'https://payment-gateway.com/pay/' . $transactionId
            ];
            
        case 'easypaisa':
            // Integrate with Easypaisa API
            return [
                'success' => true,
                'message' => 'Easypaisa payment initiated',
                'payment_url' => 'https://easypaisa.com.pk/pay/' . $transactionId
            ];
            
        case 'jazzcash':
            // Integrate with JazzCash API
            return [
                'success' => true,
                'message' => 'JazzCash payment initiated',
                'payment_url' => 'https://jazzcash.com.pk/pay/' . $transactionId
            ];
            
        case 'bank_transfer':
            return [
                'success' => true,
                'message' => 'Bank transfer instructions sent',
                'payment_url' => null
            ];
            
        case 'paypal':
            // Integrate with PayPal API
            return [
                'success' => true,
                'message' => 'PayPal payment initiated',
                'payment_url' => 'https://paypal.com/pay/' . $transactionId
            ];
            
        default:
            return [
                'success' => false,
                'message' => 'Invalid payment method'
            ];
    }
}
?>
