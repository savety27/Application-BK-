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
$sql_check = "SELECT * FROM password_reset_requests 
              WHERE USER_ID = ? AND (
                  STATUS = 'pending' 
                  OR (STATUS = 'approved' AND USED_AT IS NULL AND OTP_EXPIRES > NOW())
              )
              ORDER BY CREATED_AT DESC 
              LIMIT 1";
$stmt_check = $koneksi->prepare($sql_check);
$stmt_check->bind_param("i", $user_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$existing_request = $result_check->fetch_assoc();

$is_expired = false;
if ($existing_request && $existing_request['STATUS'] == 'approved' && $existing_request['OTP_EXPIRES']) {
    $current_time = time();
    $expiry_time = strtotime($existing_request['OTP_EXPIRES']);
    if ($current_time > $expiry_time) {
        $is_expired = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['request_reset'])) {
        $alasan = trim($_POST['alasan']);
        
        if (empty($alasan)) {
            $error = "Harap berikan alasan reset password!";
        } elseif ($existing_request && $existing_request['STATUS'] == 'pending') {
            $error = "Anda sudah memiliki request yang menunggu persetujuan admin!";
        } elseif ($existing_request && $existing_request['STATUS'] == 'approved' && !$is_expired) {
            $error = "Request Anda sudah disetujui! Gunakan OTP yang dikirim ke WhatsApp Anda.";
        } else {
            $sql_request = "INSERT INTO password_reset_requests (USER_ID, ALASAN, STATUS, CREATED_AT) VALUES (?, ?, 'pending', NOW())";
            $stmt_request = $koneksi->prepare($sql_request);
            $stmt_request->bind_param("is", $user_id, $alasan);
            
            if ($stmt_request->execute()) {
                $success = "✅ Request reset password berhasil dikirim! Tunggu persetujuan admin.";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $error = "❌ Gagal mengirim request. Silakan coba lagi.";
            }
            $stmt_request->close();
        }
    }
    
    if (isset($_POST['gunakan_otp'])) {
        if ($existing_request && $existing_request['STATUS'] == 'approved' && !$is_expired) {
            header("Location: siswa_reset_password.php?request_id=" . $existing_request['ID']);
            exit();
        }
    }
}

$sql_history = "SELECT * FROM password_reset_requests WHERE USER_ID = ? ORDER BY CREATED_AT DESC";
$stmt_history = $koneksi->prepare($sql_history);
$stmt_history->bind_param("i", $user_id);
$stmt_history->execute();
$result_history = $stmt_history->get_result();
$request_history = [];
while ($row = $result_history->fetch_assoc()) {
    $request_history[] = $row;
}
$stmt_history->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Reset Password - APK BK</title>
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
            max-width: 1200px; 
            margin: 0 auto;
            position: relative;
            z-index: 5;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
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
        
        .card {
            background: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 16px;
            margin: 20px;
            border: 1px solid rgba(102, 126, 234, 0.1);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.08);
            transition: all 0.3s ease;
            animation: slideUp 0.6s ease-out;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.15);
        }
        
        .card h2 {
            color: #2d3748;
            margin-bottom: 20px;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
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
        
        textarea, input {
            width: 100%;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 12px;
            color: #2d3748;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            resize: vertical;
            transition: all 0.3s ease;
        }
        
        textarea:focus, input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.2);
            background: rgba(255, 255, 255, 0.95);
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
            gap: 8px;
            position: relative;
            overflow: hidden;
            font-size: 16px;
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
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #059669, #10b981);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
        }
        
        .status-pending {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }
        
        .status-approved {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .status-rejected {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .status-expired {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
        }
        
        .current-request {
            background: rgba(255, 251, 240, 0.9);
            padding: 25px;
            border-radius: 16px;
            margin-bottom: 25px;
            border: 2px solid #f59e0b;
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.1);
        }
        
        .current-request.expired {
            border-color: #6b7280;
            background: rgba(248, 250, 252, 0.9);
        }
        
        .request-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(245, 158, 11, 0.1);
        }
        
        .current-request.expired .info-item {
            border-bottom: 1px solid rgba(107, 114, 128, 0.1);
        }
        
        .info-label {
            color: #4a5568;
            font-weight: 600;
        }
        
        .info-value {
            color: #2d3748;
            font-weight: 600;
        }
        
        .expired-warning {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin-top: 15px;
            border: 1px solid rgba(239, 68, 68, 0.3);
            font-weight: 600;
        }
        
        .history-item {
            background: rgba(255, 251, 240, 0.8);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
            transition: all 0.3s ease;
            animation: slideUp 0.6s ease-out;
        }
        
        .history-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.15);
        }
        
        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .history-date {
            color: #718096;
            font-size: 14px;
            font-weight: 600;
        }
        
        .history-alasan {
            color: #4a5568;
            margin-bottom: 10px;
            line-height: 1.5;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 40px;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 16px;
            margin: 20px;
            border: 2px dashed rgba(102, 126, 234, 0.3);
            animation: fadeIn 0.8s ease-out;
        }
        
        .empty-state h3 {
            color: #667eea;
            font-size: 24px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .empty-state p {
            color: #718096;
            margin-bottom: 10px;
            line-height: 1.6;
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
            
            .card {
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
            
            .request-info {
                grid-template-columns: 1fr;
            }
            
            .history-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
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
            
            .card {
                padding: 15px;
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
                <p>Ajukan request reset password dan tunggu persetujuan admin. Kode OTP akan dikirim ke WhatsApp Anda setelah disetujui.</p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class='bx bx-check-circle'></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class='bx bx-error-circle'></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($existing_request && !$is_expired): ?>
                <div class="current-request">
                    <h2><i class='bx bx-time'></i> Request Aktif</h2>
                    <div class="request-info">
                        <div class="info-item">
                            <span class="info-label">Status:</span>
                            <span class="status-badge status-<?php echo $existing_request['STATUS']; ?>">
                                <?php echo strtoupper($existing_request['STATUS']); ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Tanggal Request:</span>
                            <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($existing_request['CREATED_AT'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Alasan:</span>
                            <span class="info-value"><?php echo htmlspecialchars($existing_request['ALASAN']); ?></span>
                        </div>
                        <?php if ($existing_request['STATUS'] == 'approved'): ?>
                        <div class="info-item">
                            <span class="info-label">OTP Expires:</span>
                            <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($existing_request['OTP_EXPIRES'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($existing_request['STATUS'] == 'pending'): ?>
                        <div class="alert alert-info">
                            <i class='bx bx-time'></i> Request Anda sedang menunggu persetujuan admin. 
                            Silakan refresh halaman ini untuk mengecek status terbaru.
                        </div>
                    <?php elseif ($existing_request['STATUS'] == 'approved'): ?>
                        <form method="POST" action="">
                            <button type="submit" name="gunakan_otp" class="btn btn-success">
                                <i class='bx bx-check-shield'></i> Gunakan OTP untuk Reset Password
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <h2><i class='bx bx-edit-alt'></i> Request Reset Password</h2>
                
                <?php if (!$existing_request || $existing_request['STATUS'] == 'rejected' || $is_expired): ?>
                    <form method="POST" action="" id="formReset">
                        <div class="form-group">
                            <label><i class='bx bx-edit'></i> Alasan Reset Password *</label>
                            <textarea 
                                name="alasan" 
                                rows="4" 
                                placeholder="Jelaskan mengapa Anda perlu reset password..." 
                                required
                                style="cursor: text;"
                                id="alasanTextarea"
                            ></textarea>
                        </div>
                        
                        <button type="submit" name="request_reset" class="btn btn-primary">
                            <i class='bx bx-send'></i>
                            <?php echo $is_expired ? 'Buat Request Baru' : 'Kirim Request ke Admin'; ?>
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class='bx bx-info-circle'></i> Anda sudah memiliki request yang aktif. 
                        <?php if ($existing_request['STATUS'] == 'pending'): ?>
                            Tunggu persetujuan admin.
                        <?php else: ?>
                            Request sudah disetujui. Klik tombol "Gunakan OTP untuk Reset Password" di atas.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2><i class='bx bx-history'></i> Riwayat Request</h2>
                
                <?php if (count($request_history) > 0): ?>
                    <?php foreach($request_history as $request): 
                        $is_history_expired = false;
                        if ($request['STATUS'] == 'approved' && $request['OTP_EXPIRES']) {
                            $current_time = time();
                            $expiry_time = strtotime($request['OTP_EXPIRES']);
                            if ($current_time > $expiry_time && empty($request['USED_AT'])) {
                                $is_history_expired = true;
                            }
                        }
                    ?>
                    <div class="history-item">
                        <div class="history-header">
                            <span class="status-badge status-<?php echo $request['STATUS']; ?>">
                                <?php echo strtoupper($request['STATUS']); ?>
                            </span>
                            <span class="history-date">
                                <i class='bx bx-calendar'></i> 
                                <?php echo date('d/m/Y H:i', strtotime($request['CREATED_AT'])); ?>
                            </span>
                        </div>
                        <div class="history-alasan">
                            <strong><i class='bx bx-message-alt'></i> Alasan:</strong> <?php echo htmlspecialchars($request['ALASAN']); ?>
                        </div>
                        <?php if ($is_history_expired): ?>
                            <div style="color: #dc2626; font-weight: 600; margin-top: 10px;">
                                <i class='bx bx-time-five'></i> OTP sudah kadaluarsa
                            </div>
                        <?php elseif ($request['STATUS'] == 'approved' && !empty($request['OTP_CODE'])): ?>
                            <div style="color: #059669; font-weight: 600; margin-top: 10px;">
                                <i class='bx bx-check'></i> Request disetujui. Cek WhatsApp untuk kode OTP.
                            </div>
                        <?php elseif ($request['STATUS'] == 'rejected' && !empty($request['CATATAN_ADMIN'])): ?>
                            <div style="color: #dc2626; margin-top: 10px;">
                                <strong><i class='bx bx-message-error'></i> Catatan Admin:</strong> <?php echo htmlspecialchars($request['CATATAN_ADMIN']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class='bx bx-history' style="font-size: 48px; margin-bottom: 15px;"></i>
                        <h3>Belum Ada Riwayat</h3>
                        <p>Belum ada riwayat request reset password</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.getElementById('alasanTextarea');
            if (textarea) {
                setTimeout(() => {
                    textarea.focus();
                }, 500);
            }

            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });

            const historyItems = document.querySelectorAll('.history-item');
            historyItems.forEach((item, index) => {
                item.style.animationDelay = `${index * 0.1}s`;
            });

            const form = document.getElementById('formReset');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const alasan = document.querySelector('textarea[name="alasan"]').value.trim();
                    if (!alasan) {
                        e.preventDefault();
                        alert('Harap isi alasan reset password!');
                        document.querySelector('textarea[name="alasan"]').focus();
                    }
                });
            }

            if (textarea) {
                textarea.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
                
                textarea.addEventListener('mousedown', function(e) {
                    e.stopPropagation();
                });
            }
            
            <?php if ($existing_request && $existing_request['STATUS'] == 'pending' && !$is_expired): ?>
            setTimeout(() => {
                window.location.reload();
            }, 30000); 
            <?php endif; ?>
        });
    </script>
</body>
</html>