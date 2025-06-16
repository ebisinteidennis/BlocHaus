# BlocHaus - Crypto Real Estate Platform

A decentralized real estate platform where users can buy, rent, sell, or invest in real estate using blockchain and cryptocurrency, specifically the $BHS (BlockHaus) token.

## 🚀 Features

### Frontend Features
- **Modern Responsive Design**: Built with Bootstrap 5 and custom CSS
- **Property Listings**: Dynamic property cards with real-time data from backend
- **Search & Filter**: Advanced search functionality by location, type, and price
- **User Authentication**: Login and registration with backend integration
- **$BHS Token Integration**: All pricing displayed in $BHS currency
- **Interactive UI**: Smooth animations and user-friendly interface
- **Location-based Filtering**: Separate views for Dubai and US properties

### Backend Features
- **RESTful API**: Complete API for properties, authentication, and user management
- **Database Integration**: MySQL database with comprehensive schema
- **Admin Dashboard**: Full admin panel for managing properties, users, and site content
- **User Management**: Role-based access control (Admin, User, Consultant)
- **Property Management**: CRUD operations for properties with image support
- **Authentication System**: Secure login/registration with session management
- **Data Validation**: Server-side validation and error handling

### Admin Panel Features
- **Dashboard Overview**: Statistics and recent activity
- **Property Management**: Add, edit, delete properties with image management
- **User Management**: Manage users, roles, and account status
- **Real-time Updates**: Live data from database
- **Responsive Design**: Works on all devices

## 🏗️ Project Structure

```
BlocHaus/
├── index.html              # Main homepage
├── about.html              # About page
├── styles.css              # Main stylesheet
├── script.js               # Frontend JavaScript with API integration
├── README.md               # This file
├── backend/
│   ├── config/
│   │   └── database.php    # Database configuration
│   ├── api/
│   │   ├── auth.php        # Authentication API
│   │   └── properties.php  # Properties API
│   ├── admin/
│   │   ├── index.php       # Admin dashboard
│   │   ├── login.php       # Admin login
│   │   ├── logout.php      # Admin logout
│   │   ├── properties.php  # Property management
│   │   └── users.php       # User management
│   └── setup/
│       └── database_setup.sql # Database schema and sample data
└── [Property Images]       # Dubai property images (IMG-*.jpg)
```

## 🛠️ Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx) or PHP built-in server

### Step 1: Clone/Download the Project
```bash
# If using git
git clone [repository-url]
cd BlocHaus

# Or download and extract the ZIP file
```

### Step 2: Database Setup
1. Create a MySQL database named `blockhaus_db`
2. Update database credentials in `backend/config/database.php`
3. Run the database setup:
```bash
cd backend
php -r "
require_once 'config/database.php';
\$database = new Database();
\$conn = \$database->getConnection();
\$sql = file_get_contents('setup/database_setup.sql');
\$statements = explode(';', \$sql);
foreach (\$statements as \$statement) {
    \$statement = trim(\$statement);
    if (!empty(\$statement)) {
        try {
            \$conn->exec(\$statement);
            echo 'Executed: ' . substr(\$statement, 0, 50) . '...' . PHP_EOL;
        } catch (Exception \$e) {
            echo 'Error: ' . \$e->getMessage() . PHP_EOL;
        }
    }
}
echo 'Database setup completed!' . PHP_EOL;
"
```

### Step 3: Start the Server
```bash
# Using PHP built-in server
php -S localhost:8000

# Or configure your web server to point to the project directory
```

### Step 4: Access the Application
- **Frontend**: http://localhost:8000/index.html
- **Admin Panel**: http://localhost:8000/backend/admin/login.php

## 🔐 Default Admin Credentials
- **Email**: admin@blockhaus.com
- **Password**: password

## 💰 Currency System

The platform uses **$BHS (BlockHaus Token)** as the primary currency:
- All property prices are displayed in $BHS
- No USD or BTC pricing (as requested)
- Consistent pricing across all property listings
- Admin can set prices in $BHS when adding/editing properties

## 🏠 Property Management

### Adding Properties
1. Login to admin panel
2. Go to Properties → Add Property
3. Fill in property details:
   - Title and description
   - Location (Dubai, UAE or US locations)
   - Property type (House, Apartment, Villa, Condo)
   - Bedrooms, bathrooms, square footage
   - Price in $BHS
   - Listing type (Buy, Rent, Sell, Invest)
   - Images (comma-separated filenames or URLs)
   - Featured status

### Dubai Rental Properties
The system includes pre-loaded Dubai rental properties with:
- **Location**: All set to "Dubai, UAE"
- **Section**: All categorized under "rent"
- **Images**: Real property images (IMG-20250615-WA*.jpg)
- **Pricing**: Competitive rental prices in $BHS

### Image Management
- Images can be uploaded to the project directory
- Reference images by filename in the admin panel
- Support for both local files and external URLs
- Automatic image processing and display

## 👥 User Management

### User Roles
- **Admin**: Full access to admin panel and all features
- **User**: Regular users who can browse and interact with properties
- **Consultant**: Can manage consultations and assist users

### User Registration
- Users register through the frontend
- Automatic consultation request creation
- Email validation and secure password hashing
- Interest type tracking (buying, selling, renting, investing)

## 🔧 API Endpoints

### Authentication API (`/backend/api/auth.php`)
- `POST ?action=register` - User registration
- `POST ?action=login` - User login
- `POST ?action=logout` - User logout

### Properties API (`/backend/api/properties.php`)
- `GET` - Get all properties
- `GET ?action=featured` - Get featured properties
- `GET ?action=location&location=dubai` - Get properties by location
- `POST ?action=search` - Search properties

## 🎨 Frontend Features

### Interactive Elements
- **Property Search**: Real-time search with backend integration
- **Location Selection**: Modal for choosing Dubai or US properties
- **Authentication**: Login/registration modals with form validation
- **Responsive Design**: Mobile-friendly interface
- **Smooth Animations**: CSS transitions and JavaScript interactions

### Property Display
- **Dynamic Loading**: Properties loaded from database
- **Filtering**: By type (buy, rent, sell, invest)
- **Search**: By location, type, and price range
- **$BHS Pricing**: Consistent currency display

## 🔒 Security Features

- **Password Hashing**: Secure bcrypt password hashing
- **Session Management**: Secure PHP sessions
- **SQL Injection Prevention**: Prepared statements
- **XSS Protection**: Input sanitization
- **Role-based Access**: Admin panel access control

## 📱 Responsive Design

The platform is fully responsive and works on:
- Desktop computers
- Tablets
- Mobile phones
- All modern browsers

## 🚀 Deployment

### Production Deployment
1. Upload files to web server
2. Configure database connection
3. Set up SSL certificate
4. Configure web server (Apache/Nginx)
5. Set proper file permissions
6. Update API base URL in script.js if needed

### Environment Configuration
- Update `backend/config/database.php` with production database credentials
- Ensure proper file permissions for image uploads
- Configure web server for clean URLs (optional)

## 🔄 Future Enhancements

### Planned Features
- **Blockchain Integration**: Real Solana blockchain integration
- **Wallet Connection**: MetaMask and Phantom wallet support
- **Smart Contracts**: Automated escrow and transactions
- **Chat System**: Real-time consultant chat
- **Advanced Search**: More filtering options
- **Property Details**: Detailed property pages
- **Image Upload**: Direct image upload functionality
- **Email Notifications**: Automated email system
- **Analytics Dashboard**: Advanced reporting

### Technical Improvements
- **Caching**: Redis/Memcached integration
- **CDN**: Content delivery network for images
- **API Rate Limiting**: Prevent abuse
- **Logging**: Comprehensive error and access logging
- **Testing**: Unit and integration tests
- **Documentation**: API documentation

## 🐛 Troubleshooting

### Common Issues

**Database Connection Error**
- Check database credentials in `backend/config/database.php`
- Ensure MySQL server is running
- Verify database exists and user has permissions

**Images Not Loading**
- Check image file paths
- Ensure images are in the correct directory
- Verify file permissions

**Admin Panel Access**
- Use default credentials: admin@blockhaus.com / password
- Check if database setup completed successfully
- Verify session configuration

**API Errors**
- Check browser console for JavaScript errors
- Verify API endpoints are accessible
- Check server error logs

## 📞 Support

For technical support or questions:
- Check the troubleshooting section above
- Review the code comments for implementation details
- Ensure all setup steps were completed correctly

## 📄 License

This project is proprietary software for BlocHaus platform.

---

**BlocHaus** - Decentralizing Real Estate, One Bloc at a Time. 🏠⛓️
