<?php
header('Content-Type: application/json');

require_once '../includes/db.php'; // Your PDO connection file

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action.'];

// Helper function to validate role (optional)
function isValidRole(string $role): bool {
    $validRoles = ['admin', 'receptionist', 'employee'];
    return in_array($role, $validRoles, true);
}

try {
    if ($action === 'register') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';
        $role = 'employee'; // default role on registration

        // Basic validations
        if (!$name || !$email || !$password || !$confirmPassword) {
            $response['message'] = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'Invalid email address.';
        } elseif ($password !== $confirmPassword) {
            $response['message'] = 'Passwords do not match.';
        } else {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $response['message'] = 'Email is already registered.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, 0)");
                $stmt->execute([$name, $email, $hashedPassword, $role]);

                $response = [
                    'success' => true,
                    'message' => 'Registration successful. Please wait for admin approval.'
                ];
            }
        }
    } elseif ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            $response['message'] = 'Email and password are required.';
        } else {
            $stmt = $pdo->prepare("SELECT id, name, password, role, status FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ((int)$user['status'] !== 1) {
                    $response['message'] = 'Account is not active. Please wait for admin approval.';
                } else {
                    // Successful login
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_role'] = $user['role'];

                    // Log user session info
                    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

                    $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, ip_address, user_agent) VALUES (?, ?, ?)");
                    $stmt->execute([$user['id'], $ipAddress, $userAgent]);

                    $_SESSION['user_session_id'] = $pdo->lastInsertId();

                    $response = [
    'success' => true,
    'message' => 'Login successful.',
    'redirect' => 'customer-management.php' // <== add this
];

                }
            } else {
                $response['message'] = 'Invalid email or password.';
            }
        }
    }
} catch (Exception $e) {
    error_log('Auth error: ' . $e->getMessage());
    $response['message'] = 'An error occurred. Please try again later.';
}

echo json_encode($response);
exit;
?>