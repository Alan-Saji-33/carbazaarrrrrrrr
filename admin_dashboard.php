<?php
require 'config.php';
require 'functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['error'] = "Access denied. Admins only.";
    header("Location: login.php");
    exit();
}

// Fetch stats
$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_cars = $conn->query("SELECT COUNT(*) FROM cars")->fetch_row()[0];
$pending_verifications = $conn->query("SELECT COUNT(*) FROM verifications WHERE status = 'pending'")->fetch_row()[0];
$pending_reports = $conn->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetch_row()[0];

// Fetch recent users
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Fetch recent cars
$cars = $conn->query("SELECT cars.*, users.username AS seller_name 
                      FROM cars 
                      JOIN users ON cars.seller_id = users.id 
                      ORDER BY cars.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Fetch pending reports
$reports = $conn->query("SELECT reports.*, cars.brand, cars.model, users.username AS reporter_name 
                         FROM reports 
                         JOIN cars ON reports.car_id = cars.id 
                         JOIN users ON reports.user_id = users.id 
                         WHERE reports.status = 'pending'")->fetch_all(MYSQLI_ASSOC);

// Handle report actions
if (isset($_POST['resolve_report']) || isset($_POST['dismiss_report'])) {
    $report_id = filter_input(INPUT_POST, 'report_id', FILTER_SANITIZE_NUMBER_INT);
    $status = isset($_POST['resolve_report']) ? 'resolved' : 'dismissed';
    $stmt = $conn->prepare("UPDATE reports SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $report_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Report $status successfully!";
    } else {
        $_SESSION['error'] = "Error updating report: " . $conn->error;
    }
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

// Handle car deletion
if (isset($_POST['delete_car'])) {
    $car_id = filter_input(INPUT_POST, 'car_id', FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("DELETE FROM cars WHERE id = ?");
    $stmt->bind_param("i", $car_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Car deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting car: " . $conn->error;
    }
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CarBazaar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin: 40px auto;
            max-width: 1200px;
            animation: slideInUp 0.5s ease;
        }

        .dashboard-container h2 {
            font-size: 28px;
            margin-bottom: 20px;
            color: var(--dark);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--light);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: var(--dark);
        }

        .stat-card p {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
        }

        .table-container {
            overflow-x: auto;
            margin-bottom: 40px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--light);
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--light-gray);
        }

        th {
            background: var(--primary);
            color: white;
        }

        tr:hover {
            background: rgba(0,123,255,0.05);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
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
                    <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                </ul>
            </nav>
            <div class="user-actions">
                <div class="user-greeting">Welcome, <span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
                <a href="index.php?logout" class="btn btn-outline"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="dashboard-container">
            <h2>Admin Dashboard</h2>
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <p><?php echo $total_users; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Cars</h3>
                    <p><?php echo $total_cars; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Pending Verifications</h3>
                    <p><?php echo $pending_verifications; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Pending Reports</h3>
                    <p><?php echo $pending_reports; ?></p>
                </div>
            </div>
            <h3>Recent Users</h3>
            <div class="table-container">
                <table>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>User Type</th>
                        <th>Verification Status</th>
                        <th>Created At</th>
                    </tr>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['user_type']); ?></td>
                            <td><?php echo $user['is_verified'] ? 'Verified' : ($user['verification_status'] ?? 'Not Requested'); ?></td>
                            <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <h3>Recent Cars</h3>
            <div class="table-container">
                <table>
                    <tr>
                        <th>Brand</th>
                        <th>Model</th>
                        <th>Seller</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($cars as $car): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($car['brand']); ?></td>
                            <td><?php echo htmlspecialchars($car['model']); ?></td>
                            <td><?php echo htmlspecialchars($car['seller_name']); ?></td>
                            <td>₹<?php echo formatIndianNumber($car['price']); ?></td>
                            <td><?php echo $car['is_sold'] ? 'Sold' : 'Available'; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="car_details.php?id=<?php echo $car['id']; ?>" class="btn btn-outline"><i class="fas fa-eye"></i> View</a>
                                    <form method="POST">
                                        <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                        <button type="submit" name="delete_car" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </div>
            </div>
            <h3>Pending Reports</h3>
            <div class="table-container">
                <table>
                    <tr>
                        <th>Car</th>
                        <th>Reported By</th>
                        <th>Reason</th>
                        <th>Reported At</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($report['brand'] . ' ' . $report['model']); ?></td>
                            <td><?php echo htmlspecialchars($report['reporter_name']); ?></td>
                            <td><?php echo htmlspecialchars($report['reason']); ?></td>
                            <td><?php echo htmlspecialchars($report['created_at']); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="car_details.php?id=<?php echo $report['car_id']; ?>" class="btn btn-outline"><i class="fas fa-eye"></i> View Car</a>
                                    <form method="POST">
                                        <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                        <button type="submit" name="resolve_report" class="btn btn-success"><i class="fas fa-check"></i> Resolve</button>
                                        <button type="submit" name="dismiss_report" class="btn btn-danger"><i class="fas fa-times"></i> Dismiss</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <a href="view_verification.php" class="btn btn-primary"><i class="fas fa-id-card"></i> View Verifications</a>
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
