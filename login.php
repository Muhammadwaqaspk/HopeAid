<?php
/**
 * Heal2Rise - Login API
 * Handles user, NGO, and admin authentication
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
if (!isset($data['email']) || !isset($data['password']) || !isset($data['user_type'])) {
    sendJSONResponse(false, 'Email, password, and user type are required');
}

$email = sanitizeInput($data['email']);
$password = $data['password'];
$userType = sanitizeInput($data['user_type']);

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJSONResponse(false, 'Invalid email format');
}

$database = new Database();
$db = $database->getConnection();

try {
    switch ($userType) {
        case 'user':
            // User Login
            $query = "SELECT id, full_name, email, password_hash, status, is_verified FROM users WHERE email = :email LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                
                if (password_verify($password, $user['password_hash'])) {
                    if ($user['status'] === 'pending') {
                        sendJSONResponse(false, 'Your account is pending approval. Please wait for verification.');
                    }
                    
                    if (!$user['is_verified']) {
                        sendJSONResponse(false, 'Please verify your email address before logging in.');
                    }
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_type'] = 'user';
                    
                    // Log activity
                    logActivity($user['id'], 'user', 'LOGIN', 'User logged in successfully');
                    
                    sendJSONResponse(true, 'Login successful', [
                        'user_id' => $user['id'],
                        'name' => $user['full_name'],
                        'email' => $user['email'],
                        'redirect' => 'pages/user-dashboard.html'
                    ]);
                } else {
                    sendJSONResponse(false, 'Invalid password');
                }
            } else {
                sendJSONResponse(false, 'User not found');
            }
            break;
            
        case 'ngo':
            // NGO Login
            $query = "SELECT id, organization_name, email, password_hash, status, is_verified FROM ngos WHERE email = :email LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $ngo = $stmt->fetch();
                
                if (password_verify($password, $ngo['password_hash'])) {
                    if ($ngo['status'] === 'pending') {
                        sendJSONResponse(false, 'Your NGO registration is pending approval. Please wait for verification.');
                    }
                    
                    if ($ngo['status'] === 'rejected') {
                        sendJSONResponse(false, 'Your NGO registration has been rejected. Please contact support.');
                    }
                    
                    if ($ngo['status'] === 'suspended') {
                        sendJSONResponse(false, 'Your NGO account has been suspended. Please contact support.');
                    }
                    
                    // Set session variables
                    $_SESSION['ngo_id'] = $ngo['id'];
                    $_SESSION['ngo_name'] = $ngo['organization_name'];
                    $_SESSION['ngo_email'] = $ngo['email'];
                    $_SESSION['user_type'] = 'ngo';
                    
                    // Log activity
                    logActivity($ngo['id'], 'ngo', 'LOGIN', 'NGO logged in successfully');
                    
                    sendJSONResponse(true, 'Login successful', [
                        'ngo_id' => $ngo['id'],
                        'name' => $ngo['organization_name'],
                        'email' => $ngo['email'],
                        'redirect' => 'pages/ngo-dashboard.html'
                    ]);
                } else {
                    sendJSONResponse(false, 'Invalid password');
                }
            } else {
                sendJSONResponse(false, 'NGO not found');
            }
            break;
            
        case 'admin':
            // Admin Login
            $query = "SELECT id, username, email, password_hash, role, is_active FROM admins WHERE email = :email OR username = :email LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $admin = $stmt->fetch();
                
                if (password_verify($password, $admin['password_hash'])) {
                    if (!$admin['is_active']) {
                        sendJSONResponse(false, 'Your admin account has been deactivated.');
                    }
                    
                    // Set session variables
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_role'] = $admin['role'];
                    $_SESSION['user_type'] = 'admin';
                    
                    // Update last login
                    $updateQuery = "UPDATE admins SET last_login = NOW() WHERE id = :id";
                    $updateStmt = $db->prepare($updateQuery);
                    $updateStmt->bindParam(':id', $admin['id']);
                    $updateStmt->execute();
                    
                    // Log activity
                    logActivity($admin['id'], 'admin', 'LOGIN', 'Admin logged in successfully');
                    
                    sendJSONResponse(true, 'Login successful', [
                        'admin_id' => $admin['id'],
                        'username' => $admin['username'],
                        'email' => $admin['email'],
                        'role' => $admin['role'],
                        'redirect' => 'pages/admin-dashboard.html'
                    ]);
                } else {
                    sendJSONResponse(false, 'Invalid password');
                }
            } else {
                sendJSONResponse(false, 'Admin not found');
            }
            break;
            
        default:
            sendJSONResponse(false, 'Invalid user type');
    }
} catch (PDOException $e) {
    sendJSONResponse(false, 'Database error: ' . $e->getMessage());
}
?>
