<?php
// เริ่ม Session (ต้องเรียกใช้ session_start() ในทุกหน้าที่ต้องการใช้ Session)
session_start();

// ตรวจสอบว่า ถ้า Log in แล้ว (มี Session 'user_id' อยู่)
if (isset($_SESSION['user_id'])) {
    // ให้เด้งไปหน้า index.php ทันที (ไม่จำเป็นต้อง Log in ซ้ำ)
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in - ระบบยืมคืนอุปกรณ์การแพทย์</title>
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        body {
            background-color: #f4f4f4; /* สีพื้นหลังเหมือนหน้าหลัก */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh; /* ให้เต็มหน้าจอ */
        }
        .login-container {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            width: 350px;
            text-align: center;
        }
        .login-container h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: 90%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .login-container button {
            width: 100%;
            padding: 12px;
            background-color: #007bff; /* สีน้ำเงิน */
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }
        .login-container button:hover {
            background-color: #0056b3;
        }
        /* ส่วนแสดงข้อความ Error (ถ้า Log in ผิด) */
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            display: <?php echo isset($_GET['error']) ? 'block' : 'none'; ?>; /* PHP ควบคุมการแสดงผล */
        }
    </style>
</head>
<body>

    <div class="login-container">
        <h1>MedLoan Log in</h1>
        <p>ระบบยืมคืนอุปกรณ์การแพทย์</p>

        <div class="error-message">
            ชื่อผู้ใช้ หรือ รหัสผ่าน ไม่ถูกต้อง!
        </div>

        <form action="login_process.php" method="POST">
            <div>
                <input type="text" name="username" placeholder="Username" required>
            </div>
            <div>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit">Log in</button>
        </form>
    </div>

</body>
</html>