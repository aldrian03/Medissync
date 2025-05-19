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

// Get statistics
$query = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders
FROM orders";
$result = $conn->query($query);
$stats = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'cancel') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND status != 'cancelled'");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                // Update the counts
                $stats['cancelled_orders']++;
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Order is already cancelled']);
                }
                exit;
            }
        }
    }
}

// Fetch orders with supplier details
$query = "SELECT o.*, s.contact_number, s.email 
          FROM orders o 
          LEFT JOIN suppliers s ON o.supplier_name = s.name 
          WHERE o.status != 'cancelled'
          ORDER BY o.order_date DESC";
$result = $conn->query($query);
$orders = $result->fetch_all(MYSQLI_ASSOC);

// Fetch suppliers for dropdown
$query = "SELECT * FROM suppliers ORDER BY name";
$result = $conn->query($query);
$suppliers = $result->fetch_all(MYSQLI_ASSOC);

// Fetch medicines for dropdown
$query = "SELECT medicine_name FROM inventory ORDER BY medicine_name";
$result = $conn->query($query);
$medicines = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Orders - MediSync</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #439b7b;
            --primary-dark: #020c1b;
            --primary-light: #112240;
            --secondary-color:rgb(5, 101, 146);
            --accent-color: #f43f5e;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --background-color: #f8fafc;
            --text-color: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --hover-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --gradient-primary: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            --gradient-secondary: linear-gradient(135deg, var(--secondary-color), var(--primary-light));
            --gradient-success: linear-gradient(135deg, var(--success-color), #34d399);
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

        .tracking-info {
            background: var(--background-color);
            border-radius: var(--border-radius-md);
            padding: var(--spacing-md);
            margin-top: var(--spacing-sm);
        }

        .tracking-step {
            display: flex;
            align-items: center;
            margin-bottom: var(--spacing-sm);
        }

        .tracking-step i {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--gradient-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: var(--spacing-sm);
        }

        .tracking-step.active i {
            background: var(--gradient-secondary);
        }

        .tracking-step.completed i {
            background: var(--gradient-success);
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

        .status-text {
            font-weight: 500;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }

        .status-text.success {
            color: var(--success-color);
            background-color: rgba(16, 185, 129, 0.1);
        }

        .status-text.warning {
            color: var(--warning-color);
            background-color: rgba(245, 158, 11, 0.1);
        }

        .status-text.danger {
            color: var(--danger-color);
            background-color: rgba(239, 68, 68, 0.1);
        }

        .status-text.info {
            color: var(--secondary-color);
            background-color: rgba(5, 101, 146, 0.1);
        }

        .badge {
            display: none;
        }

        .btn-cancel {
            background: var(--gradient-danger);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-sm);
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
            opacity: 0.9;
            color: white;
        }

        .btn-cancel:disabled {
            background: var(--text-muted);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
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
                    <a class="nav-link active" href="orders.php"><i class="fas fa-truck me-2"></i>Orders</a>
                    <a class="nav-link" href="reports.php"><i class="fas fa-chart-bar me-2"></i>Reports</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Orders Management</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOrderModal">
                        <i class="fas fa-plus me-2"></i>New Order
                    </button>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card" style="height: 100%;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6>Total Orders</h6>
                                    <h3><?= $stats['total_orders'] ?></h3>
                                </div>
                                <i class="fas fa-shopping-cart icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card" style="height: 100%;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6>Pending Orders</h6>
                                    <h3><?= $stats['pending_orders'] ?></h3>
                                </div>
                                <i class="fas fa-clock icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card" style="height: 100%;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6>Delivered</h6>
                                    <h3><?= $stats['delivered_orders'] ?></h3>
                                </div>
                                <i class="fas fa-check-circle icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card" style="height: 100%;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6>Cancelled</h6>
                                    <h3><?= $stats['cancelled_orders'] ?></h3>
                                </div>
                                <i class="fas fa-times-circle icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Today's Appointments Card -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="stat-card" style="height: 100%;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6>Today's Appointments</h6>
                                    <h3>0</h3> <!-- Placeholder for actual appointments count -->
                                </div>
                                <i class="fas fa-calendar-check icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4" style="max-width: 100%;">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <select class="form-select filter-dropdown" id="statusFilter">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="processing">Processing</option>
                                    <option value="shipped">Shipped</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select filter-dropdown" id="supplierFilter">
                                    <option value="">All Suppliers</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?= htmlspecialchars($supplier['name']) ?>">
                                            <?= htmlspecialchars($supplier['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" class="form-control filter-dropdown" id="dateFilter">
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-secondary w-100" onclick="resetFilters()">
                                    <i class="fas fa-redo me-2"></i>Reset Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Supplier</th>
                                        <th>Medicine</th>
                                        <th>Quantity</th>
                                        <th>Order Date</th>
                                        <th>Status</th>
                                        <th>Tracking</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <tr data-order-id="<?= $order['id'] ?>">
                                        <td>#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?= htmlspecialchars($order['supplier_name']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <i class="fas fa-pills me-1"></i>
                                                <?= htmlspecialchars($order['medicine_name']) ?>
                                            </span>
                                        </td>
                                        <td><?= $order['quantity'] ?></td>
                                        <td><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                                        <td>
                                            <?php
                                            $statusClass = 'warning';
                                            switch($order['status']) {
                                                case 'delivered':
                                                    $statusClass = 'success';
                                                    break;
                                                case 'processing':
                                                    $statusClass = 'info';
                                                    break;
                                                case 'shipped':
                                                    $statusClass = 'info';
                                                    break;
                                                case 'cancelled':
                                                    $statusClass = 'danger';
                                                    break;
                                            }
                                            ?>
                                            <span class="status-text <?= $statusClass ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewTracking(<?= $order['id'] ?>)">
                                                <i class="fas fa-truck me-1"></i>Track
                                            </button>
                                        </td>
                                        <td>
                                            <button class="btn btn-cancel btn-sm" 
                                                    onclick="cancelOrder(<?= $order['id'] ?>)"
                                                    <?= $order['status'] === 'cancelled' || $order['status'] === 'delivered' ? 'disabled' : '' ?>>
                                                <i class="fas fa-ban me-1"></i>Cancel Order
                                            </button>
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

    <!-- Add Order Modal -->
    <div class="modal fade" id="addOrderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>New Order
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addOrderForm" method="POST">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Supplier</label>
                            <select class="form-select" name="supplier_name" required>
                                <option value="">Select Supplier</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?= htmlspecialchars($supplier['name']) ?>">
                                        <?= htmlspecialchars($supplier['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Medicine</label>
                            <select class="form-select" name="medicine_name" required>
                                <option value="">Select Medicine</option>
                                <?php foreach ($medicines as $medicine): ?>
                                    <option value="<?= htmlspecialchars($medicine['medicine_name']) ?>">
                                        <?= htmlspecialchars($medicine['medicine_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" name="quantity" required min="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Order Date</label>
                            <input type="date" class="form-control" name="order_date" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i>Place Order
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tracking Modal -->
    <div class="modal fade" id="trackingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-truck me-2"></i>Order Tracking
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="tracking-info">
                        <div class="tracking-step completed">
                            <i class="fas fa-check"></i>
                            <div>
                                <h6 class="mb-0">Order Placed</h6>
                                <small class="text-muted">Order has been placed successfully</small>
                            </div>
                        </div>
                        <div class="tracking-step active">
                            <i class="fas fa-box"></i>
                            <div>
                                <h6 class="mb-0">Processing</h6>
                                <small class="text-muted">Order is being processed</small>
                            </div>
                        </div>
                        <div class="tracking-step">
                            <i class="fas fa-truck"></i>
                            <div>
                                <h6 class="mb-0">Shipped</h6>
                                <small class="text-muted">Order is on the way</small>
                            </div>
                        </div>
                        <div class="tracking-step">
                            <i class="fas fa-home"></i>
                            <div>
                                <h6 class="mb-0">Delivered</h6>
                                <small class="text-muted">Order has been delivered</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Remove search functionality
        document.getElementById('statusFilter').addEventListener('change', applyFilters);
        document.getElementById('supplierFilter').addEventListener('change', applyFilters);
        document.getElementById('dateFilter').addEventListener('change', applyFilters);

        function applyFilters() {
            const status = document.getElementById('statusFilter').value.toLowerCase();
            const supplier = document.getElementById('supplierFilter').value.toLowerCase();
            const date = document.getElementById('dateFilter').value;
            
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const rowStatus = row.querySelector('.status-text').textContent.toLowerCase();
                const rowSupplier = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const rowDate = row.querySelector('td:nth-child(5)').textContent;
                
                const statusMatch = !status || rowStatus === status;
                const supplierMatch = !supplier || rowSupplier === supplier;
                const dateMatch = !date || rowDate.includes(date);
                
                row.style.display = statusMatch && supplierMatch && dateMatch ? '' : 'none';
            });
        }

        function resetFilters() {
            document.getElementById('statusFilter').value = '';
            document.getElementById('supplierFilter').value = '';
            document.getElementById('dateFilter').value = '';
            applyFilters();
        }

        // Add Order Form Handler
        document.getElementById('addOrderForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            
            fetch('add_order.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addOrderModal'));
                    modal.hide();
                    showToast('success', data.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'An error occurred while placing the order');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-save me-2"></i>Place Order';
            });
        });

        function viewTracking(id) {
            // Implement tracking view
            new bootstrap.Modal(document.getElementById('trackingModal')).show();
        }

        function cancelOrder(id) {
            if (confirm('Are you sure you want to cancel this order?')) {
                const button = document.querySelector(`button[onclick="cancelOrder(${id})"]`);
                const row = document.querySelector(`tr[data-order-id="${id}"]`);
                
                if (button && row) {
                    button.disabled = true;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Cancelling...';

                    fetch('cancel_order.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'id=' + id
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the row from the table
                            row.remove();

                            // Update all counts from server data
                            if (data.counts) {
                                document.querySelector('.stat-card:nth-child(1) h3').textContent = data.counts.total_orders;
                                document.querySelector('.stat-card:nth-child(2) h3').textContent = data.counts.pending_orders;
                                document.querySelector('.stat-card:nth-child(3) h3').textContent = data.counts.delivered_orders;
                                document.querySelector('.stat-card:nth-child(4) h3').textContent = data.counts.cancelled_orders;
                            }

                            // Show success message
                            showToast('success', data.message);
                        } else {
                            showToast('error', data.message || 'Failed to cancel order');
                            button.disabled = false;
                            button.innerHTML = '<i class="fas fa-ban me-1"></i>Cancel Order';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('error', 'An error occurred while cancelling the order');
                        button.disabled = false;
                        button.innerHTML = '<i class="fas fa-ban me-1"></i>Cancel Order';
                    });
                }
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
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    </script>
</body>
</html> 