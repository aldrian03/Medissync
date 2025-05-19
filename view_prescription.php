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

// Get prescription ID from URL
if (!isset($_GET['id'])) {
    header("Location: prescriptions.php");
    exit();
}

$id = (int)$_GET['id'];

// Handle form submission for updating prescription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        $patient_name = htmlspecialchars($_POST['patient_name']);
        $medicine = htmlspecialchars($_POST['medicine']);
        $dosage = htmlspecialchars($_POST['dosage']);
        
        // Get current status before update
        $stmt = $conn->prepare("SELECT status FROM prescriptions WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_status = $result->fetch_assoc()['status'];
        
        // Update prescription while maintaining the current status
        $stmt = $conn->prepare("UPDATE prescriptions SET patient_name = ?, medicine = ?, dosage = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $patient_name, $medicine, $dosage, $current_status, $id);
        
        if ($stmt->execute()) {
            header("Location: prescriptions.php?success=1");
            exit();
        } else {
            $error_message = "Failed to update prescription.";
        }
        $stmt->close();
    }
}

// Fetch prescription details
$stmt = $conn->prepare("SELECT * FROM prescriptions WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$prescription = $result->fetch_assoc();

if (!$prescription) {
    header("Location: prescriptions.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>View Prescription - MediSync</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #439b7b;
            --primary-dark: #020c1b;
            --primary-light: #112240;
            --secondary-color: #20b2aa;
            --accent-color: #64ffda;
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
            margin-left: 256px;
            padding: var(--spacing-lg);
            transition: all 0.3s ease;
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

        .prescription-header {
            background: var(--gradient-primary);
            color: white;
            padding: var(--spacing-lg);
            border-radius: var(--border-radius-md) var(--border-radius-md) 0 0;
            margin-bottom: var(--spacing-md);
        }

        .prescription-body {
            padding: var(--spacing-lg);
        }

        .form-label {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: var(--spacing-xs);
        }

        .form-control, .form-select {
            border-radius: var(--border-radius-sm);
            border: 2px solid var(--border-color);
            padding: var(--spacing-sm);
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: var(--background-color);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 155, 123, 0.1);
            background: #fff;
        }

        .btn {
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--border-radius-sm);
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
            opacity: 0.9;
        }

        .btn-secondary {
            background: var(--text-muted);
            border: none;
            color: white;
        }

        .btn-secondary:hover {
            background: var(--text-color);
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        .alert {
            border-radius: var(--border-radius-sm);
            padding: var(--spacing-sm) var(--spacing-md);
            margin-bottom: var(--spacing-md);
            border: none;
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        @media print {
            .sidebar, .no-print {
                display: none !important;
            }
            .main-content {
                margin: 0 !important;
                padding: 0 !important;
            }
            .card {
                box-shadow: none !important;
            }
            .prescription-header {
                background: none !important;
                color: black !important;
                border-bottom: 2px solid black;
            }
        }

        @media (max-width: 767.98px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
                padding: var(--spacing-md);
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
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-home me-2"></i>Dashboard
                    </a>
                    <a class="nav-link" href="inventory.php">
                        <i class="fas fa-pills me-2"></i>Inventory
                    </a>
                    <a class="nav-link active" href="prescriptions.php">
                        <i class="fas fa-prescription me-2"></i>Prescriptions
                    </a>
                    <a class="nav-link" href="orders.php">
                        <i class="fas fa-truck me-2"></i>Orders
                    </a>
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-chart-bar me-2"></i>Reports
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                    <h2>Prescription Details</h2>
                    <div>
                        <a href="prescriptions.php" class="btn btn-secondary me-2">
                            <i class="fas fa-arrow-left me-2"></i>Back to List
                        </a>
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print Prescription
                        </button>
                    </div>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?= $error_message ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="prescription-header">
                        <div class="row">
                            <div class="col-md-6">
                                <h3>MediSync</h3>
                                <p class="mb-0">Medical Prescription</p>
                            </div>
                            <div class="col-md-6 text-end">
                                <p class="mb-0">Date: <?= date('M d, Y', strtotime($prescription['created_at'])) ?></p>
                                <p class="mb-0">Prescription #: <?= str_pad($prescription['id'], 6, '0', STR_PAD_LEFT) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="prescription-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Patient Name</label>
                                    <input type="text" class="form-control" name="patient_name" 
                                           value="<?= htmlspecialchars($prescription['patient_name']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Medicine</label>
                                    <select class="form-select" name="medicine" required>
                                        <?php
                                        $query = "SELECT medicine_name FROM inventory WHERE quantity > 0 ORDER BY medicine_name";
                                        $result = $conn->query($query);
                                        while ($row = $result->fetch_assoc()) {
                                            $selected = ($row['medicine_name'] === $prescription['medicine']) ? 'selected' : '';
                                            echo "<option value='" . htmlspecialchars($row['medicine_name']) . "' $selected>" . 
                                                 htmlspecialchars($row['medicine_name']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Dosage</label>
                                    <input type="text" class="form-control" name="dosage" 
                                           value="<?= htmlspecialchars($prescription['dosage']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Status</label>
                                    <input type="text" class="form-control" 
                                           value="<?= ucfirst($prescription['status'] ?? 'pending') ?>" readonly>
                                </div>
                            </div>
                            <div class="text-end no-print">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 