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

// Handle newsletter actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'send_newsletter':
                $subject = $_POST['subject'];
                $content = $_POST['content'];
                
                // Get all active subscribers
                $query = "SELECT email FROM newsletter_subscriptions WHERE status = 'active'";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $sent_count = 0;
                foreach ($subscribers as $subscriber) {
                    // In a real application, you would use a proper email service like SendGrid, Mailgun, etc.
                    // For now, we'll just simulate sending
                    $sent_count++;
                }
                
                $success_message = "Newsletter sent to {$sent_count} subscribers!";
                break;
                
            case 'delete_subscriber':
                $subscriber_id = $_POST['subscriber_id'];
                
                $query = "DELETE FROM newsletter_subscriptions WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id', $subscriber_id);
                
                if ($stmt->execute()) {
                    $success_message = "Subscriber deleted successfully!";
                } else {
                    $error_message = "Failed to delete subscriber.";
                }
                break;
                
            case 'toggle_status':
                $subscriber_id = $_POST['subscriber_id'];
                $new_status = $_POST['new_status'];
                
                $query = "UPDATE newsletter_subscriptions SET status = :status WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':status', $new_status);
                $stmt->bindParam(':id', $subscriber_id);
                
                if ($stmt->execute()) {
                    $success_message = "Subscriber status updated successfully!";
                } else {
                    $error_message = "Failed to update subscriber status.";
                }
                break;
        }
    }
}

// Get all newsletter subscriptions
$query = "SELECT * FROM newsletter_subscriptions ORDER BY subscribed_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$active_count = count(array_filter($subscribers, fn($s) => $s['status'] === 'active'));
$unsubscribed_count = count(array_filter($subscribers, fn($s) => $s['status'] === 'unsubscribed'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter Management - BlockHaus Admin</title>
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
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .subscriber-card {
            transition: transform 0.2s ease;
        }
        .subscriber-card:hover {
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
                            <a class="nav-link" href="consultations.php">
                                <i class="fas fa-comments me-2"></i>Consultations
                            </a>
                            <a class="nav-link active" href="newsletter.php">
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
                        <h2>Newsletter Management</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sendNewsletterModal">
                            <i class="fas fa-paper-plane me-2"></i>Send Newsletter
                        </button>
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

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-users fa-2x mb-3"></i>
                                    <h3><?php echo count($subscribers); ?></h3>
                                    <p class="mb-0">Total Subscribers</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-check-circle fa-2x mb-3"></i>
                                    <h3><?php echo $active_count; ?></h3>
                                    <p class="mb-0">Active Subscribers</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-user-times fa-2x mb-3"></i>
                                    <h3><?php echo $unsubscribed_count; ?></h3>
                                    <p class="mb-0">Unsubscribed</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Subscribers List -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Subscribers List</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($subscribers)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Email</th>
                                                <th>Status</th>
                                                <th>Subscribed Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($subscribers as $subscriber): ?>
                                                <tr>
                                                    <td>
                                                        <i class="fas fa-envelope me-2"></i>
                                                        <?php echo htmlspecialchars($subscriber['email']); ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $subscriber['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                                            <?php echo ucfirst($subscriber['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?php echo date('M j, Y', strtotime($subscriber['subscribed_at'])); ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <?php if ($subscriber['status'] === 'active'): ?>
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="action" value="toggle_status">
                                                                    <input type="hidden" name="subscriber_id" value="<?php echo $subscriber['id']; ?>">
                                                                    <input type="hidden" name="new_status" value="unsubscribed">
                                                                    <button type="submit" class="btn btn-outline-warning" title="Unsubscribe">
                                                                        <i class="fas fa-user-times"></i>
                                                                    </button>
                                                                </form>
                                                            <?php else: ?>
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="action" value="toggle_status">
                                                                    <input type="hidden" name="subscriber_id" value="<?php echo $subscriber['id']; ?>">
                                                                    <input type="hidden" name="new_status" value="active">
                                                                    <button type="submit" class="btn btn-outline-success" title="Reactivate">
                                                                        <i class="fas fa-user-check"></i>
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                            
                                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this subscriber?')">
                                                                <input type="hidden" name="action" value="delete_subscriber">
                                                                <input type="hidden" name="subscriber_id" value="<?php echo $subscriber['id']; ?>">
                                                                <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-envelope fa-3x text-muted mb-3"></i>
                                    <h4 class="text-muted">No subscribers yet</h4>
                                    <p class="text-muted">Newsletter subscribers will appear here when users sign up.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Send Newsletter Modal -->
    <div class="modal fade" id="sendNewsletterModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send Newsletter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="send_newsletter">
                        
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" name="subject" class="form-control" placeholder="Newsletter subject..." required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Content</label>
                            <textarea name="content" class="form-control" rows="10" placeholder="Newsletter content..." required></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            This newsletter will be sent to <strong><?php echo $active_count; ?></strong> active subscribers.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Send Newsletter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
