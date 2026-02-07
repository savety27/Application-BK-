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

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Admin';

$default_photo = 'https://ui-avatars.com/api/?name=' . urlencode($nama_lengkap) . '&background=8b5cf6&color=fff&size=150';
$profile_photo = $default_photo;

$profile_files = glob('uploads/profile_' . $user_id . '.*');
if (!empty($profile_files)) {
    foreach ($profile_files as $file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $profile_photo = $file . '?t=' . time();
            break;
        }
    }
}

$sql_stats = "SELECT 
    (SELECT COUNT(*) FROM users WHERE ROLE = 'Siswa' AND STATUS = 'Aktif') as total_siswa,
    (SELECT COUNT(*) FROM users WHERE ROLE = 'Guru_BK' AND STATUS = 'Aktif') as total_guru,
    (SELECT COUNT(*) FROM konsultasi) as total_konsultasi,
    (SELECT COUNT(*) FROM konsultasi WHERE STATUS = 'Menunggu') as menunggu,
    (SELECT COUNT(*) FROM konsultasi WHERE STATUS = 'Disetujui') as disetujui,
    (SELECT COUNT(*) FROM konsultasi WHERE STATUS = 'Selesai') as selesai";

$result_stats = $koneksi->query($sql_stats);
$stats = $result_stats->fetch_assoc();

$sql_recent = "SELECT k.*, u.NAMA_LENGKAP as nama_siswa, s.KELAS, s.JURUSAN 
               FROM konsultasi k 
               JOIN siswa s ON k.SISWA_ID = s.ID 
               JOIN users u ON s.USER_ID = u.ID 
               ORDER BY k.CREATED_AT DESC LIMIT 5";
$recent_konsultasi = $koneksi->query($sql_recent);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - APK BK</title>
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
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .profile-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #8b5cf6;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
            transition: all 0.3s ease;
        }
        
        .profile-avatar:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4);
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .user-details {
            display: flex;
            flex-direction: column;
        }
        
        .user-details .welcome {
            font-size: 14px;
            color: #94a3b8;
            font-weight: 500;
        }
        
        .user-details .username {
            font-weight: 700;
            color: #f8fafc;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
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
            max-width: 1400px; 
            margin: 0 auto;
            position: relative;
            z-index: 5;
        }
        
        .welcome { 
            background: rgba(15, 23, 42, 0.8);
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 40px;
            border: 1px solid rgba(139, 92, 246, 0.2);
            box-shadow: 0 15px 35px rgba(139, 92, 246, 0.15);
            backdrop-filter: blur(15px);
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.8s ease-out;
        }
        
        .welcome::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.05) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.5s;
        }
        
        .welcome:hover::before {
            opacity: 1;
        }
        
        .welcome-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .welcome-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid #8b5cf6;
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.3);
        }
        
        .welcome-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .welcome-text h2 {
            font-size: 32px;
            margin-bottom: 10px;
            color: #f8fafc;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .welcome-text p {
            color: #94a3b8;
            font-size: 18px;
            font-weight: 500;
        }
        
        .welcome-details {
            color: #94a3b8;
            font-size: 16px;
            margin-top: 10px;
            padding-top: 15px;
            border-top: 1px solid rgba(139, 92, 246, 0.1);
        }
        
        .stats { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 30px; 
            margin-bottom: 40px;
        }
        
        .stat-card { 
            background: rgba(15, 23, 42, 0.8);
            padding: 35px 25px;
            border-radius: 20px;
            text-align: center;
            border: 1px solid rgba(139, 92, 246, 0.2);
            box-shadow: 0 15px 35px rgba(139, 92, 246, 0.15);
            backdrop-filter: blur(15px);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, #8b5cf6, #06b6d4);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 20px 45px rgba(139, 92, 246, 0.25);
        }
        
        .stat-card:hover::before {
            transform: scaleX(1);
        }
        
        .stat-card h3 { 
            color: #f8fafc; 
            font-size: 3.5em; 
            margin-bottom: 15px;
            font-weight: 800;
            background: linear-gradient(135deg, #8b5cf6, #06b6d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-card p { 
            color: #94a3b8;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
        }
        
        .recent-activity { 
            background: rgba(15, 23, 42, 0.8);
            padding: 40px;
            border-radius: 20px;
            border: 1px solid rgba(139, 92, 246, 0.2);
            box-shadow: 0 15px 35px rgba(139, 92, 246, 0.15);
            backdrop-filter: blur(15px);
            animation: fadeIn 0.8s ease-out 0.5s both;
        }
        
        .recent-activity h3 {
            font-size: 28px;
            margin-bottom: 25px;
            color: #f8fafc;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .activity-list { 
            display: grid; 
            gap: 20px; 
        }
        
        .activity-item {
            background: rgba(139, 92, 246, 0.1);
            padding: 25px;
            border-radius: 16px;
            border-left: 4px solid #8b5cf6;
            transition: all 0.3s ease;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }
        
        .activity-item:hover {
            transform: translateX(8px);
            background: rgba(139, 92, 246, 0.15);
        }
        
        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .student-name { 
            font-weight: 700; 
            color: #8b5cf6;
            font-size: 18px;
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-menunggu { 
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        
        .status-disetujui { 
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .status-selesai { 
            background: linear-gradient(135deg, #06b6d4, #0891b2);
            color: white;
        }
        
        .topik { 
            color: #f8fafc; 
            margin-bottom: 10px;
            font-weight: 600;
            font-size: 16px;
        }
        
        .konsultasi-info { 
            color: #94a3b8; 
            font-size: 14px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .quick-actions { 
            background: rgba(15, 23, 42, 0.8);
            padding: 40px;
            border-radius: 20px;
            border: 1px solid rgba(139, 92, 246, 0.2);
            box-shadow: 0 15px 35px rgba(139, 92, 246, 0.15);
            backdrop-filter: blur(15px);
            margin-top: 40px;
            animation: fadeIn 0.8s ease-out 0.7s both;
        }
        
        .quick-actions h3 {
            font-size: 28px;
            margin-bottom: 25px;
            color: #f8fafc;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .actions-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 25px; 
        }
        
        .action-btn { 
            background: linear-gradient(135deg, #8b5cf6, #06b6d4);
            color: white; 
            padding: 25px; 
            text-align: center; 
            border-radius: 16px; 
            text-decoration: none; 
            display: block;
            font-weight: 600;
            font-size: 16px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(139, 92, 246, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        
        .action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .action-btn:hover::before {
            left: 100%;
        }
        
        .action-btn:hover { 
            background: linear-gradient(135deg, #06b6d4, #8b5cf6);
            transform: translateY(-8px) scale(1.03);
            box-shadow: 0 15px 35px rgba(139, 92, 246, 0.4);
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
            
            .user-info {
                flex-direction: column;
                gap: 15px;
            }
            
            .user-profile {
                flex-direction: column;
                text-align: center;
            }
            
            .welcome-header {
                flex-direction: column;
                text-align: center;
            }
            
            .welcome-avatar {
                width: 70px;
                height: 70px;
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
            
            .stats {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .actions-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .welcome {
                padding: 25px;
            }
            
            .welcome-text h2 {
                font-size: 24px;
            }
            
            .stat-card {
                padding: 25px 20px;
            }
            
            .stat-card h3 {
                font-size: 2.8em;
            }
            
            .recent-activity, .quick-actions {
                padding: 25px;
            }
            
            .floating {
                display: none;
            }
            
            .activity-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    
    <div class="floating"></div>
    <div class="floating"></div>
    <div class="floating"></div>
    
    <div class="header">
        <a href="halaman utama.php" style="text-decoration: none; color: inherit;">
            <h1>⚡ APK BK - Admin Dashboard</h1>
        </a>
        <div class="user-info">
            <div class="user-profile">
                <div class="profile-avatar">
                    <img src="<?php echo $profile_photo; ?>" alt="Foto Profil <?php echo htmlspecialchars($nama_lengkap); ?>"
                         onerror="this.onerror=null; this.src='<?php echo $default_photo; ?>'">
                </div>
                <div class="user-details">
                    <div class="username">
                        <i class='bx bx-user'></i>
                        <?php echo htmlspecialchars($nama_lengkap); ?>
                    </div>
                </div>
            </div>
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
        <a href="admin_settings.php">
            <i class='bx bx-cog'></i>
            Pengaturan
        </a>
        <a href="profil.php">
            <i class='bx bx-face'></i>
            Profil 
        </a>
    </div>
    
    <div class="container">
        <div class="welcome">
            <div class="welcome-header">
                <div class="welcome-avatar">
                    <img src="<?php echo $profile_photo; ?>" alt="Foto Profil <?php echo htmlspecialchars($nama_lengkap); ?>"
                         onerror="this.onerror=null; this.src='<?php echo $default_photo; ?>'">
                </div>
                <div class="welcome-text">
                    <h2>Selamat Datang, <?php echo htmlspecialchars($nama_lengkap); ?>! 🎉</h2>
                    <p>Administrasi sistem Bimbingan Konseling SMKN 7 Batam</p>
                </div>
            </div>
            <div class="welcome-details">
                <p><i class='bx bx-info-circle'></i> Gunakan menu di atas untuk mengakses fitur lengkap sistem administrasi.</p>
            </div>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <h3><?php echo $stats['total_siswa']; ?></h3>
                <p>Total Siswa</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['total_guru']; ?></h3>
                <p>Guru BK</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['total_konsultasi']; ?></h3>
                <p>Total Konsultasi</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['menunggu']; ?></h3>
                <p>Menunggu Approve</p>
            </div>
        </div>
        
        <div class="recent-activity">
            <h3><i class='bx bx-time'></i> Konsultasi Terbaru</h3>
            <div class="activity-list">
                <?php if ($recent_konsultasi->num_rows > 0): ?>
                    <?php while($konsul = $recent_konsultasi->fetch_assoc()): ?>
                    <div class="activity-item">
                        <div class="activity-header">
                            <div class="student-name"><?php echo htmlspecialchars($konsul['nama_siswa']); ?></div>
                            <span class="status-badge status-<?php echo strtolower($konsul['STATUS']); ?>">
                                <?php echo $konsul['STATUS']; ?>
                            </span>
                        </div>
                        <div class="topik"><?php echo htmlspecialchars($konsul['TOPIK_KONSULTASI']); ?></div>
                        <div class="konsultasi-info">
                            <span><?php echo htmlspecialchars($konsul['KELAS']); ?> - <?php echo htmlspecialchars($konsul['JURUSAN']); ?></span>
                            <span><?php echo date('d/m/Y H:i', strtotime($konsul['CREATED_AT'])); ?></span>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="activity-item">
                        <p style="text-align: center; color: #94a3b8;">Belum ada konsultasi</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="quick-actions">
            <h3><i class='bx bx-rocket'></i> Aksi Cepat</h3>
            <div class="actions-grid">
                <a href="admin_manage_guru.php" class="action-btn">
                    <i class='bx bx-user-plus'></i>
                    Kelola Guru BK
                </a>
                <a href="admin_manage_siswa.php" class="action-btn">
                    <i class='bx bx-group'></i>
                    Kelola Siswa
                </a>
                <a href="admin_reports.php" class="action-btn">
                    <i class='bx bx-bar-chart'></i>
                    Lihat Laporan
                </a>
                <a href="admin_settings.php" class="action-btn">
                    <i class='bx bx-cog'></i>
                    Pengaturan Sistem
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.body.classList.add('loaded');
            
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-12px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
            
            const actionButtons = document.querySelectorAll('.action-btn');
            actionButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    ripple.classList.add('ripple');
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
            
            setInterval(() => {
                const avatarImages = document.querySelectorAll('.profile-avatar img, .welcome-avatar img');
                avatarImages.forEach(img => {
                    const src = img.src.split('?')[0];
                    img.src = src + '?t=' + new Date().getTime();
                });
            }, 30000);
        });

        const style = document.createElement('style');
        style.textContent = `
            .ripple {
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.6);
                transform: scale(0);
                animation: ripple-animation 0.6s linear;
            }
            
            @keyframes ripple-animation {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
            
            body.loaded .welcome {
                animation: fadeIn 0.8s ease-out;
            }
            
            body.loaded .stat-card {
                animation: slideUp 0.6s ease-out;
            }
            
            body.loaded .recent-activity {
                animation: fadeIn 0.8s ease-out 0.5s both;
            }
            
            body.loaded .quick-actions {
                animation: fadeIn 0.8s ease-out 0.7s both;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>