// Property Details JavaScript - Handle property details page functionality

// API Configuration
const API_BASE = './backend/api';
let currentProperty = null;
let currentUser = null;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize property details page
    initializePropertyDetails();
    initializeFormHandlers();
    checkUserSession();
});

// Initialize property details
async function initializePropertyDetails() {
    const urlParams = new URLSearchParams(window.location.search);
    const propertyId = urlParams.get('id');
    
    if (!propertyId) {
        showNotification('Property ID not found. Redirecting to home page...', 'error');
        setTimeout(() => {
            window.location.href = 'index.html';
        }, 2000);
        return;
    }
    
    await loadPropertyDetails(propertyId);
}

// Load property details from backend
async function loadPropertyDetails(propertyId) {
    try {
        const response = await fetch(`${API_BASE}/properties.php?action=get_property&id=${propertyId}`);
        const data = await response.json();
        
        if (data.success && data.data) {
            currentProperty = data.data;
            populatePropertyDetails(data.data);
        } else {
            // Fallback to static data if API fails
            loadStaticPropertyData(propertyId);
        }
    } catch (error) {
        console.error('Error loading property details:', error);
        // Fallback to static data if API fails
        loadStaticPropertyData(propertyId);
    }
}

// Fallback static property data
function loadStaticPropertyData(propertyId) {
    const staticProperties = {
        '1': {
            id: 1,
            title: 'Modern Waterfront Villa',
            description: 'Stunning waterfront villa with pool and modern amenities. This exceptional property features panoramic water views, a private infinity pool, and state-of-the-art smart home technology. The open-concept design seamlessly blends indoor and outdoor living with floor-to-ceiling windows throughout. Premium finishes include marble countertops, hardwood floors, and custom millwork. The gourmet kitchen boasts professional-grade appliances and a large island perfect for entertaining.',
            location: 'Austin, Texas',
            country: 'US',
            property_type: 'villa',
            bedrooms: 4,
            bathrooms: 3,
            square_feet: 2500,
            price_bhs: 835404.00,
            listing_type: 'buy',
            status: 'available',
            featured: true,
            images: ['https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'],
            created_at: '2024-01-15T10:30:00Z'
        },
        '2': {
            id: 2,
            title: 'Luxury Mediterranean Villa',
            description: 'Elegant Mediterranean-style villa in prime location. This magnificent estate showcases timeless architecture with modern luxury amenities. Features include a grand foyer with soaring ceilings, formal dining room, and spacious family room with fireplace. The master suite offers a private balcony, walk-in closet, and spa-like bathroom. Outdoor amenities include a resort-style pool, outdoor kitchen, and beautifully landscaped gardens.',
            location: 'Dubai, UAE',
            country: 'Dubai',
            property_type: 'villa',
            bedrooms: 5,
            bathrooms: 4,
            square_feet: 3200,
            price_bhs: 1253106.00,
            listing_type: 'buy',
            status: 'available',
            featured: true,
            images: ['https://images.unsplash.com/photo-1600585154340-be6161a56a0c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'],
            created_at: '2024-01-10T14:20:00Z'
        },
        '3': {
            id: 3,
            title: 'Contemporary Beachfront House',
            description: 'Modern beachfront property with stunning ocean views. This architectural masterpiece offers unobstructed ocean views from every room. The contemporary design features clean lines, expansive glass walls, and premium materials throughout. Highlights include a rooftop terrace, private beach access, and a wine cellar. The open-plan living area flows seamlessly to outdoor terraces perfect for al fresco dining and entertaining.',
            location: 'Dubai, UAE',
            country: 'Dubai',
            property_type: 'house',
            bedrooms: 4,
            bathrooms: 3,
            square_feet: 2800,
            price_bhs: 1002485.00,
            listing_type: 'buy',
            status: 'available',
            featured: false,
            images: ['https://images.unsplash.com/photo-1600607687644-c7171b42498b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'],
            created_at: '2024-01-08T09:15:00Z'
        },
        '4': {
            id: 4,
            title: 'Minimalist Glass Villa',
            description: 'Sleek minimalist design with floor-to-ceiling windows. This ultra-modern villa epitomizes contemporary luxury with its clean geometric lines and abundant natural light. The innovative design features disappearing glass walls that create seamless indoor-outdoor living. Premium amenities include a floating staircase, integrated sound system, and automated lighting. The minimalist aesthetic is complemented by high-end finishes and custom-designed furniture.',
            location: 'Austin, Texas',
            country: 'US',
            property_type: 'villa',
            bedrooms: 3,
            bathrooms: 2,
            square_feet: 2100,
            price_bhs: 668323.00,
            listing_type: 'buy',
            status: 'available',
            featured: false,
            images: ['https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'],
            created_at: '2024-01-05T16:45:00Z'
        },
        '5': {
            id: 5,
            title: 'Luxury Dubai Apartment',
            description: 'Modern luxury apartment with stunning city views and premium furnishings. Located in the heart of Dubai, this sophisticated apartment offers panoramic views of the city skyline. The contemporary design features high-end finishes, smart home technology, and premium appliances. Amenities include access to a fitness center, swimming pool, and 24/7 concierge service.',
            location: 'Dubai, UAE',
            country: 'Dubai',
            property_type: 'apartment',
            bedrooms: 2,
            bathrooms: 2,
            square_feet: 1200,
            price_bhs: 8500.00,
            listing_type: 'rent',
            status: 'available',
            featured: true,
            images: ['IMG-20250615-WA0006.jpg'],
            created_at: '2024-01-12T11:30:00Z'
        },
        '7': {
            id: 7,
            title: 'Modern Dubai Kitchen',
            description: 'Contemporary kitchen with marble island and premium appliances. This stunning apartment features a state-of-the-art kitchen with marble countertops, premium appliances, and custom cabinetry. The open-plan design creates a seamless flow between the kitchen and living areas, perfect for entertaining. Located in a prestigious Dubai development with world-class amenities.',
            location: 'Dubai, UAE',
            country: 'Dubai',
            property_type: 'apartment',
            bedrooms: 3,
            bathrooms: 2,
            square_feet: 1500,
            price_bhs: 12000.00,
            listing_type: 'rent',
            status: 'available',
            featured: true,
            images: ['IMG-20250615-WA0008.jpg'],
            created_at: '2024-01-14T13:20:00Z'
        }
    };
    
    const property = staticProperties[propertyId];
    
    if (property) {
        currentProperty = property;
        populatePropertyDetails(property);
        showNotification('Property details loaded successfully!', 'success');
    } else {
        showNotification('Property not found. Redirecting to home page...', 'error');
        setTimeout(() => {
            window.location.href = 'index.html';
        }, 2000);
    }
}

// Populate property details in the DOM
function populatePropertyDetails(property) {
    // Basic property information
    document.getElementById('propertyTitle').textContent = property.title;
    document.getElementById('propertyId').textContent = `#${property.id}`;
    document.getElementById('propertyLocation').textContent = property.location;
    document.getElementById('propertyCountry').textContent = property.country;
    document.getElementById('propertyDescription').textContent = property.description || 'No description available.';
    
    // Pricing information
    const cryptoPrice = parseFloat(property.price_bhs).toLocaleString();
    document.getElementById('cryptoPrice').textContent = `${cryptoPrice} $BHS`;
    
    // Calculate USD equivalent (assuming 1 BHS = 1 USD for now)
    const usdPrice = parseFloat(property.price_bhs).toLocaleString();
    document.getElementById('usdPrice').textContent = `≈ $${usdPrice}`;
    
    // Listing type
    const listingTypeMap = {
        'buy': 'For Sale',
        'rent': 'For Rent',
        'sell': 'Listed for Sale',
        'invest': 'Investment Opportunity'
    };
    document.getElementById('listingType').textContent = listingTypeMap[property.listing_type] || 'Available';
    
    // Property status
    const statusElement = document.getElementById('propertyStatus');
    statusElement.textContent = property.status.charAt(0).toUpperCase() + property.status.slice(1);
    statusElement.className = `badge ${getStatusBadgeClass(property.status)}`;
    
    // Listed date
    const listedDate = new Date(property.created_at).toLocaleDateString();
    document.getElementById('listedDate').textContent = listedDate;
    
    // Property features
    populatePropertyFeatures(property);
    
    // Property images
    populatePropertyImages(property);
}

// Populate property features
function populatePropertyFeatures(property) {
    const featuresContainer = document.getElementById('propertyFeatures');
    
    const features = [
        {
            icon: 'fas fa-bed',
            label: 'Bedrooms',
            value: property.bedrooms
        },
        {
            icon: 'fas fa-bath',
            label: 'Bathrooms',
            value: property.bathrooms
        },
        {
            icon: 'fas fa-ruler-combined',
            label: 'Square Feet',
            value: property.square_feet.toLocaleString()
        },
        {
            icon: 'fas fa-home',
            label: 'Property Type',
            value: property.property_type.charAt(0).toUpperCase() + property.property_type.slice(1)
        }
    ];
    
    featuresContainer.innerHTML = '';
    
    features.forEach(feature => {
        const featureElement = document.createElement('div');
        featureElement.className = 'feature-item';
        featureElement.innerHTML = `
            <div class="feature-icon">
                <i class="${feature.icon}"></i>
            </div>
            <div>
                <div class="fw-semibold">${feature.value}</div>
                <small class="text-muted">${feature.label}</small>
            </div>
        `;
        featuresContainer.appendChild(featureElement);
    });
}

// Populate property images
function populatePropertyImages(property) {
    const mainImage = document.getElementById('mainImage');
    const thumbnailGallery = document.getElementById('thumbnailGallery');
    
    let images = [];
    
    // Parse images from property data
    if (property.images) {
        try {
            if (typeof property.images === 'string') {
                images = JSON.parse(property.images);
            } else if (Array.isArray(property.images)) {
                images = property.images;
            }
        } catch (e) {
            console.error('Error parsing images:', e);
            images = [];
        }
    }
    
    // If no images, use a placeholder
    if (images.length === 0) {
        images = ['https://via.placeholder.com/800x600?text=No+Image+Available'];
    }
    
    // Set main image
    mainImage.src = images[0];
    mainImage.alt = property.title;
    
    // Clear and populate thumbnails
    thumbnailGallery.innerHTML = '';
    
    images.forEach((image, index) => {
        const thumbnail = document.createElement('img');
        thumbnail.src = image;
        thumbnail.alt = `${property.title} - Image ${index + 1}`;
        thumbnail.className = `thumbnail ${index === 0 ? 'active' : ''}`;
        thumbnail.addEventListener('click', () => {
            // Update main image
            mainImage.src = image;
            
            // Update active thumbnail
            document.querySelectorAll('.thumbnail').forEach(thumb => {
                thumb.classList.remove('active');
            });
            thumbnail.classList.add('active');
        });
        
        thumbnailGallery.appendChild(thumbnail);
    });
}

// Get status badge class
function getStatusBadgeClass(status) {
    const statusClasses = {
        'available': 'bg-success',
        'sold': 'bg-danger',
        'rented': 'bg-warning',
        'pending': 'bg-info'
    };
    return statusClasses[status] || 'bg-secondary';
}

// Chat with consultant function
async function chatWithConsultant() {
    if (!currentProperty) {
        showNotification('Property information not available.', 'error');
        return;
    }
    
    // For demo purposes, show a contact modal instead of requiring login
    showConsultantContactModal();
}

// Show consultant contact modal
function showConsultantContactModal() {
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
                            <p class="mb-0 text-primary fw-bold">${parseFloat(currentProperty.price_bhs).toLocaleString()} $BHS</p>
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
        const email = formData.get('email');
        const phone = formData.get('phone');
        const message = formData.get('message');
        const contactMethod = formData.get('contact_method');
        
        // Simulate sending the request
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Sending...';
        submitBtn.disabled = true;
        
        setTimeout(() => {
            modal.hide();
            showNotification(`Thank you ${name}! Your consultation request has been sent. Our consultant will contact you via ${contactMethod} within 24 hours.`, 'success');
            
            // Try to send to backend if available
            tryBackendConsultation({
                name: name,
                email: email,
                phone: phone,
                message: message,
                contact_method: contactMethod,
                property_id: currentProperty.id,
                property_title: currentProperty.title
            });
        }, 1500);
    });
}

// Try to send consultation to backend (fallback if backend is available)
async function tryBackendConsultation(consultationData) {
    try {
        const response = await fetch(`${API_BASE}/chat.php?action=create_consultation`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(consultationData)
        });
        
        const data = await response.json();
        console.log('Backend consultation result:', data);
    } catch (error) {
        console.log('Backend not available, consultation handled via frontend');
    }
}

// Check if user is logged in
async function checkUserSession() {
    try {
        const response = await fetch(`${API_BASE}/auth.php?action=check_session`);
        const data = await response.json();
        if (data.success && data.user) {
            currentUser = data.user;
            updateUIForLoggedInUser();
        }
    } catch (error) {
        console.log('No active session');
    }
}

// Update UI for logged in user
function updateUIForLoggedInUser() {
    const loginBtn = document.querySelector('button[data-bs-target="#loginModal"]');
    const getStartedBtn = document.querySelector('button[data-bs-target="#getStartedModal"]');
    
    if (loginBtn && getStartedBtn) {
        loginBtn.textContent = 'Dashboard';
        loginBtn.removeAttribute('data-bs-toggle');
        loginBtn.removeAttribute('data-bs-target');
        
        // Redirect based on user role
        if (currentUser.role === 'admin') {
            loginBtn.onclick = () => window.location.href = 'backend/admin/index.php';
        } else {
            loginBtn.onclick = () => window.location.href = 'user-dashboard.html';
        }
        
        getStartedBtn.textContent = 'Logout';
        getStartedBtn.removeAttribute('data-bs-toggle');
        getStartedBtn.removeAttribute('data-bs-target');
        getStartedBtn.onclick = handleLogout;
    }
}

// Initialize form handlers
function initializeFormHandlers() {
    // Login form
    const loginForm = document.querySelector('#loginModal form');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    // Registration form
    const registerForm = document.querySelector('#getStartedModal form');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegistration);
    }
}

// Handle login
async function handleLogin(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const loginData = {
        email: formData.get('email'),
        password: formData.get('password')
    };
    
    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Logging in...';
    submitBtn.disabled = true;
    
    try {
        const response = await fetch(`${API_BASE}/auth.php?action=login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(loginData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentUser = data.user;
            
            // Close modal and show success
            const modal = bootstrap.Modal.getInstance(document.getElementById('loginModal'));
            modal.hide();
            
            showNotification(`Welcome back, ${data.user.name}!`, 'success');
            updateUIForLoggedInUser();
            
            // Reset form
            e.target.reset();
        } else {
            showNotification('Login failed: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Login error:', error);
        showNotification('Login failed. Please try again.', 'error');
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
}

// Handle registration
async function handleRegistration(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const registerData = {
        full_name: formData.get('full_name'),
        email: formData.get('email'),
        password: formData.get('password'),
        interest_type: formData.get('interest_type')
    };
    
    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Creating Account...';
    submitBtn.disabled = true;
    
    try {
        const response = await fetch(`${API_BASE}/auth.php?action=register`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(registerData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Store user data
            currentUser = {
                id: data.user_id,
                name: registerData.full_name,
                email: registerData.email,
                interest_type: registerData.interest_type,
                role: 'user'
            };
            
            // Close modal and show success
            const modal = bootstrap.Modal.getInstance(document.getElementById('getStartedModal'));
            modal.hide();
            
            showNotification('Registration successful!', 'success');
            updateUIForLoggedInUser();
            
            // Reset form
            e.target.reset();
        } else {
            showNotification('Registration failed: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Registration error:', error);
        showNotification('Registration failed. Please try again.', 'error');
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
}

// Handle logout
async function handleLogout() {
    try {
        const response = await fetch(`${API_BASE}/auth.php?action=logout`, {
            method: 'POST'
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentUser = null;
            showNotification('Logged out successfully!', 'success');
            
            // Reset UI
            setTimeout(() => {
                location.reload();
            }, 1000);
        }
    } catch (error) {
        console.error('Logout error:', error);
    }
}

// Utility function to show notifications
function showNotification(message, type = 'info') {
    // Create notification element
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

// Export functions for global access
window.chatWithConsultant = chatWithConsultant;
