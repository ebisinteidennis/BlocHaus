<?php
require_once 'backend/config/database.php';

// Get property ID from URL
$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$property_id) {
    header('Location: index.html');
    exit();
}

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Fetch property details
$query = "SELECT * FROM properties WHERE id = :id AND status = 'available'";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $property_id);
$stmt->execute();

$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    header('Location: index.html');
    exit();
}

// Parse images
$images = json_decode($property['images'], true) ?: [];
if (empty($images)) {
    $images = ['https://via.placeholder.com/800x600?text=No+Image+Available'];
}

// Format price
$crypto_price = number_format($property['price_bhs'], 0);
$usd_price = number_format($property['price_bhs'], 0); // Assuming 1:1 ratio for now

// Listing type mapping
$listing_types = [
    'buy' => 'For Sale',
    'rent' => 'For Rent',
    'sell' => 'Listed for Sale',
    'invest' => 'Investment Opportunity'
];

$listing_type = $listing_types[$property['listing_type']] ?? 'Available';

// Status badge class
$status_classes = [
    'available' => 'bg-success',
    'sold' => 'bg-danger',
    'rented' => 'bg-warning',
    'pending' => 'bg-info'
];

$status_class = $status_classes[$property['status']] ?? 'bg-secondary';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($property['title']); ?> - BlockHaus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <style>
        .property-gallery {
            position: relative;
            margin-bottom: 2rem;
        }
        
        .main-image {
            width: 100%;
            height: 500px;
            object-fit: cover;
            border-radius: 15px;
        }
        
        .thumbnail-gallery {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            overflow-x: auto;
            padding: 10px 0;
        }
        
        .thumbnail {
            width: 100px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .thumbnail:hover,
        .thumbnail.active {
            border-color: #6366f1;
            transform: scale(1.05);
        }
        
        .property-info-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .price-section {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 10px;
        }
        
        .feature-icon {
            width: 40px;
            height: 40px;
            background: #6366f1;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .chat-consultant-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            padding: 1rem 2rem;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        
        .chat-consultant-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
            color: white;
        }
        
        .back-btn {
            margin-bottom: 2rem;
        }
        
        .property-description {
            line-height: 1.8;
            color: #64748b;
        }
        
        .location-info {
            background: #f1f5f9;
            padding: 1.5rem;
            border-radius: 10px;
            margin: 2rem 0;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.html" style="color: #4a5568;">
                <img src="WhatsApp Image 2025-06-15 at 03.19.58_ae118555.jpg" alt="BlockHaus Logo" style="height: 40px; width: auto;" class="me-2">
                BlockHaus
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.html#buy">Buy</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.html#rent">Rent</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.html#sell">Sell</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.html#invest">Invest</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.html#about">About</a>
                    </li>
                </ul>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#loginModal">Log in</button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#getStartedModal">Get Started</button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Property Details Section -->
    <section class="property-details-section" style="padding-top: 120px; padding-bottom: 80px; background: #f8fafc;">
        <div class="container">
            <!-- Back Button -->
            <div class="back-btn">
                <button class="btn btn-outline-primary" onclick="history.back()">
                    <i class="fas fa-arrow-left me-2"></i>Back to Properties
                </button>
            </div>

            <div class="row">
                <!-- Property Gallery -->
                <div class="col-lg-8">
                    <div class="property-gallery">
                        <img id="mainImage" src="<?php echo htmlspecialchars($images[0]); ?>" alt="<?php echo htmlspecialchars($property['title']); ?>" class="main-image">
                        <div class="thumbnail-gallery">
                            <?php foreach ($images as $index => $image): ?>
                                <img src="<?php echo htmlspecialchars($image); ?>" 
                                     alt="<?php echo htmlspecialchars($property['title']); ?> - Image <?php echo $index + 1; ?>" 
                                     class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                     onclick="changeMainImage('<?php echo htmlspecialchars($image); ?>', this)">
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Property Info -->
                <div class="col-lg-4">
                    <div class="price-section">
                        <h3 class="mb-3"><?php echo htmlspecialchars($property['title']); ?></h3>
                        <div class="price-info">
                            <div class="crypto-price mb-2">
                                <span class="h2"><?php echo $crypto_price; ?> $BHS</span>
                            </div>
                            <div class="usd-price">
                                <span class="h5 opacity-75">≈ $<?php echo $usd_price; ?></span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="badge bg-light text-dark"><?php echo $listing_type; ?></span>
                        </div>
                    </div>

                    <!-- Chat with Consultant Button -->
                    <div class="text-center mb-4">
                        <button class="btn chat-consultant-btn w-100" onclick="chatWithConsultant()">
                            <i class="fas fa-comments me-2"></i>Chat with Consultant
                        </button>
                    </div>

                    <!-- Property Features -->
                    <div class="property-info-card">
                        <h5 class="mb-3">Property Features</h5>
                        <div class="feature-grid">
                            <div class="feature-item">
                                <div class="feature-icon">
                                    <i class="fas fa-bed"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?php echo $property['bedrooms']; ?></div>
                                    <small class="text-muted">Bedrooms</small>
                                </div>
                            </div>
                            <div class="feature-item">
                                <div class="feature-icon">
                                    <i class="fas fa-bath"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?php echo $property['bathrooms']; ?></div>
                                    <small class="text-muted">Bathrooms</small>
                                </div>
                            </div>
                            <div class="feature-item">
                                <div class="feature-icon">
                                    <i class="fas fa-ruler-combined"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?php echo number_format($property['square_feet']); ?></div>
                                    <small class="text-muted">Square Feet</small>
                                </div>
                            </div>
                            <div class="feature-item">
                                <div class="feature-icon">
                                    <i class="fas fa-home"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?php echo ucfirst($property['property_type']); ?></div>
                                    <small class="text-muted">Property Type</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Property Details -->
            <div class="row mt-5">
                <div class="col-lg-8">
                    <div class="property-info-card">
                        <h4 class="mb-4">Property Description</h4>
                        <div class="property-description">
                            <?php echo nl2br(htmlspecialchars($property['description'] ?: 'No description available.')); ?>
                        </div>
                    </div>

                    <div class="property-info-card">
                        <h4 class="mb-4">Location Information</h4>
                        <div class="location-info">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-map-marker-alt text-primary me-3"></i>
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($property['location']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($property['country']); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="property-info-card">
                        <h5 class="mb-3">Contact Information</h5>
                        <div class="contact-info">
                            <div class="mb-3">
                                <strong>Property ID:</strong>
                                <span>#<?php echo $property['id']; ?></span>
                            </div>
                            <div class="mb-3">
                                <strong>Listed:</strong>
                                <span><?php echo date('M j, Y', strtotime($property['created_at'])); ?></span>
                            </div>
                            <div class="mb-3">
                                <strong>Status:</strong>
                                <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($property['status']); ?></span>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="consultant-info">
                            <h6 class="mb-3">Your Consultant</h6>
                            <div class="d-flex align-items-center">
                                <div class="consultant-avatar me-3">
                                    <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80" 
                                         alt="Consultant" class="rounded-circle" width="50" height="50">
                                </div>
                                <div>
                                    <h6 class="mb-0">John Consultant</h6>
                                    <small class="text-muted">Real Estate Specialist</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Additional Chat Button -->
                        <div class="mt-4">
                            <button class="btn chat-consultant-btn w-100" onclick="chatWithConsultant()">
                                <i class="fas fa-comments me-2"></i>Start Consultation
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Log In to BlockHaus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Log In</button>
                    </form>
                    <div class="text-center mt-3">
                        <p>Don't have an account? <a href="#" data-bs-toggle="modal" data-bs-target="#getStartedModal" data-bs-dismiss="modal">Get Started</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Get Started Modal -->
    <div class="modal fade" id="getStartedModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Get Started with BlockHaus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">I'm interested in</label>
                            <select name="interest_type" class="form-select" required>
                                <option value="">Select your interest</option>
                                <option value="buying">Buying Properties</option>
                                <option value="selling">Selling Properties</option>
                                <option value="investing">Investing in Real Estate</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Register & Chat with Consultant</button>
                    </form>
                    <div class="text-center mt-3">
                        <p>Already have an account? <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">Log In</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Property data for JavaScript
        const currentProperty = <?php echo json_encode($property); ?>;
        
        // Change main image function
        function changeMainImage(imageSrc, thumbnail) {
            document.getElementById('mainImage').src = imageSrc;
            
            // Update active thumbnail
            document.querySelectorAll('.thumbnail').forEach(thumb => {
                thumb.classList.remove('active');
            });
            thumbnail.classList.add('active');
        }
        
        // Chat with consultant function
        function chatWithConsultant() {
            // Create modal HTML
            const modalHTML = `
                <div class="modal fade" id="consultantModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Contact Our Consultant</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="text-center mb-4">
                                    <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80" 
                                         alt="Consultant" class="rounded-circle mb-3" width="80" height="80">
                                    <h6>John Consultant</h6>
                                    <p class="text-muted">Real Estate Specialist</p>
                                </div>
                                
                                <div class="property-info mb-4 p-3 bg-light rounded">
                                    <h6>Property: ${currentProperty.title}</h6>
                                    <p class="mb-0">ID: #${currentProperty.id} | ${currentProperty.location}</p>
                                    <p class="mb-0 text-primary fw-bold"><?php echo $crypto_price; ?> $BHS</p>
                                </div>
                                
                                <form id="consultantForm">
                                    <div class="mb-3">
                                        <label class="form-label">Your Name</label>
                                        <input type="text" class="form-control" name="name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Phone (Optional)</label>
                                        <input type="tel" class="form-control" name="phone">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Message</label>
                                        <textarea class="form-control" name="message" rows="3" placeholder="I'm interested in this property and would like to schedule a consultation..."></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Preferred Contact Method</label>
                                        <select class="form-select" name="contact_method">
                                            <option value="email">Email</option>
                                            <option value="phone">Phone Call</option>
                                            <option value="whatsapp">WhatsApp</option>
                                            <option value="video">Video Call</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Send Consultation Request</button>
                                </form>
                                
                                <div class="mt-4 text-center">
                                    <p class="mb-2"><strong>Or contact us directly:</strong></p>
                                    <p class="mb-1"><i class="fas fa-envelope me-2"></i> consultant@blockhaus.com</p>
                                    <p class="mb-1"><i class="fas fa-phone me-2"></i> +1 (555) 123-4567</p>
                                    <p class="mb-0"><i class="fab fa-whatsapp me-2"></i> +1 (555) 123-4567</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal if any
            const existingModal = document.getElementById('consultantModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Add modal to body
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('consultantModal'));
            modal.show();
            
            // Handle form submission
            document.getElementById('consultantForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(e.target);
                const name = formData.get('name');
                const contactMethod = formData.get('contact_method');
                
                // Simulate sending the request
                const submitBtn = e.target.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.textContent = 'Sending...';
                submitBtn.disabled = true;
                
                setTimeout(() => {
                    modal.hide();
                    showNotification(`Thank you ${name}! Your consultation request has been sent. Our consultant will contact you via ${contactMethod} within 24 hours.`, 'success');
                }, 1500);
            });
        }
        
        // Utility function to show notifications
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show`;
            notification.style.cssText = `
                position: fixed;
                top: 100px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            `;
            
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>
