<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

// Get dashboard statistics
$database = new Database();
$conn = $database->getConnection();

// Get counts
$stats = [];

// Total users
$query = "SELECT COUNT(*) as count FROM users WHERE role = 'user'";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total properties
$query = "SELECT COUNT(*) as count FROM properties";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['total_properties'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Active consultations
$query = "SELECT COUNT(*) as count FROM consultations WHERE status IN ('pending', 'active')";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['active_consultations'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Newsletter subscribers
$query = "SELECT COUNT(*) as count FROM newsletter_subscriptions WHERE status = 'active'";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['newsletter_subscribers'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Recent users
$query = "SELECT full_name, email, interest_type, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->execute();
$recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent consultations
$query = "SELECT c.*, u.full_name, u.email FROM consultations c 
          JOIN users u ON c.user_id = u.id 
          ORDER BY c.created_at DESC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->execute();
$recent_consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BlockHaus Admin Dashboard</title>
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
            font-size: 2.5rem;
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
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="users.php">
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
                    <h2>Dashboard Overview</h2>
                    <div class="text-muted">
                        Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                                    <div class="text-muted">Total Users</div>
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
                                    <div class="stat-number"><?php echo $stats['total_properties']; ?></div>
                                    <div class="text-muted">Properties</div>
                                </div>
                                <div class="text-primary">
                                    <i class="fas fa-building fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <div class="stat-number"><?php echo $stats['active_consultations']; ?></div>
                                    <div class="text-muted">Active Consultations</div>
                                </div>
                                <div class="text-primary">
                                    <i class="fas fa-comments fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <div class="stat-number"><?php echo $stats['newsletter_subscribers']; ?></div>
                                    <div class="text-muted">Newsletter Subscribers</div>
                                </div>
                                <div class="text-primary">
                                    <i class="fas fa-envelope fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="table-card">
                            <div class="card-header">
                                <h5 class="mb-0">Recent Users</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Name</th>
                                                <th>Interest</th>
                                                <th>Joined</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_users as $user): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo ucfirst($user['interest_type']); ?></span>
                                                </td>
                                                <td>
                                                    <small><?php echo date('M j, Y', strtotime($user['created_at'])); ?></small>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6 mb-4">
                        <div class="table-card">
                            <div class="card-header">
                                <h5 class="mb-0">Recent Consultations</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>User</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_consultations as $consultation): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($consultation['full_name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($consultation['email']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo ucfirst($consultation['consultation_type']); ?></span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusClass = '';
                                                    switch($consultation['status']) {
                                                        case 'pending': $statusClass = 'bg-warning'; break;
                                                        case 'active': $statusClass = 'bg-success'; break;
                                                        case 'completed': $statusClass = 'bg-secondary'; break;
                                                        case 'cancelled': $statusClass = 'bg-danger'; break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($consultation['status']); ?></span>
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
                
                <!-- Quick Actions -->
                <div class="row">
                    <div class="col-12">
                        <div class="table-card">
                            <div class="card-header">
                                <h5 class="mb-0">Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-2">
                                        <a href="properties.php?action=add" class="btn btn-primary w-100">
                                            <i class="fas fa-plus me-2"></i>Add Property
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="users.php" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-users me-2"></i>Manage Users
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="consultations.php" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-comments me-2"></i>View Consultations
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="newsletter.php" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-envelope me-2"></i>Send Newsletter
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
