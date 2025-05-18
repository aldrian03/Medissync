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
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --background-color: #f8f9fa;
            --text-color: #2c3e50;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--background-color);
            color: var(--text-color);
        }

        .sidebar {
            background: var(--primary-color);
            min-height: 100vh;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link {
            color: #fff;
            padding: 0.8rem 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: var(--secondary-color);
        }

        .main-content {
            padding: 2rem;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .stat-card {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .stat-card .icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }

        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h3 class="text-white mb-4">MediSync</h3>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php"><i class="fas fa-home me-2"></i>Dashboard</a>
                    <a class="nav-link" href="inventory.php"><i class="fas fa-pills me-2"></i>Inventory</a>
                    <a class="nav-link" href="prescriptions.php"><i class="fas fa-prescription me-2"></i>Prescriptions</a>
                    <a class="nav-link" href="orders.php"><i class="fas fa-truck me-2"></i>Orders</a>
                    <a class="nav-link active" href="reports.php"><i class="fas fa-chart-bar me-2"></i>Reports</a>
                    <a class="nav-link" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <h2 class="mb-4">Reports & Analytics</h2>

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
                                                    <span class="badge bg-<?= $statusClass ?>"><?= $status ?></span>
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
                                                    <span class="badge bg-<?= $order['status'] === 'delivered' ? 'success' : 'warning' ?>">
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
        // Monthly Prescriptions Chart
        const prescriptionsCtx = document.getElementById('prescriptionsChart').getContext('2d');
        new Chart(prescriptionsCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Prescriptions',
                    data: [65, 59, 80, 81, 56, 55],
                    borderColor: '#3498db',
                    tension: 0.4,
                    fill: true,
                    backgroundColor: 'rgba(52, 152, 219, 0.1)'
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
                        '#2ecc71',
                        '#f1c40f',
                        '#e74c3c'
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