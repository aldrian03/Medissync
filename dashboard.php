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

// Get total unique patients
$query = "SELECT COUNT(DISTINCT patient_name) as total_patients FROM prescriptions";
$result = $conn->query($query);
$total_patients = $result->fetch_assoc()['total_patients'];

// Get today's appointments
$today = date('Y-m-d');
$query = "SELECT COUNT(*) as total_appointments FROM appointments WHERE appointment_date = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
$appointments_today = $result->fetch_assoc()['total_appointments'];

// Get total stocks from inventory
$query = "SELECT SUM(quantity) as total_stocks FROM inventory";
$result = $conn->query($query);
$total_stocks = $result->fetch_assoc()['total_stocks'] ?? 0;

// Get pending orders
$query = "SELECT COUNT(*) as pending_orders FROM orders WHERE status = 'pending'";
$result = $conn->query($query);
$pending_orders = $result->fetch_assoc()['pending_orders'] ?? 0;

// Get weekly appointments for chart
$weekStart = date('Y-m-d', strtotime('monday this week'));
$weekEnd = date('Y-m-d', strtotime('sunday this week'));
$query = "SELECT DATE(appointment_date) as date, COUNT(*) as count 
          FROM appointments 
          WHERE appointment_date BETWEEN ? AND ?
          GROUP BY DATE(appointment_date)
          ORDER BY date";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $weekStart, $weekEnd);
$stmt->execute();
$weeklyData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent prescriptions
$query = "SELECT * FROM prescriptions ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($query);
$recent_prescriptions = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MediSync Dashboard</title>
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
            --warning-color:rgb(190, 145, 38);
            --danger-color:rgb(156, 50, 50);
            --background-color: #f8fafc;
            --text-color: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --hover-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --gradient-primary: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            --gradient-secondary: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            --gradient-success: linear-gradient(135deg, var(--success-color), var(--accent-color));
            --gradient-warning: linear-gradient(135deg, var(--warning-color),rgb(145, 105, 5));
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
            background-color: rgba(37, 99, 235, 0.05);
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

        .badge {
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-sm);
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        .badge.bg-success {
            background: var(--gradient-success) !important;
        }

        .badge.bg-warning {
            background: var(--gradient-warning) !important;
        }

        .badge.bg-danger {
            background: var(--gradient-danger) !important;
        }

        .modal-content {
            border-radius: var(--border-radius-md);
            border: none;
            box-shadow: var(--hover-shadow);
        }

        .modal-header {
            background: var(--gradient-primary);
            color: white;
            border-radius: var(--border-radius-md) var(--border-radius-md) 0 0;
            padding: var(--spacing-md);
        }

        .form-control, .form-select {
            border-radius: var(--border-radius-sm);
            border: 1px solid var(--border-color);
            padding: var(--spacing-sm);
            font-size: 0.95rem;
            color: var(--text-color);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.15);
        }

        .input-group-text {
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: var(--border-radius-sm) 0 0 var(--border-radius-sm);
            padding: var(--spacing-sm);
        }

        .search-container {
            position: relative;
            width: 300px;
        }

        .search-bar {
            width: 100%;
            padding: 12px 40px 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius-md);
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
        }

        .search-bar:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 155, 123, 0.1);
            outline: none;
        }

        .search-icon {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
            transition: color 0.3s ease;
        }

        .search-bar:focus + .search-icon {
            color: var(--primary-color);
        }

        .filter-dropdown {
            border-radius: var(--border-radius-sm);
            padding: var(--spacing-sm);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .filter-dropdown:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.15);
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
                    <a class="nav-link active" href="dashboard.php">
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
                    <a class="nav-link" href="reports.php">
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
                        <h2 class="mb-0">Dashboard</h2>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="search-container position-relative">
                            <input type="text" class="search-bar" placeholder="Search...">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Patients</h6>
                                        <h3><?= $total_patients ?></h3>
                                    </div>
                                    <i class="fas fa-users icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Today's Appointments</h6>
                                        <h3><?= $appointments_today ?></h3>
                                    </div>
                                    <i class="fas fa-calendar-check icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Stocks</h6>
                                        <h3><?= number_format($total_stocks) ?></h3>
                                    </div>
                                    <i class="fas fa-pills icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Pending Orders</h6>
                                        <h3><?= $pending_orders ?></h3>
                                    </div>
                                    <i class="fas fa-shopping-cart icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts and Tables -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="chart-container mb-4">
                            <h4>Weekly Appointments</h4>
                            <canvas id="weeklyAppointmentsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Prescriptions Section -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h4 class="mb-0">Recent Prescriptions</h4>
                                <p class="text-muted mb-0">Manage your recent prescriptions</p>
                            </div>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPrescriptionModal">
                                <i class="fas fa-plus me-2"></i>Add New Prescription
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Patient Name</th>
                                        <th>Medicine</th>
                                        <th>Dosage</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_prescriptions as $prescription): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">

                                                </div>
                                                <?= htmlspecialchars($prescription['patient_name']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <i class="fas fa-pills me-1"></i>
                                                <?= htmlspecialchars($prescription['medicine']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($prescription['dosage']) ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-calendar-alt text-muted me-2"></i>
                                                <?= date('M d, Y', strtotime($prescription['created_at'])) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-primary" onclick="editPrescription(<?= $prescription['id'] ?>)" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deletePrescription(<?= $prescription['id'] ?>)" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
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

    <!-- Add Prescription Modal -->
    <div class="modal fade" id="addPrescriptionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Add New Prescription
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addPrescriptionForm" method="POST">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Patient Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" name="patient_name" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Medicine</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-pills"></i></span>
                                <select class="form-select" name="medicine" required>
                                    <option value="">Select Medicine</option>
                                    <?php
                                    $query = "SELECT medicine_name FROM inventory WHERE quantity > 0 ORDER BY medicine_name";
                                    $result = $conn->query($query);
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($row['medicine_name']) . "'>" . 
                                             htmlspecialchars($row['medicine_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dosage</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-syringe"></i></span>
                                <input type="text" class="form-control" name="dosage" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i>Save Prescription
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Prescription Modal -->
    <div class="modal fade" id="editPrescriptionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Prescription
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editPrescriptionForm" method="POST">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Patient Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" name="patient_name" id="edit_patient_name" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Medicine</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-pills"></i></span>
                                <select class="form-select" name="medicine" id="edit_medicine" required>
                                    <?php
                                    $query = "SELECT medicine_name FROM inventory WHERE quantity > 0 ORDER BY medicine_name";
                                    $result = $conn->query($query);
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($row['medicine_name']) . "'>" . 
                                             htmlspecialchars($row['medicine_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dosage</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-syringe"></i></span>
                                <input type="text" class="form-control" name="dosage" id="edit_dosage" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i>Update Prescription
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Weekly Appointments Chart
        const ctx = document.getElementById('weeklyAppointmentsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Appointments',
                    data: [20, 18, 22, 25, 24, 15, 10],
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

        // Add Prescription Form Handler
        document.getElementById('addPrescriptionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            
            // Disable submit button and show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
            
            fetch('add_prescription.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addPrescriptionModal'));
                    modal.hide();
                    
                    // Show success toast
                    const toast = document.createElement('div');
                    toast.className = 'position-fixed bottom-0 end-0 p-3';
                    toast.style.zIndex = '11';
                    toast.innerHTML = `
                        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                            <div class="toast-header bg-success text-white">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong class="me-auto">Success</strong>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                            </div>
                            <div class="toast-body">
                                ${data.message}
                            </div>
                        </div>
                    `;
                    document.body.appendChild(toast);
                    
                    // Remove toast after 3 seconds
                    setTimeout(() => {
                        toast.remove();
                        location.reload();
                    }, 3000);
                } else {
                    // Show error message
                    alert(data.message || 'Failed to add prescription');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the prescription');
            })
            .finally(() => {
                // Reset submit button
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-save me-2"></i>Save Prescription';
            });
        });

        // Edit Prescription Function
        function editPrescription(id) {
            // Show loading state
            const editButton = event.currentTarget;
            const originalContent = editButton.innerHTML;
            editButton.disabled = true;
            editButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch('get_prescription.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('edit_id').value = data.prescription.id;
                        document.getElementById('edit_patient_name').value = data.prescription.patient_name;
                        document.getElementById('edit_medicine').value = data.prescription.medicine;
                        document.getElementById('edit_dosage').value = data.prescription.dosage;
                        
                        new bootstrap.Modal(document.getElementById('editPrescriptionModal')).show();
                    } else {
                        // Show error toast
                        showToast('error', data.message || 'Failed to fetch prescription details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', 'An error occurred while fetching prescription details');
                })
                .finally(() => {
                    // Reset button state
                    editButton.disabled = false;
                    editButton.innerHTML = originalContent;
                });
        }

        // Edit Prescription Form Handler
        document.getElementById('editPrescriptionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            
            // Disable submit button and show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
            
            fetch('update_prescription.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close the modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editPrescriptionModal'));
                    modal.hide();
                    
                    // Show success message
                    showToast('success', data.message || 'Prescription updated successfully');
                    
                    // Reload the page after a short delay
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('error', data.message || 'Failed to update prescription');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'An error occurred while updating the prescription');
            })
            .finally(() => {
                // Reset submit button
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-save me-2"></i>Update Prescription';
            });
        });

        // Delete Prescription Function
        function deletePrescription(id) {
            if (confirm('Are you sure you want to delete this prescription?')) {
                const deleteButton = event.currentTarget;
                const originalContent = deleteButton.innerHTML;
                deleteButton.disabled = true;
                deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                fetch('delete_prescription.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('success', data.message || 'Prescription deleted successfully');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast('error', data.message || 'Failed to delete prescription');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', 'An error occurred while deleting the prescription');
                })
                .finally(() => {
                    deleteButton.disabled = false;
                    deleteButton.innerHTML = originalContent;
                });
            }
        }

        // Toast notification function
        function showToast(type, message) {
            const toast = document.createElement('div');
            toast.className = 'position-fixed bottom-0 end-0 p-3';
            toast.style.zIndex = '11';
            
            const bgColor = type === 'success' ? 'bg-success' : 'bg-danger';
            const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
            
            toast.innerHTML = `
                <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header ${bgColor} text-white">
                        <i class="fas fa-${icon} me-2"></i>
                        <strong class="me-auto">${type === 'success' ? 'Success' : 'Error'}</strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Remove toast after 3 seconds
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

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
    </script>
</body>
</html>
