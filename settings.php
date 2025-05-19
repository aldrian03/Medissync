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

// Get user settings
$user_id = $_SESSION['user'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Redirect if user not found
if (!$user) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Settings - MediSync</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #3b82f6;
            --secondary-color: #0ea5e9;
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

        .settings-section {
            background: white;
            border-radius: var(--border-radius-md);
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-md);
            box-shadow: var(--card-shadow);
        }

        .settings-section h4 {
            color: var(--text-color);
            font-weight: 600;
            margin-bottom: var(--spacing-md);
            padding-bottom: var(--spacing-sm);
            border-bottom: 1px solid var(--border-color);
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

        .form-label {
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: var(--spacing-xs);
        }

        .form-text {
            color: var(--text-muted);
            font-size: 0.875rem;
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

        .btn-secondary {
            background: var(--gradient-secondary);
            border: none;
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--border-radius-sm);
            transition: all 0.3s ease;
            font-weight: 500;
            letter-spacing: 0.3px;
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
            opacity: 0.9;
            color: white;
        }

        .alert {
            border-radius: var(--border-radius-sm);
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-md);
            border: none;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .alert-info {
            background: rgba(14, 165, 233, 0.1);
            color: var(--secondary-color);
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--border-color);
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background: var(--gradient-primary);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .color-picker {
            width: 100%;
            height: 40px;
            padding: 0;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-sm);
            cursor: pointer;
        }

        .color-picker::-webkit-color-swatch-wrapper {
            padding: 0;
        }

        .color-picker::-webkit-color-swatch {
            border: none;
            border-radius: var(--border-radius-sm);
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
                    <a class="nav-link" href="dashboard.php">
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
                    <a class="nav-link active" href="settings.php">
                        <i class="fas fa-cog me-2"></i>Settings
                    </a>
                    <a class="nav-link text-danger mt-4" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <h2 class="mb-4">Settings</h2>

                <!-- Profile Settings -->
                <div class="card settings-section">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <div class="d-flex align-items-center">
                                <div class="settings-icon bg-primary text-white">
                                    <i class="fas fa-user"></i>
                                </div>
                                Profile Settings
                            </div>
                        </h5>
                        <form id="profileForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Role</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['role'] ?? '') ?>" readonly>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="card settings-section">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <div class="d-flex align-items-center">
                                <div class="settings-icon bg-warning text-white">
                                    <i class="fas fa-lock"></i>
                                </div>
                                Security Settings
                            </div>
                        </h5>
                        <form id="passwordForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" class="form-control" name="current_password">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="new_password">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" name="confirm_password">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key me-2"></i>Change Password
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Notification Settings -->
                <div class="card settings-section">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <div class="d-flex align-items-center">
                                <div class="settings-icon bg-success text-white">
                                    <i class="fas fa-bell"></i>
                                </div>
                                Notification Settings
                            </div>
                        </h5>
                        <form id="notificationForm">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                                    <label class="form-check-label" for="emailNotifications">Email Notifications</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="lowStockAlerts" checked>
                                    <label class="form-check-label" for="lowStockAlerts">Low Stock Alerts</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="orderUpdates" checked>
                                    <label class="form-check-label" for="orderUpdates">Order Status Updates</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Preferences
                            </button>
                        </form>
                    </div>
                </div>

                <!-- System Settings -->
                <div class="card settings-section">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <div class="d-flex align-items-center">
                                <div class="settings-icon bg-info text-white">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                System Settings
                            </div>
                        </h5>
                        <form id="systemForm">
                            <div class="mb-3">
                                <label class="form-label">Default Currency</label>
                                <select class="form-select" name="currency">
                                    <option value="USD">USD ($)</option>
                                    <option value="EUR">EUR (€)</option>
                                    <option value="GBP">GBP (£)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date Format</label>
                                <select class="form-select" name="date_format">
                                    <option value="Y-m-d">YYYY-MM-DD</option>
                                    <option value="d-m-Y">DD-MM-YYYY</option>
                                    <option value="m-d-Y">MM-DD-YYYY</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Time Zone</label>
                                <select class="form-select" name="timezone">
                                    <option value="UTC">UTC</option>
                                    <option value="EST">Eastern Time</option>
                                    <option value="PST">Pacific Time</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Profile Form Handler
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
            
            fetch('update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', data.message);
                } else {
                    showToast('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'An error occurred while updating profile');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-save me-2"></i>Save Changes';
            });
        });

        // Password Form Handler
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
            
            fetch('update_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', data.message);
                    this.reset();
                } else {
                    showToast('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'An error occurred while updating password');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-key me-2"></i>Change Password';
            });
        });

        // Notification Form Handler
        document.getElementById('notificationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
            
            fetch('update_notifications.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', data.message);
                } else {
                    showToast('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'An error occurred while updating notification settings');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-save me-2"></i>Save Preferences';
            });
        });

        // System Settings Form Handler
        document.getElementById('systemForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
            
            fetch('update_system_settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', data.message);
                } else {
                    showToast('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'An error occurred while updating system settings');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-save me-2"></i>Save Settings';
            });
        });

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