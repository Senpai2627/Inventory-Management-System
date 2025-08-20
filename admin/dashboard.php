<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['otp_verified'] !== true) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requirePermission('view_dashboard');

// Get user statistics
$conn = getDBConnection();
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
    'new_users' => $conn->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 DAY)")->fetch_assoc()['count'],
    'expired_sessions' => $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM user_sessions WHERE expires_at < NOW() AND expires_at >= DATE_SUB(NOW(), INTERVAL 10 DAY)")->fetch_assoc()['count'],
    'inactive_users' => $conn->query("SELECT COUNT(*) as count FROM users WHERE is_active = 0 AND updated_at >= DATE_SUB(NOW(), INTERVAL 3 DAY)")->fetch_assoc()['count'],
    'role_distribution' => $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role")->fetch_all(MYSQLI_ASSOC),
    'user_registrations' => $conn->query("SELECT 
            DATE(created_at) as date, 
            COUNT(*) as count 
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
        GROUP BY DATE(created_at) 
        ORDER BY date")->fetch_all(MYSQLI_ASSOC),
    'active_sessions' => $conn->query("SELECT 
            DATE(created_at) as date, 
            COUNT(*) as count 
        FROM user_sessions 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
        GROUP BY DATE(created_at) 
        ORDER BY date")->fetch_all(MYSQLI_ASSOC)
];
$conn->close();

// Prepare data for charts
$registrationDates = array_column($stats['user_registrations'], 'date');
$registrationCounts = array_column($stats['user_registrations'], 'count');
$sessionDates = array_column($stats['active_sessions'], 'date');
$sessionCounts = array_column($stats['active_sessions'], 'count');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-blue: #2563eb;
            --light-blue: #eff6ff;
            --dark-blue: #1e40af;
            --white: #ffffff;
            --gray-light: #f3f4f6;
            --gray-medium: #e5e7eb;
            --gray-dark: #6b7280;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--white);
            color: #111827;
        }
        
        .dashboard-header {
            background-color: var(--white);
            border-bottom: 1px solid var(--gray-medium);
            padding: 1.5rem 0;
            margin-bottom: 1.5rem;
        }
        
        .dashboard-header h2 {
            font-weight: 600;
            margin: 0;
            color: var(--dark-blue);
        }
        
        .widget {
            background: var(--white);
            border-radius: 8px;
            padding: 1.25rem;
            border: 1px solid var(--gray-medium);
            transition: all 0.2s ease;
            height: 100%;
        }
        
        .widget:hover {
            border-color: var(--primary-blue);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        
        .widget-icon {
            font-size: 1.5rem;
            color: var(--primary-blue);
            margin-bottom: 0.75rem;
        }
        
        .widget-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark-blue);
            margin-bottom: 0.25rem;
        }
        
        .widget-title {
            font-size: 0.875rem;
            color: var(--gray-dark);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .card {
            border: 1px solid var(--gray-medium);
            border-radius: 8px;
            margin-bottom: 1.5rem;
            background: var(--white);
        }
        
        .card-header {
            background: var(--white);
            color: var(--dark-blue);
            border-bottom: 1px solid var(--gray-medium);
            padding: 1rem 1.25rem;
            font-weight: 600;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .role-badge {
            padding: 0.75rem;
            border-radius: 6px;
            font-weight: 500;
            display: inline-block;
            width: 100%;
            text-align: center;
            background-color: var(--gray-light);
            border-left: 3px solid var(--primary-blue);
            color: var(--dark-blue);
        }
        
        .badge-admin {
            border-left-color: var(--dark-blue);
        }
        
        .badge-manager {
            border-left-color: #1d4ed8;
        }
        
        .badge-staff {
            border-left-color: #0369a1;
        }
        
        .nav-tabs .nav-link.active {
            border-bottom: 2px solid var(--primary-blue);
            color: var(--primary-blue);
            font-weight: 500;
        }
        
        .nav-tabs .nav-link {
            color: var(--gray-dark);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="dashboard-header">
        <div class="container">
            <h2><i class="fas fa-tachometer-alt me-2"></i>Dashboard Overview</h2>
        </div>
    </div>
    
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="widget">
                    <div class="widget-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="widget-value"><?php echo $stats['total_users']; ?></div>
                    <div class="widget-title">Total Users</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="widget">
                    <div class="widget-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="widget-value"><?php echo $stats['new_users']; ?></div>
                    <div class="widget-title">New Users (3 days)</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="widget">
                    <div class="widget-icon">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="widget-value"><?php echo $stats['expired_sessions']; ?></div>
                    <div class="widget-title">Expired Sessions</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="widget">
                    <div class="widget-icon">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <div class="widget-value"><?php echo $stats['inactive_users']; ?></div>
                    <div class="widget-title">Inactive Users</div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>User Roles</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="roleChart"></canvas>
                        </div>
                        <div class="row mt-3">
                            <?php foreach ($stats['role_distribution'] as $role): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="role-badge <?php echo 'badge-' . str_replace('_', '', $role['role']); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $role['role'])); ?>
                                        <div class="fs-4 fw-bold"><?php echo $role['count']; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Registrations (30 days)</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="registrationChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Activity Overview</h5>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-3" id="chartTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="combined-tab" data-bs-toggle="tab" data-bs-target="#combined-tab-pane" type="button" role="tab">Combined</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="registrations-tab" data-bs-toggle="tab" data-bs-target="#registrations-tab-pane" type="button" role="tab">Registrations</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="sessions-tab" data-bs-toggle="tab" data-bs-target="#sessions-tab-pane" type="button" role="tab">Sessions</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="chartTabsContent">
                            <div class="tab-pane fade show active" id="combined-tab-pane" role="tabpanel">
                                <div class="chart-container" style="height: 350px;">
                                    <canvas id="combinedChart"></canvas>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="registrations-tab-pane" role="tabpanel">
                                <div class="chart-container" style="height: 350px;">
                                    <canvas id="registrationsChart"></canvas>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="sessions-tab-pane" role="tabpanel">
                                <div class="chart-container" style="height: 350px;">
                                    <canvas id="sessionsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Role Distribution Pie Chart
        const roleCtx = document.getElementById('roleChart').getContext('2d');
        const roleChart = new Chart(roleCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_map(function($role) { 
                    return ucfirst(str_replace('_', ' ', $role['role'])); 
                }, $stats['role_distribution'])); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($stats['role_distribution'], 'count')); ?>,
                    backgroundColor: [
                        'rgba(37, 179, 235, 0.7)',
                        'rgba(29, 78, 216, 0.7)',
                        'rgba(56, 30, 175, 0.7)'
                    ],
                    borderColor: [
                        'rgba(37, 179, 235, 0.7)',
                        'rgba(29, 78, 216, 0.7)',
                        'rgba(56, 30, 175, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                },
                cutout: '70%'
            }
        });
        
        // Registration Line Chart
        const regCtx = document.getElementById('registrationChart').getContext('2d');
        const registrationChart = new Chart(regCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($registrationDates); ?>,
                datasets: [{
                    label: 'User Registrations',
                    data: <?php echo json_encode($registrationCounts); ?>,
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    borderColor: 'rgba(37, 99, 235, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        },
                        grid: {
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Combined Bar Chart
        const combinedCtx = document.getElementById('combinedChart').getContext('2d');
        const combinedChart = new Chart(combinedCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($registrationDates); ?>,
                datasets: [
                    {
                        label: 'Registrations',
                        data: <?php echo json_encode($registrationCounts); ?>,
                        backgroundColor: 'rgba(37, 99, 235, 0.7)',
                        borderColor: 'rgba(37, 99, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Active Sessions',
                        data: <?php echo json_encode($sessionCounts); ?>,
                        backgroundColor: 'rgba(29, 78, 216, 0.7)',
                        borderColor: 'rgba(29, 78, 216, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        },
                        grid: {
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
        
        // Additional charts for tabs
        const registrationsCtx = document.getElementById('registrationsChart').getContext('2d');
        new Chart(registrationsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($registrationDates); ?>,
                datasets: [{
                    label: 'User Registrations',
                    data: <?php echo json_encode($registrationCounts); ?>,
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    borderColor: 'rgba(37, 99, 235, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        },
                        grid: {
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        
        const sessionsCtx = document.getElementById('sessionsChart').getContext('2d');
        new Chart(sessionsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($sessionDates); ?>,
                datasets: [{
                    label: 'Active Sessions',
                    data: <?php echo json_encode($sessionCounts); ?>,
                    backgroundColor: 'rgba(29, 78, 216, 0.1)',
                    borderColor: 'rgba(29, 78, 216, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        },
                        grid: {
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>