// BlockHaus JavaScript - Interactive functionality

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all functionality
    initializeNavigation();
    initializeModals();
    initializePropertySearch();
    initializeAnimations();
    initializeBuyLocationModal();
    initializeFormHandlers();
});

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
            priceInput.placeholder = 'Budget range in BTC/ETH';
            break;
        case 'rentTab':
            locationInput.placeholder = 'Where would you like to rent?';
            priceInput.placeholder = 'Monthly rent range';
            break;
        case 'sellTab':
            locationInput.placeholder = 'Property location to sell';
            priceInput.placeholder = 'Expected price range';
            break;
        case 'investTab':
            locationInput.placeholder = 'Investment location preference';
            priceInput.placeholder = 'Investment budget range';
            break;
    }
}

// Handle property search
function handlePropertySearch() {
    const formData = new FormData(document.querySelector('.search-form'));
    const searchData = Object.fromEntries(formData);
    
    // Show loading state
    const submitBtn = document.querySelector('.search-form button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.classList.add('loading');
    submitBtn.disabled = true;
    
    // Simulate search (replace with actual API call)
    setTimeout(() => {
        submitBtn.classList.remove('loading');
        submitBtn.disabled = false;
        
        // Show results or redirect to results page
        showSearchResults(searchData);
    }, 2000);
}

// Show search results
function showSearchResults(searchData) {
    // For now, scroll to listings section
    document.getElementById('buy').scrollIntoView({ behavior: 'smooth' });
    
    // Show notification
    showNotification('Search completed! Showing available properties.', 'success');
}

// Filter listings based on selected tab
function filterListings(tabId) {
    const listings = document.querySelectorAll('.property-listing-card');
    
    // Add filter animation
    listings.forEach((listing, index) => {
        listing.style.opacity = '0.5';
        listing.style.transform = 'scale(0.95)';
        
        setTimeout(() => {
            listing.style.opacity = '1';
            listing.style.transform = 'scale(1)';
        }, index * 100);
    });
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
function selectLocation(location) {
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
    
    // Filter listings based on location
    filterListingsByLocation(location);
}

// Filter listings by location
function filterListingsByLocation(location) {
    const listings = document.querySelectorAll('.property-listing-card');
    
    listings.forEach(listing => {
        const locationText = listing.querySelector('.location').textContent.toLowerCase();
        const shouldShow = location === 'dubai' ? 
            locationText.includes('dubai') : 
            locationText.includes('texas') || locationText.includes('austin');
        
        if (shouldShow) {
            listing.style.display = 'block';
            listing.style.opacity = '1';
        } else {
            listing.style.opacity = '0.3';
        }
    });
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
function handleLogin(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const loginData = Object.fromEntries(formData);
    
    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    submitBtn.classList.add('loading');
    submitBtn.disabled = true;
    
    // Simulate login (replace with actual authentication)
    setTimeout(() => {
        submitBtn.classList.remove('loading');
        submitBtn.disabled = false;
        
        // Close modal and show success
        const modal = bootstrap.Modal.getInstance(document.getElementById('loginModal'));
        modal.hide();
        
        showNotification('Welcome back to BlockHaus!', 'success');
        
        // Update UI for logged in state
        updateUIForLoggedInUser();
    }, 1500);
}

// Handle registration
function handleRegistration(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const registerData = Object.fromEntries(formData);
    
    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    submitBtn.classList.add('loading');
    submitBtn.disabled = true;
    
    // Simulate registration (replace with actual API call)
    setTimeout(() => {
        submitBtn.classList.remove('loading');
        submitBtn.disabled = false;
        
        // Close modal and show success
        const modal = bootstrap.Modal.getInstance(document.getElementById('getStartedModal'));
        modal.hide();
        
        showNotification('Registration successful! A consultant will contact you shortly.', 'success');
        
        // Show consultant chat modal or redirect to dashboard
        showConsultantChat();
    }, 2000);
}

// Handle newsletter signup
function handleNewsletterSignup(e) {
    e.preventDefault();
    const email = e.target.querySelector('input[type="email"]').value;
    
    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Subscribing...';
    submitBtn.disabled = true;
    
    // Simulate subscription (replace with actual API call)
    setTimeout(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
        
        showNotification('Successfully subscribed to our newsletter!', 'success');
        
        // Clear form
        e.target.reset();
    }, 1500);
}

// Update UI for logged in user
function updateUIForLoggedInUser() {
    const loginBtn = document.querySelector('button[data-bs-target="#loginModal"]');
    const getStartedBtn = document.querySelector('button[data-bs-target="#getStartedModal"]');
    
    if (loginBtn && getStartedBtn) {
        loginBtn.textContent = 'Dashboard';
        loginBtn.removeAttribute('data-bs-toggle');
        loginBtn.removeAttribute('data-bs-target');
        loginBtn.onclick = () => window.location.href = 'dashboard.html';
        
        getStartedBtn.style.display = 'none';
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
        const propertyName = card.querySelector('h5').textContent;
        
        // Add click animation
        card.style.transform = 'scale(0.98)';
        setTimeout(() => {
            card.style.transform = '';
        }, 150);
        
        // Show property details (placeholder)
        showNotification(`Viewing details for ${propertyName}`, 'info');
    }
});

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

// Initialize price updates (simulate real-time crypto prices)
function initializePriceUpdates() {
    const cryptoPrices = document.querySelectorAll('.crypto-price');
    const usdPrices = document.querySelectorAll('.usd-price');
    
    // Simulate price updates every 30 seconds
    setInterval(() => {
        cryptoPrices.forEach((priceEl, index) => {
            const currentBTC = parseFloat(priceEl.textContent.replace(/[^\d.]/g, ''));
            const fluctuation = (Math.random() - 0.5) * 0.1; // ±5% fluctuation
            const newBTC = Math.max(0.1, currentBTC + fluctuation);
            const newUSD = Math.round(newBTC * 83540.4); // Approximate BTC to USD
            
            priceEl.textContent = `${newBTC.toFixed(1)} BTC`;
            if (usdPrices[index]) {
                usdPrices[index].textContent = `≈ $${newUSD.toLocaleString()}`;
            }
        });
    }, 30000);
}

// Initialize price updates
initializePriceUpdates();

// Export functions for global access
window.selectLocation = selectLocation;
