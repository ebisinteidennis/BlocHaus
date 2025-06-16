// BlockHaus JavaScript - Interactive functionality with Backend Integration

// API Configuration
const API_BASE = './backend/api';
let currentUser = null;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all functionality
    initializeNavigation();
    initializeModals();
    initializePropertySearch();
    initializeAnimations();
    initializeBuyLocationModal();
    initializeFormHandlers();
    loadProperties();
    checkUserSession();
});

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

// Load properties from backend
async function loadProperties() {
    try {
        const response = await fetch(`${API_BASE}/properties.php`);
        const data = await response.json();
        
        if (data.success) {
            updatePropertyListings(data.data);
        }
    } catch (error) {
        console.error('Error loading properties:', error);
    }
}

// Update property listings in the DOM
function updatePropertyListings(properties) {
    const listingsContainer = document.querySelector('.listings-section .row');
    if (!listingsContainer) return;
    
    // Clear existing listings except the first few static ones
    const existingCards = listingsContainer.querySelectorAll('.col-lg-4');
    existingCards.forEach((card, index) => {
        if (index >= 6) { // Keep first 6 static cards
            card.remove();
        }
    });
    
    // Add new properties from database
    properties.forEach(property => {
        const propertyCard = createPropertyCard(property);
        listingsContainer.appendChild(propertyCard);
    });
}

// Create property card element
function createPropertyCard(property) {
    const col = document.createElement('div');
    col.className = 'col-lg-4 col-md-6';
    
    const mainImage = property.images && property.images.length > 0 ? property.images[0] : 'https://via.placeholder.com/600x400';
    
    col.innerHTML = `
        <div class="property-listing-card" data-property-id="${property.id}">
            <div class="property-image">
                <img src="${mainImage}" alt="${property.title}" class="img-fluid">
                <div class="property-price">
                    <span class="crypto-price">${property.price_formatted.bhs}</span>
                </div>
            </div>
            <div class="property-details">
                <h5>${property.title}</h5>
                <p class="location"><i class="fas fa-map-marker-alt"></i> ${property.location}</p>
                <div class="property-features">
                    <span><i class="fas fa-bed"></i> ${property.bedrooms} Beds</span>
                    <span><i class="fas fa-bath"></i> ${property.bathrooms} Baths</span>
                    <span><i class="fas fa-ruler-combined"></i> ${property.square_feet.toLocaleString()} Sq ft</span>
                </div>
            </div>
        </div>
    `;
    
    return col;
}

// Navigation functionality
function initializeNavigation() {
    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Navbar background on scroll
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 50) {
            navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
        } else {
            navbar.style.background = 'rgba(255, 255, 255, 0.95)';
            navbar.style.boxShadow = '0 1px 3px rgba(0, 0, 0, 0.05)';
        }
    });
}

// Modal functionality
function initializeModals() {
    // Handle modal transitions
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('show.bs.modal', function() {
            document.body.style.overflow = 'hidden';
        });
        
        modal.addEventListener('hidden.bs.modal', function() {
            document.body.style.overflow = 'auto';
        });
    });
}

// Property search functionality
function initializePropertySearch() {
    // Handle search form submission
    const searchForm = document.querySelector('.search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handlePropertySearch();
        });
    }

    // Handle search tab changes
    const searchTabs = document.querySelectorAll('input[name="searchType"]');
    searchTabs.forEach(tab => {
        tab.addEventListener('change', function() {
            updateSearchPlaceholders(this.id);
        });
    });

    // Handle listing tab changes
    const listingTabs = document.querySelectorAll('input[name="listingType"]');
    listingTabs.forEach(tab => {
        tab.addEventListener('change', function() {
            filterListings(this.id);
        });
    });
}

// Update search form based on selected tab
function updateSearchPlaceholders(tabId) {
    const locationInput = document.querySelector('.search-form input[placeholder*="Address"]');
    const priceInput = document.querySelector('.search-form input[placeholder*="price"]');
    
    switch(tabId) {
        case 'buyTab':
            locationInput.placeholder = 'Where would you like to buy?';
            priceInput.placeholder = 'Budget range in $BHS';
            break;
        case 'rentTab':
            locationInput.placeholder = 'Where would you like to rent?';
            priceInput.placeholder = 'Monthly rent range in $BHS';
            break;
        case 'sellTab':
            locationInput.placeholder = 'Property location to sell';
            priceInput.placeholder = 'Expected price range in $BHS';
            break;
        case 'investTab':
            locationInput.placeholder = 'Investment location preference';
            priceInput.placeholder = 'Investment budget in $BHS';
            break;
    }
}

// Handle property search
async function handlePropertySearch() {
    const formData = new FormData(document.querySelector('.search-form'));
    const searchData = {
        search_type: document.querySelector('input[name="searchType"]:checked').id.replace('Tab', ''),
        location: formData.get('location') || '',
        property_type: formData.get('property_type') || '',
        cryptocurrency_type: formData.get('cryptocurrency_type') || '',
        price_range: formData.get('price_range') || ''
    };
    
    // Show loading state
    const submitBtn = document.querySelector('.search-form button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Searching...';
    submitBtn.disabled = true;
    
    try {
        const response = await fetch(`${API_BASE}/properties.php?action=search`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(searchData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            updatePropertyListings(data.data);
            showSearchResults(data.data.length);
        } else {
            showNotification('Search failed: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Search error:', error);
        showNotification('Search failed. Please try again.', 'error');
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
}

// Show search results
function showSearchResults(count) {
    // Scroll to listings section
    document.getElementById('buy').scrollIntoView({ behavior: 'smooth' });
    
    // Show notification
    showNotification(`Found ${count} properties matching your search.`, 'success');
}

// Filter listings based on selected tab
async function filterListings(tabId) {
    const listingType = tabId.replace('Listings', '');
    
    try {
        const response = await fetch(`${API_BASE}/properties.php?listing_type=${listingType}`);
        const data = await response.json();
        
        if (data.success) {
            updatePropertyListings(data.data);
        }
    } catch (error) {
        console.error('Filter error:', error);
    }
}

// Initialize buy location modal
function initializeBuyLocationModal() {
    // Show buy location modal when Buy tab is selected in search
    const buyTab = document.getElementById('buyTab');
    if (buyTab) {
        buyTab.addEventListener('change', function() {
            if (this.checked) {
                setTimeout(() => {
                    const buyLocationModal = new bootstrap.Modal(document.getElementById('buyLocationModal'));
                    buyLocationModal.show();
                }, 500);
            }
        });
    }
}

// Handle location selection
async function selectLocation(location) {
    const modal = bootstrap.Modal.getInstance(document.getElementById('buyLocationModal'));
    modal.hide();
    
    // Update search form with selected location
    const locationInput = document.querySelector('.search-form input[placeholder*="Address"]');
    if (location === 'dubai') {
        locationInput.value = 'Dubai, UAE';
        showNotification('Great choice! Showing properties in Dubai.', 'success');
    } else if (location === 'us') {
        locationInput.value = 'United States';
        showNotification('Excellent! Showing properties in the US.', 'success');
    }
    
    // Load properties for selected location
    try {
        const response = await fetch(`${API_BASE}/properties.php?action=location&location=${location}`);
        const data = await response.json();
        
        if (data.success) {
            updatePropertyListings(data.data);
        }
    } catch (error) {
        console.error('Location filter error:', error);
    }
}

// Initialize animations
function initializeAnimations() {
    // Intersection Observer for fade-in animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, observerOptions);
    
    // Add fade-in class to elements
    const animatedElements = document.querySelectorAll('.property-listing-card, .feature-card, .testimonial-card');
    animatedElements.forEach(el => {
        el.classList.add('fade-in');
        observer.observe(el);
    });
    
    // Parallax effect for hero section
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const heroImage = document.querySelector('.hero-image');
        if (heroImage) {
            heroImage.style.transform = `translateY(${scrolled * 0.1}px)`;
        }
    });
}

// Form handlers
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
    
    // Newsletter form
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', handleNewsletterSignup);
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
            
            // Store user data for dashboard
            localStorage.setItem('userData', JSON.stringify(data.user));
            
            // Close modal and show success
            const modal = bootstrap.Modal.getInstance(document.getElementById('loginModal'));
            modal.hide();
            
            showNotification(`Welcome back, ${data.user.name}! Redirecting to dashboard...`, 'success');
            
            // Redirect to dashboard after a short delay
            setTimeout(() => {
                if (data.user.role === 'admin') {
                    window.location.href = 'backend/admin/index.php';
                } else {
                    window.location.href = 'user-dashboard.html';
                }
            }, 1500);
            
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
            // Store user data for dashboard
            const userData = {
                id: data.user_id,
                name: registerData.full_name,
                email: registerData.email,
                interest_type: registerData.interest_type,
                role: 'user'
            };
            localStorage.setItem('userData', JSON.stringify(userData));
            
            // Close modal and show success
            const modal = bootstrap.Modal.getInstance(document.getElementById('getStartedModal'));
            modal.hide();
            
            showNotification('Registration successful! Redirecting to your dashboard...', 'success');
            
            // Redirect to dashboard after a short delay
            setTimeout(() => {
                window.location.href = 'user-dashboard.html';
            }, 2000);
            
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

// Handle newsletter signup
async function handleNewsletterSignup(e) {
    e.preventDefault();
    const email = e.target.querySelector('input[type="email"]').value;
    
    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Subscribing...';
    submitBtn.disabled = true;
    
    try {
        const response = await fetch(`${API_BASE}/newsletter.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email: email })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Successfully subscribed to our newsletter!', 'success');
            e.target.reset();
        } else {
            showNotification('Subscription failed: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Newsletter error:', error);
        showNotification('Subscription failed. Please try again.', 'error');
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
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
            location.reload();
        }
    } catch (error) {
        console.error('Logout error:', error);
    }
}

// Show consultant chat
function showConsultantChat() {
    // This would typically open a chat widget or redirect to chat page
    setTimeout(() => {
        showNotification('Consultant chat is now available! Click the chat icon to start.', 'info');
        
        // Add chat widget (placeholder)
        addChatWidget();
    }, 1000);
}

// Add chat widget
function addChatWidget() {
    const chatWidget = document.createElement('div');
    chatWidget.innerHTML = `
        <div class="chat-widget" style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">
            <button class="btn btn-primary rounded-circle" style="width: 60px; height: 60px; box-shadow: 0 4px 20px rgba(99, 102, 241, 0.3);">
                <i class="fas fa-comments"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(chatWidget);
    
    // Add click handler for chat
    chatWidget.querySelector('button').addEventListener('click', () => {
        showNotification('Chat feature coming soon! Our consultant will contact you via email.', 'info');
    });
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

// Property card interactions
document.addEventListener('click', function(e) {
    if (e.target.closest('.property-listing-card')) {
        const card = e.target.closest('.property-listing-card');
        const propertyId = card.dataset.propertyId;
        const propertyName = card.querySelector('h5').textContent;
        
        // Add click animation
        card.style.transform = 'scale(0.98)';
        setTimeout(() => {
            card.style.transform = '';
        }, 150);
        
        // Navigate to property details page
        if (propertyId) {
            window.location.href = `property-details.html?id=${propertyId}`;
        } else {
            // For static cards without propertyId, show notification
            showNotification(`Viewing details for ${propertyName}`, 'info');
            // You can add a default ID or handle static properties differently
            window.location.href = `property-details.html?id=1`;
        }
    }
});

// Dropdown functionality for About Us section
function toggleDropdown(id) {
    const content = document.getElementById(id);
    const toggle = content.previousElementSibling;
    
    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'block';
        toggle.classList.add('active');
        
        // Add smooth slide down animation
        content.style.maxHeight = '0px';
        content.style.overflow = 'hidden';
        content.style.transition = 'max-height 0.3s ease-out';
        
        setTimeout(() => {
            content.style.maxHeight = content.scrollHeight + 'px';
        }, 10);
        
        setTimeout(() => {
            content.style.maxHeight = 'none';
            content.style.overflow = 'visible';
        }, 300);
    } else {
        // Add smooth slide up animation
        content.style.maxHeight = content.scrollHeight + 'px';
        content.style.overflow = 'hidden';
        content.style.transition = 'max-height 0.3s ease-out';
        
        setTimeout(() => {
            content.style.maxHeight = '0px';
        }, 10);
        
        setTimeout(() => {
            content.style.display = 'none';
            toggle.classList.remove('active');
        }, 300);
    }
}

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    // ESC key to close modals
    if (e.key === 'Escape') {
        const openModal = document.querySelector('.modal.show');
        if (openModal) {
            const modal = bootstrap.Modal.getInstance(openModal);
            modal.hide();
        }
    }
});

// Handle window resize
window.addEventListener('resize', function() {
    // Adjust layout for mobile
    const isMobile = window.innerWidth < 768;
    const heroContent = document.querySelector('.hero-content');
    
    if (heroContent) {
        if (isMobile) {
            heroContent.style.textAlign = 'center';
        } else {
            heroContent.style.textAlign = 'left';
        }
    }
});

// Export functions for global access
window.selectLocation = selectLocation;
window.toggleDropdown = toggleDropdown;
