# Inventory Management System with RBAC

A professional, role-based access control (RBAC) inventory management system built with PHP and MySQL. This system provides comprehensive inventory tracking, user management, transaction processing, and detailed audit logging for organizations.

## 🎯 Features

### Core Inventory Management
- **Item Management**: Add, edit, and delete inventory items with detailed information
- **Category Management**: Organize items into categories for better organization
- **Supplier Management**: Manage supplier information and contacts
- **Stock Tracking**: Real-time inventory quantity tracking with low-stock alerts
- **Barcode Support**: Track items using barcode numbers
- **Warehouse Locations**: Organize items by storage locations

### Transaction Management
- **Transaction Types**: Support for purchases, sales, adjustments, and transfers
- **Transaction History**: Complete audit trail of all inventory movements
- **Reference Numbers**: Track transactions with reference numbers
- **Quantity Management**: Process inventory transactions with notes and timestamps

### User & Access Control
- **Role-Based Access Control (RBAC)**: Three user roles with different permission levels
  - **Admin**: Full system access including user and role management
  - **Inventory Manager**: Comprehensive inventory management capabilities
  - **Staff**: Basic inventory view and transaction processing
- **User Permissions**: Granular permission system for flexible access control
- **User Sessions**: Secure session management with "Remember Me" functionality
- **OTP Verification**: Two-factor authentication with OTP verification on login

### Reports & Analytics
- **Inventory Reports**: Summary reports by category and item status
- **Transaction Reports**: Detailed transaction history with date filtering
- **Low Stock Alerts**: Identify items below reorder levels
- **Dashboard Analytics**: Visual charts and statistics for inventory overview
- **Data Export**: Export inventory data for external analysis

### Security & Monitoring
- **Audit Logging**: Complete audit trail of all user actions with IP tracking
- **Geolocation Tracking**: Track login locations and user activity
- **Password Security**: Bcrypt password hashing for secure storage
- **IP Address Tracking**: Monitor and log user IP addresses
- **Session Management**: Secure session handling with expiration
- **reCAPTCHA Protection**: Bot protection on login and registration forms
- **Input Sanitization**: All user inputs are sanitized to prevent SQL injection

### User Management
- **User Registration**: New user registration with email verification
- **User Profiles**: User profile management and updates
- **User Activation/Deactivation**: Control user access status
- **Email Management**: Email field for user communication

## 📋 System Requirements

- **Server**: Apache/Nginx with PHP support
- **PHP**: Version 8.2 or higher
- **Database**: MySQL 10.4+ or MariaDB
- **Browser**: Modern browser with JavaScript support
- **Extensions Required**:
  - MySQLi extension (PHP data object)
  - OpenSSL (for secure connections)

## 🚀 Installation & Setup

### 1. Prerequisites
Ensure you have XAMPP or similar local server environment installed with:
- Apache running
- MySQL/MariaDB running
- PHP 8.2+

### 2. Database Setup

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create a new database or import the SQL file:
   - Click on "Import" tab
   - Select `inventory_rbac_system.sql` from the project root
   - Click "Import"
3. The database will be created with all necessary tables and sample data

### 3. Configuration

Edit [config/database.php](config/database.php) with your database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Your MySQL password
define('DB_NAME', 'inventory_rbac_system');
```

### 4. Access the Application

Navigate to: `http://localhost/inventory_system`

## 📝 Default User Credentials

| Username | Password | Role | Email |
|----------|----------|------|-------|
| admin | ******* | Admin | admin@inventory.com |
| manager | ******* | Inventory Manager | baka@gmail.com |
| rommel | ****** | Staff | rommel@gmail.com |

> ⚠️ **Important**: Change all default passwords immediately after first login for security!

## 👥 User Roles & Permissions

### Admin
- Full system access
- User management (create, edit, delete)
- Role and permission management
- View all reports and audit logs
- Dashboard access with full analytics

**Permissions**: All 18 permissions included

### Inventory Manager
- Inventory management (add, edit items)
- Manage categories and suppliers
- Process transactions
- View reports and analytics
- Low stock alerts

**Permissions**: 10 of 18 permissions

### Staff
- View inventory items
- Process transactions
- View transactions
- Receive low stock alerts
- View dashboard

**Permissions**: 5 of 18 permissions

## 📂 Directory Structure

```
inventory_system/
├── config/
│   └── database.php              # Database configuration
├── includes/
│   ├── auth.php                  # Authentication functions
│   ├── functions.php             # General utility functions
│   ├── inventory_functions.php   # Inventory-specific functions
│   ├── db_operations.php         # Database operations
│   ├── header.php                # Common header component
│   └── footer.php                # Common footer component
├── assets/
│   ├── css/
│   │   ├── style.css             # Main stylesheet
│   │   ├── login.css             # Login page styles
│   │   ├── register.css          # Registration page styles
│   │   └── reusable/
│   │       ├── header.css        # Header component styles
│   │       └── footer.css        # Footer component styles
│   └── js/
│       └── script.js             # Client-side scripts
├── admin/
│   ├── dashboard.php             # Admin dashboard
│   ├── users.php                 # User management
│   ├── roles.php                 # Role management
│   ├── items.php                 # Inventory items management
│   ├── categories.php            # Category management
│   ├── suppliers.php             # Supplier management
│   ├── transactions.php          # Transaction management
│   ├── audit_logs.php            # Audit log viewer
│   ├── reports.php               # Report generation
│   ├── add_item.php              # Add new item form
│   ├── update_item.php           # Update item form
│   ├── delete_item.php           # Delete item handler
│   ├── item_details.php          # Item detail view
│   ├── process_transaction.php   # Process transaction handler
│   └── profile.php               # Admin profile management
├── manager/
│   └── [Similar structure to admin with limited permissions]
├── staff/
│   └── [Simplified interface for staff access]
├── index.php                     # Login page & home
├── login.php                     # Login handler
├── register.php                  # User registration
├── logout.php                    # Logout handler
├── verify-otp.php               # OTP verification
├── inventory_rbac_system.sql     # Database schema & sample data
└── README.md                     # This file
```

## 🔑 Key Files Overview

| File | Purpose |
|------|---------|
| [config/database.php](config/database.php) | MySQL connection setup |
| [index.php](index.php) | Landing page and authentication check |
| [login.php](login.php) | User login with reCAPTCHA & OTP setup |
| [verify-otp.php](verify-otp.php) | OTP verification for 2FA |
| [register.php](register.php) | User registration form |
| [includes/auth.php](includes/auth.php) | RBAC and permission checking functions |
| [includes/functions.php](includes/functions.php) | General utility functions |
| [includes/inventory_functions.php](includes/inventory_functions.php) | Inventory-specific operations |
| [admin/dashboard.php](admin/dashboard.php) | Admin overview with analytics |
| [admin/items.php](admin/items.php) | Complete item management interface |

## 🔒 Security Features

### Authentication & Authorization
- **Secure Password Hashing**: BCrypt algorithm for password storage
- **OTP 2FA**: One-time password verification on login
- **Session Management**: Secure PHP session handling
- **RBAC Implementation**: Granular permission system

### Data Protection
- **SQL Injection Prevention**: Prepared statements (MySQLi)
- **Input Sanitization**: All user inputs sanitized with `sanitizeInput()`
- **XSS Prevention**: HTML special characters encoding

### Monitoring & Logging
- **Comprehensive Audit Logs**: Track all user actions
- **IP Tracking**: Monitor login and activity from different IPs
- **Geolocation Logging**: Track user locations
- **Failed Login Attempts**: Log and monitor failed login attempts
- **User Agent Tracking**: Track device/browser information

## 🎨 User Interface

- **Bootstrap 5**: Responsive modern UI framework
- **Font Awesome 6**: Icon library
- **Chart.js**: Data visualization for reports
- **Responsive Design**: Mobile-friendly interface
- **Clean Navigation**: Organized role-based navigation
- **Dashboard Analytics**: Real-time system statistics

## 📊 Database Schema

### Main Tables
- **users**: User accounts and roles
- **inventory_items**: Inventory item details
- **inventory_transactions**: All inventory movements
- **categories**: Item categories
- **suppliers**: Supplier information
- **audit_logs**: Complete system audit trail
- **permissions**: Permission definitions
- **role_permissions**: Role-permission mappings
- **user_permissions**: User-specific permission overrides
- **user_sessions**: Active session tracking
- **page_views**: User page view tracking

## 🔧 Usage Guide

### Admin Panel
1. Login with admin credentials
2. Access dashboard for system overview
3. Manage users, roles, and permissions
4. Monitor audit logs and activities

### Inventory Management
1. Navigate to Items section
2. Add items with categories and suppliers
3. Update inventory quantities via transactions
4. Monitor low stock items

### Transaction Processing
1. Go to Transactions section
2. Select transaction type (purchase/sale/adjustment/transfer)
3. Enter quantity and reference number
4. System automatically updates inventory

### Reporting
1. Navigate to Reports section
2. Select report type (summary/category/transactions)
3. Choose date range if needed
4. Export data in required format

## 🐛 Troubleshooting

### Database Connection Error
- Check MySQL is running in XAMPP
- Verify database credentials in [config/database.php](config/database.php)
- Ensure `inventory_rbac_system` database exists

### Permission Denied Errors
- Verify user role and permissions
- Check if user has required permission for that action
- Contact admin to update permissions

### OTP Not Received
- reCAPTCHA must be completed successfully
- Check that email field is correctly filled
- Reload page if timeout occurs

## 📧 Support & Contact

For issues or questions, check:
- Audit logs for detailed action history
- Database integrity and connections
- User role and permission settings
- System error logs

## 📄 License

This project is proprietary and for authorized use only.

## ✅ Version History

- **v1.0** (2025-07-11): Initial release with RBAC, audit logging, and inventory management

---

**Last Updated**: February 20, 2026
**Status**: Production Ready



