<?php
// login.php

// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'db_connect.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Process login
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validate credentials using the database
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['username'] = $username;
            $_SESSION['fullname'] = $user['fullname']; // Set fullname in session
            // Ensure proper redirection
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid credentials";
        }
    } else {
        $error = "Invalid credentials";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Futuristic Portal</title>
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
            background: linear-gradient(45deg, #4a8aff, #8e54e9);
            border: none;
            color: white;
            padding: 15px 0;
            width: 100%;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(74, 138, 255, 0.4);
        }
        
        button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(74, 138, 255, 0.6);
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
            left: 20%;
            width: 300px;
            height: 300px;
            background: linear-gradient(45deg, #4a8aff, transparent);
            filter: blur(50px);
            animation: float 8s ease-in-out infinite;
        }
        
        .shape-2 {
            bottom: 20%;
            right: 20%;
            width: 400px;
            height: 400px;
            background: linear-gradient(45deg, #8e54e9, transparent);
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
        <h1>LOGIN</h1>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="username">USERNAME</label>
                <input type="text" id="username" name="username" required autocomplete="off">
            </div>
            
            <div class="form-group">
                <label for="password">PASSWORD</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">ACCESS PORTAL</button>
        </form>
        
        <div class="links">
            <p>Don't have an account? <a href="signup.php">Sign Up</a></p>
        </div>
    </div>
</body>
</html>