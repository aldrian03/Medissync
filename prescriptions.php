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
            if ($stmt->execute()) {
                // Update the pending count after successful insertion
                $stats['pending']++;
            }
        } elseif ($_POST['action'] === 'cancel') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE prescriptions SET status = 'unapproved' WHERE id = ? AND status = 'pending'");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                // Update the counts
                $stats['pending']--;
                $stats['unapproved']++;
            }
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
            width: inherit;
            max-width: inherit;
        }

        .sidebar h3 {
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: var(--spacing-lg);
            padding-bottom: var(--spacing-sm);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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
            background: rgba(255, 255, 255, 0.1);
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

        .main-content {
            padding: var(--spacing-lg);
            margin-left: 16.666667%;
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

        .stats-card {
            text-align: center;
            padding: var(--spacing-md);
            border-radius: var(--border-radius-md);
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--gradient-primary);
            opacity: 0.1;
            z-index: 0;
        }

        .stats-card .icon {
            font-size: 2.5rem;
            margin-bottom: var(--spacing-sm);
            color: var(--primary-color);
            position: relative;
            z-index: 1;
        }

        .stats-card .count {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: var(--spacing-xs);
            color: var(--primary-color);
            position: relative;
            z-index: 1;
        }

        .stats-card .label {
            font-size: 1.1rem;
            color: var(--text-color);
            font-weight: 500;
            position: relative;
            z-index: 1;
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
        }

        .table tbody tr:hover {
            background-color: rgba(53, 131, 102, 0.05);
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
            background: var(--primary-color) !important;
        }

        .badge.bg-warning {
            background: #ffd166 !important;
        }

        .badge.bg-danger {
            background: var(--accent-color) !important;
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

        .modal-body {
            padding: var(--spacing-md);
        }

        .form-control, .form-select {
            border-radius: var(--border-radius-sm);
            border: 1px solid #e0e0e0;
            padding: var(--spacing-sm);
            font-size: 0.95rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(53, 131, 102, 0.15);
        }

        .input-group-text {
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: var(--border-radius-sm) 0 0 var(--border-radius-sm);
            padding: var(--spacing-sm);
        }

        .input-group .form-control {
            border-radius: 0 var(--border-radius-sm) var(--border-radius-sm) 0;
        }

        .search-bar {
            border-radius: var(--border-radius-sm);
            padding: var(--spacing-sm) var(--spacing-md);
            border: 1px solid #e0e0e0;
            width: 300px;
            transition: all 0.3s ease;
        }

        .search-bar:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(53, 131, 102, 0.15);
        }

        .btn-group .btn {
            padding: 0.5rem 0.75rem;
            border-radius: var(--border-radius-sm);
        }

        .alert {
            border-radius: var(--border-radius-sm);
            padding: var(--spacing-sm) var(--spacing-md);
            margin-bottom: var(--spacing-md);
            border: none;
        }

        .alert-success {
            background-color: rgba(53, 131, 102, 0.1);
            color: var(--primary-color);
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--accent-color);
        }

        @media (max-width: 767.98px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
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
            <div class="col-md-3 col-lg-2 sidebar">
                <h3 class="text-white mb-4">MediSync</h3>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php"><i class="fas fa-home me-2"></i>Dashboard</a>
                    <a class="nav-link" href="inventory.php"><i class="fas fa-pills me-2"></i>Inventory</a>
                    <a class="nav-link active" href="prescriptions.php"><i class="fas fa-prescription me-2"></i>Prescriptions</a>
                    <a class="nav-link" href="orders.php"><i class="fas fa-truck me-2"></i>Orders</a>
                    <a class="nav-link" href="reports.php"><i class="fas fa-chart-bar me-2"></i>Reports</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <button class="btn btn-primary d-md-none mb-3" id="sidebarToggleMobile">
                    <i class="fas fa-bars"></i> Menu
                </button>
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
                        <div class="card stats-card bg-warning bg-opacity-10" id="pendingPrescriptionsCard">
                            <div class="icon text-warning">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="count text-warning" id="pendingCount"><?= $stats['pending'] ?></div>
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
                                    <tr data-prescription-id="<?= $prescription['id'] ?>">
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
                    <form method="POST" id="addPrescriptionForm">
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
                        // Update the counts in the cards
                        const pendingCount = document.getElementById('pendingCount');
                        const currentPending = parseInt(pendingCount.textContent);
                        pendingCount.textContent = currentPending - 1;
                        
                        // Update the approved count
                        const approvedCount = document.querySelector('.stats-card.bg-success .count');
                        const currentApproved = parseInt(approvedCount.textContent);
                        approvedCount.textContent = currentApproved + 1;
                        
                        // Reload the page after a short delay
                        setTimeout(() => location.reload(), 1000);
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
                const formData = new FormData();
                formData.append('action', 'cancel');
                formData.append('id', id);

                fetch('prescriptions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the pending count
                        const pendingCount = document.getElementById('pendingCount');
                        const currentPending = parseInt(pendingCount.textContent);
                        pendingCount.textContent = currentPending - 1;
                        
                        // Update the unapproved count
                        const unapprovedCount = document.querySelector('.stats-card.bg-danger .count');
                        const currentUnapproved = parseInt(unapprovedCount.textContent);
                        unapprovedCount.textContent = currentUnapproved + 1;
                        
                        // Remove the row from the table
                        const row = document.querySelector(`tr[data-prescription-id="${id}"]`);
                        if (row) {
                            row.remove();
                        }
                        
                        // Show success message
                        showToast('success', 'Prescription cancelled successfully');
                    } else {
                        showToast('error', data.message || 'Failed to cancel prescription');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', 'An error occurred while cancelling the prescription');
                });
            }
        }

        // Add event listener for the Add Prescription form
        document.getElementById('addPrescriptionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('add_prescription.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the pending count
                    const pendingCount = document.getElementById('pendingCount');
                    const currentPending = parseInt(pendingCount.textContent);
                    pendingCount.textContent = currentPending + 1;
                    
                    // Close the modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addPrescriptionModal'));
                    modal.hide();
                    
                    // Show success message
                    showToast('success', 'Prescription added successfully');
                    
                    // Reload the page after a short delay
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('error', data.message || 'Failed to add prescription');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'An error occurred while adding the prescription');
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarToggleMobile = document.getElementById('sidebarToggleMobile');
            const mainContent = document.querySelector('.main-content');

            function toggleSidebar() {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            }

            function toggleMobileSidebar() {
                sidebar.classList.toggle('show');
            }

            sidebarToggle.addEventListener('click', toggleSidebar);
            sidebarToggleMobile.addEventListener('click', toggleMobileSidebar);

            // Close sidebar on mobile when clicking outside
            document.addEventListener('click', function(event) {
                const isClickInside = sidebar.contains(event.target) || 
                                    sidebarToggleMobile.contains(event.target);
                
                if (!isClickInside && window.innerWidth < 768 && sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                }
            });
        });
    </script>
</body>
</html> 