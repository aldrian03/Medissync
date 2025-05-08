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
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #fdf8e5;
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
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .login-box {
            width: 100%;
            padding: 30px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-box h2 {
            font-size: 26px;
            font-weight: bold;
            color: rgb(66, 121, 155);
            margin-bottom: 20px;
            text-align: center;
        }

        .login-box p {
            color: red;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: rgb(80, 156, 124);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: rgb(88, 197, 157);
        }

        .options {
            display: flex;
            justify-content: space-between;
            width: 100%;
            align-items: center;
            margin-top: 10px;
        }

        .options label {
            font-size: 14px;
            color: #007bff;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
        }

        .options input[type="checkbox"] {
            margin-right: 5px;
        }

        .options a {
            font-size: 14px;
            color: #007bff;
            text-decoration: none;
        }

        .options a:hover {
            text-decoration: underline;
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
            border-radius: 12px;
            object-fit: cover;
        }

        @media screen and (min-width: 600px) {
            .login-container {
                flex-direction: row;
            }

            .image-box {
                width: 50%;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>LOGIN</h2>

            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

            <form action="" method="POST">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" placeholder="Enter your email" required>

                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="********" required>

                <div class="options">
                    <label>
                        <input type="checkbox" name="remember">Remember me
                    </label>
                    <a href="#">Forgot password?</a>
                </div>

                <button type="submit">Sign in</button>
            </form>
            <p>Don't have an account? <a href="register.php">Sign up for free!</a></p>
        </div>
        <div class="image-box">
            <img src="assets/image.png" alt="Login Image">
        </div>
    </div>
</body>
</html>
