<?php
/**
 * Heal2Rise - User Registration API
 * Handles new user registration
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
$requiredFields = ['full_name', 'email', 'password', 'issue_category', 'issue_description'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        sendJSONResponse(false, ucfirst(str_replace('_', ' ', $field)) . ' is required');
    }
}

// Sanitize inputs
$fullName = sanitizeInput($data['full_name']);
$email = sanitizeInput($data['email']);
$password = $data['password'];
$phone = isset($data['phone']) ? sanitizeInput($data['phone']) : null;
$age = isset($data['age']) ? intval($data['age']) : null;
$gender = isset($data['gender']) ? sanitizeInput($data['gender']) : null;
$address = isset($data['address']) ? sanitizeInput($data['address']) : null;
$city = isset($data['city']) ? sanitizeInput($data['city']) : null;
$country = 'Pakistan';
$issueCategory = sanitizeInput($data['issue_category']);
$issueDescription = sanitizeInput($data['issue_description']);
$emergencyContactName = isset($data['emergency_contact_name']) ? sanitizeInput($data['emergency_contact_name']) : null;
$emergencyContactPhone = isset($data['emergency_contact_phone']) ? sanitizeInput($data['emergency_contact_phone']) : null;

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJSONResponse(false, 'Invalid email format');
}

// Validate password
if (strlen($password) < 8) {
    sendJSONResponse(false, 'Password must be at least 8 characters long');
}

// Hash password
$passwordHash = password_hash($password, PASSWORD_BCRYPT);

$database = new Database();
$db = $database->getConnection();

try {
    // Check if email already exists
    $checkQuery = "SELECT id FROM users WHERE email = :email LIMIT 1";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':email', $email);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        sendJSONResponse(false, 'Email already registered. Please use a different email or login.');
    }
    
    // Insert new user
    $query = "INSERT INTO users (full_name, email, password_hash, phone, age, gender, address, city, country, 
              issue_category, issue_description, emergency_contact_name, emergency_contact_phone, 
              status, is_verified, registration_date) 
              VALUES (:full_name, :email, :password_hash, :phone, :age, :gender, :address, :city, :country, 
              :issue_category, :issue_description, :emergency_contact_name, :emergency_contact_phone, 
              'pending', 1, NOW())";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':full_name', $fullName);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password_hash', $passwordHash);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':age', $age);
    $stmt->bindParam(':gender', $gender);
    $stmt->bindParam(':address', $address);
    $stmt->bindParam(':city', $city);
    $stmt->bindParam(':country', $country);
    $stmt->bindParam(':issue_category', $issueCategory);
    $stmt->bindParam(':issue_description', $issueDescription);
    $stmt->bindParam(':emergency_contact_name', $emergencyContactName);
    $stmt->bindParam(':emergency_contact_phone', $emergencyContactPhone);
    
    if ($stmt->execute()) {
        $userId = $db->lastInsertId();
        
        // Log activity
        logActivity($userId, 'user', 'REGISTER', 'New user registered');
        
        // Send welcome email (optional)
        $subject = "Welcome to " . APP_NAME;
        $message = "<h2>Welcome to " . APP_NAME . "!</h2>
                   <p>Dear $fullName,</p>
                   <p>Thank you for registering with us. Your account is currently pending approval.</p>
                   <p>We will review your registration and connect you with a suitable NGO shortly.</p>
                   <p>Best regards,<br>" . APP_NAME . " Team</p>";
        
        @sendEmail($email, $subject, $message);
        
        // Notify admins
        $adminSubject = "New User Registration";
        $adminMessage = "<h2>New User Registration</h2>
                        <p>A new user has registered:</p>
                        <ul>
                            <li>Name: $fullName</li>
                            <li>Email: $email</li>
                            <li>Issue Category: $issueCategory</li>
                        </ul>
                        <p>Please review and assign to an appropriate NGO.</p>";
        
        @sendEmail(ADMIN_EMAIL, $adminSubject, $adminMessage);
        
        sendJSONResponse(true, 'Registration successful! Your account is pending approval. You will be notified once approved.', [
            'user_id' => $userId,
            'redirect' => 'pages/login.html'
        ]);
    } else {
        sendJSONResponse(false, 'Registration failed. Please try again.');
    }
} catch (PDOException $e) {
    sendJSONResponse(false, 'Database error: ' . $e->getMessage());
}
?>
