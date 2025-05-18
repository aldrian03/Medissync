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

        .table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
        }

        .table thead {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary {
            background: var(--secondary-color);
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .search-bar {
            border-radius: 20px;
            padding: 0.8rem 1.5rem;
            border: 1px solid #ddd;
            width: 300px;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .status-badge.success {
            background-color: #2ecc71;
            color: white;
        }

        .status-badge.warning {
            background-color: #f1c40f;
            color: white;
        }

        .status-badge.danger {
            background-color: #e74c3c;
            color: white;
        }

        .avatar-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .table > :not(caption) > * > * {
            padding: 1rem;
        }

        .table tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }

        .badge {
            padding: 0.5rem 1rem;
            font-weight: 500;
        }

        .btn-group .btn {
            padding: 0.5rem 0.75rem;
        }

        .modal-header {
            border-radius: 0.5rem 0.5rem 0 0;
        }

        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
        }

        .form-control, .form-select {
            border-left: none;
        }

        .form-control:focus, .form-select:focus {
            border-color: #dee2e6;
            box-shadow: none;
        }

        .input-group:focus-within {
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .input-group:focus-within .input-group-text,
        .input-group:focus-within .form-control,
        .input-group:focus-within .form-select {
            border-color: #3498db;
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
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Dashboard</h2>
                    <div class="d-flex align-items-center">
                        <input type="text" class="search-bar me-3" placeholder="Search...">
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
                                                <div class="avatar-circle bg-primary text-white me-2">
                                                    <?= strtoupper(substr($prescription['patient_name'], 0, 1)) ?>
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
    </script>
</body>
</html>
