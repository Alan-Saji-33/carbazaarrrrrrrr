<?php
require 'config.php';
require 'functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    $_SESSION['error'] = "Access denied. Admins only.";
    header("Location: login.php");
    exit();
}

// Fetch pending verifications
$verifications = $conn->query("SELECT verifications.*, users.username, users.email 
                               FROM verifications 
                               JOIN users ON verifications.user_id = users.id 
                               WHERE verifications.status = 'pending'")->fetch_all(MYSQLI_ASSOC);

// Handle verification actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['approve']) || isset($_POST['reject']))) {
    $verification_id = filter_input(INPUT_POST, 'verification_id', FILTER_SANITIZE_NUMBER_INT);
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
    $status = isset($_POST['approve']) ? 'approved' : 'rejected';
    $rejection_reason = isset($_POST['rejection_reason']) ? filter_input(INPUT_POST, 'rejection_reason', FILTER_SANITIZE_STRING) : null;

    $stmt = $conn->prepare("UPDATE verifications SET status = ?, rejection_reason = ? WHERE id = ?");
    $stmt->bind_param("ssi", $status, $rejection_reason, $verification_id);
    if ($stmt->execute()) {
        $is_verified = $status == 'approved' ? 1 : 0;
        $stmt = $conn->prepare("UPDATE users SET is_verified = ?, verification_status = ?, rejection_reason = ? WHERE id = ?");
        $stmt->bind_param("issi", $is_verified, $status, $rejection_reason, $user_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Verification $status successfully!";
        } else {
            $_SESSION['error'] = "Error updating user: " . $conn->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Error updating verification: " . $conn->error;
    }
    header("Location: view_verification.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Verifications - CarBazaar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .verification-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin: 40px auto;
            max-width: 1000px;
            animation: slideInUp 0.5s ease;
        }

        .verification-container h2 {
            font-size: 28px;
            margin-bottom: 20px;
            color: var(--dark);
        }

        .verification-card {
            background: var(--light);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .verification-card img {
            max-width: 100%;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .rejection-form {
            margin-top: 15px;
        }

        .rejection-form textarea {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid var(--light-gray);
            margin-bottom: 10px;
        }

        @media (max-width: 576px) {
            .verification-container {
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
        <div class="verification-container">
            <h2>Pending Verifications</h2>
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <?php if (empty($verifications)): ?>
                <p>No pending verifications.</p>
            <?php else: ?>
                <?php foreach ($verifications as $verification): ?>
                    <div class="verification-card">
                        <h3><?php echo htmlspecialchars($verification['username']); ?></h3>
                        <p><i class="fas fa-envelope"></i> Email: <?php echo htmlspecialchars($verification['email']); ?></p>
                        <p><i class="fas fa-id-card"></i> Document Type: <?php echo htmlspecialchars($verification['document_type']); ?></p>
                        <p><i class="fas fa-file-alt"></i> Document Number: <?php echo htmlspecialchars($verification['document_number']); ?></p>
                        <p><i class="fas fa-image"></i> Document Image:</p>
                        <img src="<?php echo htmlspecialchars($verification['document_path']); ?>" alt="Document Image">
                        <div class="action-buttons">
                            <form method="POST">
                                <input type="hidden" name="verification_id" value="<?php echo $verification['id']; ?>">
                                <input type="hidden" name="user_id" value="<?php echo $verification['user_id']; ?>">
                                <button type="submit" name="approve" class="btn btn-success"><i class="fas fa-check"></i> Approve</button>
                            </form>
                            <form method="POST" class="rejection-form">
                                <input type="hidden" name="verification_id" value="<?php echo $verification['id']; ?>">
                                <input type="hidden" name="user_id" value="<?php echo $verification['user_id']; ?>">
                                <textarea name="rejection_reason" placeholder="Enter rejection reason (optional)" rows="3"></textarea>
                                <button type="submit" name="reject" class="btn btn-danger"><i class="fas fa-times"></i> Reject</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <div class="auth-footer">
                <p><a href="admin_dashboard.php">Back to Dashboard</a></p>
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
