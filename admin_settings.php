<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: index.php");
    exit();
}

$sql_pending = "SELECT COUNT(*) as total FROM password_reset_requests WHERE STATUS = 'pending'";
$result_pending = $koneksi->query($sql_pending);
$pending_count = $result_pending ? $result_pending->fetch_assoc()['total'] : 0;

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $nama_lengkap = trim($_POST['nama_lengkap']);
        $email = trim($_POST['email']);
        $no_telepon = trim($_POST['no_telepon']);
        
        $sql_update = "UPDATE users SET NAMA_LENGKAP = ?, EMAIL = ?, NO_TELEPON = ? WHERE ID = ?";
        $stmt_update = $koneksi->prepare($sql_update);
        $stmt_update->bind_param("sssi", $nama_lengkap, $email, $no_telepon, $_SESSION['user_id']);
        
        if ($stmt_update->execute()) {
            $_SESSION['nama_lengkap'] = $nama_lengkap;
            $success = "Profil berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui profil!";
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
    
        $sql_check = "SELECT PASSWORD FROM users WHERE ID = ?";
        $stmt_check = $koneksi->prepare($sql_check);
        $stmt_check->bind_param("i", $_SESSION['user_id']);
        $stmt_check->execute();
        $user = $stmt_check->get_result()->fetch_assoc();
        
        if (!password_verify($current_password, $user['PASSWORD'])) {
            $error = "Password saat ini salah!";
        } else if ($new_password !== $confirm_password) {
            $error = "Password baru tidak cocok!";
        } else if (strlen($new_password) < 6) {
            $error = "Password baru minimal 6 karakter!";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_update = "UPDATE users SET PASSWORD = ? WHERE ID = ?";
            $stmt_update = $koneksi->prepare($sql_update);
            $stmt_update->bind_param("si", $hashed_password, $_SESSION['user_id']);
            
            if ($stmt_update->execute()) {
                $success = "Password berhasil diubah!";
            } else {
                $error = "Gagal mengubah password!";
            }
        }
    }
}

$sql_admin = "SELECT * FROM users WHERE ID = ?";
$stmt_admin = $koneksi->prepare($sql_admin);
$stmt_admin->bind_param("i", $_SESSION['user_id']);
$stmt_admin->execute();
$admin_data = $stmt_admin->get_result()->fetch_assoc();

if (!isset($_SESSION['nama_lengkap'])) {
    $_SESSION['nama_lengkap'] = $admin_data['NAMA_LENGKAP'] ?? 'Admin';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - APK BK</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Poppins', 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            min-height: 100vh;
            color: #f8fafc;
            overflow-x: hidden;
            position: relative;
        }
        
        .floating {
            position: fixed;
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
            z-index: 0;
            filter: blur(1px);
            opacity: 0.3;
        }
        
        .floating:nth-child(1) {
            width: 120px;
            height: 120px;
            top: 10%;
            left: 5%;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.4) 0%, rgba(139, 92, 246, 0.1) 70%);
            animation-delay: 0s;
            box-shadow: 0 0 30px rgba(139, 92, 246, 0.3);
        }
        
        .floating:nth-child(2) {
            width: 160px;
            height: 160px;
            top: 60%;
            right: 8%;
            background: radial-gradient(circle, rgba(14, 165, 233, 0.4) 0%, rgba(14, 165, 233, 0.1) 70%);
            animation-delay: 2s;
            box-shadow: 0 0 40px rgba(14, 165, 233, 0.3);
        }
        
        .floating:nth-child(3) {
            width: 200px;
            height: 200px;
            bottom: 10%;
            left: 40%;
            background: radial-gradient(circle, rgba(236, 72, 153, 0.4) 0%, rgba(236, 72, 153, 0.1) 70%);
            animation-delay: 4s;
            box-shadow: 0 0 50px rgba(236, 72, 153, 0.3);
        }
        
        @keyframes float {
            0%, 100% { 
                transform: translateY(0px) translateX(0px) rotate(0deg) scale(1);
                opacity: 0.2;
            }
            25% { 
                transform: translateY(-20px) translateX(10px) rotate(90deg) scale(1.03);
                opacity: 0.4;
            }
            50% { 
                transform: translateY(-10px) translateX(-5px) rotate(180deg) scale(1.06);
                opacity: 0.3;
            }
            75% { 
                transform: translateY(-15px) translateX(3px) rotate(270deg) scale(1.03);
                opacity: 0.5;
            }
        }
        
        .header { 
            background: rgba(15, 23, 42, 0.95);
            color: #f8fafc; 
            padding: 20px 40px; 
            display: flex; 
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #8b5cf6;
            box-shadow: 0 8px 32px rgba(139, 92, 246, 0.2);
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
            background: linear-gradient(90deg, transparent, rgba(139, 92, 246, 0.05), transparent);
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
            background: linear-gradient(135deg, #8b5cf6, #06b6d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-info span {
            font-weight: 600;
            color: #cbd5e1;
        }
        
        .logout-btn {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .logout-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }
        
        .logout-btn:hover::before {
            left: 100%;
        }
        
        .logout-btn:hover {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 25px rgba(239, 68, 68, 0.4);
        }
        
        .nav { 
            background: rgba(15, 23, 42, 0.9);
            padding: 18px 40px;
            display: flex;
            gap: 25px;
            border-bottom: 1px solid rgba(139, 92, 246, 0.2);
            backdrop-filter: blur(15px);
            overflow-x: auto;
            scrollbar-width: none;
            z-index: 10;
        }
        
        .nav::-webkit-scrollbar {
            display: none;
        }
        
        .nav a { 
            color: #94a3b8; 
            text-decoration: none; 
            padding: 14px 22px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            white-space: nowrap;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav a::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(6, 182, 212, 0.1));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
            z-index: -1;
        }
        
        .nav a:hover { 
            color: #8b5cf6;
            border-color: #8b5cf6;
            transform: translateY(-2px);
        }
        
        .nav a:hover::before {
            transform: scaleX(1);
        }
        
        .nav a.active {
            background: rgba(139, 92, 246, 0.2);
            color: #8b5cf6;
            border-color: #8b5cf6;
        }

        .nav a .badge {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            margin-left: 8px;
            animation: pulse 2s infinite;
            min-width: 20px;
            display: inline-block;
            text-align: center;
            box-shadow: 0 3px 10px rgba(239, 68, 68, 0.3);
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .container { 
            padding: 40px; 
            max-width: 1200px; 
            margin: 0 auto;
            position: relative;
            z-index: 5;
        }
        
        .page-header {
            background: rgba(15, 23, 42, 0.8);
            padding: 35px 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            border: 1px solid rgba(139, 92, 246, 0.2);
            box-shadow: 0 15px 35px rgba(139, 92, 246, 0.15);
            backdrop-filter: blur(15px);
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.8s ease-out;
        }
        
        .page-header h2 {
            font-size: 32px;
            margin-bottom: 10px;
            color: #f8fafc;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .page-header p {
            color: #94a3b8;
            font-size: 16px;
            font-weight: 500;
        }
        
        .alert {
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 25px;
            font-weight: 600;
            backdrop-filter: blur(15px);
            border: 1px solid;
            animation: fadeIn 0.5s ease-out;
        }
        
        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            border-color: rgba(34, 197, 94, 0.4);
            color: #4ade80;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.4);
            color: #f87171;
        }
        
        .settings-tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            background: rgba(15, 23, 42, 0.8);
            padding: 20px;
            border-radius: 20px;
            border: 1px solid rgba(139, 92, 246, 0.2);
            box-shadow: 0 15px 35px rgba(139, 92, 246, 0.15);
            backdrop-filter: blur(15px);
        }
        
        .settings-tab {
            padding: 18px 30px;
            background: rgba(15, 23, 42, 0.6);
            border: 2px solid rgba(139, 92, 246, 0.3);
            border-radius: 16px;
            color: #94a3b8;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            justify-content: center;
            text-align: center;
        }
        
        .settings-tab:hover {
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
            border-color: #8b5cf6;
            transform: translateY(-3px);
        }
        
        .settings-tab.active {
            background: rgba(139, 92, 246, 0.2);
            color: #8b5cf6;
            border-color: #8b5cf6;
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.25);
        }
        
        .settings-content {
            display: none;
            animation: fadeIn 0.5s ease-out;
        }
        
        .settings-content.active {
            display: block;
        }
        
        .form-container {
            background: rgba(15, 23, 42, 0.8);
            padding: 40px;
            border-radius: 20px;
            border: 1px solid rgba(139, 92, 246, 0.2);
            box-shadow: 0 15px 35px rgba(139, 92, 246, 0.15);
            backdrop-filter: blur(15px);
            margin-bottom: 30px;
        }
        
        .form-container h3 {
            font-size: 24px;
            margin-bottom: 30px;
            color: #8b5cf6;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid rgba(139, 92, 246, 0.2);
            padding-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 12px;
            color: #e2e8f0;
            font-weight: 600;
            font-size: 15px;
        }
        
        input {
            width: 100%;
            padding: 16px 20px;
            background: rgba(15, 23, 42, 0.6);
            border: 2px solid rgba(139, 92, 246, 0.3);
            border-radius: 12px;
            color: #f8fafc;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        input:focus {
            outline: none;
            border-color: #8b5cf6;
            background: rgba(15, 23, 42, 0.8);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2);
            transform: translateY(-2px);
        }
        
        input:read-only {
            background: rgba(30, 41, 59, 0.4);
            border-color: rgba(100, 116, 139, 0.3);
            color: #94a3b8;
            cursor: not-allowed;
        }
        
        .form-hint {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 8px;
            font-weight: 500;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #8b5cf6, #06b6d4);
            color: white;
            padding: 18px 35px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 700;
            width: 100%;
            margin-top: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(139, 92, 246, 0.3);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .submit-btn:hover::before {
            left: 100%;
        }
        
        .submit-btn:hover {
            background: linear-gradient(135deg, #06b6d4, #8b5cf6);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 10px 25px rgba(139, 92, 246, 0.4);
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
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
            
            .nav {
                padding: 12px 20px;
                flex-wrap: wrap;
                justify-content: center;
                gap: 12px;
            }
            
            .container {
                padding: 20px;
            }
            
            .settings-tabs {
                flex-direction: column;
            }
            
            .form-container {
                padding: 25px;
            }
            
            .page-header {
                padding: 25px 20px;
            }
            
            .page-header h2 {
                font-size: 24px;
            }
            
            .floating {
                display: none;
            }
        }
        
        @media (max-width: 480px) {
            .settings-tab {
                padding: 15px 20px;
                font-size: 14px;
            }
            
            input {
                padding: 14px 16px;
            }
            
            .submit-btn {
                padding: 16px 25px;
            }
        }
    </style>
</head>
<body>
    
    <div class="floating"></div>
    <div class="floating"></div>
    <div class="floating"></div>
    
    <div class="header">
        <h1>⚡ APK BK - Pengaturan Sistem</h1>
        <div class="user-info">
            <span>Halo, <strong><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></strong> 👑</span>
            <a href="logout.php" class="logout-btn">
                <i class='bx bx-log-out'></i>
                Logout
            </a>
        </div>
    </div>
    
    <div class="nav">
        <a href="admin_dashboard.php">
            <i class='bx bx-home'></i>
            Dashboard
        </a>
        <a href="admin_manage_guru.php">
            <i class='bx bx-user-plus'></i>
            Kelola Guru BK
        </a>
        <a href="admin_manage_siswa.php">
            <i class='bx bx-group'></i>
            Kelola Siswa
        </a>
        <a href="admin_manage_reset_requests.php">
            <i class='bx bx-message-square-dots'></i>
            Request Reset Password
        <?php if ($pending_count > 0): ?>
            <span class="badge"><?php echo $pending_count; ?></span>
        <?php endif; ?>
        </a>
        <a href="admin_reports.php">
            <i class='bx bx-bar-chart'></i>
            Laporan
        </a>
         <a href="admin_laporan_konsultasi.php">
            <i class='bx bx-bar-chart'></i>
            Laporan Konsultasi
        </a>
        <a href="admin_settings.php" class="active">
            <i class='bx bx-cog'></i>
            Pengaturan
        </a>
        <a href="profil.php">
            <i class='bx bx-face'></i>
            Profil 
        </a>
    </div>
    
    <div class="container">
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class='bx bx-check-circle'></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="page-header">
            <h2><i class='bx bx-cog'></i> Pengaturan Sistem</h2>
            <p>Kelola pengaturan akun dan sistem Bimbingan Konseling</p>
        </div>
        
        <div class="settings-tabs">
            <div class="settings-tab active" onclick="showSettings('profile')">
                <i class='bx bx-user'></i> Profil Saya
            </div>
            <div class="settings-tab" onclick="showSettings('password')">
                <i class='bx bx-lock-alt'></i> Ubah Password
            </div>
        </div>
        
        <div id="profile-settings" class="settings-content active">
            <div class="form-container">
                <h3><i class='bx bx-user-circle'></i> Edit Profil Admin</h3>
                <form method="POST" action="">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" value="<?php echo htmlspecialchars($admin_data['USERNAME'] ?? ''); ?>" readonly>
                        <div class="form-hint">Username tidak dapat diubah</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Nama Lengkap *</label>
                        <input type="text" name="nama_lengkap" value="<?php echo htmlspecialchars($admin_data['NAMA_LENGKAP'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($admin_data['EMAIL'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>No. Telepon</label>
                        <input type="tel" name="no_telepon" value="<?php echo htmlspecialchars($admin_data['NO_TELEPON'] ?? ''); ?>">
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class='bx bx-save'></i> Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
        
        <div id="password-settings" class="settings-content">
            <div class="form-container">
                <h3><i class='bx bx-shield-quarter'></i> Ubah Password</h3>
                <form method="POST" action="">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="form-group">
                        <label>Password Saat Ini *</label>
                        <input type="password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Password Baru *</label>
                        <input type="password" name="new_password" required minlength="6">
                        <div class="form-hint">Minimal 6 karakter</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Konfirmasi Password Baru *</label>
                        <input type="password" name="confirm_password" required minlength="6">
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class='bx bx-key'></i> Ubah Password
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showSettings(settingType) {
            document.querySelectorAll('.settings-content').forEach(content => {
                content.classList.remove('active');
            });
            document.querySelectorAll('.settings-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.getElementById(settingType + '-settings').classList.add('active');
            event.currentTarget.classList.add('active');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-2px)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });
            });

            const formContainers = document.querySelectorAll('.form-container');
            formContainers.forEach(container => {
                container.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                container.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>