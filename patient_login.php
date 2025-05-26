<?php
session_start();
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email']; 
    $password = $_POST['password']; 
    $remember = isset($_POST['remember']);
    $recaptcha_response = $_POST['g-recaptcha-response'];

    // Verify reCAPTCHA
    $recaptcha_secret = "6LfUBBErAAAAAF611yhBH8HuxIOj_P9tjBOdQfb0";
    $verify_response = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
    $response_data = json_decode($verify_response);

    if (!$response_data->success) {
        $error = "Please complete the reCAPTCHA verification.";
    } else if (!empty($email) && !empty($password)) {
        // Query to check if the user exists in the database
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // Verify the password
            if (password_verify($password, $user['password'])) {
                $_SESSION['user'] = $user['email'];
                $_SESSION['name'] = $user['name'];

                if ($remember) {
                    setcookie('email', $email, time() + (86400 * 30), "/"); // 30 days
                }

                header("Location: patient_dashboard.php");
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "No account found with that email.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Login - Medisync</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
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

        .login-container {
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
            gap: 40px;
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

        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .login-box {
            width: 50%;
            padding: 40px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .image-box {
            width: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
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

        .login-box h2 {
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

        .login-box h2::after {
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

        .error-message {
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

        .success-message {
            color: var(--success-color);
            font-size: 14px;
            margin: 5px 0;
            padding: 10px;
            background: rgba(32, 178, 170, 0.1);
            border-radius: 8px;
            border-left: 4px solid var(--success-color);
            animation: slideIn 0.5s ease-out;
            box-shadow: 0 2px 4px rgba(32, 178, 170, 0.1);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        .options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 15px 0;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--primary-color);
        }

        .forgot-password {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .forgot-password:hover {
            color: var(--primary-dark);
            text-decoration: underline;
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

        .register-link {
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

        .register-link:hover {
            background: rgba(67, 155, 123, 0.1);
        }

        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            position: relative;
        }

        .register-link a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: width 0.3s ease;
        }

        .register-link a:hover::after {
            width: 100%;
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

        @media screen and (max-width: 767px) {
            .login-container {
                padding: 20px;
                margin: 10px;
                flex-direction: column;
            }

            .login-box {
                width: 100%;
                padding: 20px;
            }

            .image-box {
                display: none;
            }
        }

        .g-recaptcha {
            margin: 20px 0;
            display: flex;
            justify-content: center;
            transform: scale(0.9);
            transform-origin: center;
        }

        .g-recaptcha:hover {
            transform: scale(0.95);
        }
    </style>
    <script>
        // Form submission loading state
        document.querySelector('form').addEventListener('submit', function() {
            const button = this.querySelector('button[type="submit"]');
            button.classList.add('loading');
        });
    </script>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>Patient Login</h2>

            <?php if (isset($_SESSION['register_success'])): ?>
                <div class="success-message">
                    <?php 
                    echo htmlspecialchars($_SESSION['register_success']);
                    unset($_SESSION['register_success']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" placeholder="Enter your email" required
                           value="<?php echo isset($_COOKIE['email']) ? htmlspecialchars($_COOKIE['email']) : ''; ?>">
                    <i class="fas fa-envelope"></i>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" placeholder="Enter your password" required>
                    <i class="fas fa-lock"></i>
                </div>

                <div class="options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" <?php echo isset($_COOKIE['email']) ? 'checked' : ''; ?>>
                        Remember me
                    </label>
                    <a href="#" class="forgot-password">Forgot password?</a>
                </div>

                <div class="g-recaptcha" data-sitekey="6LfUBBErAAAAAArWjou2gh_5OiZ3u0cqLFPWDryr"></div>

                <button type="submit">Sign in</button>
            </form>
            <p class="register-link">Don't have an account? <a href="patient_register.php">Sign up for free!</a></p>
        </div>
        <div class="image-box">
            <img src="assets/image.png" alt="Login Image">
        </div>
    </div>
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</body>
</html> 