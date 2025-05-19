<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "medlog";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get monthly statistics
$currentMonth = date('Y-m');
$query = "SELECT 
    COUNT(DISTINCT patient_name) as total_patients,
    COUNT(*) as total_prescriptions
    FROM prescriptions 
    WHERE DATE_FORMAT(created_at, '%Y-%m') = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $currentMonth);
$stmt->execute();
$monthlyStats = $stmt->get_result()->fetch_assoc();

// Get inventory alerts
$query = "SELECT * FROM inventory WHERE quantity <= 10 OR expiry_date <= DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY)";
$result = $conn->query($query);
$inventoryAlerts = $result->fetch_all(MYSQLI_ASSOC);

// Get recent orders
$query = "SELECT * FROM orders ORDER BY order_date DESC LIMIT 5";
$result = $conn->query($query);
$recentOrders = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reports - MediSync</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #439b7b;  /* Navy */
            --primary-dark: #020c1b;
            --primary-light: #112240;
            --secondary-color: #20b2aa;  /* Teal */
            --accent-color: #64ffda;  /* Light Teal */
            --success-color: #20b2aa;
            --warning-color: #ffd166;
            --danger-color: #ef4444;
            --background-color: #f8fafc;
            --text-color: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --hover-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --gradient-primary: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            --gradient-secondary: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            --gradient-success: linear-gradient(135deg, var(--success-color), var(--accent-color));
            --gradient-warning: linear-gradient(135deg, var(--warning-color), #fbbf24);
            --gradient-danger: linear-gradient(135deg, var(--danger-color), #f87171);
            --border-radius-sm: 8px;
            --border-radius-md: 12px;
            --border-radius-lg: 16px;
            --spacing-xs: 0.5rem;
            --spacing-sm: 1rem;
            --spacing-md: 1.5rem;
            --spacing-lg: 2rem;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .sidebar {
            background: var(--gradient-primary);
            min-height: 100vh;
            padding: var(--spacing-md);
            box-shadow: var(--card-shadow);
            position: fixed;
            width: 256px;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .sidebar.hidden {
            transform: translateX(-100%);
        }

        .main-content {
            transition: all 0.3s ease;
            margin-left: 256px;
            width: calc(100% - 256px);
            padding: var(--spacing-lg);
        }

        .main-content.ml-0 {
            margin-left: 0;
            width: 100%;
        }

        .toggle-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            padding: 0;
            z-index: 1001;
        }

        .toggle-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .sidebar h3 {
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: var(--spacing-lg);
            padding-bottom: var(--spacing-sm);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.9);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--border-radius-sm);
            margin-bottom: var(--spacing-xs);
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(5px);
            color: white;
        }

        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: 600;
        }

        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
        }

        .card {
            border: none;
            border-radius: var(--border-radius-md);
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            background: white;
            overflow: hidden;
        }

        .card:hover {
            box-shadow: var(--hover-shadow);
        }

        .stat-card {
            background: var(--gradient-primary);
            color: white;
            border-radius: var(--border-radius-md);
            padding: var(--spacing-md);
            position: relative;
            overflow: hidden;
        }

        .stat-card .icon {
            font-size: 2.5rem;
            opacity: 0.8;
            color: var(--accent-color);
        }

        .chart-container {
            background: white;
            border-radius: var(--border-radius-md);
            padding: var(--spacing-md);
            box-shadow: var(--card-shadow);
        }

        .chart-container h4 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: var(--spacing-md);
        }

        .table {
            background: white;
            border-radius: var(--border-radius-md);
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }

        .table thead {
            background: var(--gradient-primary);
            color: white;
        }

        .table th {
            font-weight: 600;
            padding: var(--spacing-md);
            border: none;
        }

        .table td {
            padding: var(--spacing-md);
            vertical-align: middle;
            color: var(--text-color);
        }

        .table tbody tr:hover {
            background-color: rgba(10, 25, 47, 0.05);
        }

        .status-text {
            font-weight: 500;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }

        .status-text.success {
            color: var(--success-color);
            background-color: rgba(32, 178, 170, 0.1);
        }

        .status-text.warning {
            color: var(--warning-color);
            background-color: rgba(255, 209, 102, 0.1);
        }

        .status-text.danger {
            color: var(--danger-color);
            background-color: rgba(239, 68, 68, 0.1);
        }

        .badge {
            display: none;
        }

        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--border-radius-sm);
            transition: all 0.3s ease;
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
            opacity: 0.9;
        }

        @media (max-width: 767.98px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.hidden {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .main-content.ml-64 {
                margin-left: 0;
            }

            .toggle-btn {
                margin-right: 0.75rem !important;
            }
        }

        @media (min-width: 768px) {
            .sidebar.collapsed {
                width: 70px;
            }
            .sidebar.collapsed .nav-link span,
            .sidebar.collapsed h3,
            .sidebar.collapsed .text-danger {
                display: none;
            }
            .sidebar.collapsed .nav-link {
                text-align: center;
                padding: 0.8rem 0;
            }
            .sidebar.collapsed .nav-link i {
                margin: 0;
                font-size: 1.2rem;
            }
            .main-content.expanded {
                margin-left: 70px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="sidebar" id="sidebar">
                <h3 class="text-white mb-4">MediSync</h3>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-home me-2"></i>Dashboard
                    </a>
                    <a class="nav-link" href="inventory.php">
                        <i class="fas fa-pills me-2"></i>Inventory
                    </a>
                    <a class="nav-link" href="prescriptions.php">
                        <i class="fas fa-prescription me-2"></i>Prescriptions
                    </a>
                    <a class="nav-link" href="orders.php">
                        <i class="fas fa-truck me-2"></i>Orders
                    </a>
                    <a class="nav-link active" href="reports.php">
                        <i class="fas fa-chart-bar me-2"></i>Reports
                    </a>
                    <a class="nav-link text-danger mt-4" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="main-content" id="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center">
                        <button class="toggle-btn me-3" onclick="toggleSidebar()">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h2 class="mb-0">Reports & Analytics</h2>
                    </div>
                </div>

                <!-- Monthly Statistics -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Patients This Month</h6>
                                        <h3><?= $monthlyStats['total_patients'] ?></h3>
                                    </div>
                                    <i class="fas fa-users icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Prescriptions</h6>
                                        <h3><?= $monthlyStats['total_prescriptions'] ?></h3>
                                    </div>
                                    <i class="fas fa-prescription icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Inventory Alerts</h6>
                                        <h3><?= count($inventoryAlerts) ?></h3>
                                    </div>
                                    <i class="fas fa-exclamation-triangle icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h4>Monthly Prescriptions</h4>
                            <canvas id="prescriptionsChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h4>Inventory Status</h4>
                            <canvas id="inventoryChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Alerts and Recent Orders -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h4>Inventory Alerts</h4>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Medicine</th>
                                                <th>Quantity</th>
                                                <th>Expiry Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($inventoryAlerts as $alert): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($alert['medicine_name']) ?></td>
                                                <td><?= $alert['quantity'] ?></td>
                                                <td><?= date('M d, Y', strtotime($alert['expiry_date'])) ?></td>
                                                <td>
                                                    <?php
                                                    $status = 'Low Stock';
                                                    $statusClass = 'warning';
                                                    if (strtotime($alert['expiry_date']) <= strtotime('+30 days')) {
                                                        $status = 'Expiring Soon';
                                                        $statusClass = 'danger';
                                                    }
                                                    ?>
                                                    <span class="status-text <?= $statusClass ?>"><?= $status ?></span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h4>Recent Orders</h4>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Supplier</th>
                                                <th>Medicine</th>
                                                <th>Quantity</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentOrders as $order): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($order['supplier_name']) ?></td>
                                                <td><?= htmlspecialchars($order['medicine_name']) ?></td>
                                                <td><?= $order['quantity'] ?></td>
                                                <td>
                                                    <span class="status-text <?= $order['status'] === 'delivered' ? 'success' : 'warning' ?>">
                                                        <?= ucfirst($order['status']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Toggle sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            sidebar.classList.toggle('hidden');
            mainContent.classList.toggle('ml-64');
            mainContent.classList.toggle('ml-0');
        }

        // Close sidebar on mobile when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.querySelector('.toggle-btn');
            const mainContent = document.getElementById('main-content');
            
            if (window.innerWidth <= 767.98) {
                if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
                    sidebar.classList.add('hidden');
                    mainContent.classList.remove('ml-64');
                    mainContent.classList.add('ml-0');
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            
            if (window.innerWidth <= 767.98) {
                sidebar.classList.add('hidden');
                mainContent.classList.remove('ml-64');
                mainContent.classList.add('ml-0');
            } else {
                sidebar.classList.remove('hidden');
                mainContent.classList.add('ml-64');
                mainContent.classList.remove('ml-0');
            }
        });

        // Monthly Prescriptions Chart
        const prescriptionsCtx = document.getElementById('prescriptionsChart').getContext('2d');
        new Chart(prescriptionsCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Prescriptions',
                    data: [65, 59, 80, 81, 56, 55],
                    borderColor: '#20b2aa',
                    tension: 0.4,
                    fill: true,
                    backgroundColor: 'rgba(32, 178, 170, 0.1)'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.1)'
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

        // Inventory Status Chart
        const inventoryCtx = document.getElementById('inventoryChart').getContext('2d');
        new Chart(inventoryCtx, {
            type: 'doughnut',
            data: {
                labels: ['In Stock', 'Low Stock', 'Out of Stock'],
                datasets: [{
                    data: [70, 20, 10],
                    backgroundColor: [
                        '#20b2aa',  // Teal
                        '#ffd166',  // Warning
                        '#ef4444'   // Danger
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html> 