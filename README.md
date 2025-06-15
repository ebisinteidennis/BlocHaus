# BlockHaus - Crypto Real Estate Platform

A modern crypto real estate platform that allows users to buy, sell, rent, and invest in properties using cryptocurrency. Built with HTML, CSS, JavaScript frontend and PHP backend with admin dashboard.

## Features

### Frontend Features
- **Responsive Design**: Beautiful purple gradient design matching the provided mockups
- **Property Listings**: Display properties with dual pricing (BTC/USD/BHS format)
- **Location-Based Search**: Properties in US and Dubai locations
- **Interactive Search**: Property search with filters for location, type, and cryptocurrency
- **User Authentication**: Login and registration modals
- **Consultant Chat**: Registration includes consultant consultation setup
- **Newsletter Subscription**: Email subscription functionality
- **Testimonials**: Customer testimonials section
- **Mobile Responsive**: Works perfectly on all devices

### Backend Features
- **PHP API**: RESTful API endpoints for all functionality
- **MySQL Database**: Comprehensive database schema
- **Admin Dashboard**: Full admin panel for managing the platform
- **User Management**: User registration, login, and profile management
- **Property Management**: CRUD operations for properties
- **Consultation System**: Chat system between users and consultants
- **Newsletter System**: Email subscription management
- **Analytics**: Search tracking and user analytics

## Project Structure

```
BlockHaus/
├── index.html                 # Main frontend page
├── styles.css                 # Custom CSS with purple gradient theme
├── script.js                  # Frontend JavaScript functionality
├── backend/
│   ├── config/
│   │   └── database.php       # Database configuration
│   ├── setup/
│   │   └── database_setup.sql # Database schema and sample data
│   ├── api/
│   │   ├── auth.php          # Authentication API endpoints
│   │   └── properties.php    # Properties API endpoints
│   └── admin/
│       ├── index.php         # Admin dashboard
│       ├── login.php         # Admin login page
│       └── logout.php        # Admin logout script
└── README.md                 # This file
```

## Setup Instructions

### Prerequisites
- Web server (Apache/Nginx)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern web browser

### Installation Steps

1. **Clone/Download the Project**
   ```bash
   # Place all files in your web server directory
   # For XAMPP: C:/xampp/htdocs/blockhaus/
   # For WAMP: C:/wamp64/www/blockhaus/
   ```

2. **Database Setup**
   - Open phpMyAdmin or your MySQL client
   - Import the database schema:
     ```sql
     # Run the SQL file: backend/setup/database_setup.sql
     ```
   - Or manually create the database:
     ```sql
     CREATE DATABASE blockhaus_db;
     ```
   - Then import the SQL file

3. **Configure Database Connection**
   - Edit `backend/config/database.php`
   - Update database credentials if needed:
     ```php
     private $host = 'localhost';
     private $db_name = 'blockhaus_db';
     private $username = 'root';  // Your MySQL username
     private $password = '';      // Your MySQL password
     ```

4. **Set Permissions**
   - Ensure web server has read/write access to the project directory
   - For Linux/Mac:
     ```bash
     chmod -R 755 /path/to/blockhaus/
     ```

5. **Access the Application**
   - Frontend: `http://localhost/blockhaus/index.html`
   - Admin Panel: `http://localhost/blockhaus/backend/admin/login.php`

## Default Admin Credentials

### Admin Account
- **Email**: admin@blockhaus.com
- **Password**: password

### Consultant Account
- **Email**: consultant@blockhaus.com
- **Password**: password

## API Endpoints

### Authentication API (`backend/api/auth.php`)
- `POST /auth.php?action=register` - User registration
- `POST /auth.php?action=login` - User login
- `POST /auth.php?action=logout` - User logout

### Properties API (`backend/api/properties.php`)
- `GET /properties.php` - Get all properties
- `GET /properties.php?action=get&id={id}` - Get specific property
- `GET /properties.php?action=featured` - Get featured properties
- `GET /properties.php?action=location&location={location}` - Get properties by location
- `POST /properties.php?action=search` - Search properties

## Database Schema

### Main Tables
- **users**: User accounts (customers, admins, consultants)
- **properties**: Property listings with crypto pricing
- **consultations**: Consultation requests and chat sessions
- **chat_messages**: Messages between users and consultants
- **newsletter_subscriptions**: Email newsletter subscribers
- **property_searches**: Search analytics and tracking
- **transactions**: Future crypto transaction records

## Key Features Implementation

### Dual Pricing System
Properties display prices in multiple formats:
- Bitcoin (BTC): `10.0 BTC`
- USD equivalent: `≈ $835,404`
- BlockHaus Token (BHS): `835,404 BHS`

### Location-Based Filtering
- **US Properties**: Austin, Texas locations
- **Dubai Properties**: Dubai, UAE locations
- Automatic filtering based on user selection

### Consultant Chat System
- Users register and automatically get consultation request
- Admin can assign consultants to users
- Chat system for real-time communication

### Admin Dashboard Features
- User management and analytics
- Property CRUD operations
- Consultation management
- Newsletter subscriber management
- Real-time statistics and reporting

## Customization

### Styling
- Main colors defined in CSS variables in `styles.css`
- Purple gradient theme: `#8b5cf6` to `#ec4899`
- Bootstrap 5 for responsive layout

### Adding New Properties
1. Access admin dashboard
2. Navigate to Properties section
3. Add new property with:
   - Title, description, location
   - Property type (house, apartment, villa, condo)
   - Pricing in BTC and USD
   - Property images
   - Listing type (buy, rent, sell, invest)

### Extending API
- Add new endpoints in `backend/api/` directory
- Follow existing pattern for authentication and error handling
- Update database schema as needed

## Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection prevention with prepared statements
- Session management for authentication
- Input validation and sanitization
- CORS headers for API security

## Browser Compatibility

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Performance Optimizations

- Optimized images with proper sizing
- CSS and JavaScript minification ready
- Database indexing on frequently queried columns
- Lazy loading for property images
- Responsive image loading

## Future Enhancements

1. **Crypto Integration**
   - Real blockchain transactions
   - Wallet connectivity (MetaMask, etc.)
   - Smart contracts for property transfers

2. **Advanced Features**
   - Virtual property tours
   - Real-time chat with WebSocket
   - Advanced search filters
   - Property comparison tool
   - Investment portfolio tracking

3. **Mobile App**
   - React Native or Flutter app
   - Push notifications
   - Offline property browsing

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check MySQL service is running
   - Verify database credentials in `config/database.php`
   - Ensure database exists and is accessible

2. **Admin Login Not Working**
   - Verify database has been set up with sample data
   - Check if admin user exists in users table
   - Default password is "password" (hashed in database)

3. **Properties Not Loading**
   - Check API endpoints are accessible
   - Verify database has sample property data
   - Check browser console for JavaScript errors

4. **Styling Issues**
   - Ensure Bootstrap CSS is loading
   - Check custom CSS file path
   - Verify Font Awesome icons are loading

### Support

For technical support or questions:
- Check the database setup SQL file for sample data
- Verify all file permissions are correct
- Ensure PHP extensions (PDO, MySQL) are enabled
- Check web server error logs for detailed error messages

## License

This project is created for demonstration purposes. Modify and use as needed for your crypto real estate platform.
