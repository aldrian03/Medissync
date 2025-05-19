<?php
session_start();
include 'database.php'; // Ensure the correct path is provided

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email']; 
    $password = $_POST['password']; 
    $remember = isset($_POST['remember']);

    if (!empty($email) && !empty($password)) {
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

                if ($remember) {
                    setcookie('email', $email, time() + (86400 * 30), "/"); // 30 days
                }

                header("Location: dashboard.php");
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
    <title>Login</title>
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

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: var(--gradient-primary);
            display: flex;
            justify-content: center;
            align-items: center; 
            height: 100vh;
            flex-direction: column;
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
        }

        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .login-box {
            width: 100%;
            padding: 40px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-box h2 {
            font-size: 32px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 30px;
            text-align: center;
            letter-spacing: -0.5px;
        }

        .login-box p.error {
            color: var(--danger-color);
            font-size: 14px;
            margin: 5px 0;
            padding: 10px;
            background: rgba(239, 68, 68, 0.1);
            border-radius: 8px;
            border-left: 4px solid var(--danger-color);
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
            font-weight: 500;
            font-size: 14px;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            margin: 8px 0 20px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            box-sizing: border-box;
            font-size: 15px;
            transition: all 0.3s ease;
            background: var(--background-color);
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 155, 123, 0.1);
            background: #fff;
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
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 155, 123, 0.2);
            opacity: 0.9;
        }

        .options {
            display: flex;
            justify-content: space-between;
            width: 100%;
            align-items: center;
            margin: 15px 0;
        }

        .options label {
            font-size: 14px;
            color: var(--text-color);
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
            margin: 0;
        }

        .options input[type="checkbox"] {
            width: auto;
            margin: 0 8px 0 0;
            accent-color: var(--primary-color);
        }

        .options a {
            font-size: 14px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .options a:hover {
            color: var(--primary-dark);
        }

        .image-box {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 20px;
        }

        .image-box img {
            max-width: 100%;
            height: auto;
            border-radius: 16px;
            object-fit: cover;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
        }

        .image-box img:hover {
            transform: scale(1.02);
            box-shadow: var(--hover-shadow);
        }

        .signup-link {
            text-align: center;
            margin-top: 20px;
            color: var(--text-color);
            font-size: 14px;
        }

        .signup-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .signup-link a:hover {
            color: var(--primary-dark);
        }

        @media screen and (min-width: 768px) {
            .login-container {
                flex-direction: row;
                gap: 40px;
            }

            .image-box {
                width: 50%;
            }

            .login-box {
                width: 50%;
            }
        }

        @media screen and (max-width: 767px) {
            .login-container {
                padding: 20px;
                margin: 10px;
            }

            .login-box {
                padding: 20px;
            }

            .image-box {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>Medisync</h2>

            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

            <form action="" method="POST">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" placeholder="Enter your email" required>

                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="Enter your password" required>

                <div class="options">
                    <label>
                        <input type="checkbox" name="remember">Remember me
                    </label>
                    <a href="#">Forgot password?</a>
                </div>

                <button type="submit">Sign in</button>
            </form>
            <p class="signup-link">Don't have an account? <a href="register.php">Sign up for free!</a></p>
        </div>
        <div class="image-box">
            <img src="assets/image.png" alt="Login Image">
        </div>
    </div>
</body>
</html>
