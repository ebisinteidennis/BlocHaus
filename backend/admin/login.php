<?php
session_start();

// If already logged in and is admin, redirect to dashboard
if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin') {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config/database.php';
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required';
    } else {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "SELECT id, full_name, email, password, role FROM users WHERE email = :email AND role IN ('admin', 'consultant') AND is_active = 1";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['full_name'];
                
                header('Location: index.php');
                exit();
            } else {
                $error = 'Invalid password';
            }
        } else {
            $error = 'Admin account not found';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BlockHaus Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-purple: #6366f1;
            --secondary-purple: #8b5cf6;
            --light-purple: #a855f7;
            --dark-purple: #4c1d95;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-purple), var(--secondary-purple));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary-purple), var(--secondary-purple));
            color: white;
            padding: 40px 30px 30px;
            text-align: center;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-purple);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-purple), var(--secondary-purple));
            border: none;
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary-purple), var(--light-purple));
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.3);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .brand-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <div class="brand-icon">
                <i class="fas fa-home fa-2x"></i>
            </div>
            <h3 class="mb-2">BlockHaus Admin</h3>
            <p class="mb-0 opacity-75">Sign in to your admin account</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0" style="border: 2px solid #e2e8f0; border-right: none; border-radius: 10px 0 0 10px;">
                            <i class="fas fa-envelope text-muted"></i>
                        </span>
                        <input type="email" class="form-control border-start-0" id="email" name="email" 
                               style="border-radius: 0 10px 10px 0;" required 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0" style="border: 2px solid #e2e8f0; border-right: none; border-radius: 10px 0 0 10px;">
                            <i class="fas fa-lock text-muted"></i>
                        </span>
                        <input type="password" class="form-control border-start-0" id="password" name="password" 
                               style="border-radius: 0 10px 10px 0;" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                </button>
            </form>
            
            <div class="text-center">
                <small class="text-muted">
                    <i class="fas fa-shield-alt me-1"></i>
                    Admin access only
                </small>
            </div>
            
            <hr class="my-4">
            
            <div class="text-center">
                <a href="../../index.html" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Website
                </a>
            </div>
        </div>
    </div>
    
    <!-- Demo Credentials Info -->
    <div class="position-fixed bottom-0 start-0 p-3">
        <div class="card" style="max-width: 300px;">
            <div class="card-body">
                <h6 class="card-title text-primary">Demo Credentials</h6>
                <p class="card-text small mb-2">
                    <strong>Admin:</strong><br>
                    Email: admin@blockhaus.com<br>
                    Password: password
                </p>
                <p class="card-text small mb-0">
                    <strong>Consultant:</strong><br>
                    Email: consultant@blockhaus.com<br>
                    Password: password
                </p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
