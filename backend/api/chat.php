<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

class ChatAPI {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function sendMessage($data) {
        try {
            session_start();
            
            if (!isset($_SESSION['user_id'])) {
                return ['success' => false, 'message' => 'User not logged in'];
            }
            
            $user_id = $_SESSION['user_id'];
            
            // Validate input
            if (empty($data['message'])) {
                return ['success' => false, 'message' => 'Message is required'];
            }
            
            // Use provided consultation_id or get/create one for the user
            if (isset($data['consultation_id'])) {
                $consultation_id = $data['consultation_id'];
                
                // Verify user has access to this consultation
                if (!$this->hasConsultationAccess($user_id, $consultation_id)) {
                    return ['success' => false, 'message' => 'Access denied to this consultation'];
                }
            } else {
                // Get or create consultation for this user
                $consultation_id = $this->getOrCreateConsultation($user_id);
                
                if (!$consultation_id) {
                    return ['success' => false, 'message' => 'Failed to create consultation'];
                }
            }
            
            // Insert message
            $query = "INSERT INTO chat_messages (consultation_id, sender_id, message, message_type) VALUES (:consultation_id, :sender_id, :message, :message_type)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':consultation_id', $consultation_id);
            $stmt->bindParam(':sender_id', $user_id);
            $stmt->bindParam(':message', $data['message']);
            $message_type = $data['message_type'] ?? 'text';
            $stmt->bindParam(':message_type', $message_type);
            
            if ($stmt->execute()) {
                $message_id = $this->conn->lastInsertId();
                
                // Get the inserted message with user info
                $message = $this->getMessageById($message_id);
                
                return [
                    'success' => true,
                    'message' => 'Message sent successfully',
                    'data' => $message
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to send message'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
        }
    }
    
    public function getMessages($consultation_id = null) {
        try {
            session_start();
            
            if (!isset($_SESSION['user_id'])) {
                return ['success' => false, 'message' => 'User not logged in'];
            }
            
            $user_id = $_SESSION['user_id'];
            
            // If no consultation_id provided, get the user's active consultation
            if (!$consultation_id) {
                $consultation_id = $this->getOrCreateConsultation($user_id);
            }
            
            if (!$consultation_id) {
                return ['success' => false, 'message' => 'No consultation found'];
            }
            
            // Get messages for this consultation
            $query = "SELECT cm.*, u.full_name, u.role 
                     FROM chat_messages cm 
                     JOIN users u ON cm.sender_id = u.id 
                     WHERE cm.consultation_id = :consultation_id 
                     ORDER BY cm.created_at ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':consultation_id', $consultation_id);
            $stmt->execute();
            
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Mark messages as read for the current user
            $this->markMessagesAsRead($consultation_id, $user_id);
            
            return [
                'success' => true,
                'data' => $messages,
                'consultation_id' => $consultation_id
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
        }
    }
    
    public function getConsultationInfo($user_id = null) {
        try {
            session_start();
            
            if (!$user_id && !isset($_SESSION['user_id'])) {
                return ['success' => false, 'message' => 'User not logged in'];
            }
            
            $user_id = $user_id ?? $_SESSION['user_id'];
            
            // Get consultation info
            $query = "SELECT c.*, u.full_name as user_name, u.interest_type, 
                            cons.full_name as consultant_name
                     FROM consultations c 
                     JOIN users u ON c.user_id = u.id 
                     LEFT JOIN users cons ON c.consultant_id = cons.id 
                     WHERE c.user_id = :user_id AND c.status IN ('pending', 'active')
                     ORDER BY c.created_at DESC LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $consultation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($consultation) {
                return [
                    'success' => true,
                    'data' => $consultation
                ];
            } else {
                return ['success' => false, 'message' => 'No active consultation found'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
        }
    }
    
    private function getOrCreateConsultation($user_id) {
        // Check if user has an active consultation
        $query = "SELECT id FROM consultations WHERE user_id = :user_id AND status IN ('pending', 'active') ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $consultation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($consultation) {
            return $consultation['id'];
        }
        
        // Create new consultation if none exists
        $query = "INSERT INTO consultations (user_id, consultation_type, status) VALUES (:user_id, 'general', 'active')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    private function getMessageById($message_id) {
        $query = "SELECT cm.*, u.full_name, u.role 
                 FROM chat_messages cm 
                 JOIN users u ON cm.sender_id = u.id 
                 WHERE cm.id = :message_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':message_id', $message_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function markMessagesAsRead($consultation_id, $user_id) {
        $query = "UPDATE chat_messages SET is_read = 1 
                 WHERE consultation_id = :consultation_id 
                 AND sender_id != :user_id 
                 AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':consultation_id', $consultation_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
    }
    
    private function hasConsultationAccess($user_id, $consultation_id) {
        // Check if user is the consultation owner or is an admin/consultant
        $query = "SELECT c.user_id, u.role 
                 FROM consultations c 
                 JOIN users u ON u.id = :user_id 
                 WHERE c.id = :consultation_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':consultation_id', $consultation_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return false;
        }
        
        // User has access if they own the consultation or are admin/consultant
        return ($result['user_id'] == $user_id) || in_array($result['role'], ['admin', 'consultant']);
    }
}

// Handle API requests
$chat = new ChatAPI();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'POST':
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'send':
                    echo json_encode($chat->sendMessage($input));
                    break;
                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
        } else {
            echo json_encode($chat->sendMessage($input));
        }
        break;
    case 'GET':
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'messages':
                    $consultation_id = $_GET['consultation_id'] ?? null;
                    echo json_encode($chat->getMessages($consultation_id));
                    break;
                case 'consultation':
                    echo json_encode($chat->getConsultationInfo());
                    break;
                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
        } else {
            echo json_encode($chat->getMessages());
        }
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
