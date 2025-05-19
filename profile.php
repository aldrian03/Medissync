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

        .profile-header {
            background: var(--gradient-primary);
            padding: var(--spacing-lg);
            color: white;
            text-align: center;
            position: relative;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: var(--card-shadow);
            margin-bottom: var(--spacing-md);
            object-fit: cover;
        }

        .profile-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: var(--spacing-xs);
        }

        .profile-role {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: var(--spacing-md);
        }

        .profile-stats {
            display: flex;
            justify-content: center;
            gap: var(--spacing-lg);
            margin-top: var(--spacing-md);
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: var(--spacing-xs);
        }

        .stat-label {
            font-size: 0.875rem;
            opacity: 0.9;
        }

        .profile-section {
            padding: var(--spacing-md);
            border-bottom: 1px solid var(--border-color);
        }

        .profile-section:last-child {
            border-bottom: none;
        }

        .section-title {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: var(--spacing-md);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .section-title i {
            color: var(--primary-color);
        }

        .info-item {
            display: flex;
            margin-bottom: var(--spacing-sm);
        }

        .info-label {
            width: 150px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .info-value {
            flex: 1;
            color: var(--text-color);
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

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: var(--spacing-sm);
            padding: var(--spacing-sm) 0;
            border-bottom: 1px solid var(--border-color);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gradient-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: var(--spacing-xs);
        }

        .activity-time {
            font-size: 0.875rem;
            color: var(--text-muted);
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
            .profile-stats {
                flex-direction: column;
                gap: var(--spacing-md);
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