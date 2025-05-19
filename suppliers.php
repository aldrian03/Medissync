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

        .supplier-logo {
            width: 50px;
            height: 50px;
            border-radius: var(--border-radius-sm);
            object-fit: cover;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-sm);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-badge.active {
            background: var(--gradient-success);
            color: white;
        }

        .status-badge.inactive {
            background: var(--gradient-danger);
            color: white;
        }

        .status-badge.pending {
            background: var(--gradient-warning);
            color: white;
        }

        .contact-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .contact-info i {
            color: var(--primary-color);
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