<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Siswa') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sql_request = "SELECT prr.*, u.NAMA_LENGKAP 
                FROM password_reset_requests prr 
                JOIN users u ON prr.USER_ID = u.ID 
                WHERE prr.USER_ID = ? AND prr.STATUS = 'approved' 
                AND prr.OTP_EXPIRES > NOW() 
                AND prr.USED_AT IS NULL 
                ORDER BY prr.CREATED_AT DESC 
                LIMIT 1";
$stmt_request = $koneksi->prepare($sql_request);
$stmt_request->bind_param("i", $user_id);
$stmt_request->execute();
$approved_request = $stmt_request->get_result()->fetch_assoc();

if (!$approved_request) {
    header("Location: siswa_request_reset.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Disetujui - APK BK</title>
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
            max-width: 800px; 
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
        
        .struk-card {
            background: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 16px;
            margin: 20px;
            border: 2px solid #10b981;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.2);
            transition: all 0.3s ease;
            animation: slideUp 0.6s ease-out;
            position: relative;
            overflow: hidden;
        }
        
        .struk-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .struk-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .struk-header .icon {
            font-size: 64px;
            color: #10b981;
            margin-bottom: 15px;
        }
        
        .struk-header h3 {
            color: #059669;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .struk-info {
            background: rgba(16, 185, 129, 0.05);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(16, 185, 129, 0.1);
        }
        
        .info-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .info-label {
            color: #4a5568;
            font-weight: 600;
        }
        
        .info-value {
            color: #2d3748;
            font-weight: 600;
            text-align: right;
        }
        
        .btn-reset {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 15px 25px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 16px;
            width: 100%;
            margin-top: 20px;
        }
        
        .btn-reset::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-reset:hover::before {
            left: 100%;
        }
        
        .btn-reset:hover {
            background: linear-gradient(135deg, #059669, #10b981);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4);
        }
        
        .timer-warning {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin-top: 20px;
            border: 1px solid rgba(245, 158, 11, 0.3);
            font-weight: 600;
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
            
            .struk-card {
                padding: 20px;
                margin: 15px;
            }
            
            .page-header {
                padding: 25px 20px;
                margin: 15px;
            }
            
            .page-header h2 {
                font-size: 24px;
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
            
            .struk-card {
                padding: 15px;
            }
            
            .btn-reset {
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
        <h1><i class='bx bx-check-shield'></i> Request Disetujui - APK BK</h1>
        <a href="dashboard_siswa.php" class="back-btn">
            <i class='bx bx-arrow-back'></i>
            Kembali ke Dashboard
        </a>
    </div>
    
    <div class="container">
        <div class="content">
            <div class="page-header">
                <h2><i class='bx bx-party'></i> Request Disetujui!</h2>
                <p>Request reset password Anda telah disetujui oleh admin. Silakan lanjutkan proses reset password dengan menekan tombol di bawah.</p>
            </div>
            
            <div class="struk-card">
                <div class="struk-header">
                    <div class="icon">
                        <i class='bx bx-check-circle'></i>
                    </div>
                    <h3>REQUEST DISETUJUI</h3>
                    <p style="color: #718096;">Request reset password Anda telah diverifikasi dan disetujui</p>
                </div>
                
                <div class="struk-info">
                    <div class="info-item">
                        <span class="info-label">Nama Siswa:</span>
                        <span class="info-value"><?php echo htmlspecialchars($approved_request['NAMA_LENGKAP']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tanggal Request:</span>
                        <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($approved_request['CREATED_AT'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status:</span>
                        <span class="info-value" style="color: #059669; font-weight: 700;">DISETUJUI</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Alasan Reset:</span>
                        <span class="info-value"><?php echo htmlspecialchars($approved_request['ALASAN']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Kode OTP:</span>
                        <span class="info-value" style="color: #667eea; font-weight: 700;">
                            <i class='bx bx-shield-alt'></i> 
                            Akan dikirim via WhatsApp
                        </span>
                    </div>
                </div>
                
                <div class="timer-warning" id="timer">
                    ⏰ OTP berlaku hingga: <?php echo date('H:i', strtotime($approved_request['OTP_EXPIRES'])); ?>
                </div>
                
                <a href="siswa_reset_password.php" class="btn-reset">
                    <i class='bx bx-lock-reset'></i>
                    Lanjutkan Reset Password
                </a>
            </div>
        </div>
    </div>

    <script>
        let expiryTime = new Date('<?php echo $approved_request['OTP_EXPIRES']; ?>').getTime();
        
        function updateTimer() {
            const now = new Date().getTime();
            const distance = expiryTime - now;
            
            if (distance < 0) {
                document.getElementById('timer').innerHTML = 
                    '<span style="color: #ef4444;">❌ Kode OTP telah kadaluarsa! Silakan request ulang.</span>';
                document.querySelector('.btn-reset').style.display = 'none';
                return;
            }
            
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            document.getElementById('timer').innerHTML = 
                `⏰ OTP berlaku: ${minutes} menit ${seconds} detik lagi`;
        }
        
        updateTimer();
        setInterval(updateTimer, 1000);
    </script>
</body>
</html>