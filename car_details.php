<?php
require 'config.php';
require 'functions.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid car ID.";
    header("Location: index.php");
    exit();
}

$car_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$stmt = $conn->prepare("SELECT cars.*, users.username AS seller_name, users.phone AS seller_phone, users.email AS seller_email, users.is_verified 
                        FROM cars 
                        JOIN users ON cars.seller_id = users.id 
                        WHERE cars.id = ?");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$car = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$car) {
    $_SESSION['error'] = "Car not found.";
    header("Location: index.php");
    exit();
}

// Handle favorite toggle
if (isset($_POST['toggle_favorite']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM favorites WHERE user_id = ? AND car_id = ?");
    $stmt->bind_param("ii", $user_id, $car_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND car_id = ?");
        $action = "removed from";
    } else {
        $stmt = $conn->prepare("INSERT INTO favorites (user_id, car_id) VALUES (?, ?)");
        $action = "added to";
    }
    $stmt->bind_param("ii", $user_id, $car_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Car $action your favorites!";
    } else {
        $_SESSION['error'] = "Error toggling favorite: " . $conn->error;
    }
    $stmt->close();
    header("Location: car_details.php?id=$car_id");
    exit();
}

// Check if car is in favorites
$is_favorite = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM favorites WHERE user_id = ? AND car_id = ?");
    $stmt->bind_param("ii", $user_id, $car_id);
    $stmt->execute();
    $is_favorite = $stmt->get_result()->num_rows > 0;
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?> - CarBazaar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .car-details-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin: 40px auto;
            max-width: 1000px;
            animation: slideInUp 0.5s ease;
        }

        .car-details-container h2 {
            font-size: 28px;
            margin-bottom: 20px;
            color: var(--dark);
        }

        .car-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 30px;
        }

        .car-images img {
            width: 100%;
            max-width: 300px;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .car-images img:hover {
            transform: scale(1.05);
        }

        .main-image {
            width: 100%;
            max-width: 600px;
            height: 400px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .car-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .car-info-item {
            background: var(--light);
            padding: 15px;
            border-radius: 8px;
            font-size: 15px;
        }

        .car-info-item i {
            margin-right: 10px;
            color: var(--primary);
        }

        .seller-info {
            background: var(--light);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .verified-badge {
            background: var(--success);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 10px;
        }

        .car-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .car-info {
                grid-template-columns: 1fr;
            }

            .main-image {
                height: 300px;
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
                <?php if (isset($_SESSION['username'])): ?>
                    <div class="user-greeting">Welcome, <span><?php echo htmlspecialchars($_SESSION['username']); ?></span></div>
                    <a href="index.php?logout" class="btn btn-outline"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="register.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="car-details-container">
            <h2><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?> (<?php echo $car['year']; ?>)</h2>
            <?php if ($car['is_sold']): ?>
                <div class="sold-badge" style="display: inline-block;">SOLD</div>
            <?php endif; ?>
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <?php $images = explode(',', $car['image_paths']); ?>
            <img src="<?php echo htmlspecialchars($images[0]); ?>" alt="Main Image" class="main-image" id="main-image">
            <div class="car-images">
                <?php foreach ($images as $image): ?>
                    <img src="<?php echo htmlspecialchars($image); ?>" alt="Car Image" onclick="changeMainImage(this.src)">
                <?php endforeach; ?>
            </div>
            <div class="car-info">
                <div class="car-info-item"><i class="fas fa-rupee-sign"></i> Price: ₹<?php echo formatIndianNumber($car['price']); ?></div>
                <div class="car-info-item"><i class="fas fa-tachometer-alt"></i> KM Driven: <?php echo formatIndianNumber($car['km_driven']); ?> km</div>
                <div class="car-info-item"><i class="fas fa-gas-pump"></i> Fuel Type: <?php echo htmlspecialchars($car['fuel_type']); ?></div>
                <div class="car-info-item"><i class="fas fa-cog"></i> Transmission: <?php echo htmlspecialchars($car['transmission']); ?></div>
                <div class="car-info-item"><i class="fas fa-map-marker-alt"></i> Location: <?php echo htmlspecialchars($car['location']); ?></div>
                <div class="car-info-item"><i class="fas fa-user"></i> Ownership: <?php echo htmlspecialchars($car['ownership']); ?></div>
                <div class="car-info-item"><i class="fas fa-shield-alt"></i> Insurance: <?php echo htmlspecialchars($car['insurance']); ?></div>
            </div>
            <div class="car-description">
                <h3>Description</h3>
                <p><?php echo htmlspecialchars($car['description']); ?></p>
            </div>
            <div class="seller-info">
                <h3>Seller Information</h3>
                <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($car['seller_name']); ?>
                    <?php if ($car['is_verified']): ?>
                        <span class="verified-badge">Verified Seller</span>
                    <?php endif; ?>
                </p>
                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($car['seller_phone'] ?: 'Not provided'); ?></p>
                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($car['seller_email']); ?></p>
            </div>
            <div class="car-actions">
                <?php if (isset($_SESSION['user_id']) && !$car['is_sold']): ?>
                    <form method="POST">
                        <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                        <button type="submit" name="toggle_favorite" class="btn btn-outline favorite-btn <?php echo $is_favorite ? 'active' : ''; ?>">
                            <i class="fas fa-heart"></i> <?php echo $is_favorite ? 'Remove Favorite' : 'Add to Favorites'; ?>
                        </button>
                    </form>
                <?php endif; ?>
                <?php if (!$car['is_sold']): ?>
                    <a href="report_car.php?id=<?php echo $car['id']; ?>" class="btn btn-danger"><i class="fas fa-flag"></i> Report Car</a>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $car['seller_id'] && !$car['is_sold']): ?>
                    <a href="edit_car.php?id=<?php echo $car['id']; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Edit Car</a>
                <?php endif; ?>
                <a href="index.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Listings</a>
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
        function changeMainImage(src) {
            document.getElementById('main-image').src = src;
        }
    </script>
</body>
</html>
