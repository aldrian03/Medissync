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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $medicine_name = htmlspecialchars($_POST['medicine_name']);
            $quantity = (int)$_POST['quantity'];
            $expiry_date = $_POST['expiry_date'];
            
            // Check if medicine name already exists
            $check_stmt = $conn->prepare("SELECT id FROM inventory WHERE medicine_name = ?");
            $check_stmt->bind_param("s", $medicine_name);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error_message = "Medicine name already exists in the inventory.";
            } else {
                $stmt = $conn->prepare("INSERT INTO inventory (medicine_name, quantity, expiry_date) VALUES (?, ?, ?)");
                $stmt->bind_param("sis", $medicine_name, $quantity, $expiry_date);
                
                if ($stmt->execute()) {
                    $success_message = "Medicine added successfully.";
                } else {
                    $error_message = "Failed to add medicine.";
                }
                $stmt->close();
            }
            $check_stmt->close();
        }
    }
}

// Fetch inventory items
$query = "SELECT * FROM inventory ORDER BY medicine_name";
$result = $conn->query($query);
$inventory_items = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Medicine Inventory - MediSync</title>
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

        .search-bar {
            border-radius: var(--border-radius-sm);
            padding: var(--spacing-sm) var(--spacing-md);
            border: 1px solid var(--border-color);
            width: 300px;
            transition: all 0.3s ease;
        }

        .search-bar:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.15);
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

        .stock-level {
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-sm);
            font-size: 0.875rem;
        }

        .stock-level.low {
            background: var(--gradient-danger);
            color: white;
        }

        .stock-level.medium {
            background: var(--gradient-warning);
            color: white;
        }

        .stock-level.high {
            background: var(--gradient-success);
            color: white;
        }

        .product-image {
            width: 50px;
            height: 50px;
            border-radius: var(--border-radius-sm);
            object-fit: cover;
        }

        .category-badge {
            background: var(--gradient-secondary);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-sm);
            font-size: 0.875rem;
            font-weight: 500;
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
                    <a class="nav-link active" href="inventory.php">
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
                        <h2 class="mb-0">Medicine Inventory</h2>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMedicineModal">
                        <i class="fas fa-plus me-2"></i>Add Medicine
                    </button>
                </div>

                <!-- Stock Status Cards -->
                <div class="row mb-4">
                    <?php
                    // Get total unique medicines
                    $query = "SELECT COUNT(DISTINCT medicine_name) as total FROM inventory";
                    $result = $conn->query($query);
                    $total = $result->fetch_assoc()['total'];

                    // Get low stock medicines
                    $query = "SELECT COUNT(DISTINCT medicine_name) as low_stock FROM inventory WHERE quantity <= 10 AND quantity > 0";
                    $result = $conn->query($query);
                    $lowStock = $result->fetch_assoc()['low_stock'];

                    // Get out of stock medicines
                    $query = "SELECT COUNT(DISTINCT medicine_name) as out_of_stock FROM inventory WHERE quantity = 0";
                    $result = $conn->query($query);
                    $outOfStock = $result->fetch_assoc()['out_of_stock'];

                    // Get total stock count
                    $query = "SELECT SUM(quantity) as total_stock FROM inventory";
                    $result = $conn->query($query);
                    $totalStock = $result->fetch_assoc()['total_stock'] ?? 0;
                    ?>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title">Total Medicines</h5>
                                        <h2 class="mb-0"><?= $total ?></h2>
                                    </div>
                                    <i class="fas fa-pills icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card" style="background: var(--gradient-warning);">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title">Low Stock Items</h5>
                                        <h2 class="mb-0"><?= $lowStock ?></h2>
                                    </div>
                                    <i class="fas fa-exclamation-triangle icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card" style="background: var(--gradient-danger);">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title">Out of Stock</h5>
                                        <h2 class="mb-0"><?= $outOfStock ?></h2>
                                    </div>
                                    <i class="fas fa-times-circle icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card" style="background: var(--gradient-success);">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title">Total Stock</h5>
                                        <h2 class="mb-0"><?= $totalStock ?></h2>
                                    </div>
                                    <i class="fas fa-boxes icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="searchInput" placeholder="Search medicines...">
                        </div>
                    </div>
                </div>

                <!-- Medicine List Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="inventoryTable">
                                <thead>
                                    <tr>
                                        <th>Medicine Name</th>
                                        <th>Quantity</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inventory_items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['medicine_name']) ?></td>
                                        <td>
                                            <span class="badge bg-secondary"><?= $item['quantity'] ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $status = 'In Stock';
                                            $statusClass = 'success';
                                            if ($item['quantity'] <= 10 && $item['quantity'] > 0) {
                                                $status = 'Low Stock';
                                                $statusClass = 'warning';
                                            }
                                            if ($item['quantity'] == 0) {
                                                $status = 'Out of Stock';
                                                $statusClass = 'danger';
                                            }
                                            ?>
                                            <span class="badge bg-<?= $statusClass ?>"><?= $status ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-primary" onclick="editMedicine(<?= $item['id'] ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteMedicine(<?= $item['id'] ?>)">
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

    <!-- Add Medicine Modal -->
    <div class="modal fade" id="addMedicineModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Medicine</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?= $error_message ?></div>
                    <?php endif; ?>
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success"><?= $success_message ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Medicine Name</label>
                            <input type="text" class="form-control" name="medicine_name" required 
                                   pattern="[A-Za-z0-9\s-]+" title="Only letters, numbers, spaces and hyphens are allowed">
                            <small class="text-muted">Medicine name must be unique</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" name="quantity" required min="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" class="form-control" name="expiry_date" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Add Medicine</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Medicine Modal -->
    <div class="modal fade" id="editMedicineModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Medicine</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editMedicineForm" method="POST">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Medicine Name</label>
                            <input type="text" class="form-control" name="medicine_name" id="edit_medicine_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" name="quantity" id="edit_quantity" required min="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" class="form-control" name="expiry_date" id="edit_expiry_date" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Update Medicine</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const rows = document.querySelectorAll('#inventoryTable tbody tr');
            
            rows.forEach(row => {
                const medicineName = row.querySelector('td:first-child').textContent.toLowerCase();
                row.style.display = medicineName.includes(searchText) ? '' : 'none';
            });
        });

        // Check for duplicate medicine name
        function checkDuplicateMedicine(medicineName) {
            const rows = document.querySelectorAll('#inventoryTable tbody tr');
            for (let row of rows) {
                const existingName = row.querySelector('td:first-child').textContent.trim().toLowerCase();
                if (existingName === medicineName.toLowerCase()) {
                    return true;
                }
            }
            return false;
        }

        // Handle Add Medicine form submission
        document.querySelector('#addMedicineModal form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const medicineName = this.querySelector('[name="medicine_name"]').value.trim();
            
            if (checkDuplicateMedicine(medicineName)) {
                alert('Medicine name already exists in the inventory.');
                return;
            }
            
            this.submit();
        });

        // Edit medicine function
        function editMedicine(id) {
            // Fetch medicine details
            fetch('get_medicine.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate the edit form
                        document.getElementById('edit_id').value = data.medicine.id;
                        document.getElementById('edit_medicine_name').value = data.medicine.medicine_name;
                        document.getElementById('edit_quantity').value = data.medicine.quantity;
                        document.getElementById('edit_expiry_date').value = data.medicine.expiry_date;
                        
                        // Show the modal
                        new bootstrap.Modal(document.getElementById('editMedicineModal')).show();
                    } else {
                        alert('Failed to fetch medicine details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while fetching medicine details');
                });
        }

        // Handle edit form submission
        document.getElementById('editMedicineForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('update_medicine.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to update medicine');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating medicine');
            });
        });

        // Delete medicine function
        function deleteMedicine(id) {
            if (confirm('Are you sure you want to delete this medicine?')) {
                fetch('delete_medicine.php', {
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
                        alert('Failed to delete medicine');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting medicine');
                });
            }
        }
    </script>
</body>
</html> 