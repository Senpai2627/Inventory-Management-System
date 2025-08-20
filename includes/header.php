<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php'; // Ensure functions are available


// Get correct base path based on user role
function getBasePath() {
    $role = getCurrentUserRole();
    return BASE_URL . '/' . ($role === 'admin' ? 'admin' : ($role === 'inventory_manager' ? 'manager' : 'staff'));
}

$basePath = getBasePath();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #1a73e8;
            --dark-blue: #0d47a1;
            --light-blue: #e8f0fe;
            --hover-blue: #4285f4;
            --white: #ffffff;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--white);
            overflow-x: hidden;
            padding-top: 70px; /* Add padding to body to account for fixed navbar */
        }
        
        /* Sticky Navbar */
        .navbar {
            background: var(--white) !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            padding: 0.8rem 1rem;
            position: fixed; /* Make navbar fixed */
            top: 0; /* Position at the top */
            width: 100%; /* Full width */
            z-index: 1030; /* Ensure navbar stays on top of other elements */
            border-bottom: 1px solid #e0e0e0;
        }
        
        .navbar:hover {
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.15);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: 0.5px;
            color: var(--primary-blue) !important;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .navbar-brand::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 0;
            background-color: var(--primary-blue);
            transition: width 0.3s ease;
        }
        
        .navbar-brand:hover::after {
            width: 100%;
        }
        
        .nav-link {
            color: #555 !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            margin: 0 0.2rem;
            border-radius: 4px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .nav-link:hover {
            color: var(--primary-blue) !important;
            background-color: var(--light-blue);
            transform: translateY(-2px);
        }
        
        .nav-link i {
            transition: transform 0.3s ease;
        }
        
        .nav-link:hover i {
            transform: scale(1.1);
        }
        
        /* Dropdown Animation */
        .dropdown-menu {
            border: 1px solid #e0e0e0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.3s ease;
            border-radius: 8px;
            padding: 0.5rem 0;
            margin-top: 0; /* Remove gap between navbar and dropdown */
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .dropdown-item {
            padding: 0.5rem 1.5rem;
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover {
            background-color: var(--light-blue);
            color: var(--primary-blue) !important;
            padding-left: 1.8rem;
        }
        
        /* Alert Animations */
        .alert {
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            animation: slideIn 0.5s ease;
            border-left: 4px solid;
            background-color: var(--white);
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            color: #28a745;
            border-left-color: #28a745;
        }
        
        .alert-danger {
            color: #dc3545;
            border-left-color: #dc3545;
        }
        
        /* Container Animation */
        .container {
            animation: fadeInUp 0.6s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Active Nav Item */
        .nav-item.active .nav-link {
            background-color: var(--light-blue);
            font-weight: 600;
            color: var(--primary-blue) !important;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .navbar-collapse {
                padding: 1rem 0;
                background-color: var(--white);
            }
            
            .nav-link {
                margin: 0.2rem 0;
            }
            
            body {
                padding-top: 60px; /* Adjust padding for mobile */
            }
        }
    </style>

</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
                <i class="fas fa-box-open me-2"></i>Inventory System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= getDashboardLink() ?>">
                                <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                            </a>
                        </li>
                        <?php if (hasPermission('view_inventory')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= $basePath ?>/items.php">
                                    <i class="fas fa-boxes me-1"></i> Inventory
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (hasPermission('view_transactions')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= $basePath ?>/transactions.php">
                                    <i class="fas fa-exchange-alt me-1"></i> Transactions
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (hasPermission('manage_categories')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= $basePath ?>/categories.php">
                                    <i class="fas fa-tags me-1"></i> Categories
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (hasPermission('manage_suppliers')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= $basePath ?>/suppliers.php">
                                    <i class="fas fa-truck me-1"></i> Suppliers
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (hasPermission('view_users') && getCurrentUserRole() == 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= BASE_URL ?>/admin/users.php">
                                    <i class="fas fa-users-cog me-1"></i> User Management
                                </a>
                            </li>
                        <?php endif; ?>
                         <?php if (hasPermission('view_audit_logs') && getCurrentUserRole() == 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= BASE_URL ?>/admin/audit_logs.php">
                                    <i class="fas fa-clipboard-list me-1"></i> Audit Logs
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (hasPermission('generate_reports') && getCurrentUserRole() == 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= BASE_URL ?>/admin/reports.php">
                                    <i class="fas fa-chart-bar me-1"></i> Reports
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['username']); ?> 
                                <span class="badge bg-primary text-white ms-1"><?php echo ucfirst(str_replace('_', ' ', getCurrentUserRole())); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= $basePath ?>/profile.php"><i class="fas fa-user-edit me-1"></i> Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>/login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>/register.php"><i class="fas fa-user-plus me-1"></i> Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Add active class to current nav item
            document.addEventListener('DOMContentLoaded', function() {
                const currentUrl = window.location.pathname;
                const navLinks = document.querySelectorAll('.nav-link');
                
                navLinks.forEach(link => {
                    if (link.getAttribute('href') === currentUrl) {
                        link.parentElement.classList.add('active');
                    }
                });
                
                // Smooth hover effect for dropdown
                const dropdowns = document.querySelectorAll('.dropdown');
                dropdowns.forEach(dropdown => {
                    dropdown.addEventListener('mouseenter', function() {
                        const menu = this.querySelector('.dropdown-menu');
                        menu.classList.add('show');
                    });
                    
                    dropdown.addEventListener('mouseleave', function() {
                        const menu = this.querySelector('.dropdown-menu');
                        menu.classList.remove('show');
                    });
                });
            });
        </script>
</body>
</html>