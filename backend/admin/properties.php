<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Handle image uploads
function handleImageUploads($files) {
    $uploaded_images = [];
    $upload_dir = '../../uploads/properties/';
    
    // Create upload directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if (isset($files['images'])) {
        $file_count = count($files['images']['name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            if ($files['images']['error'][$i] === UPLOAD_ERR_OK) {
                $tmp_name = $files['images']['tmp_name'][$i];
                $original_name = $files['images']['name'][$i];
                $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                
                // Validate file type
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($file_extension, $allowed_extensions)) {
                    // Generate unique filename
                    $new_filename = uniqid('property_') . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($tmp_name, $upload_path)) {
                        $uploaded_images[] = 'uploads/properties/' . $new_filename;
                    }
                }
            }
        }
    }
    
    return $uploaded_images;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $title = $_POST['title'];
                $description = $_POST['description'];
                $location = $_POST['location'];
                $country = $_POST['country'];
                $property_type = $_POST['property_type'];
                $bedrooms = (int)$_POST['bedrooms'];
                $bathrooms = (int)$_POST['bathrooms'];
                $square_feet = (int)$_POST['square_feet'];
                $price_bhs = (float)$_POST['price_bhs'];
                $listing_type = $_POST['listing_type'];
                $featured = isset($_POST['featured']) ? 1 : 0;
                
                // Handle image uploads
                $uploaded_images = handleImageUploads($_FILES);
                
                // Also handle manual image URLs if provided
                if (!empty($_POST['manual_images'])) {
                    $manual_images = array_map('trim', explode(',', $_POST['manual_images']));
                    $uploaded_images = array_merge($uploaded_images, $manual_images);
                }
                
                $images = json_encode($uploaded_images);
                
                $query = "INSERT INTO properties (title, description, location, country, property_type, bedrooms, bathrooms, square_feet, price_bhs, listing_type, featured, images) 
                         VALUES (:title, :description, :location, :country, :property_type, :bedrooms, :bathrooms, :square_feet, :price_bhs, :listing_type, :featured, :images)";
                
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':location', $location);
                $stmt->bindParam(':country', $country);
                $stmt->bindParam(':property_type', $property_type);
                $stmt->bindParam(':bedrooms', $bedrooms);
                $stmt->bindParam(':bathrooms', $bathrooms);
                $stmt->bindParam(':square_feet', $square_feet);
                $stmt->bindParam(':price_bhs', $price_bhs);
                $stmt->bindParam(':listing_type', $listing_type);
                $stmt->bindParam(':featured', $featured);
                $stmt->bindParam(':images', $images);
                
                if ($stmt->execute()) {
                    $success_message = "Property added successfully!";
                } else {
                    $error_message = "Failed to add property.";
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $title = $_POST['title'];
                $description = $_POST['description'];
                $location = $_POST['location'];
                $country = $_POST['country'];
                $property_type = $_POST['property_type'];
                $bedrooms = (int)$_POST['bedrooms'];
                $bathrooms = (int)$_POST['bathrooms'];
                $square_feet = (int)$_POST['square_feet'];
                $price_bhs = (float)$_POST['price_bhs'];
                $listing_type = $_POST['listing_type'];
                $status = $_POST['status'];
                $featured = isset($_POST['featured']) ? 1 : 0;
                
                // Get existing images
                $query = "SELECT images FROM properties WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                $existing_property = $stmt->fetch(PDO::FETCH_ASSOC);
                $existing_images = json_decode($existing_property['images'], true) ?: [];
                
                // Handle new image uploads
                $uploaded_images = handleImageUploads($_FILES);
                
                // Combine existing images with new uploads
                $all_images = array_merge($existing_images, $uploaded_images);
                
                // Also handle manual image URLs if provided
                if (!empty($_POST['manual_images'])) {
                    $manual_images = array_map('trim', explode(',', $_POST['manual_images']));
                    $all_images = array_merge($all_images, $manual_images);
                }
                
                $images = json_encode(array_unique($all_images));
                
                $query = "UPDATE properties SET title = :title, description = :description, location = :location, country = :country, 
                         property_type = :property_type, bedrooms = :bedrooms, bathrooms = :bathrooms, square_feet = :square_feet, 
                         price_bhs = :price_bhs, listing_type = :listing_type, status = :status, featured = :featured, images = :images 
                         WHERE id = :id";
                
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':location', $location);
                $stmt->bindParam(':country', $country);
                $stmt->bindParam(':property_type', $property_type);
                $stmt->bindParam(':bedrooms', $bedrooms);
                $stmt->bindParam(':bathrooms', $bathrooms);
                $stmt->bindParam(':square_feet', $square_feet);
                $stmt->bindParam(':price_bhs', $price_bhs);
                $stmt->bindParam(':listing_type', $listing_type);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':featured', $featured);
                $stmt->bindParam(':images', $images);
                
                if ($stmt->execute()) {
                    $success_message = "Property updated successfully!";
                } else {
                    $error_message = "Failed to update property.";
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                $query = "DELETE FROM properties WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id', $id);
                
                if ($stmt->execute()) {
                    $success_message = "Property deleted successfully!";
                } else {
                    $error_message = "Failed to delete property.";
                }
                break;
        }
    }
}

// Get all properties
$query = "SELECT * FROM properties ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get property for editing if ID is provided
$edit_property = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $query = "SELECT * FROM properties WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $edit_id);
    $stmt->execute();
    $edit_property = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($edit_property) {
        $edit_property['images'] = json_decode($edit_property['images'], true) ?: [];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Properties Management - BlockHaus Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-purple: #6366f1;
            --secondary-purple: #8b5cf6;
            --light-purple: #a855f7;
            --dark-purple: #4c1d95;
        }
        
        .sidebar {
            background: linear-gradient(135deg, var(--primary-purple), var(--secondary-purple));
            min-height: 100vh;
            color: white;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .property-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .property-card:hover {
            transform: translateY(-5px);
        }
        
        .property-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .property-status {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-available { background: #10b981; color: white; }
        .status-sold { background: #ef4444; color: white; }
        .status-rented { background: #f59e0b; color: white; }
        .status-pending { background: #6b7280; color: white; }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-purple), var(--secondary-purple));
            border: none;
            border-radius: 8px;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary-purple), var(--light-purple));
        }
        
        .image-preview {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 8px;
            margin: 5px;
        }
        
        .image-upload-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .image-upload-item {
            position: relative;
            display: inline-block;
        }
        
        .image-upload-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #ddd;
        }
        
        .image-upload-item .remove-btn {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-3">
                    <h4 class="text-white mb-4">
                        <i class="fas fa-home me-2"></i>BlockHaus Admin
                    </h4>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users me-2"></i>Users
                        </a>
                        <a class="nav-link active" href="properties.php">
                            <i class="fas fa-building me-2"></i>Properties
                        </a>
                        <a class="nav-link" href="consultations.php">
                            <i class="fas fa-comments me-2"></i>Consultations
                        </a>
                        <a class="nav-link" href="newsletter.php">
                            <i class="fas fa-envelope me-2"></i>Newsletter
                        </a>
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Properties Management</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPropertyModal">
                        <i class="fas fa-plus me-2"></i>Add Property
                    </button>
                </div>
                
                <!-- Messages -->
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Properties Grid -->
                <div class="row g-4">
                    <?php foreach ($properties as $property): ?>
                        <?php 
                        $images = json_decode($property['images'], true) ?: [];
                        $main_image = !empty($images) ? $images[0] : 'https://via.placeholder.com/400x200';
                        ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="property-card">
                                <div class="property-image" style="background-image: url('<?php echo htmlspecialchars($main_image); ?>')">
                                    <div class="property-status status-<?php echo $property['status']; ?>">
                                        <?php echo ucfirst($property['status']); ?>
                                    </div>
                                </div>
                                <div class="p-3">
                                    <h5 class="mb-2"><?php echo htmlspecialchars($property['title']); ?></h5>
                                    <p class="text-muted mb-2">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo htmlspecialchars($property['location']); ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-primary"><?php echo ucfirst($property['listing_type']); ?></span>
                                        <strong class="text-primary"><?php echo number_format($property['price_bhs'], 0); ?> $BHS</strong>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted small mb-3">
                                        <span><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?></span>
                                        <span><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?></span>
                                        <span><i class="fas fa-ruler-combined"></i> <?php echo number_format($property['square_feet']); ?> sq ft</span>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="?edit=<?php echo $property['id']; ?>" class="btn btn-outline-primary btn-sm flex-fill">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <button class="btn btn-outline-danger btn-sm" onclick="deleteProperty(<?php echo $property['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Property Modal -->
    <div class="modal fade" id="addPropertyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Property</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" name="location" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Country</label>
                                <select class="form-select" name="country" required>
                                    <option value="">Select Country</option>
                                    <option value="US">United States</option>
                                    <option value="Dubai">Dubai</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Property Type</label>
                                <select class="form-select" name="property_type" required>
                                    <option value="">Select Type</option>
                                    <option value="house">House</option>
                                    <option value="apartment">Apartment</option>
                                    <option value="villa">Villa</option>
                                    <option value="condo">Condo</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Bedrooms</label>
                                <input type="number" class="form-control" name="bedrooms" min="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Bathrooms</label>
                                <input type="number" class="form-control" name="bathrooms" min="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Square Feet</label>
                                <input type="number" class="form-control" name="square_feet" min="0" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price ($BHS)</label>
                                <input type="number" class="form-control" name="price_bhs" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Listing Type</label>
                                <select class="form-select" name="listing_type" required>
                                    <option value="">Select Type</option>
                                    <option value="buy">Buy</option>
                                    <option value="rent">Rent</option>
                                    <option value="sell">Sell</option>
                                    <option value="invest">Invest</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Upload Images</label>
                            <input type="file" class="form-control" name="images[]" multiple accept="image/*" id="imageUpload">
                            <small class="text-muted">Select multiple images (JPG, PNG, GIF, WebP)</small>
                            <div id="imagePreview" class="image-upload-preview"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Or Enter Image URLs (optional)</label>
                            <textarea class="form-control" name="manual_images" rows="2" placeholder="https://example.com/image1.jpg, https://example.com/image2.jpg"></textarea>
                            <small class="text-muted">Enter image URLs separated by commas (optional)</small>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="featured" id="featured">
                            <label class="form-check-label" for="featured">
                                Featured Property
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Property</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Property Modal -->
    <?php if ($edit_property): ?>
    <div class="modal fade show" id="editPropertyModal" tabindex="-1" style="display: block;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Property</h5>
                    <a href="properties.php" class="btn-close"></a>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?php echo $edit_property['id']; ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($edit_property['title']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($edit_property['location']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required><?php echo htmlspecialchars($edit_property['description']); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Country</label>
                                <select class="form-select" name="country" required>
                                    <option value="US" <?php echo $edit_property['country'] === 'US' ? 'selected' : ''; ?>>United States</option>
                                    <option value="Dubai" <?php echo $edit_property['country'] === 'Dubai' ? 'selected' : ''; ?>>Dubai</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Property Type</label>
                                <select class="form-select" name="property_type" required>
                                    <option value="house" <?php echo $edit_property['property_type'] === 'house' ? 'selected' : ''; ?>>House</option>
                                    <option value="apartment" <?php echo $edit_property['property_type'] === 'apartment' ? 'selected' : ''; ?>>Apartment</option>
                                    <option value="villa" <?php echo $edit_property['property_type'] === 'villa' ? 'selected' : ''; ?>>Villa</option>
                                    <option value="condo" <?php echo $edit_property['property_type'] === 'condo' ? 'selected' : ''; ?>>Condo</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Bedrooms</label>
                                <input type="number" class="form-control" name="bedrooms" value="<?php echo $edit_property['bedrooms']; ?>" min="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Bathrooms</label>
                                <input type="number" class="form-control" name="bathrooms" value="<?php echo $edit_property['bathrooms']; ?>" min="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Square Feet</label>
                                <input type="number" class="form-control" name="square_feet" value="<?php echo $edit_property['square_feet']; ?>" min="0" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Price ($BHS)</label>
                                <input type="number" class="form-control" name="price_bhs" value="<?php echo $edit_property['price_bhs']; ?>" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Listing Type</label>
                                <select class="form-select" name="listing_type" required>
                                    <option value="buy" <?php echo $edit_property['listing_type'] === 'buy' ? 'selected' : ''; ?>>Buy</option>
                                    <option value="rent" <?php echo $edit_property['listing_type'] === 'rent' ? 'selected' : ''; ?>>Rent</option>
                                    <option value="sell" <?php echo $edit_property['listing_type'] === 'sell' ? 'selected' : ''; ?>>Sell</option>
                                    <option value="invest" <?php echo $edit_property['listing_type'] === 'invest' ? 'selected' : ''; ?>>Invest</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="available" <?php echo $edit_property['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                    <option value="sold" <?php echo $edit_property['status'] === 'sold' ? 'selected' : ''; ?>>Sold</option>
                                    <option value="rented" <?php echo $edit_property['status'] === 'rented' ? 'selected' : ''; ?>>Rented</option>
                                    <option value="pending" <?php echo $edit_property['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Current Images</label>
                            <div class="current-images mb-2">
                                <?php foreach ($edit_property['images'] as $image): ?>
                                    <div class="d-inline-block position-relative me-2 mb-2">
                                        <img src="<?php echo htmlspecialchars($image); ?>" class="image-preview" alt="Property Image">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Upload Additional Images</label>
                            <input type="file" class="form-control" name="images[]" multiple accept="image/*" id="editImageUpload">
                            <small class="text-muted">Select additional images (JPG, PNG, GIF, WebP)</small>
                            <div id="editImagePreview" class="image-upload-preview"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Or Add Image URLs (optional)</label>
                            <textarea class="form-control" name="manual_images" rows="2" placeholder="https://example.com/image1.jpg, https://example.com/image2.jpg"></textarea>
                            <small class="text-muted">Enter additional image URLs separated by commas (optional)</small>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="featured" id="editFeatured" <?php echo $edit_property['featured'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="editFeatured">
                                Featured Property
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="properties.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Property</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    <?php endif; ?>
    
    <!-- Delete Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteProperty(id) {
            if (confirm('Are you sure you want to delete this property?')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
        
        // Image preview functionality
        document.getElementById('imageUpload').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            for (let i = 0; i < e.target.files.length; i++) {
                const file = e.target.files[i];
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'image-upload-item';
                        div.innerHTML = `
                            <img src="${e.target.result}" alt="Preview">
                            <button type="button" class="remove-btn" onclick="this.parentElement.remove()">×</button>
                        `;
                        preview.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                }
            }
        });
        
        // Edit image preview functionality
        const editImageUpload = document.getElementById('editImageUpload');
        if (editImageUpload) {
            editImageUpload.addEventListener('change', function(e) {
                const preview = document.getElementById('editImagePreview');
                preview.innerHTML = '';
                
                for (let i = 0; i < e.target.files.length; i++) {
                    const file = e.target.files[i];
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const div = document.createElement('div');
                            div.className = 'image-upload-item';
                            div.innerHTML = `
                                <img src="${e.target.result}" alt="Preview">
                                <button type="button" class="remove-btn" onclick="this.parentElement.remove()">×</button>
                            `;
                            preview.appendChild(div);
                        };
                        reader.readAsDataURL(file);
                    }
                }
            });
        }
    </script>
</body>
</html>
