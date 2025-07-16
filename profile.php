<?php
require 'config.php';
require 'functions.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login to view your profile.";
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch favorite cars
$favorite_cars = [];
$stmt = $conn->prepare("SELECT cars.* FROM cars 
                        JOIN favorites ON cars.id = favorites.car_id 
                        WHERE favorites.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$favorite_cars_result = $stmt->get_result();
while ($row = $favorite_cars_result->fetch_assoc()) {
    $favorite_cars[] = $row;
}
$stmt->close();

// Fetch listed cars (for sellers)
$listed_cars = [];
if ($_SESSION['user_type'] == 'seller') {
    $stmt = $conn->prepare("SELECT * FROM cars WHERE seller_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $listed_cars_result = $stmt->get_result();
    while ($row = $listed_cars_result->fetch_assoc()) {
        $listed_cars[] = $row;
    }
    $stmt->close();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    try {
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
        $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $user['password'];

        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ?, location = ?, password = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $username, $email, $phone, $location, $password, $user_id);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Profile updated successfully!";
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['phone'] = $phone;
            $_SESSION['location'] = $location;
            header("Location: profile.php");
            exit();
        } else {
            throw new Exception("Error updating profile: " . $conn->error);
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
    <title>My Profile - CarBazaar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin: 40px auto;
            max-width: 1000px;
            animation: slideInUp 0.5s ease;
        }

        .profile-container h2 {
            font-size: 28px;
            margin-bottom: 20px;
            color: var(--dark);
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid var(--light-gray);
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 500;
            color: var(--gray);
            transition: all 0.3s;
        }

        .tab.active {
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .profile-details {
            background: var(--light);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .profile-details p {
            margin-bottom: 10px;
            font-size: 16px;
        }

        .profile-details p i {
            margin-right: 10px;
            color: var(--primary);
        }

        @media (max-width: 576px) {
            .profile-container {
                padding: 20px;
            }

            .tabs {
                flex-direction: column;
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
                    <?php if ($_SESSION['user_type'] == 'admin'): ?>
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
        <div class="profile-container">
            <h2>My Profile</h2>
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <div class="tabs">
                <div class="tab active" data-tab="profile">Profile</div>
                <div class="tab" data-tab="favorites">Favorites</div>
                <?php if ($_SESSION['user_type'] == 'seller'): ?>
                    <div class="tab" data-tab="listed">My Listings</div>
                <?php endif; ?>
            </div>
            <div class="tab-content active" id="profile">
                <div class="profile-details">
                    <p><i class="fas fa-user"></i> Username: <?php echo htmlspecialchars($user['username']); ?></p>
                    <p><i class="fas fa-envelope"></i> Email: <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><i class="fas fa-phone"></i> Phone: <?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></p>
                    <p><i class="fas fa-map-marker-alt"></i> Location: <?php echo htmlspecialchars($user['location']); ?></p>
                    <?php if ($_SESSION['user_type'] == 'seller'): ?>
                        <p><i class="fas fa-shield-alt"></i> Verification Status: 
                            <?php if ($user['is_verified']): ?>
                                <span class="verified-badge">Verified</span>
                            <?php else: ?>
                                <a href="verify_seller.php" class="btn btn-outline">Verify Now</a>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
                <h3>Update Profile</h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="username"><i class="fas fa-user"></i> Username</label>
                        <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="location"><i class="fas fa-map-marker-alt"></i> Location</label>
                        <input type="text" id="location" name="location" class="form-control" value="<?php echo htmlspecialchars($user['location']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock"></i> New Password (leave blank to keep current)</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter new password">
                    </div>
                    <div class="form-group">
                        <button type="submit" name="update_profile" class="btn btn-primary" style="width: 100%;"><i class="fas fa-save"></i> Update Profile</button>
                    </div>
                </form>
            </div>
            <div class="tab-content" id="favorites">
                <h3>My Favorite Cars</h3>
                <div class="cars-grid">
                    <?php if (!empty($favorite_cars)): ?>
                        <?php foreach ($favorite_cars as $car): ?>
                            <div class="car-card">
                                <?php if ($car['is_sold']): ?>
                                    <div class="sold-badge">SOLD</div>
                                <?php else: ?>
                                    <div class="car-badge">NEW</div>
                                <?php endif; ?>
                                <div class="car-image">
                                    <?php $images = explode(',', $car['image_paths']); ?>
                                    <img src="<?php echo htmlspecialchars($images[0]); ?>" alt="<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>">
                                </div>
                                <div class="car-details">
                                    <h3 class="car-title"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h3>
                                    <div class="car-price">₹<?php echo formatIndianNumber($car['price']); ?></div>
                                    <div class="car-specs">
                                        <span class="car-spec"><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($car['year']); ?></span>
                                        <span class="car-spec"><i class="fas fa-tachometer-alt"></i> <?php echo formatIndianNumber($car['km_driven']); ?> km</span>
                                        <span class="car-spec"><i class="fas fa-gas-pump"></i> <?php echo htmlspecialchars($car['fuel_type']); ?></span>
                                    </div>
                                    <div class="car-actions">
                                        <a href="car_details.php?id=<?php echo $car['id']; ?>" class="btn btn-outline"><i class="fas fa-eye"></i> View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No favorite cars yet.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($_SESSION['user_type'] == 'seller'): ?>
                <div class="tab-content" id="listed">
                    <h3>My Listed Cars</h3>
                    <div class="cars-grid">
                        <?php if (!empty($listed_cars)): ?>
                            <?php foreach ($listed_cars as $car): ?>
                                <div class="car-card">
                                    <?php if ($car['is_sold']): ?>
                                        <div class="sold-badge">SOLD</div>
                                    <?php else: ?>
                                        <div class="car-badge">NEW</div>
                                    <?php endif; ?>
                                    <div class="car-image">
                                        <?php $images = explode(',', $car['image_paths']); ?>
                                        <img src="<?php echo htmlspecialchars($images[0]); ?>" alt="<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>">
                                    </div>
                                    <div class="car-details">
                                        <h3 class="car-title"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h3>
                                        <div class="car-price">₹<?php echo formatIndianNumber($car['price']); ?></div>
                                        <div class="car-specs">
                                            <span class="car-spec"><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($car['year']); ?></span>
                                            <span class="car-spec"><i class="fas fa-tachometer-alt"></i> <?php echo formatIndianNumber($car['km_driven']); ?> km</span>
                                            <span class="car-spec"><i class="fas fa-gas-pump"></i> <?php echo htmlspecialchars($car['fuel_type']); ?></span>
                                        </div>
                                        <div class="car-actions">
                                            <a href="car_details.php?id=<?php echo $car['id']; ?>" class="btn btn-outline"><i class="fas fa-eye"></i> View Details</a>
                                            <?php if (!$car['is_sold']): ?>
                                                <a href="edit_car.php?id=<?php echo $car['id']; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Edit</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No cars listed yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
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
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                document.getElementById(this.dataset.tab).classList.add('active');
            });
        });
    </script>
</body>
</html>
