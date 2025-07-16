<?php
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'seller') {
    $_SESSION['error'] = "Please login as a seller to access verification.";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$verification_check = $conn->prepare("SELECT * FROM verifications WHERE user_id = ? AND status = 'pending'");
$verification_check->bind_param("i", $user_id);
$verification_check->execute();
$has_pending = $verification_check->get_result()->num_rows > 0;
$verification_check->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_verification']) && !$has_pending) {
    try {
        $target_dir = "Uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $target_file = $target_dir . uniqid() . '.' . strtolower(pathinfo($_FILES["aadhaar"]["name"], PATHINFO_EXTENSION));
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png'];

        if (!in_array($imageFileType, $allowed_types)) {
            throw new Exception("Invalid image format. Only JPG, JPEG, PNG are allowed.");
        }

        if (move_uploaded_file($_FILES["aadhaar"]["tmp_name"], $target_file)) {
            $stmt = $conn->prepare("INSERT INTO verifications (user_id, aadhaar_path) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $target_file);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Verification request submitted successfully!";
            } else {
                throw new Exception("Error submitting verification: " . $conn->error);
            }
            $stmt->close();
        } else {
            throw new Exception("Error uploading Aadhaar image.");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header("Location: verify_seller.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Verification - CarBazaar</title>
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

        .preview-image {
            max-width: 100%;
            margin-top: 20px;
            border-radius: 8px;
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
                <h2>Seller Verification</h2>
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <?php
                $stmt = $conn->prepare("SELECT verification_status, rejection_reason FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                ?>
                <?php if ($user['verification_status'] == 'rejected'): ?>
                    <div class="alert alert-error">
                        Verification rejected: <?php echo htmlspecialchars($user['rejection_reason']); ?>
                        <p><a href="verify_seller.php" class="btn btn-primary">Try Again</a></p>
                    </div>
                <?php elseif ($user['verification_status'] == 'pending' || $has_pending): ?>
                    <div class="alert alert-warning">Your verification request is pending review by admin.</div>
                <?php elseif ($user['verification_status'] == 'approved'): ?>
                    <div class="alert alert-success">Your account is verified! You can now list cars.</div>
                    <a href="add_car.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Car</a>
                <?php else: ?>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="aadhaar"><i class="fas fa-id-card"></i> Upload Aadhaar Card</label>
                            <input type="file" id="aadhaar" name="aadhaar" class="form-control" accept="image/*" required onchange="previewImage(event)">
                            <img id="preview" class="preview-image" style="display: none;">
                        </div>
                        <div class="form-group">
                            <button type="submit" name="submit_verification" class="btn btn-primary" style="width: 100%;"><i class="fas fa-upload"></i> Submit for Verification</button>
                        </div>
                    </form>
                <?php endif; ?>
                <div class="auth-footer">
                    <p><a href="index.php">Back to Home</a></p>
                </div>
            </div>
        </div>
    </div>
    <script>
        function previewImage(event) {
            const preview = document.getElementById('preview');
            preview.src = URL.createObjectURL(event.target.files[0]);
            preview.style.display = 'block';
        }
    </script>
</body>
</html>
