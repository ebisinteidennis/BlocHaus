<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Handle consultation actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'assign_consultant':
                $consultation_id = $_POST['consultation_id'];
                $consultant_id = $_POST['consultant_id'];
                
                $query = "UPDATE consultations SET consultant_id = :consultant_id, status = 'active' WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':consultant_id', $consultant_id);
                $stmt->bindParam(':id', $consultation_id);
                
                if ($stmt->execute()) {
                    $success_message = "Consultant assigned successfully!";
                } else {
                    $error_message = "Failed to assign consultant.";
                }
                break;
                
            case 'update_status':
                $consultation_id = $_POST['consultation_id'];
                $status = $_POST['status'];
                
                $query = "UPDATE consultations SET status = :status WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':id', $consultation_id);
                
                if ($stmt->execute()) {
                    $success_message = "Status updated successfully!";
                } else {
                    $error_message = "Failed to update status.";
                }
                break;
        }
    }
}

// Get all consultations with user and consultant details
$query = "SELECT c.*, u.full_name as user_name, u.email as user_email, 
          cons.full_name as consultant_name, p.title as property_title
          FROM consultations c 
          LEFT JOIN users u ON c.user_id = u.id 
          LEFT JOIN users cons ON c.consultant_id = cons.id 
          LEFT JOIN properties p ON c.property_id = p.id 
          ORDER BY c.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all consultants
$query = "SELECT id, full_name FROM users WHERE role = 'consultant' AND is_active = 1";
$stmt = $conn->prepare($query);
$stmt->execute();
$consultants = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultations Management - BlockHaus Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1rem;
            margin: 0.25rem 0;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        .consultation-card {
            transition: transform 0.2s ease;
        }
        .consultation-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="p-3">
                        <h4 class="text-white mb-4">
                            <i class="fas fa-home me-2"></i>BlockHaus Admin
                        </h4>
                        <nav class="nav flex-column">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                            <a class="nav-link" href="properties.php">
                                <i class="fas fa-building me-2"></i>Properties
                            </a>
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users me-2"></i>Users
                            </a>
                            <a class="nav-link active" href="consultations.php">
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
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Consultations Management</h2>
                        <div class="d-flex gap-2">
                            <span class="badge bg-primary">Total: <?php echo count($consultations); ?></span>
                            <span class="badge bg-warning">Pending: <?php echo count(array_filter($consultations, fn($c) => $c['status'] === 'pending')); ?></span>
                            <span class="badge bg-success">Active: <?php echo count(array_filter($consultations, fn($c) => $c['status'] === 'active')); ?></span>
                        </div>
                    </div>

                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <?php foreach ($consultations as $consultation): ?>
                            <div class="col-lg-6 col-xl-4 mb-4">
                                <div class="card consultation-card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">Consultation #<?php echo $consultation['id']; ?></h6>
                                        <span class="badge status-badge <?php 
                                            echo $consultation['status'] === 'pending' ? 'bg-warning' : 
                                                ($consultation['status'] === 'active' ? 'bg-success' : 
                                                ($consultation['status'] === 'completed' ? 'bg-info' : 'bg-secondary')); 
                                        ?>">
                                            <?php echo ucfirst($consultation['status']); ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <strong>Client:</strong><br>
                                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($consultation['user_name']); ?><br>
                                            <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($consultation['user_email']); ?>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <strong>Type:</strong> 
                                            <span class="badge bg-light text-dark"><?php echo ucfirst($consultation['consultation_type']); ?></span>
                                        </div>
                                        
                                        <?php if ($consultation['property_title']): ?>
                                            <div class="mb-3">
                                                <strong>Property:</strong><br>
                                                <i class="fas fa-building me-1"></i><?php echo htmlspecialchars($consultation['property_title']); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($consultation['consultant_name']): ?>
                                            <div class="mb-3">
                                                <strong>Consultant:</strong><br>
                                                <i class="fas fa-user-tie me-1"></i><?php echo htmlspecialchars($consultation['consultant_name']); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($consultation['notes']): ?>
                                            <div class="mb-3">
                                                <strong>Notes:</strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($consultation['notes']); ?></small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="mb-3">
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                Created: <?php echo date('M j, Y g:i A', strtotime($consultation['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <div class="d-flex gap-2">
                                            <?php if ($consultation['status'] === 'pending'): ?>
                                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal<?php echo $consultation['id']; ?>">
                                                    <i class="fas fa-user-plus me-1"></i>Assign
                                                </button>
                                            <?php endif; ?>
                                            
                                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $consultation['id']; ?>">
                                                <i class="fas fa-edit me-1"></i>Update Status
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Assign Consultant Modal -->
                            <div class="modal fade" id="assignModal<?php echo $consultation['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Assign Consultant</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="assign_consultant">
                                                <input type="hidden" name="consultation_id" value="<?php echo $consultation['id']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Select Consultant</label>
                                                    <select name="consultant_id" class="form-select" required>
                                                        <option value="">Choose consultant...</option>
                                                        <?php foreach ($consultants as $consultant): ?>
                                                            <option value="<?php echo $consultant['id']; ?>">
                                                                <?php echo htmlspecialchars($consultant['full_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Assign Consultant</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Update Status Modal -->
                            <div class="modal fade" id="statusModal<?php echo $consultation['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Update Status</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="consultation_id" value="<?php echo $consultation['id']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Status</label>
                                                    <select name="status" class="form-select" required>
                                                        <option value="pending" <?php echo $consultation['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="active" <?php echo $consultation['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                        <option value="completed" <?php echo $consultation['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                        <option value="cancelled" <?php echo $consultation['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Update Status</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (empty($consultations)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No consultations yet</h4>
                            <p class="text-muted">Consultations will appear here when users register.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
