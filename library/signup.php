<?php
// signup.php
session_start();

// Include database connection
require_once 'db_connect.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Process signup
    $username = $_POST['username'];
    $fullname = $_POST['fullname'];
    $password = $_POST['password'];

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Set default usertype to 'user'
    $usertype = 'user';

    // Insert user into the database
    $sql = "INSERT INTO users (username, fullname, password, usertype) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ssss", $username, $fullname, $hashedPassword, $usertype);
        if ($stmt->execute()) {
            echo "<script>
                if (confirm('Account created successfully! Do you want to login now?')) {
                    window.location.href = 'login.php';
                } else {
                    window.location.href = 'signup.php';
                }
            </script>";
        } else {
            echo "<script>alert('Error: Could not create account. Please try again.');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Error: Could not prepare statement.');</script>";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | Futuristic Portal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        
        .container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            width: 400px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        h1 {
            font-weight: 700;
            font-size: 32px;
            margin-bottom: 30px;
            text-align: center;
            color: #fff;
            letter-spacing: 1px;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        input {
            width: 100%;
            padding: 15px 20px;
            border: none;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.07);
            color: #fff;
            font-size: 16px;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 15px rgba(74, 138, 255, 0.5);
            border: 1px solid rgba(74, 138, 255, 0.8);
        }
        
        label {
            position: absolute;
            top: -10px;
            left: 15px;
            background: rgba(74, 138, 255, 0.8);
            padding: 0 10px;
            font-size: 12px;
            border-radius: 4px;
            color: #fff;
            font-weight: 500;
        }
        
        .error {
            color: #ff6b6b;
            font-size: 14px;
            margin-top: 5px;
            text-align: center;
        }
        
        button {
            background: linear-gradient(45deg, #8e54e9, #4a8aff);
            border: none;
            color: white;
            padding: 15px 0;
            width: 100%;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(142, 84, 233, 0.4);
        }
        
        button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(142, 84, 233, 0.6);
        }
        
        .links {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
        }
        
        .links a {
            color: #4a8aff;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .links a:hover {
            color: #8e54e9;
            text-decoration: underline;
        }
        
        .shape {
            position: absolute;
            z-index: -1;
            border-radius: 50%;
            opacity: 0.4;
        }
        
        .shape-1 {
            top: 20%;
            right: 20%;
            width: 300px;
            height: 300px;
            background: linear-gradient(45deg, #8e54e9, transparent);
            filter: blur(50px);
            animation: float 8s ease-in-out infinite;
        }
        
        .shape-2 {
            bottom: 20%;
            left: 20%;
            width: 400px;
            height: 400px;
            background: linear-gradient(45deg, #4a8aff, transparent);
            filter: blur(70px);
            animation: float 10s ease-in-out infinite reverse;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0) scale(1);
            }
            50% {
                transform: translateY(-20px) scale(1.05);
            }
        }
    </style>
</head>
<body>
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    
    <div class="container">
        <h1>CREATE ACCOUNT</h1>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="username">USERNAME</label>
                <input type="text" id="username" name="username" required autocomplete="off">
            </div>
            
            <div class="form-group">
                <label for="fullname">FULL NAME</label>
                <input type="text" id="fullname" name="fullname" required autocomplete="off">
            </div>
            
            <div class="form-group">
                <label for="password">PASSWORD</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">REGISTER</button>
        </form>
        
        <div class="links">
            <p>Already have an account? <a href="login.php">Login</a></p>
        </div>
    </div>
</body>
</html>