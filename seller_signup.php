<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    try {
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, phone, location, user_type, verification_status) VALUES (?, ?, ?, ?, ?, 'seller', 'pending')");
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        if (!$username || !$email || !$password || !$location) {
            throw new Exception("Invalid input data.");
        }

        $stmt->bind_param("sssss", $username, $password, $email, $phone, $location);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Registration successful! Please submit Aadhaar verification.";
            header("Location: verify_seller.php");
            exit();
        } else {
            throw new Exception("Registration failed: " . $conn->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Registration - CarBazaar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .auth-container {
            display: flex;
            min-height: 100vh;
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1541348263662-e068671d7078');
            background-size: cover;
            background-position: center;
            align-items: center;
            justify-content: center;
            animation: fadeIn 1s ease;
        }

        .auth-box {
            display: flex;
            width: 90%;
            max-width: 900px;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .auth-image {
            flex: 1;
            background: url('https://images.unsplash.com/photo-1541348263662-e068671d7078');
            background-size: cover;
            background-position: center;
        }

        .auth-form {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .auth-form h2 {
            font-size: 28px;
            margin-bottom: 20px;
            color: var(--dark);
            text-align: center;
        }

        .auth-form .form-group {
            margin-bottom: 20px;
        }

        .auth-footer {
            text-align: center;
            margin-top: 20px;
        }

        .auth-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .auth-box {
                flex-direction: column;
            }
            .auth-image {
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-image"></div>
            <div class="auth-form">
                <h2>Register as Seller</h2>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="username"><i class="fas fa-user"></i> Username</label>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Choose a username" required>
                    </div>
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="text" id="phone" name="phone" class="form-control" placeholder="Enter your phone number">
                    </div>
                    <div class="form-group">
                        <label for="location"><i class="fas fa-map-marker-alt"></i> Location</label>
                        <input type="text" id="location" name="location" class="form-control" placeholder="e.g. Mumbai" required>
                    </div>
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock"></i> Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Create a password" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="register" class="btn btn-primary" style="width: 100%;"><i class="fas fa-user-plus"></i> Register</button>
                    </div>
                    <div class="auth-footer">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
                        <p>Want to buy cars? <a href="buyer_signup.php">Register as Buyer</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
