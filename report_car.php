<?php
require 'config.php';
require 'functions.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login to report a car.";
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid car ID.";
    header("Location: index.php");
    exit();
}

$car_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$stmt = $conn->prepare("SELECT brand, model FROM cars WHERE id = ?");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$car = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$car) {
    $_SESSION['error'] = "Car not found.";
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['report_car'])) {
    try {
        $user_id = $_SESSION['user_id'];
        $reason = filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_STRING);

        if (empty($reason)) {
            throw new Exception("Please provide a reason for reporting.");
        }

        $stmt = $conn->prepare("INSERT INTO reports (user_id, car_id, reason, status) VALUES (?, ?, ?, 'pending')");
        $stmt->bind_param("iis", $user_id, $car_id, $reason);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Car reported successfully! An admin will review your report.";
            header("Location: car_details.php?id=$car_id");
            exit();
        } else {
            throw new Exception("Error reporting car: " . $conn->error);
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
    <title>Report Car - CarBazaar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .report-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin: 40px auto;
            max-width: 600px;
            animation: slideInUp 0.5s ease;
        }

        .report-container h2 {
            font-size: 28px;
            margin-bottom: 20px;
            color: var(--dark);
            text-align: center;
        }

        .report-container .form-group {
            margin-bottom: 20px;
        }

        .report-container textarea {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid var(--light-gray);
            resize: vertical;
        }

        @media (max-width: 576px) {
            .report-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-container">
            <a href="index.php" class="logo">
                <div class="logo-icon"><i class="fas fa-car"></i></div>
                <div class="logo-text">Car<span>Bazaar</span></div>
            </a>
            <nav>
                <ul>
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="index.php#cars"><i class="fas fa-car"></i> Cars</a></li>
                    <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                    <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin'): ?>
                        <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="user-actions">
                <div class="user-greeting">Welcome, <span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
                <a href="index.php?logout" class="btn btn-outline"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="report-container">
            <h2>Report Car: <?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h2>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="reason"><i class="fas fa-flag"></i> Reason for Reporting</label>
                    <textarea id="reason" name="reason" class="form-control" rows="5" placeholder="Describe the issue with this listing" required></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" name="report_car" class="btn btn-danger" style="width: 100%;"><i class="fas fa-flag"></i> Submit Report</button>
                </div>
            </form>
            <div class="auth-footer">
                <p><a href="car_details.php?id=<?php echo $car_id; ?>">Back to Car Details</a></p>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="footer-bottom">
                <p>© <?php echo date('Y'); ?> CarBazaar. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
