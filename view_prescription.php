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
        
        $stmt = $conn->prepare("UPDATE prescriptions SET patient_name = ?, medicine = ?, dosage = ? WHERE id = ?");
        $stmt->bind_param("sssi", $patient_name, $medicine, $dosage, $id);
        
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

        .prescription-header {
            background: var(--primary-color);
            color: white;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
            margin-bottom: 2rem;
        }

        .prescription-body {
            padding: 2rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--primary-color);
        }

        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.8rem;
            border: 1px solid #ddd;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
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
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cog me-2"></i>Settings
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