<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

class AuthAPI {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function register($data) {
        try {
            // Validate input
            if (empty($data['full_name']) || empty($data['email']) || empty($data['password']) || empty($data['interest_type'])) {
                return ['success' => false, 'message' => 'All fields are required'];
            }
            
            // Check if email already exists
            $query = "SELECT id FROM users WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $data['email']);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            
            // Hash password
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Insert user
            $query = "INSERT INTO users (full_name, email, password, interest_type) VALUES (:full_name, :email, :password, :interest_type)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':full_name', $data['full_name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':interest_type', $data['interest_type']);
            
            if ($stmt->execute()) {
                $user_id = $this->conn->lastInsertId();
                
                // Create initial consultation request
                $this->createConsultationRequest($user_id, $data['interest_type']);
                
                return [
                    'success' => true, 
                    'message' => 'Registration successful! A consultant will contact you shortly.',
                    'user_id' => $user_id
                ];
            } else {
                return ['success' => false, 'message' => 'Registration failed'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
        }
    }
    
    public function login($data) {
        try {
            if (empty($data['email']) || empty($data['password'])) {
                return ['success' => false, 'message' => 'Email and password are required'];
            }
            
            $query = "SELECT id, full_name, email, password, role, interest_type FROM users WHERE email = :email AND is_active = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $data['email']);
            $stmt->execute();
            
            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($data['password'], $user['password'])) {
                    // Start session
                    session_start();
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_name'] = $user['full_name'];
                    
                    return [
                        'success' => true,
                        'message' => 'Login successful',
                        'user' => [
                            'id' => $user['id'],
                            'name' => $user['full_name'],
                            'email' => $user['email'],
                            'role' => $user['role'],
                            'interest_type' => $user['interest_type']
                        ]
                    ];
                } else {
                    return ['success' => false, 'message' => 'Invalid password'];
                }
            } else {
                return ['success' => false, 'message' => 'User not found'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
        }
    }
    
    public function logout() {
        session_start();
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    private function createConsultationRequest($user_id, $interest_type) {
        $consultation_type = 'general';
        if ($interest_type === 'investing') {
            $consultation_type = 'investment';
        }
        
        $query = "INSERT INTO consultations (user_id, consultation_type, notes) VALUES (:user_id, :consultation_type, :notes)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':consultation_type', $consultation_type);
        $notes = "New user interested in " . $interest_type;
        $stmt->bindParam(':notes', $notes);
        $stmt->execute();
    }
}

// Handle API requests
$auth = new AuthAPI();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'POST':
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'register':
                    echo json_encode($auth->register($input));
                    break;
                case 'login':
                    echo json_encode($auth->login($input));
                    break;
                case 'logout':
                    echo json_encode($auth->logout());
                    break;
                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Action required']);
        }
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
