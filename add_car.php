<?php
require 'config.php';
require 'functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'seller') {
    $_SESSION['error'] = "Please login as a seller to add cars.";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT is_verified FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user['is_verified']) {
    $_SESSION['error'] = "Your account is not verified. Please complete verification.";
    header("Location: verify_seller.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_car'])) {
    try {
        $target_dir = "Uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $image_paths = [];
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['size'][$key] > 0) {
                $imageFileType = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
                if (!in_array($imageFileType, $allowed_types)) {
                    throw new Exception("Invalid image format for file " . $_FILES['images']['name'][$key] . ". Only JPG, JPEG, PNG, and GIF are allowed.");
                }
                $new_filename = uniqid() . '.' . $imageFileType;
                $target_file = $target_dir . $new_filename;
                if (move_uploaded_file($tmp_name, $target_file)) {
                    $image_paths[] = $target_file;
                } else {
                    throw new Exception("Error uploading image " . $_FILES['images']['name'][$key] . ".");
                }
            }
        }
        
        if (empty($image_paths)) {
            throw new Exception("At least one image is required.");
        }

        $stmt = $conn->prepare("INSERT INTO cars (seller_id, model, brand, year, price, km_driven, fuel_type, transmission, location, ownership, insurance, image_paths, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $seller_id = $_SESSION['user_id'];
        $model = filter_input(INPUT_POST, 'model', FILTER_SANITIZE_STRING);
        $brand = filter_input(INPUT_POST, 'brand', FILTER_SANITIZE_STRING);
        $year = filter_input(INPUT_POST, 'year', FILTER_SANITIZE_NUMBER_INT);
        $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $km_driven = filter_input(INPUT_POST, 'km_driven', FILTER_SANITIZE_NUMBER_INT);
        $fuel_type = filter_input(INPUT_POST, 'fuel_type', FILTER_SANITIZE_STRING);
        $transmission = filter_input(INPUT_POST, 'transmission', FILTER_SANITIZE_STRING);
        $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
        $ownership = filter_input(INPUT_POST, 'ownership', FILTER_SANITIZE_STRING);
        $insurance = filter_input(INPUT_POST, 'insurance', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $image_paths_str = implode(',', $image_paths);

        $stmt->bind_param("issidssssssss", $seller_id, $model, $brand, $year, $price, $km_driven, $fuel_type, $transmission, $location, $ownership, $insurance, $image_paths_str, $description);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Car added successfully!";
            header("Location: index.php");
            exit();
        } else {
            throw new Exception("Error adding car: " . $conn->error);
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
    <title>Add Car - CarBazaar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .form-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin: 40px auto;
            max-width: 800px;
            animation: slideInUp 0.5s ease;
        }

        .form-container h2 {
            font-size: 28px;
            margin-bottom: 30px;
            color: var(--dark);
            text-align: center;
        }

        .form-container .form-group {
            margin-bottom: 20px;
        }

        .preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .preview-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }

        @media (max-width: 576px) {
            .form-container {
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
                </ul>
            </nav>
            <div class="user-actions">
                <div class="user-greeting">Welcome, <span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
                <a href="index.php?logout" class="btn btn-outline"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="form-container">
            <h2>Add New Car</h2>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="brand"><i class="fas fa-car"></i> Brand</label>
                    <input type="text" id="brand" name="brand" class="form-control" placeholder="e.g. Toyota" required>
                </div>
                <div class="form-group">
                    <label for="model"><i class="fas fa-car-alt"></i> Model</label>
                    <input type="text" id="model" name="model" class="form-control" placeholder="e.g. Corolla" required>
                </div>
                <div class="form-group">
                    <label for="year"><i class="fas fa-calendar-alt"></i> Year</label>
                    <input type="number" id="year" name="year" class="form-control" min="1900" max="<?php echo date('Y'); ?>" required>
                </div>
                <div class="form-group">
                    <label for="price"><i class="fas fa-rupee-sign"></i> Price (₹)</label>
                    <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="km_driven"><i class="fas fa-tachometer-alt"></i> Kilometers Driven</label>
                    <input type="number" id="km_driven" name="km_driven" class="form-control" min="0" required>
                </div>
                <div class="form-group">
                    <label for="fuel_type"><i class="fas fa-gas-pump"></i> Fuel Type</label>
                    <select id="fuel_type" name="fuel_type" class="form-control" required>
                        <option value="Petrol">Petrol</option>
                        <option value="Diesel">Diesel</option>
                        <option value="Electric">Electric</option>
                        <option value="Hybrid">Hybrid</option>
                        <option value="CNG">CNG</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="transmission"><i class="fas fa-cog"></i> Transmission</label>
                    <select id="transmission" name="transmission" class="form-control" required>
                        <option value="Automatic">Automatic</option>
                        <option value="Manual">Manual</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="location"><i class="fas fa-map-marker-alt"></i> Location</label>
                    <input type="text" id="location" name="location" class="form-control" placeholder="e.g. Mumbai" required>
                </div>
                <div class="form-group">
                    <label for="ownership"><i class="fas fa-user"></i> Ownership</label>
                    <select id="ownership" name="ownership" class="form-control" required>
                        <option value="First">First</option>
                        <option value="Second">Second</option>
                        <option value="Third">Third</option>
                        <option value="Fourth+">Fourth+</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="insurance"><i class="fas fa-shield-alt"></i> Insurance Details</label>
                    <input type="text" id="insurance" name="insurance" class="form-control" placeholder="e.g. Comprehensive, valid till 2025" required>
                </div>
                <div class="form-group">
                    <label for="images"><i class="fas fa-camera"></i> Car Images (Max 5)</label>
                    <input type="file" id="images" name="images[]" class="form-control" accept="image/*" multiple required onchange="previewImages(event)">
                    <div class="preview-container" id="preview-container"></div>
                </div>
                <div class="form-group">
                    <label for="description"><i class="fas fa-file-alt"></i> Description</label>
                    <textarea id="description" name="description" class="form-control" rows="5" placeholder="Describe the car's condition, features, etc." required></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" name="add_car" class="btn btn-primary" style="width: 100%;"><i class="fas fa-plus"></i> Add Car</button>
                </div>
            </form>
            <div class="auth-footer">
                <p><a href="index.php">Back to Home</a></p>
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

    <script>
        function previewImages(event) {
            const previewContainer = document.getElementById('preview-container');
            previewContainer.innerHTML = '';
            const files = event.target.files;
            if (files.length > 5) {
                alert('Maximum 5 images allowed.');
                event.target.value = '';
                return;
            }
            for (let i = 0; i < files.length; i++) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(files[i]);
                img.className = 'preview-image';
                previewContainer.appendChild(img);
            }
        }
    </script>
</body>
</html>
