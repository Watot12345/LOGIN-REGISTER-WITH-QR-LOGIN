<?php
require_once('connect.php');
session_start();

$db = new Database();
$conn = $db->getConnection();

// Handle QR code authentication
if (isset($_POST['qr_data'])) {
    $data = json_decode($_POST['qr_data'], true);
    
    if ($data && isset($data['username']) && isset($data['secret'])) {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE name = :name AND qr_secret = :secret");
            $stmt->bindValue(':name', $data['username']);
            $stmt->bindValue(':secret', $data['secret']);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['name'];
                header("Location: Kupal.php");
                exit();
            }
        } catch(PDOException $e) {
            // Handle error
        }
    }
}

// Handle password authentication
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['name'])) {
    $name = trim($_POST['name']);
    $password = trim($_POST['password']);

    if (empty($name) || empty($password)) {
        $error = "All fields are required";
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE name = :name");
            $stmt->bindValue(':name', $name);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['name'];
                header("Location: Kupal.php");
                exit();
            } else {
                $error = "Invalid username or password";
            }
        } catch(PDOException $e) {
            $error = "Database error";
        }
    } 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login with QR Code</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500&display=swap" rel="stylesheet">
    <!-- Using jsQR library for better performance -->
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
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
            position: relative;
        }
        #qr-scanner {
            width: 100%;
            max-height: 300px;
            object-fit: cover;
            border: 1px solid #ccc;
        }
        .scanning-active {
            border: 3px solid #4CAF50;
        }
        #scan-region {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 70%;
            height: 70%;
            border: 2px dashed rgba(0, 255, 0, 0.5);
            pointer-events: none;
        }
        #qr-upload-result {
            margin-top: 10px;
            font-size: 0.9em;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Login</h1>
        
        <div class="tab-buttons">
            <button class="tab-button active" onclick="openTab('password-tab')">Password</button>
            <button class="tab-button" onclick="openTab('qr-tab')">QR Code</button>
        </div>
        
        <!-- Password Login Tab -->
        <div id="password-tab" class="tab-content active">
            <form method="post" action="Login.php">
                <div class="input-control">
                    <label for="username">Username</label>
                    <input id="username" name="name" type="text" required>
                </div>
                
                <div class="input-control">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <button type="submit">Login</button>
            </form>
        </div>
        
        <!-- QR Code Login Tab -->
        <div id="qr-tab" class="tab-content">
            <div class="qr-options">
                <h3>Scan QR Code</h3>
                <div id="scanner-container">
                    <video id="qr-scanner" playsinline></video>
                    <div id="scan-region"></div>
                </div>
                <p id="scan-status">Point your camera at a QR code</p>
                
                <h3>Or Upload QR Code</h3>
                <input type="file" id="qr-upload" accept="image/*">
                <div id="qr-upload-result"></div>
            </div>
        </div>
        
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>

    <script>
        // Tab switching
        function openTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            document.getElementById(tabId).classList.add('active');
            event.currentTarget.classList.add('active');
            
            // Initialize scanner when QR tab is opened
            if (tabId === 'qr-tab' && !window.scannerActive) {
                initQRScanner();
            } else if (tabId !== 'qr-tab' && window.qrStream) {
                // Stop camera when switching away from QR tab
                qrStream.getTracks().forEach(track => track.stop());
                window.scannerActive = false;
            }
        }
        
        // QR Code Scanner - Optimized Version
        let qrStream = null;
        let lastScanTime = 0;
        const SCAN_THROTTLE = 500; // Only process one scan every 500ms
        let scanCanvas = null;
        let scanContext = null;
        
        function initQRScanner() {
            const video = document.getElementById('qr-scanner');
            const status = document.getElementById('scan-status');
            
            // Create canvas for QR processing
            scanCanvas = document.createElement('canvas');
            scanContext = scanCanvas.getContext('2d', { willReadFrequently: true });
            
            navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: "environment",
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            }).then(function(stream) {
                qrStream = stream;
                video.srcObject = stream;
                video.play();
                window.scannerActive = true;
                
                // Start scanning loop
                requestAnimationFrame(scanQRCode);
            }).catch(function(err) {
                console.error("Camera error: ", err);
                status.textContent = "Camera error: " + err.message;
            });
        }
        
        function scanQRCode() {
            if (!window.scannerActive) return;
            
            const video = document.getElementById('qr-scanner');
            const status = document.getElementById('scan-status');
            
            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                scanCanvas.width = video.videoWidth;
                scanCanvas.height = video.videoHeight;
                scanContext.drawImage(video, 0, 0, scanCanvas.width, scanCanvas.height);
                
                const imageData = scanContext.getImageData(0, 0, scanCanvas.width, scanCanvas.height);
                const code = jsQR(imageData.data, imageData.width, imageData.height, {
                    inversionAttempts: "dontInvert",
                });
                
                if (code) {
                    const now = Date.now();
                    if (now - lastScanTime < SCAN_THROTTLE) {
                        requestAnimationFrame(scanQRCode);
                        return;
                    }
                    lastScanTime = now;
                    
                    status.textContent = "Code detected! Verifying...";
                    
                    try {
                        const data = JSON.parse(code.data);
                        if (data.type === 'auth') {
                            // Send QR data to server
                            fetch('Login.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'qr_data=' + encodeURIComponent(code.data)
                            })
                            .then(response => {
                                if (response.redirected) {
                                    window.location.href = response.url;
                                } else {
                                    status.textContent = "Login failed. Please try again.";
                                }
                            })
                            .catch(() => {
                                status.textContent = "Network error. Please try again.";
                            });
                        } else {
                            status.textContent = "Invalid QR code type.";
                        }
                    } catch (e) {
                        status.textContent = "Invalid QR code format.";
                    }
                }
            }
            
            requestAnimationFrame(scanQRCode);
        }
        
        // QR Code Upload
        document.getElementById('qr-upload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const status = document.getElementById('qr-upload-result');
            
            if (!file) return;
            
            status.textContent = "Processing QR code...";
            
            const reader = new FileReader();
            reader.onload = function(event) {
                const img = new Image();
                img.onload = function() {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    canvas.width = img.width;
                    canvas.height = img.height;
                    ctx.drawImage(img, 0, 0);
                    
                    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    const code = jsQR(imageData.data, imageData.width, imageData.height);
                    
                    if (code) {
                        try {
                            const data = JSON.parse(code.data);
                            if (data.type === 'auth') {
                                status.textContent = "QR code detected! Authenticating...";
                                
                                fetch('Login.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded',
                                    },
                                    body: 'qr_data=' + encodeURIComponent(code.data)
                                })
                                .then(response => {
                                    if (response.redirected) {
                                        window.location.href = response.url;
                                    } else {
                                        status.textContent = "Login failed. Please try again.";
                                    }
                                });
                            } else {
                                status.textContent = "Invalid QR code type.";
                            }
                        } catch (e) {
                            status.textContent = "Invalid QR code format.";
                        }
                    } else {
                        status.textContent = "No QR code found in image.";
                    }
                };
                img.src = event.target.result;
            };
            reader.readAsDataURL(file);
        });
        
        // Clean up camera stream when leaving page
        window.addEventListener('beforeunload', function() {
            if (qrStream) {
                qrStream.getTracks().forEach(track => track.stop());
            }
        });
    </script>
</body>
</html>