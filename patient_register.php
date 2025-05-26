<?php
session_start();
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $errors = [];

    // Validate name
    if (empty($name)) {
        $errors[] = "Name is required";
    } elseif (strlen($name) < 2) {
        $errors[] = "Name must be at least 2 characters long";
    }

    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {
        // Check if email already exists
        $query = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Email already exists";
        }
    }

    // Validate password
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }

    // Validate password confirmation
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert new patient
        $query = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('sss', $name, $email, $hashed_password);

        if ($stmt->execute()) {
            // Set success message in session
            $_SESSION['register_success'] = "Registration successful! Please login to continue.";
            
            // Redirect to login page
            header("Location: patient_login.php");
            exit();
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration - Medisync</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: var(--gradient-primary);
            display: flex;
            justify-content: center;
            align-items: center; 
            min-height: 100vh;
            flex-direction: column;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(67, 155, 123, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(32, 178, 170, 0.1) 0%, transparent 50%),
                url('data:image/svg+xml,<svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1" fill="rgba(255,255,255,0.05)"/></svg>');
            opacity: 0.5;
            z-index: 0;
        }

        .register-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            max-width: 1000px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            box-shadow: var(--hover-shadow);
            padding: 40px;
            margin: 20px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 1;
            animation: slideUp 0.5s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .register-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .register-box {
            width: 100%;
            padding: 40px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .register-box h2 {
            font-size: 32px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 30px;
            text-align: center;
            letter-spacing: -0.5px;
            position: relative;
            padding-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .register-box h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: var(--gradient-primary);
            border-radius: 2px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .error-list {
            list-style: none;
            padding: 0;
            margin: 0 0 20px 0;
        }

        .error-list li {
            color: var(--danger-color);
            font-size: 14px;
            margin: 5px 0;
            padding: 10px;
            background: rgba(239, 68, 68, 0.1);
            border-radius: 8px;
            border-left: 4px solid var(--danger-color);
            animation: shake 0.5s ease-in-out;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.1);
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .form-group {
            position: relative;
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.05);
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            box-sizing: border-box;
            font-size: 15px;
            transition: all 0.3s ease;
            background: var(--background-color);
            position: relative;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 155, 123, 0.1),
                        inset 0 2px 4px rgba(0, 0, 0, 0.05);
            background: #fff;
            transform: translateY(-2px);
        }

        .form-group i {
            position: absolute;
            right: 15px;
            top: 45px;
            color: var(--text-muted);
            transition: all 0.3s ease;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }

        input:focus + i {
            color: var(--primary-color);
            transform: scale(1.1);
        }

        button {
            width: 100%;
            padding: 14px;
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 10px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(67, 155, 123, 0.2);
        }

        button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                120deg,
                transparent,
                rgba(255, 255, 255, 0.3),
                transparent
            );
            transition: 0.5s;
        }

        button:hover::before {
            left: 100%;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(67, 155, 123, 0.3);
            opacity: 0.9;
        }

        .image-box {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 20px;
            position: relative;
        }

        .image-box::before {
            content: '';
            position: absolute;
            top: -20px;
            left: -20px;
            right: -20px;
            bottom: -20px;
            background: var(--gradient-secondary);
            border-radius: 30px;
            z-index: -1;
            opacity: 0.1;
            filter: blur(10px);
        }

        .image-box img {
            max-width: 100%;
            height: auto;
            border-radius: 16px;
            object-fit: cover;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            animation: float 6s ease-in-out infinite;
            border: 4px solid rgba(255, 255, 255, 0.1);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(1deg); }
        }

        .image-box img:hover {
            transform: scale(1.02) rotate(2deg);
            box-shadow: var(--hover-shadow);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: var(--text-color);
            font-size: 14px;
            position: relative;
            padding: 10px;
            border-radius: 8px;
            background: rgba(67, 155, 123, 0.05);
            transition: all 0.3s ease;
        }

        .login-link:hover {
            background: rgba(67, 155, 123, 0.1);
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            position: relative;
        }

        .login-link a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: width 0.3s ease;
        }

        .login-link a:hover::after {
            width: 100%;
        }

        .g-recaptcha {
            display: none;
        }

        .role-switch {
            text-align: center;
            margin-top: 20px;
            color: var(--text-color);
            font-size: 14px;
            padding: 10px;
            border-radius: 8px;
            background: rgba(67, 155, 123, 0.1);
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .role-switch:hover {
            background: rgba(67, 155, 123, 0.15);
            transform: translateY(-2px);
        }

        .role-switch a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            position: relative;
        }

        .role-switch a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: width 0.3s ease;
        }

        .role-switch a:hover::after {
            width: 100%;
        }

        @media screen and (min-width: 768px) {
            .register-container {
                flex-direction: row;
                gap: 40px;
            }

            .image-box {
                width: 50%;
            }

            .register-box {
                width: 50%;
            }
        }

        @media screen and (max-width: 767px) {
            .register-container {
                padding: 20px;
                margin: 10px;
            }

            .register-box {
                padding: 20px;
            }

            .image-box {
                display: none;
            }
        }

        /* Password strength indicator */
        .password-strength {
            height: 4px;
            margin-top: -15px;
            margin-bottom: 15px;
            border-radius: 2px;
            transition: all 0.3s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .strength-weak { 
            background: var(--danger-color); 
            width: 33.33%; 
            box-shadow: 0 0 8px rgba(239, 68, 68, 0.3);
        }
        .strength-medium { 
            background: var(--warning-color); 
            width: 66.66%; 
            box-shadow: 0 0 8px rgba(255, 209, 102, 0.3);
        }
        .strength-strong { 
            background: var(--success-color); 
            width: 100%; 
            box-shadow: 0 0 8px rgba(32, 178, 170, 0.3);
        }

        /* Loading animation for button */
        @keyframes button-loading {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        button.loading {
            position: relative;
            color: transparent;
        }

        button.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin: -10px 0 0 -10px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: button-loading 0.8s linear infinite;
        }
    </style>
    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthIndicator = document.createElement('div');
            strengthIndicator.className = 'password-strength';
            
            // Remove existing indicator
            const existingIndicator = document.querySelector('.password-strength');
            if (existingIndicator) {
                existingIndicator.remove();
            }

            // Calculate password strength
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/) && password.match(/[^a-zA-Z\d]/)) strength++;

            // Add appropriate class
            if (strength === 1) strengthIndicator.classList.add('strength-weak');
            else if (strength === 2) strengthIndicator.classList.add('strength-medium');
            else if (strength === 3) strengthIndicator.classList.add('strength-strong');

            // Insert after password input
            this.parentNode.insertBefore(strengthIndicator, this.nextSibling);
        });

        // Form submission loading state
        document.querySelector('form').addEventListener('submit', function() {
            const button = this.querySelector('button[type="submit"]');
            button.classList.add('loading');
        });
    </script>
</head>
<body>
    <div class="register-container">
        <div class="register-box">
            <h2>Patient Registration</h2>

            <?php if (!empty($errors)): ?>
                <ul class="error-list">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" name="name" id="name" placeholder="Enter your full name" required 
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    <i class="fas fa-user"></i>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" placeholder="Enter your email" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    <i class="fas fa-envelope"></i>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" placeholder="Enter your password" required
                           minlength="8">
                    <i class="fas fa-lock"></i>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" 
                           placeholder="Confirm your password" required minlength="8">
                    <i class="fas fa-lock"></i>
                </div>

                <button type="submit">Create Account</button>
            </form>
            <p class="login-link">Already have an account? <a href="patient_login.php">Sign in</a></p>
        </div>
    </div>
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</body>
</html> 