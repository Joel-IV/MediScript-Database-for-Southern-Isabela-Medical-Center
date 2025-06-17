<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['fullname'])) {
    header('Location: login.php'); // Redirect to login page if not logged in
    exit;
}

require_once 'db_connect.php'; // Include the database connection file

// Fetch user details from the database
$query = "SELECT username, fullname, usertype FROM users WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$stmt->close();

if (!$userData) {
    echo "Error fetching user data.";
    exit;
}

// Admin password for changing user type
$adminPassword = 'admin123';

// Process form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update user profile in the database
        $username = htmlspecialchars($_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
        $fullname = htmlspecialchars($_POST['fullname']);

        $updateQuery = "UPDATE users SET username = ?, password = ?, fullname = ? WHERE username = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param('ssss', $username, $password, $fullname, $_SESSION['username']);

        if ($stmt->execute()) {
            $_SESSION['username'] = $username; // Update session username
            $_SESSION['fullname'] = $fullname; // Update session fullname
            $message = 'Profile updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error updating profile. Please try again.';
            $messageType = 'error';
        }

        $stmt->close();
    } elseif (isset($_POST['change_usertype'])) {
        // Verify admin password before changing user type
        if ($_POST['admin_password'] === $adminPassword) {
            $usertype = ($_POST['usertype'] === 'admin' || $_POST['usertype'] === 'user') ? $_POST['usertype'] : 'user';

            $updateUserTypeQuery = "UPDATE users SET usertype = ? WHERE username = ?";
            $stmt = $conn->prepare($updateUserTypeQuery);
            $stmt->bind_param('ss', $usertype, $_SESSION['username']);

            if ($stmt->execute()) {
                $message = 'User type changed successfully!';
                $messageType = 'success';
                
                // Update the user data without page reload
                $userData['usertype'] = $usertype;
            } else {
                $message = 'Error changing user type. Please try again.';
                $messageType = 'error';
            }

            $stmt->close();
        } else {
            $message = 'Incorrect admin password!';
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile Management</title>
    <style>
        html, body {
            height: 100%;
            width: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            position: relative;
            padding: 20px;
            box-sizing: border-box;
        }
        
        .container {
            width: 100%;
            max-width: 800px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin: 80px auto 20px auto;
        }
        
        .header {
            background-color: #4B6CB7;
            color: white;
            padding: 30px 20px;
            text-align: center;
            background-image: linear-gradient(to right, #4B6CB7, #182848);
        }
        
        .header img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid white;
            background-color: white;
            padding: 5px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .content {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }
        
        input[type="text"],
        input[type="password"],
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus,
        select:focus {
            outline: none;
            border-color: #4B6CB7;
            box-shadow: 0 0 5px rgba(75, 108, 183, 0.5);
        }
        
        button {
            background-color: #4B6CB7;
            color: white;
            border: none;
            padding: 14px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s;
            display: block;
            width: 100%;
        }
        
        button:hover {
            background-color: #3b5998;
        }
        
        hr {
            margin: 40px 0;
            border: 0;
            height: 1px;
            background-image: linear-gradient(to right, rgba(0,0,0,0), rgba(0,0,0,0.1), rgba(0,0,0,0));
        }
        
        .section-title {
            color: #4B6CB7;
            margin-bottom: 25px;
            font-size: 24px;
            text-align: center;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
            font-weight: 600;
            text-align: center;
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .user-icon {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .user-badge {
            display: inline-block;
            padding: 8px 15px;
            background-color: #e9ecef;
            color: #343a40;
            border-radius: 20px;
            font-size: 16px;
            margin-top: 15px;
            font-weight: bold;
        }
        
        .premium {
            background-color: #ffd700;
            color: #333;
        }
        
        .admin {
            background-color: #dc3545;
            color: white;
        }
        
        /* Message that fades out */
        .alert.fade-out {
            animation: fadeOut 5s forwards;
        }
        
        @keyframes fadeOut {
            0% { opacity: 1; }
            80% { opacity: 1; }
            100% { opacity: 0; height: 0; padding: 0; margin: 0; border: 0; }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                margin-top: 100px;
                border-radius: 0;
            }
            
            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Header and Navigation -->
    <div style="position: fixed; top: 10px; right: 20px; z-index: 1000;">
        <div style="position: relative; display: inline-block;">
            <button onclick="toggleDropdown()" style="background-color: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; width: auto;">
                <?php echo htmlspecialchars($_SESSION['fullname']); ?>
            </button>
            <div id="userDropdown" style="display: none; position: absolute; right: 0; background-color: white; border: 1px solid #ccc; border-radius: 5px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); z-index: 1000; overflow: hidden; width: 150px;">
                <a href="profile.php" style="display: block; padding: 10px 20px; text-decoration: none; color: #333;">Profile</a>
                <a href="index.php" style="display: block; padding: 10px 20px; text-decoration: none; color: #333;">Main</a>
                <a href="logout.php" style="display: block; padding: 10px 20px; text-decoration: none; color: #333;">Logout</a>
            </div>
        </div>
    </div>

    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        // Close dropdown if clicked outside
        window.onclick = function(event) {
            if (!event.target.matches('button')) {
                const dropdown = document.getElementById('userDropdown');
                if (dropdown.style.display === 'block') {
                    dropdown.style.display = 'none';
                }
            }
        };
        
        // Auto hide success messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alertSuccess = document.querySelector('.alert-success');
            if (alertSuccess) {
                setTimeout(function() {
                    alertSuccess.classList.add('fade-out');
                }, 1000);
                setTimeout(function() {
                    alertSuccess.style.display = 'none';
                }, 6000);
            }
        });
    </script>

    <div class="container">
        <div class="header">
            <div class="logo">
                <div style="width: 60px; height: 60px; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; background-color: #f4f4f4;">
                    <img src="logo/logo2.gif" alt="MediScript Logo" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <h1 style="margin: 0;">MediScript Database</h1>
            </div>
            <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSJjdXJyZW50Q29sb3IiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIiBjbGFzcz0ibHVjaWRlIGx1Y2lkZS11c2VyIj48cGF0aCBkPSJNMTkgMjFhNyA3IDAgMSAwLTE0IDB2LTJhNCA0IDAgMCAxIDQtNGg2YTQgNCAwIDAgMSA0IDR2MloiLz48Y2lyY2xlIGN4PSIxMiIgY3k9IjciIHI9IjQiLz48L3N2Zz4=" alt="User Profile" style="width: 80px; height: 80px;">
            <h1>User Profile Management</h1>
            <div class="user-badge <?php echo ($userData['usertype'] === 'admin') ? 'admin' : (($userData['usertype'] === 'premium') ? 'premium' : ''); ?>">
                <?php echo ucfirst($userData['usertype']); ?> User
            </div>
        </div>
        
        <div class="content">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="user-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="#4B6CB7" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21a7 7 0 1 0-14 0v-2a4 4 0 0 1 4-4h6a4 4 0 0 1 4 4v2Z"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            
            <h2 class="section-title">Edit Profile Information</h2>
            
            <form method="post" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($userData['username']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter new password" required>
                </div>
                
                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($userData['fullname']); ?>" required>
                </div>
                
                <button type="submit" name="update_profile">Update Profile</button>
            </form>
            
            <hr>
            
            <h2 class="section-title">Change User Type</h2>
            <form method="post" action="">
                <div class="form-group">
                    <label for="usertype">User Type</label>
                    <select id="usertype" name="usertype">
                        <option value="user" <?php echo ($userData['usertype'] === 'user') ? 'selected' : ''; ?>>User</option>
                        <option value="admin" <?php echo ($userData['usertype'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="admin_password">Admin Password</label>
                    <input type="password" id="admin_password" name="admin_password" placeholder="Enter admin password to change user type" required>
                </div>
                
                <button type="submit" name="change_usertype">Change User Type</button>
            </form>
        </div>
    </div>
</body>
</html>