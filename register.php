<?php
session_start();
include 'database.php'; // Ensure the correct path is provided

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($email) && !empty($password) && !empty($confirm_password)) {
        if ($password === $confirm_password) {
            // Check if email already exists
            $query = "SELECT * FROM users WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert into database
                $query = "INSERT INTO users (email, password) VALUES (?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('ss', $email, $hashed_password);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Registration successful! You can now log in.";
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Something went wrong. Please try again.";
                }
            } else {
                $error = "Email already exists. Please choose another one.";
            }
        } else {
            $error = "Passwords do not match.";
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
    <title>Register</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fdf8e5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 0;
            flex-direction: column;
        }

        .register-container {
            background: #fff;
            width: 100%;
            max-width: 1000px;
            border-radius: 20px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 20px;
        }

        .register-box {
            width: 100%;
            padding: 30px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .register-box h2 {
            font-size: 26px;
            font-weight: bold;
            color: rgb(66, 121, 155);
            margin-bottom: 20px;
            text-align: center;
        }

        .register-box p {
            color: red;
            font-size: 14px;
            text-align: center;
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
            .register-container {
                flex-direction: row;
            }

            .image-box {
                width: 50%;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-box">
            <h2>REGISTER</h2>

            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

            <form action="" method="POST">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" placeholder="Enter your email" required>

                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="********" required>

                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="********" required>

                <button type="submit">Sign up</button>
            </form>
            <p>Already have an account? <a href="index.php">Log in</a></p>
        </div>
        <div class="image-box">
            <img src="assets/image.png" alt="Register Image">
        </div>
    </div>
</body>
</html>
