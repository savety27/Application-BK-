<?php
session_start();
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = strtolower($_POST['email']);
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM users WHERE EMAIL = ? AND STATUS = 'Aktif'"; 
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("s", $email); 
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['PASSWORD'])) {
            $_SESSION['user_id'] = $user['ID'];
            $_SESSION['username'] = $user['USERNAME'];
            $_SESSION['role'] = $user['ROLE'];
            $_SESSION['nama_lengkap'] = $user['NAMA_LENGKAP'];
            
            if ($user['ROLE'] == 'Siswa') {
                header("Location: dashboard_siswa.php");
            } else if ($user['ROLE'] == 'Guru_BK') {
                header("Location: dashboard_guru.php");
            } else if ($user['ROLE'] == 'Admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: dashboard_siswa.php");
            }
            exit();
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Email tidak ditemukan atau akun tidak aktif!"; 
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - APK BK</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #0c2461 0%, #1e3799 25%, #4a69bd 50%, #6a89cc 100%);
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            margin: 0;
            position: relative;
            overflow: hidden;
        }
        
        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            overflow: hidden;
        }
        
        .floating-element {
            position: absolute;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-element:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .floating-element:nth-child(2) {
            width: 60px;
            height: 60px;
            top: 70%;
            left: 80%;
            animation-delay: 2s;
        }
        
        .floating-element:nth-child(3) {
            width: 100px;
            height: 100px;
            top: 50%;
            left: 5%;
            animation-delay: 4s;
        }
        
        .floating-element:nth-child(4) {
            width: 50px;
            height: 50px;
            top: 20%;
            left: 85%;
            animation-delay: 1s;
        }
        
        .floating-element:nth-child(5) {
            width: 70px;
            height: 70px;
            top: 80%;
            left: 20%;
            animation-delay: 3s;
        }
        
        @keyframes float {
            0%, 100% { 
                transform: translateY(0px) rotate(0deg); 
            }
            50% { 
                transform: translateY(-20px) rotate(180deg); 
            }
        }
        
        .login-wrapper {
            position: relative;
            z-index: 10;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.98);
            padding: 50px 40px;
            border-radius: 20px;
            box-shadow: 
                0 25px 50px rgba(12, 36, 97, 0.2),
                0 0 0 1px rgba(255, 255, 255, 0.3);
            width: 420px;
            position: relative;
            z-index: 20;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #1e3799, #4a69bd, #0c2461, #1e3799);
            border-radius: 22px;
            z-index: -1;
            animation: borderGlow 3s ease-in-out infinite;
            opacity: 0.8;
        }
        
        @keyframes borderGlow {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 0.9; }
        }
        
        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 
                0 30px 60px rgba(12, 36, 97, 0.25),
                0 0 0 1px rgba(255, 255, 255, 0.4);
        }
        
        .login-content {
            position: relative;
            z-index: 30;
        }
        
        h2 { 
            text-align: center; 
            color: #041144ff; 
            margin-bottom: 40px;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 1px;
            position: relative;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #0e1e5fff, #6c89dbff);
            border-radius: 2px;
        }
        
        .form-group { 
            margin-bottom: 25px; 
            position: relative;
        }
        
        label { 
            display: block; 
            margin-bottom: 8px; 
            color: #2d3748;
            font-weight: 600;
            font-size: 14px;
            letter-spacing: 0.5px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        input[type="email"], 
        input[type="password"] { 
            width: 100%; 
            padding: 15px 20px 15px 50px; 
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 10px; 
            box-sizing: border-box;
            color: #2d3748;
            font-size: 15px;
            transition: all 0.3s ease;
            font-family: 'Segoe UI', sans-serif;
            font-weight: 500;
        }
        
        input[type="email"]:focus, 
        input[type="password"]:focus { 
            outline: none;
            border-color: #4a69bd;
            box-shadow: 0 0 0 3px rgba(74, 105, 189, 0.1);
            background: #ffffff;
        }
        
        input[type="email"]::placeholder, 
        input[type="password"]::placeholder {
            color: #a0aec0;
            font-weight: 400;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            z-index: 2;
            pointer-events: none;
        }
        
        .input-icon svg {
            width: 100%;
            height: 100%;
            fill: #718096;
            transition: fill 0.3s ease;
        }
        
        input:focus + .input-icon svg {
            fill: #4a69bd;
        }
        
        button { 
            width: 100%; 
            padding: 16px; 
            background: linear-gradient(135deg, #1e3799 0%, #4a69bd 100%);
            color: white; 
            border: none; 
            border-radius: 10px; 
            cursor: pointer; 
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            font-family: 'Segoe UI', sans-serif;
            text-transform: uppercase;
            box-shadow: 0 4px 15px rgba(30, 55, 153, 0.3);
        }
        
        button:hover { 
            background: linear-gradient(135deg, #273ca1 0%, #5a7ad1 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(30, 55, 153, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        button::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }
        
        button:hover::after {
            left: 100%;
        }
        
        .error { 
            color: #e53e3e; 
            text-align: center; 
            margin-bottom: 20px;
            padding: 12px 16px;
            background: rgba(229, 62, 62, 0.1);
            border: 1px solid rgba(229, 62, 62, 0.3);
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .brand {
            text-align: center;
            color: #4a5568;
            font-size: 13px;
            margin-top: 30px;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .register-link {
            text-align: center;
            margin-top: 25px;
            color: #4a5568;
            font-size: 15px;
            font-weight: 500;
        }
        
        .register-link a {
            color: #1e3799;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
            padding: 5px 10px;
            border-radius: 5px;
        }
        
        .register-link a:hover {
            color: #4a69bd;
            text-decoration: underline;
            background: rgba(30, 55, 153, 0.1);
        }
    </style>
</head>
<body>
  
    <div class="floating-elements">
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
    </div>
    
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-content">
                <h2>Login APK BK</h2>
                
                <?php if (isset($error)): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Email:</label> 
                        <div class="input-wrapper">
                            <input type="email" name="email" required placeholder="Masukkan email Anda"> 
                            <div class="input-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/> 
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Password:</label>
                        <div class="input-wrapper">
                            <input type="password" name="password" required placeholder="Masukkan password Anda">
                            <div class="input-icon">
                                <svg viewBox="0 0 24 24">
                                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM12 17c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zM15.1 8H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit">Login</button>
                </form>

                <div class="register-link">
                    Belum punya akun? <a href="register.php">Daftar di sini</a>
                </div>
                
                <div class="brand">
                    APK BK - Bimbingan Konseling    
                </div>
            </div>
        </div>
    </div>
</body>
</html>