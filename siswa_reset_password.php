<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Siswa') {
    header("Location: index.php");
    exit();
}

$success = '';
$error = '';

$user_id = $_SESSION['user_id'];
$sql_request = "SELECT * FROM password_reset_requests 
                WHERE USER_ID = ? AND STATUS = 'approved' 
                AND OTP_EXPIRES > NOW() 
                AND USED_AT IS NULL 
                ORDER BY CREATED_AT DESC 
                LIMIT 1";
$stmt_request = $koneksi->prepare($sql_request);
$stmt_request->bind_param("i", $user_id);
$stmt_request->execute();
$approved_request = $stmt_request->get_result()->fetch_assoc();

if (!$approved_request) {
    header("Location: siswa_request_reset.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $otp_code = trim($_POST['otp_code']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    if (empty($otp_code)) {
        $error = "Kode OTP harus diisi!";
    } elseif ($otp_code != $approved_request['OTP_CODE']) {
        $error = "Kode OTP tidak valid!";
    } elseif (empty($new_password)) {
        $error = "Password baru harus diisi!";
    } elseif (strlen($new_password) < 6) {
        $error = "Password minimal 6 karakter!";
    } elseif ($new_password != $confirm_password) {
        $error = "Konfirmasi password tidak cocok!";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $sql_reset = "UPDATE users SET PASSWORD = ? WHERE ID = ?";
        $stmt_reset = $koneksi->prepare($sql_reset);
        $stmt_reset->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt_reset->execute()) {
            $sql_used = "UPDATE password_reset_requests SET USED_AT = NOW() WHERE ID = ?";
            $stmt_used = $koneksi->prepare($sql_used);
            $stmt_used->bind_param("i", $approved_request['ID']);
            $stmt_used->execute();
            
            $success = "Password berhasil direset! Password baru Anda: " . htmlspecialchars($new_password);
        } else {
            $error = "Gagal mereset password!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - APK BK</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Poppins', 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #434190 0%, #553c9a 100%);
            min-height: 100vh;
            color: #2d3748;
            overflow-x: hidden;
            position: relative;
        }
        
        .floating {
            position: fixed;
            border-radius: 50%;
            animation: float 4s ease-in-out infinite;
            z-index: 0;
            filter: blur(1px);
            opacity: 0.7;
        }
        
        .floating:nth-child(1) {
            width: 150px;
            height: 150px;
            top: 15%;
            left: 8%;
            background: radial-gradient(circle, rgba(255,255,255,0.8) 0%, rgba(255,255,255,0.3) 70%);
            animation-delay: 0s;
            box-shadow: 0 0 40px rgba(255,255,255,0.4);
        }
        
        .floating:nth-child(2) {
            width: 180px;
            height: 180px;
            top: 65%;
            right: 7%;
            background: radial-gradient(circle, rgba(255,255,255,0.7) 0%, rgba(255,255,255,0.2) 70%);
            animation-delay: 1.5s;
            box-shadow: 0 0 50px rgba(255,255,255,0.3);
        }
        
        .floating:nth-child(3) {
            width: 200px;
            height: 200px;
            bottom: 15%;
            left: 35%;
            background: radial-gradient(circle, rgba(255,255,255,0.6) 0%, rgba(255,255,255,0.25) 70%);
            animation-delay: 3s;
            box-shadow: 0 0 45px rgba(255,255,255,0.35);
        }
        
        @keyframes float {
            0%, 100% { 
                transform: translateY(0px) translateX(0px) rotate(0deg) scale(1);
                opacity: 0.6;
            }
            25% { 
                transform: translateY(-30px) translateX(15px) rotate(90deg) scale(1.05);
                opacity: 0.8;
            }
            50% { 
                transform: translateY(-15px) translateX(-10px) rotate(180deg) scale(1.1);
                opacity: 0.7;
            }
            75% { 
                transform: translateY(-25px) translateX(5px) rotate(270deg) scale(1.05);
                opacity: 0.9;
            }
        }
        
        .header { 
            background: rgba(255, 255, 255, 0.95);
            color: #2d3748; 
            padding: 20px 40px; 
            display: flex; 
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #667eea;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.1);
            backdrop-filter: blur(20px);
            position: relative;
            overflow: hidden;
            z-index: 10;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.05), transparent);
            transform: translateX(-100%);
        }
        
        .header:hover::before {
            animation: shimmer 2s;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 0.5px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .back-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }
        
        .back-btn:hover::before {
            left: 100%;
        }
        
        .back-btn:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .container { 
            padding: 40px; 
            max-width: 600px; 
            margin: 0 auto;
            position: relative;
            z-index: 5;
        }
        
        .content {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            border: 1px solid rgba(102, 126, 234, 0.1);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.1);
            backdrop-filter: blur(15px);
            overflow: hidden;
            animation: fadeIn 0.8s ease-out;
        }
        
        .page-header {
            background: rgba(102, 126, 234, 0.05);
            padding: 30px;
            border-radius: 16px;
            margin: 20px;
            border: 1px solid rgba(102, 126, 234, 0.15);
            animation: fadeIn 0.8s ease-out;
        }
        
        .page-header h2 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
        }
        
        .page-header p {
            color: #718096;
            font-size: 16px;
            line-height: 1.6;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin: 20px;
            font-weight: 600;
            border: 2px solid;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            animation: fadeIn 0.5s ease-out;
        }
        
        .alert-success {
            border-color: rgba(34, 197, 94, 0.4);
            color: #16a34a;
            box-shadow: 0 5px 15px rgba(34, 197, 94, 0.1);
        }
        
        .alert-error {
            border-color: rgba(239, 68, 68, 0.4);
            color: #dc2626;
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.1);
        }
        
        .alert-info {
            border-color: rgba(59, 130, 246, 0.4);
            color: #3b82f6;
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.1);
        }
        
        .form-content {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 600;
        }
        
        input {
            width: 100%;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 12px;
            color: #2d3748;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.2);
            background: rgba(255, 255, 255, 0.95);
        }
        
        .otp-input {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 5px;
        }
        
        .timer {
            text-align: center;
            color: #f59e0b;
            font-weight: 600;
            margin: 10px 0;
            font-size: 14px;
            padding: 10px;
            background: rgba(245, 158, 11, 0.1);
            border-radius: 8px;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 12px;
            color: #718096;
        }
        
        .btn {
            padding: 15px 25px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
            font-size: 16px;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            border: 2px solid rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary:hover {
            background: rgba(102, 126, 234, 0.2);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.2);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .container {
                padding: 20px;
            }
            
            .page-header {
                padding: 25px 20px;
                margin: 15px;
            }
            
            .page-header h2 {
                font-size: 24px;
            }
            
            .form-content {
                padding: 20px;
            }
            
            .floating {
                display: none;
            }
        }
        
        @media (max-width: 480px) {
            .header {
                padding: 12px 15px;
            }
            
            .header h1 {
                font-size: 20px;
            }
            
            .back-btn {
                padding: 10px 18px;
                font-size: 14px;
            }
            
            .btn {
                padding: 12px 20px;
                font-size: 14px;
            }
            
            .page-header {
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>
    
    <div class="floating"></div>
    <div class="floating"></div>
    <div class="floating"></div>
    
    <div class="header">
        <h1><i class='bx bx-lock-reset'></i> Reset Password - APK BK</h1>
        <a href="dashboard_siswa.php" class="back-btn">
            <i class='bx bx-arrow-back'></i>
            Kembali ke Dashboard
        </a>
    </div>
    
    <div class="container">
        <div class="content">
            <div class="page-header">
                <h2><i class='bx bx-key'></i> Reset Password</h2>
                <p>Masukkan kode OTP yang telah dikirim ke WhatsApp Anda dan buat password baru.</p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class='bx bx-check-circle'></i> <?php echo $success; ?>
                    <div style="margin-top: 15px;">
                        <a href="dashboard_siswa.php" class="btn btn-primary">
                            <i class='bx bx-home'></i> Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="form-content">
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class='bx bx-error-circle'></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <i class='bx bx-info-circle'></i> Request reset password Anda telah disetujui admin. Masukkan kode OTP dan password baru.
                    </div>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label><i class='bx bx-shield-alt'></i> Kode OTP *</label>
                            <input type="text" name="otp_code" class="otp-input" required 
                                   placeholder="000000" maxlength="6" 
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            <div class="timer" id="timer">
                                OTP berlaku hingga: <?php echo date('H:i', strtotime($approved_request['OTP_EXPIRES'])); ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class='bx bx-lock'></i> Password Baru *</label>
                            <input type="password" name="new_password" required 
                                   placeholder="Password baru (min. 6 karakter)" 
                                   minlength="6" id="newPassword">
                            <div class="password-strength" id="passwordStrength"></div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class='bx bx-lock-alt'></i> Konfirmasi Password *</label>
                            <input type="password" name="confirm_password" required 
                                   placeholder="Ulangi password baru" 
                                   minlength="6" id="confirmPassword">
                            <div class="password-strength" id="confirmMessage"></div>
                        </div>
                        
                        <button type="submit" name="reset_password" class="btn btn-primary">
                            <i class='bx bx-check-shield'></i> Reset Password
                        </button>
                        
                        <a href="dashboard_siswa.php" class="btn btn-secondary">
                            <i class='bx bx-arrow-back'></i> Kembali ke Dashboard
                        </a>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let expiryTime = new Date('<?php echo $approved_request['OTP_EXPIRES']; ?>').getTime();
        
        function updateTimer() {
            const now = new Date().getTime();
            const distance = expiryTime - now;
            
            if (distance < 0) {
                document.getElementById('timer').innerHTML = '<span style="color: #ef4444;">Kode OTP telah kadaluarsa! Request ulang.</span>';
                document.querySelector('button[type="submit"]').disabled = true;
                return;
            }
            
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            document.getElementById('timer').innerHTML = 
                `⏰ OTP berlaku: ${minutes} menit ${seconds} detik lagi`;
        }
        
        updateTimer();
        setInterval(updateTimer, 1000);
        
        const newPassword = document.getElementById('newPassword');
        const confirmPassword = document.getElementById('confirmPassword');
        const passwordStrength = document.getElementById('passwordStrength');
        const confirmMessage = document.getElementById('confirmMessage');
        
        newPassword.addEventListener('input', function() {
            const password = this.value;
            let strength = '';
            let color = '#ef4444';
            
            if (password.length === 0) {
                strength = '';
            } else if (password.length < 6) {
                strength = 'Password terlalu pendek';
                color = '#ef4444';
            } else if (password.length < 8) {
                strength = 'Password lemah';
                color = '#f59e0b';
            } else if (password.length < 12) {
                strength = 'Password cukup';
                color = '#3b82f6';
            } else {
                strength = 'Password kuat';
                color = '#10b981';
            }
            
            passwordStrength.textContent = strength;
            passwordStrength.style.color = color;
        });
        
        confirmPassword.addEventListener('input', function() {
            if (this.value !== newPassword.value) {
                confirmMessage.textContent = 'Password tidak cocok';
                confirmMessage.style.color = '#ef4444';
            } else {
                confirmMessage.textContent = 'Password cocok';
                confirmMessage.style.color = '#10b981';
            }
        });
        
        document.querySelector('input[name="otp_code"]').addEventListener('input', function() {
            if (this.value.length === 6) {
                this.blur();
            }
        });
    </script>
</body>
</html>