<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

class PropertiesAPI {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function getProperties($filters = []) {
        try {
            $query = "SELECT * FROM properties WHERE status = 'available'";
            $params = [];
            
            // Apply filters
            if (!empty($filters['listing_type'])) {
                $query .= " AND listing_type = :listing_type";
                $params[':listing_type'] = $filters['listing_type'];
            }
            
            if (!empty($filters['country'])) {
                $query .= " AND country = :country";
                $params[':country'] = $filters['country'];
            }
            
            if (!empty($filters['property_type'])) {
                $query .= " AND property_type = :property_type";
                $params[':property_type'] = $filters['property_type'];
            }
            
            if (!empty($filters['min_price'])) {
                $query .= " AND price_bhs >= :min_price";
                $params[':min_price'] = $filters['min_price'];
            }
            
            if (!empty($filters['max_price'])) {
                $query .= " AND price_bhs <= :max_price";
                $params[':max_price'] = $filters['max_price'];
            }
            
            if (!empty($filters['location'])) {
                $query .= " AND location LIKE :location";
                $params[':location'] = '%' . $filters['location'] . '%';
            }
            
            // Order by featured first, then by created date
            $query .= " ORDER BY featured DESC, created_at DESC";
            
            // Add limit if specified
            if (!empty($filters['limit'])) {
                $query .= " LIMIT :limit";
                $params[':limit'] = (int)$filters['limit'];
            }
            
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            foreach ($params as $key => $value) {
                if ($key === ':limit') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->execute();
            $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Process images JSON
            foreach ($properties as &$property) {
                $property['images'] = json_decode($property['images'], true) ?: [];
                $property['price_formatted'] = [
                    'bhs' => number_format($property['price_bhs'], 0) . ' $BHS'
                ];
            }
            
            return [
                'success' => true,
                'data' => $properties,
                'count' => count($properties)
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
        }
    }
    
    public function getProperty($id) {
        try {
            $query = "SELECT * FROM properties WHERE id = :id AND status = 'available'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if ($stmt->rowCount() === 1) {
                $property = $stmt->fetch(PDO::FETCH_ASSOC);
                $property['images'] = json_decode($property['images'], true) ?: [];
                $property['price_formatted'] = [
                    'bhs' => number_format($property['price_bhs'], 0) . ' $BHS'
                ];
                
                return [
                    'success' => true,
                    'data' => $property
                ];
            } else {
                return ['success' => false, 'message' => 'Property not found'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
        }
    }
    
    public function searchProperties($searchData) {
        try {
            // Log the search
            $this->logSearch($searchData);
            
            $filters = [];
            
            if (!empty($searchData['search_type'])) {
                $filters['listing_type'] = $searchData['search_type'];
            }
            
            if (!empty($searchData['location'])) {
                // Handle location mapping
                $location = strtolower($searchData['location']);
                if (strpos($location, 'dubai') !== false) {
                    $filters['country'] = 'Dubai';
                } elseif (strpos($location, 'us') !== false || strpos($location, 'united states') !== false) {
                    $filters['country'] = 'US';
                } else {
                    $filters['location'] = $searchData['location'];
                }
            }
            
            if (!empty($searchData['property_type']) && $searchData['property_type'] !== 'Select property') {
                $filters['property_type'] = strtolower($searchData['property_type']);
            }
            
            // Handle price range
            if (!empty($searchData['price_range'])) {
                $priceRange = explode('-', $searchData['price_range']);
                if (count($priceRange) === 2) {
                    $filters['min_price'] = trim($priceRange[0]);
                    $filters['max_price'] = trim($priceRange[1]);
                }
            }
            
            return $this->getProperties($filters);
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Search error: ' . $e->getMessage()];
        }
    }
    
    private function logSearch($searchData) {
        try {
            session_start();
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            
            $query = "INSERT INTO property_searches (user_id, search_type, location, property_type, cryptocurrency_type) 
                     VALUES (:user_id, :search_type, :location, :property_type, :cryptocurrency_type)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':search_type', $searchData['search_type'] ?? null);
            $stmt->bindParam(':location', $searchData['location'] ?? null);
            $stmt->bindParam(':property_type', $searchData['property_type'] ?? null);
            $stmt->bindParam(':cryptocurrency_type', $searchData['cryptocurrency_type'] ?? null);
            
            $stmt->execute();
        } catch (Exception $e) {
            // Log error but don't fail the search
            error_log("Search logging failed: " . $e->getMessage());
        }
    }
    
    public function getFeaturedProperties() {
        return $this->getProperties(['featured' => true, 'limit' => 6]);
    }
    
    public function getPropertiesByLocation($location) {
        $filters = [];
        
        if (strtolower($location) === 'dubai') {
            $filters['country'] = 'Dubai';
        } elseif (strtolower($location) === 'us') {
            $filters['country'] = 'US';
        }
        
        return $this->getProperties($filters);
    }
}

// Handle API requests
$properties = new PropertiesAPI();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'get':
                    if (isset($_GET['id'])) {
                        echo json_encode($properties->getProperty($_GET['id']));
                    } else {
                        echo json_encode($properties->getProperties($_GET));
                    }
                    break;
                case 'get_property':
                    if (isset($_GET['id'])) {
                        echo json_encode($properties->getProperty($_GET['id']));
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Property ID required']);
                    }
                    break;
                case 'featured':
                    echo json_encode($properties->getFeaturedProperties());
                    break;
                case 'location':
                    if (isset($_GET['location'])) {
                        echo json_encode($properties->getPropertiesByLocation($_GET['location']));
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Location required']);
                    }
                    break;
                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
        } else {
            echo json_encode($properties->getProperties($_GET));
        }
        break;
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($_GET['action']) && $_GET['action'] === 'search') {
            echo json_encode($properties->searchProperties($input));
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
