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

// Get prescription statistics
$stats = [
    'approved' => 0,
    'pending' => 0,
    'unapproved' => 0
];

$query = "SELECT 
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'unapproved' THEN 1 ELSE 0 END) as unapproved
FROM prescriptions";
$result = $conn->query($query);
if ($result) {
    $row = $result->fetch_assoc();
    $stats['approved'] = $row['approved'] ?? 0;
    $stats['pending'] = $row['pending'] ?? 0;
    $stats['unapproved'] = $row['unapproved'] ?? 0;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $patient_name = htmlspecialchars($_POST['patient_name']);
            $medicine = htmlspecialchars($_POST['medicine']);
            $dosage = htmlspecialchars($_POST['dosage']);
            
            $stmt = $conn->prepare("INSERT INTO prescriptions (patient_name, medicine, dosage, status) VALUES (?, ?, ?, 'pending')");
            $stmt->bind_param("sss", $patient_name, $medicine, $dosage);
            $stmt->execute();
        }
    }
}

// Fetch prescriptions
$query = "SELECT * FROM prescriptions ORDER BY created_at DESC";
$result = $conn->query($query);
$prescriptions = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prescriptions - MediSync</title>
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

        .stats-card {
            text-align: center;
            padding: 1.5rem;
        }

        .stats-card .icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .stats-card .count {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stats-card .label {
            font-size: 1.1rem;
            color: #666;
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
                    <a class="nav-link active" href="prescriptions.php"><i class="fas fa-prescription me-2"></i>Prescriptions</a>
                    <a class="nav-link" href="orders.php"><i class="fas fa-truck me-2"></i>Orders</a>
                    <a class="nav-link" href="reports.php"><i class="fas fa-chart-bar me-2"></i>Reports</a>
                    <a class="nav-link" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Prescriptions</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPrescriptionModal">
                        <i class="fas fa-plus me-2"></i>Add Prescription
                    </button>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card stats-card bg-success bg-opacity-10">
                            <div class="icon text-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="count text-success"><?= $stats['approved'] ?></div>
                            <div class="label">Approved Prescriptions</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stats-card bg-warning bg-opacity-10">
                            <div class="icon text-warning">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="count text-warning"><?= $stats['pending'] ?></div>
                            <div class="label">Pending Prescriptions</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stats-card bg-danger bg-opacity-10">
                            <div class="icon text-danger">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div class="count text-danger"><?= $stats['unapproved'] ?></div>
                            <div class="label">Unapproved Prescriptions</div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" id="searchInput" placeholder="Search prescriptions...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="patientFilter">
                                    <option value="">All Patients</option>
                                    <?php
                                    $query = "SELECT DISTINCT patient_name FROM prescriptions ORDER BY patient_name";
                                    $result = $conn->query($query);
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($row['patient_name']) . "'>" . htmlspecialchars($row['patient_name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="medicineFilter">
                                    <option value="">All Medicines</option>
                                    <?php
                                    $query = "SELECT DISTINCT medicine FROM prescriptions ORDER BY medicine";
                                    $result = $conn->query($query);
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($row['medicine']) . "'>" . htmlspecialchars($row['medicine']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-secondary w-100" onclick="resetFilters()">
                                    <i class="fas fa-redo me-2"></i>Reset
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Prescriptions Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="prescriptionsTable">
                                <thead>
                                    <tr>
                                        <th>Patient Name</th>
                                        <th>Medicine</th>
                                        <th>Dosage</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($prescriptions as $prescription): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($prescription['patient_name']) ?></td>
                                        <td><?= htmlspecialchars($prescription['medicine']) ?></td>
                                        <td><?= htmlspecialchars($prescription['dosage']) ?></td>
                                        <td>
                                            <?php
                                            $status = $prescription['status'] ?? '';
                                            $statusClass = '';
                                            $statusText = '';
                                            
                                            switch ($status) {
                                                case 'approved':
                                                    $statusClass = 'success';
                                                    $statusText = 'Approved';
                                                    break;
                                                case 'pending':
                                                    $statusClass = 'warning';
                                                    $statusText = 'Pending';
                                                    break;
                                                default:
                                                    $statusClass = 'danger';
                                                    $statusText = 'Unapproved';
                                            }
                                            ?>
                                            <span class="badge bg-<?= $statusClass ?>"><?= $statusText ?></span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($prescription['created_at'])) ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="view_prescription.php?id=<?= $prescription['id'] ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <?php if ($status !== 'approved'): ?>
                                                <button class="btn btn-sm btn-success" onclick="approvePrescription(<?= $prescription['id'] ?>)">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                                <?php endif; ?>
                                                <?php if ($status === 'pending'): ?>
                                                <button class="btn btn-sm btn-danger" onclick="cancelPrescription(<?= $prescription['id'] ?>)">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                                <?php endif; ?>
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
                <div class="modal-header">
                    <h5 class="modal-title">Add New Prescription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Patient Name</label>
                            <input type="text" class="form-control" name="patient_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Medicine</label>
                            <select class="form-select" name="medicine" required>
                                <option value="">Select Medicine</option>
                                <?php
                                $query = "SELECT medicine_name FROM inventory WHERE quantity > 0 ORDER BY medicine_name";
                                $result = $conn->query($query);
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($row['medicine_name']) . "'>" . htmlspecialchars($row['medicine_name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dosage</label>
                            <input type="text" class="form-control" name="dosage" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Add Prescription</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const rows = document.querySelectorAll('#prescriptionsTable tbody tr');
            
            rows.forEach(row => {
                const patientName = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
                const medicine = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                row.style.display = patientName.includes(searchText) || medicine.includes(searchText) ? '' : 'none';
            });
        });

        // Patient filter
        document.getElementById('patientFilter').addEventListener('change', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#prescriptionsTable tbody tr');
            
            rows.forEach(row => {
                const patientName = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
                row.style.display = !filter || patientName === filter ? '' : 'none';
            });
        });

        // Medicine filter
        document.getElementById('medicineFilter').addEventListener('change', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#prescriptionsTable tbody tr');
            
            rows.forEach(row => {
                const medicine = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                row.style.display = !filter || medicine === filter ? '' : 'none';
            });
        });

        // Reset filters
        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('patientFilter').value = '';
            document.getElementById('medicineFilter').value = '';
            const rows = document.querySelectorAll('#prescriptionsTable tbody tr');
            rows.forEach(row => row.style.display = '');
        }

        // Approve prescription function
        function approvePrescription(id) {
            if (confirm('Are you sure you want to approve this prescription?')) {
                fetch('approve_prescription.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to approve prescription');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while approving the prescription');
                });
            }
        }

        // Cancel prescription function
        function cancelPrescription(id) {
            if (confirm('Are you sure you want to cancel this prescription?')) {
                fetch('cancel_prescription.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to cancel prescription');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while cancelling the prescription');
                });
            }
        }
    </script>
</body>
</html> 