<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

class NewsletterAPI {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function subscribe($data) {
        try {
            // Validate input
            if (empty($data['email'])) {
                return ['success' => false, 'message' => 'Email is required'];
            }
            
            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format'];
            }
            
            // Check if email already exists
            $query = "SELECT id, status FROM newsletter_subscriptions WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $data['email']);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing['status'] === 'active') {
                    return ['success' => false, 'message' => 'Email is already subscribed'];
                } else {
                    // Reactivate subscription
                    $query = "UPDATE newsletter_subscriptions SET status = 'active', subscribed_at = CURRENT_TIMESTAMP WHERE id = :id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':id', $existing['id']);
                    
                    if ($stmt->execute()) {
                        return ['success' => true, 'message' => 'Successfully resubscribed to newsletter!'];
                    } else {
                        return ['success' => false, 'message' => 'Failed to resubscribe'];
                    }
                }
            }
            
            // Insert new subscription
            $query = "INSERT INTO newsletter_subscriptions (email, status) VALUES (:email, 'active')";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $data['email']);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Successfully subscribed to newsletter!'];
            } else {
                return ['success' => false, 'message' => 'Subscription failed'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
        }
    }
    
    public function unsubscribe($data) {
        try {
            if (empty($data['email'])) {
                return ['success' => false, 'message' => 'Email is required'];
            }
            
            $query = "UPDATE newsletter_subscriptions SET status = 'unsubscribed', unsubscribed_at = CURRENT_TIMESTAMP WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $data['email']);
            
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Successfully unsubscribed from newsletter'];
            } else {
                return ['success' => false, 'message' => 'Email not found in subscription list'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
        }
    }
    
    public function getSubscriptions() {
        try {
            $query = "SELECT email, status, subscribed_at FROM newsletter_subscriptions ORDER BY subscribed_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => $subscriptions,
                'total' => count($subscriptions),
                'active' => count(array_filter($subscriptions, fn($s) => $s['status'] === 'active'))
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
        }
    }
}

// Handle API requests
$newsletter = new NewsletterAPI();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'POST':
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'subscribe':
                    echo json_encode($newsletter->subscribe($input));
                    break;
                case 'unsubscribe':
                    echo json_encode($newsletter->unsubscribe($input));
                    break;
                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
        } else {
            // Default action is subscribe
            echo json_encode($newsletter->subscribe($input));
        }
        break;
    case 'GET':
        echo json_encode($newsletter->getSubscriptions());
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
