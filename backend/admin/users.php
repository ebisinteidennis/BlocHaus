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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_role':
                $user_id = (int)$_POST['user_id'];
                $new_role = $_POST['role'];
                
                $query = "UPDATE users SET role = :role WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':role', $new_role);
                $stmt->bindParam(':id', $user_id);
                
                if ($stmt->execute()) {
                    $success_message = "User role updated successfully!";
                } else {
                    $error_message = "Failed to update user role.";
                }
                break;
                
            case 'toggle_status':
                $user_id = (int)$_POST['user_id'];
                $new_status = (int)$_POST['status'];
                
                $query = "UPDATE users SET is_active = :status WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':status', $new_status);
                $stmt->bindParam(':id', $user_id);
                
                if ($stmt->execute()) {
                    $success_message = "User status updated successfully!";
                } else {
                    $error_message = "Failed to update user status.";
                }
                break;
                
            case 'delete':
                $user_id = (int)$_POST['user_id'];
                
                // Don't allow deleting the current admin
                if ($user_id === $_SESSION['user_id']) {
                    $error_message = "You cannot delete your own account.";
                } else {
                    $query = "DELETE FROM users WHERE id = :id";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':id', $user_id);
                    
                    if ($stmt->execute()) {
                        $success_message = "User deleted successfully!";
                    } else {
                        $error_message = "Failed to delete user.";
                    }
                }
                break;
        }
    }
}

// Get all users
$query = "SELECT * FROM users ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user statistics
$stats = [];

// Total users by role
$query = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$stmt = $conn->prepare($query);
$stmt->execute();
$role_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($role_stats as $stat) {
    $stats[$stat['role']] = $stat['count'];
}

// Active vs inactive users
$query = "SELECT is_active, COUNT(*) as count FROM users GROUP BY is_active";
$stmt = $conn->prepare($query);
$stmt->execute();
$status_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($status_stats as $stat) {
    $stats[$stat['is_active'] ? 'active' : 'inactive'] = $stat['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - BlockHaus Admin</title>
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
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--primary-purple);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-purple);
        }
        
        .table-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .table-card .card-header {
            background: linear-gradient(135deg, var(--primary-purple), var(--secondary-purple));
            color: white;
            border: none;
            padding: 16px 24px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-purple), var(--secondary-purple));
            border: none;
            border-radius: 8px;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary-purple), var(--light-purple));
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-purple), var(--secondary-purple));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-active { background: #dcfce7; color: #166534; }
        .status-inactive { background: #fef2f2; color: #dc2626; }
        
        .role-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .role-admin { background: #fef3c7; color: #92400e; }
        .role-user { background: #dbeafe; color: #1e40af; }
        .role-consultant { background: #f3e8ff; color: #7c3aed; }
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
                        <a class="nav-link active" href="users.php">
                            <i class="fas fa-users me-2"></i>Users
                        </a>
                        <a class="nav-link" href="properties.php">
                            <i class="fas fa-building me-2"></i>Properties
                        </a>
                        <a class="nav-link" href="consultations.php">
                            <i class="fas fa-comments me-2"></i>Consultations
                        </a>
                        <a class="nav-link" href="newsletter.php">
                            <i class="fas fa-envelope me-2"></i>Newsletter
                        </a>
                        <a class="nav-link" href="analytics.php">
                            <i class="fas fa-chart-bar me-2"></i>Analytics
                        </a>
                        <hr class="my-3" style="border-color: rgba(255,255,255,0.3);">
                        <a class="nav-link" href="../../index.html" target="_blank">
                            <i class="fas fa-external-link-alt me-2"></i>View Site
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
                    <h2>Users Management</h2>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary">
                            <i class="fas fa-download me-2"></i>Export Users
                        </button>
                        <button class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>Add User
                        </button>
                    </div>
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
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <div class="stat-number"><?php echo $stats['user'] ?? 0; ?></div>
                                    <div class="text-muted">Regular Users</div>
                                </div>
                                <div class="text-primary">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <div class="stat-number"><?php echo $stats['admin'] ?? 0; ?></div>
                                    <div class="text-muted">Administrators</div>
                                </div>
                                <div class="text-primary">
                                    <i class="fas fa-user-shield fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <div class="stat-number"><?php echo $stats['consultant'] ?? 0; ?></div>
                                    <div class="text-muted">Consultants</div>
                                </div>
                                <div class="text-primary">
                                    <i class="fas fa-user-tie fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <div class="stat-number"><?php echo $stats['active'] ?? 0; ?></div>
                                    <div class="text-muted">Active Users</div>
                                </div>
                                <div class="text-primary">
                                    <i class="fas fa-user-check fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Users Table -->
                <div class="table-card">
                    <div class="card-header">
                        <h5 class="mb-0">All Users</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>Interest</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-3">
                                                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo ucfirst($user['interest_type']); ?></span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?php echo date('M j, Y', strtotime($user['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="#" onclick="editUser(<?php echo $user['id']; ?>)">
                                                            <i class="fas fa-edit me-2"></i>Edit
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="#" onclick="toggleUserStatus(<?php echo $user['id']; ?>, <?php echo $user['is_active'] ? 0 : 1; ?>)">
                                                            <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?> me-2"></i>
                                                            <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                        </a>
                                                    </li>
                                                    <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                            <i class="fas fa-trash me-2"></i>Delete
                                                        </a>
                                                    </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Hidden Forms -->
    <form id="statusForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="toggle_status">
        <input type="hidden" name="user_id" id="statusUserId">
        <input type="hidden" name="status" id="statusValue">
    </form>
    
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="user_id" id="deleteUserId">
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUser(userId) {
            // Implement edit user functionality
            alert('Edit user functionality will be implemented');
        }
        
        function toggleUserStatus(userId, newStatus) {
            const action = newStatus ? 'activate' : 'deactivate';
            if (confirm(`Are you sure you want to ${action} this user?`)) {
                document.getElementById('statusUserId').value = userId;
                document.getElementById('statusValue').value = newStatus;
                document.getElementById('statusForm').submit();
            }
        }
        
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                document.getElementById('deleteUserId').value = userId;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>
