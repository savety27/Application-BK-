<?php
session_start();
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $email = $_POST['email'];
    $no_telepon = $_POST['no_telepon'];
    
    $role = 'Siswa';
    
    if (strlen($password) < 6) {
        $error = "Password harus minimal 6 karakter!";
    } else {
        $check_sql = "SELECT * FROM users WHERE USERNAME = ?";
        $check_stmt = $koneksi->prepare($check_sql);
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Username sudah digunakan!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $insert_sql = "INSERT INTO users (USERNAME, PASSWORD, ROLE, NAMA_LENGKAP, EMAIL, NO_TELEPON) VALUES (?, ?, ?, ?, ?, ?)";
            $insert_stmt = $koneksi->prepare($insert_sql);
            $insert_stmt->bind_param("ssssss", $username, $hashed_password, $role, $nama_lengkap, $email, $no_telepon);
            
           if ($insert_stmt->execute()) {
            header("Location: index.php");
            exit;
        } else {
            $error = "Registrasi gagal!";
        }
    }
}
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - APK BK</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #0c2461 0%, #1e3799 25%, #4a69bd 50%, #6a89cc 100%);
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            margin: 0;
            position: relative;
            overflow: auto;
            padding: 20px;
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
        
        .register-wrapper {
            position: relative;
            z-index: 10;
        }
        
        .register-container {
            background: rgba(255, 255, 255, 0.98);
            padding: 50px 40px;
            border-radius: 20px;
            box-shadow: 
                0 25px 50px rgba(12, 36, 97, 0.2),
                0 0 0 1px rgba(255, 255, 255, 0.3);
            width: 100%;
            max-width: 500px;
            position: relative;
            z-index: 20;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .register-container:hover {
            transform: translateY(-5px);
            box-shadow: 
                0 30px 60px rgba(12, 36, 97, 0.25),
                0 0 0 1px rgba(255, 255, 255, 0.4);
        }
        
        .register-content {
            position: relative;
            z-index: 30;
        }
        
        h2 { 
            text-align: center; 
            color: #1e3799; 
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
            width: 80px;
            height: 3px;
            background: linear-gradient(90deg, #1e3799, #4a69bd);
            border-radius: 2px;
        }
        
        .form-group { 
            margin-bottom: 20px; 
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
        
        input[type="text"], 
        input[type="password"],
        input[type="email"],
        input[type="tel"] { 
            width: 100%; 
            padding: 15px 20px; 
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
        
        input[type="text"]:focus, 
        input[type="password"]:focus,
        input[type="email"]:focus,
        input[type="tel"]:focus { 
            outline: none;
            border-color: #4a69bd;
            box-shadow: 0 0 0 3px rgba(74, 105, 189, 0.1);
            background: #ffffff;
        }
        
        input[type="text"]::placeholder, 
        input[type="password"]::placeholder,
        input[type="email"]::placeholder,
        input[type="tel"]::placeholder {
            color: #a0aec0;
            font-weight: 400;
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
            margin-top: 10px;
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
        
        .success { 
            color: #38a169; 
            text-align: center; 
            margin-bottom: 20px;
            padding: 12px 16px;
            background: rgba(56, 161, 105, 0.1);
            border: 1px solid rgba(56, 161, 105, 0.3);
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            color: #4a5568;
            font-size: 15px;
            font-weight: 500;
        }
        
        .login-link a {
            color: #1e3799;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
            padding: 5px 10px;
            border-radius: 5px;
        }
        
        .login-link a:hover {
            color: #4a69bd;
            text-decoration: underline;
            background: rgba(30, 55, 153, 0.1);
        }

        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }

        .info-text {
            color: #718096;
            font-size: 12px;
            margin-top: 5px;
            font-style: italic;
        }

        .role-info {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: rgba(74, 105, 189, 0.1);
            border: 1px solid rgba(74, 105, 189, 0.2);
            border-radius: 10px;
            color: #4a5568;
            font-size: 14px;
            font-weight: 500;
        }

        .role-info strong {
            color: #1e3799;
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 30px 20px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            h2 {
                font-size: 28px;
            }
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
    
    <div class="register-wrapper">
        <div class="register-container">
            <div class="register-content">
                <h2>Registrasi Siswa</h2>
                
                <?php if (isset($error)): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Nama Lengkap:</label>
                        <input type="text" name="nama_lengkap" required placeholder="Masukkan nama lengkap">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Username:</label>
                            <input type="text" name="username" required placeholder="Buat username">
                            <div class="info-text">Username untuk login</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Password:</label>
                            <input type="password" name="password" required placeholder="Buat password">
                            <div class="info-text">Minimal 6 karakter</div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email:</label>
                            <input type="email" name="email" required placeholder="Masukkan email">
                        </div>
                        
                        <div class="form-group">
                            <label>No. Telepon:</label>
                            <input type="tel" name="no_telepon" required placeholder="Masukkan nomor telepon">
                        </div>
                    </div>
                    
                    <div class="role-info">
                        📝 Registrasi ini khusus untuk <strong>"SISWA"</strong>
                    </div>
                    
                    <button type="submit">Daftar sebagai Siswa</button>
                </form>
                
                <div class="login-link">
                    Sudah punya akun? <a href="index.php">Login di sini</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>