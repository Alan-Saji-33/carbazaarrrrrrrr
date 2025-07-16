<?php
require 'config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CarBazaar</title>
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
            text-align: center;
        }

        .auth-form h2 {
            font-size: 28px;
            margin-bottom: 20px;
            color: var(--dark);
        }

        .auth-form .btn {
            margin: 10px 0;
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
                <h2>Join CarBazaar</h2>
                <p>Are you looking to buy or sell cars?</p>
                <a href="buyer_signup.php" class="btn btn-primary"><i class="fas fa-shopping-cart"></i> Register as Buyer</a>
                <a href="seller_signup.php" class="btn btn-outline"><i class="fas fa-store"></i> Register as Seller</a>
                <div class="auth-footer">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
