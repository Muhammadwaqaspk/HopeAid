<?php
/**
 * Heal2Rise - NGO Registration API
 * Handles new NGO registration
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
$requiredFields = ['organization_name', 'registration_number', 'email', 'password', 'phone', 'address', 'city', 'description'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        sendJSONResponse(false, ucfirst(str_replace('_', ' ', $field)) . ' is required');
    }
}

// Sanitize inputs
$organizationName = sanitizeInput($data['organization_name']);
$registrationNumber = sanitizeInput($data['registration_number']);
$email = sanitizeInput($data['email']);
$password = $data['password'];
$phone = sanitizeInput($data['phone']);
$website = isset($data['website']) ? sanitizeInput($data['website']) : null;
$address = sanitizeInput($data['address']);
$city = sanitizeInput($data['city']);
$country = 'Pakistan';
$description = sanitizeInput($data['description']);

// Process services
$services = isset($data['services']) ? $data['services'] : [];
$servicesOffered = implode(', ', $services);

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

// Handle logo upload
$logoPath = null;
if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/logos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileName = time() . '_' . basename($_FILES['logo']['name']);
    $targetPath = $uploadDir . $fileName;
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (in_array($_FILES['logo']['type'], $allowedTypes)) {
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
            $logoPath = 'uploads/logos/' . $fileName;
        }
    }
}

$database = new Database();
$db = $database->getConnection();

try {
    // Check if email already exists
    $checkQuery = "SELECT id FROM ngos WHERE email = :email LIMIT 1";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':email', $email);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        sendJSONResponse(false, 'Email already registered. Please use a different email or login.');
    }
    
    // Check if registration number already exists
    $checkRegQuery = "SELECT id FROM ngos WHERE registration_number = :reg_number LIMIT 1";
    $checkRegStmt = $db->prepare($checkRegQuery);
    $checkRegStmt->bindParam(':reg_number', $registrationNumber);
    $checkRegStmt->execute();
    
    if ($checkRegStmt->rowCount() > 0) {
        sendJSONResponse(false, 'Registration number already exists.');
    }
    
    // Insert new NGO
    $query = "INSERT INTO ngos (organization_name, registration_number, email, password_hash, phone, website, 
              address, city, country, description, services_offered, logo, status, registration_date) 
              VALUES (:organization_name, :registration_number, :email, :password_hash, :phone, :website, 
              :address, :city, :country, :description, :services_offered, :logo, 'pending', NOW())";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':organization_name', $organizationName);
    $stmt->bindParam(':registration_number', $registrationNumber);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password_hash', $passwordHash);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':website', $website);
    $stmt->bindParam(':address', $address);
    $stmt->bindParam(':city', $city);
    $stmt->bindParam(':country', $country);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':services_offered', $servicesOffered);
    $stmt->bindParam(':logo', $logoPath);
    
    if ($stmt->execute()) {
        $ngoId = $db->lastInsertId();
        
        // Log activity
        logActivity($ngoId, 'ngo', 'REGISTER', 'New NGO registered: ' . $organizationName);
        
        // Send confirmation email
        $subject = "NGO Registration - " . APP_NAME;
        $message = "<h2>Thank you for registering your NGO!</h2>
                   <p>Dear $organizationName,</p>
                   <p>Thank you for registering with " . APP_NAME . ". Your registration is currently pending approval.</p>
                   <p>Our team will review your application and contact you within 3-5 business days.</p>
                   <p>Registration Details:</p>
                   <ul>
                       <li>Organization: $organizationName</li>
                       <li>Registration Number: $registrationNumber</li>
                       <li>Email: $email</li>
                   </ul>
                   <p>Best regards,<br>" . APP_NAME . " Team</p>";
        
        @sendEmail($email, $subject, $message);
        
        // Notify admins
        $adminSubject = "New NGO Registration - Approval Required";
        $adminMessage = "<h2>New NGO Registration</h2>
                        <p>A new NGO has registered and requires approval:</p>
                        <ul>
                            <li>Organization: $organizationName</li>
                            <li>Registration Number: $registrationNumber</li>
                            <li>Email: $email</li>
                            <li>Phone: $phone</li>
                            <li>City: $city</li>
                            <li>Services: $servicesOffered</li>
                        </ul>
                        <p>Please review and approve/reject the registration.</p>";
        
        @sendEmail(ADMIN_EMAIL, $adminSubject, $adminMessage);
        
        sendJSONResponse(true, 'NGO registration submitted successfully! Your application is pending approval. You will be notified within 3-5 business days.', [
            'ngo_id' => $ngoId,
            'redirect' => 'pages/login.html'
        ]);
    } else {
        sendJSONResponse(false, 'Registration failed. Please try again.');
    }
} catch (PDOException $e) {
    sendJSONResponse(false, 'Database error: ' . $e->getMessage());
}
?>
