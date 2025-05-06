<?php
require_once('connect.php');
$db = new Database();
$conn = $db->getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $password = trim($_POST['password']);
    $age = trim($_POST['age']);

    if (empty($name) || empty($age) || empty($password)) {
        echo "<h2>All fields are required</h2>";
    } else {
        // Generate unique QR secret
        $qr_secret = bin2hex(random_bytes(32));
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $conn->prepare("INSERT INTO users(name, password, age, qr_secret) VALUES (:name, :password, :age, :qr_secret)");
            
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':password', $hashed_password);
            $stmt->bindValue(':age', $age);
            $stmt->bindValue(':qr_secret', $qr_secret);
            
            if ($stmt->execute()) {
                // Generate QR code data (will be rendered client-side)
                $qr_data = json_encode([
                    'type' => 'auth',
                    'username' => $name,
                    'secret' => $qr_secret,
                    'timestamp' => time()
                ]);
                
                // Show success message and QR code
                echo '<div class="success-message">';
                echo '<h1>Registration Successful!</h1>';
                echo '<p>Scan this QR code to log in later:</p>';
                echo '<div id="qrcode" style="width:200px; height:200px; margin:20px auto;"></div>';
                echo '<p>Or <a href="Login.php">login manually</a></p>';
                echo '</div>';
                
                // Add JavaScript to generate QR code
                echo '<script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>';
              echo '<script> new QRCode(document.getElementById("qrcode"), { text: ' . json_encode($qr_data) . ', width: 200, height: 200 }); </script>';
                
                exit(); // Stop further execution
            }
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration with QR Code</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500&display=swap" rel="stylesheet">
    <style>
        .container {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
        }
        .input-control {
            margin-bottom: 15px;
        }
        .error {
            color: red;
            font-size: 0.8em;
        }
        .success-message {
            text-align: center;
            margin-top: 50px;
        }
        .qr-options {
            margin: 20px 0;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
        }
        .tab-buttons {
            display: flex;
            margin-bottom: 15px;
        }
        .tab-button {
            flex: 1;
            padding: 10px;
            text-align: center;
            background: #eee;
            border: none;
            cursor: pointer;
        }
        .tab-button.active {
            background: #4CAF50;
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        #scanner-container {
            width: 100%;
            margin: 0 auto;
        }
        video {
            width: 100%;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>
    <div class="container">
        <form id="form" method="post" action="register.php">
            <h1>Registration</h1>
            
            <div class="input-control">
                <label for="username">Username</label>
                <input id="username" name="name" type="text" required>
                <div class="error"></div>
            </div>
            
            <div class="input-control">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required>
                <div class="error"></div>
            </div>
            
            <div class="input-control">
                <label for="age">Age</label>
                <input id="age" name="age" type="number" required>
                <div class="error"></div>
            </div>
            
            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="Login.php">Login here</a></p>
    </div>
</body>
</html>