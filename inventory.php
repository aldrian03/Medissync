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
            cursor: pointer;
            text-decoration: none;
            display: block;
        }

        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
            color: #fff;
        }

        .sidebar .nav-link.active {
            background: var(--secondary-color);
            color: #fff;
        }

        .sidebar .nav-link.active:hover {
            background: var(--secondary-color);
            color: #fff;
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
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-home me-2"></i>Dashboard
                    </a>
                    <a class="nav-link active" href="inventory.php" style="pointer-events: auto;">
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
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cog me-2"></i>Settings
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Medicine Inventory</h2>
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
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Medicines</h5>
                                <h2 class="mb-0"><?= $total ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Low Stock Items</h5>
                                <h2 class="mb-0"><?= $lowStock ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <h5 class="card-title">Out of Stock</h5>
                                <h2 class="mb-0"><?= $outOfStock ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Stock</h5>
                                <h2 class="mb-0"><?= $totalStock ?></h2>
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