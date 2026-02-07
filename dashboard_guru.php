<?php
session_start();
include 'koneksi.php';
include 'notifikasi_helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Guru_BK') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Guru BK';

$default_photo = 'https://ui-avatars.com/api/?name=' . urlencode($nama_lengkap) . '&background=3182ce&color=fff&size=150';
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

$guru = null;
$total_konsul = 0;
$menunggu = 0;
$disetujui = 0;
$selesai = 0;
$jumlah_notif_konsultasi = 0;

$sql_guru = "SELECT g.* FROM guru_bk g WHERE g.user_id = ?";
$stmt_guru = $koneksi->prepare($sql_guru);
if ($stmt_guru) {
    $stmt_guru->bind_param("i", $user_id);
    $stmt_guru->execute();
    $guru_result = $stmt_guru->get_result();
    $guru = $guru_result->fetch_assoc();
    $stmt_guru->close();
}

if ($guru && isset($guru['ID'])) {
    $guru_id = $guru['ID'];
    
    $sql_menunggu = "SELECT COUNT(*) as jumlah FROM konsultasi 
                    WHERE STATUS = 'Menunggu' 
                    AND (PILIHAN_GURU_1 = ? OR PILIHAN_GURU_2 = ?)";
    $stmt_menunggu = $koneksi->prepare($sql_menunggu);
    $stmt_menunggu->bind_param("ii", $guru_id, $guru_id);
    $stmt_menunggu->execute();
    $result_menunggu = $stmt_menunggu->get_result()->fetch_assoc();
    $jumlah_notif_konsultasi = $result_menunggu['jumlah'] ?? 0;
    
    $sql_stats = "SELECT 
        COUNT(*) as total_konsul,
        SUM(CASE WHEN status = 'Menunggu' THEN 1 ELSE 0 END) as menunggu,
        SUM(CASE WHEN status = 'Disetujui' THEN 1 ELSE 0 END) as disetujui,
        SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END) as selesai
        FROM konsultasi 
        WHERE (guru_bk_id = ?)  
        OR (status = 'Menunggu' AND (PILIHAN_GURU_1 = ? OR PILIHAN_GURU_2 = ?))"; 
    
    $stmt_stats = $koneksi->prepare($sql_stats);
    if ($stmt_stats) {
        $stmt_stats->bind_param("iii", $guru['ID'], $guru['ID'], $guru['ID']);
        $stmt_stats->execute();
        $stats_result = $stmt_stats->get_result();
        $stats = $stats_result->fetch_assoc();
        
        if ($stats) {
            $total_konsul = $stats['total_konsul'] ?? 0;
            $menunggu = $stats['menunggu'] ?? 0;
            $disetujui = $stats['disetujui'] ?? 0;
            $selesai = $stats['selesai'] ?? 0;
        }
        $stmt_stats->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Guru - APK BK</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Poppins', 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #1a365d 0%, #2d3748 50%, #4a5568 100%);
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
            opacity: 0.5;
        }
        
        .floating:nth-child(1) {
            width: 150px;
            height: 150px;
            top: 15%;
            left: 8%;
            background: radial-gradient(circle, rgba(74, 222, 128, 0.3) 0%, rgba(74, 222, 128, 0.1) 70%);
            animation-delay: 0s;
            box-shadow: 0 0 40px rgba(74, 222, 128, 0.3);
        }
        
        .floating:nth-child(2) {
            width: 180px;
            height: 180px;
            top: 65%;
            right: 7%;
            background: radial-gradient(circle, rgba(96, 165, 250, 0.3) 0%, rgba(96, 165, 250, 0.1) 70%);
            animation-delay: 1.5s;
            box-shadow: 0 0 50px rgba(96, 165, 250, 0.3);
        }
        
        .floating:nth-child(3) {
            width: 200px;
            height: 200px;
            bottom: 15%;
            left: 35%;
            background: radial-gradient(circle, rgba(248, 113, 113, 0.3) 0%, rgba(248, 113, 113, 0.1) 70%);
            animation-delay: 3s;
            box-shadow: 0 0 45px rgba(248, 113, 113, 0.3);
        }
        
        @keyframes float {
            0%, 100% { 
                transform: translateY(0px) translateX(0px) rotate(0deg) scale(1);
                opacity: 0.4;
            }
            25% { 
                transform: translateY(-30px) translateX(15px) rotate(90deg) scale(1.05);
                opacity: 0.6;
            }
            50% { 
                transform: translateY(-15px) translateX(-10px) rotate(180deg) scale(1.1);
                opacity: 0.5;
            }
            75% { 
                transform: translateY(-25px) translateX(5px) rotate(270deg) scale(1.05);
                opacity: 0.7;
            }
        }
        
        .header { 
            background: rgba(255, 255, 255, 0.95);
            color: #2d3748; 
            padding: 20px 40px; 
            display: flex; 
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #3182ce;
            box-shadow: 0 8px 32px rgba(49, 130, 206, 0.1);
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
            background: linear-gradient(90deg, transparent, rgba(49, 130, 206, 0.05), transparent);
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
            background: linear-gradient(135deg, #3182ce, #2b6cb0);
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
            border: 3px solid #3182ce;
            box-shadow: 0 4px 15px rgba(49, 130, 206, 0.3);
            transition: all 0.3s ease;
        }
        
        .profile-avatar:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(49, 130, 206, 0.4);
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
            color: #718096;
            font-weight: 500;
        }
        
        .user-details .username {
            font-weight: 700;
            color: #2d3748;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .user-info span {
            font-weight: 600;
            color: #4a5568;
        }
        
        .logout-btn {
            background: linear-gradient(135deg, #e53e3e, #c53030);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(229, 62, 62, 0.3);
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
            background: linear-gradient(135deg, #c53030, #e53e3e);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 25px rgba(229, 62, 62, 0.4);
        }
        
        .nav { 
            background: rgba(255, 255, 255, 0.9);
            padding: 18px 40px;
            display: flex;
            gap: 25px;
            border-bottom: 1px solid rgba(49, 130, 206, 0.1);
            backdrop-filter: blur(15px);
            overflow-x: auto;
            scrollbar-width: none;
            z-index: 10;
        }
        
        .nav::-webkit-scrollbar {
            display: none;
        }
        
        .nav a { 
            color: #718096; 
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
            background: linear-gradient(135deg, rgba(49, 130, 206, 0.1), rgba(43, 108, 176, 0.1));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
            z-index: -1;
        }
        
        .nav a:hover { 
            color: #3182ce;
            border-color: #3182ce;
            transform: translateY(-2px);
        }
        
        .nav a:hover::before {
            transform: scaleX(1);
        }
        
        .nav-badge {
            position: relative;
        }
        
        .badge {
            position: absolute;
            top: 2px;
            right: 2px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s infinite;
            box-shadow: 0 2px 8px rgba(255, 107, 107, 0.4);
            border: 2px solid white;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .action-badge {
            margin-left: 8px;
            background: white !important;
            color: #3182ce !important;
            padding: 1px 6px !important;
            border-radius: 10px;
            font-size: 11px !important;
            font-weight: 600;
            min-width: 18px;
            text-align: center;
            display: inline-block;
        }
        
        .container { 
            padding: 40px; 
            max-width: 1400px; 
            margin: 0 auto;
            position: relative;
            z-index: 5;
        }
        
        .welcome { 
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 40px;
            border: 1px solid rgba(49, 130, 206, 0.1);
            box-shadow: 0 15px 35px rgba(49, 130, 206, 0.1);
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
            background: radial-gradient(circle, rgba(49, 130, 206, 0.05) 0%, transparent 70%);
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
            border: 4px solid #3182ce;
            box-shadow: 0 8px 25px rgba(49, 130, 206, 0.3);
        }
        
        .welcome-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .welcome-text h2 {
            font-size: 32px;
            margin-bottom: 10px;
            color: #2d3748;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .welcome-text p {
            color: #718096;
            font-size: 18px;
            font-weight: 500;
        }
        
        .welcome-details {
            color: #718096;
            font-size: 16px;
            margin-top: 10px;
            padding-top: 15px;
            border-top: 1px solid rgba(49, 130, 206, 0.1);
        }
        
        .stats { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 30px; 
            margin-bottom: 40px;
        }
        
        .stat-card { 
            background: rgba(255, 253, 231, 0.95); 
            padding: 35px 25px;
            border-radius: 20px;
            text-align: center;
            border: 1px solid rgba(49, 130, 206, 0.1);
            box-shadow: 0 15px 35px rgba(49, 130, 206, 0.1);
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
            background: linear-gradient(90deg, #3182ce, #2b6cb0);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 20px 45px rgba(49, 130, 206, 0.2);
        }
        
        .stat-card:hover::before {
            transform: scaleX(1);
        }
        
        .stat-card h3 { 
            color: #2d3748; 
            font-size: 3.5em; 
            margin-bottom: 15px;
            font-weight: 800;
            background: linear-gradient(135deg, #3182ce, #2b6cb0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-card p { 
            color: #718096;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
        }
        
        .quick-actions { 
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            border: 1px solid rgba(49, 130, 206, 0.1);
            box-shadow: 0 15px 35px rgba(49, 130, 206, 0.1);
            backdrop-filter: blur(15px);
            animation: fadeIn 0.8s ease-out 0.5s both;
        }
        
        .quick-actions h3 {
            font-size: 28px;
            margin-bottom: 25px;
            color: #2d3748;
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
            background: linear-gradient(135deg, #3182ce, #2b6cb0);
            color: white; 
            padding: 25px; 
            text-align: center; 
            border-radius: 16px; 
            text-decoration: none; 
            display: block;
            font-weight: 600;
            font-size: 16px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(49, 130, 206, 0.3);
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
            background: linear-gradient(135deg, #2b6cb0, #3182ce);
            transform: translateY(-8px) scale(1.03);
            box-shadow: 0 15px 35px rgba(49, 130, 206, 0.4);
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
            
            .quick-actions {
                padding: 25px;
            }
            
            .floating {
                display: none;
            }
            
            .badge {
                top: 1px;
                right: 1px;
                width: 16px;
                height: 16px;
                font-size: 9px;
            }
            
            .action-badge {
                margin-left: 6px;
                padding: 1px 4px !important;
                font-size: 10px !important;
            }
        }
        
        @media (max-width: 480px) {
            .header h1 {
                font-size: 22px;
            }
            
            .nav a {
                padding: 12px 16px;
                font-size: 13px;
            }
            
            .welcome {
                padding: 20px;
            }
            
            .stat-card {
                padding: 20px 15px;
            }
            
            .quick-actions {
                padding: 20px;
            }
            
            .action-btn {
                padding: 20px;
                font-size: 14px;
            }
            
            .badge {
                top: 0px;
                right: 0px;
                width: 14px;
                height: 14px;
                font-size: 8px;
                border: 1px solid white;
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
            <h1>👨‍🏫 APK BK - Dashboard Guru BK</h1>
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
        <a href="dashboard_guru.php">
            <i class='bx bx-home'></i>
            Dashboard
        </a>
        <a href="approve_konsultasi.php" class="nav-badge">
            <i class='bx bx-check-shield'></i>
            Approve Konsultasi
            <?php if ($jumlah_notif_konsultasi > 0): ?>
                <span class="badge"><?php echo $jumlah_notif_konsultasi; ?></span>
            <?php endif; ?>
        </a>
        <a href="sesi_konsultasi.php">
            <i class='bx bx-conversation'></i>
            Sesi Konsultasi
        </a>
        <a href="kelola_jadwal.php">
            <i class='bx bx-calendar'></i>
            Kelola Jadwal
        </a>
        <a href="review_form.php">
            <i class='bx bx-clipboard'></i>
            Review Form
        </a>
         <a href="dokumen/pemanggilan_ortu.php">
            <i class='bx bx-file'></i>
            Dokumen 
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
                    <p>
                        <?php 
                        if ($guru) {
                            echo "NIP: " . ($guru['NIP'] ?? '-') . " | Pengalaman: " . ($guru['PENGALAMAN_MENGAJAR'] ?? '-') . " ";
                        } else {
                            echo "Data guru belum lengkap. Silakan lengkapi profil Anda.";
                        }
                        ?>
                    </p>
                </div>
            </div>
            <div class="welcome-details">
                <p><i class='bx bx-info-circle'></i> Gunakan menu di atas untuk mengakses fitur lengkap aplikasi BK.</p>
                <?php if ($jumlah_notif_konsultasi > 0): ?>
                    <p style="color: #3182ce; font-weight: 600;">
                        <i class='bx bx-bell'></i> Anda memiliki <?php echo $jumlah_notif_konsultasi; ?> konsultasi yang menunggu persetujuan!
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <h3><?php echo $total_konsul; ?></h3>
                <p>Total Konsultasi</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $menunggu; ?></h3>
                <p>Menunggu Approve</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $disetujui; ?></h3>
                <p>Disetujui</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $selesai; ?></h3>
                <p>Selesai</p>
            </div>
        </div>
        
        <div class="quick-actions">
            <h3><i class='bx bx-rocket'></i> Aksi Cepat</h3>
            <div class="actions-grid">
                <a href="approve_konsultasi.php" class="action-btn">
                    <i class='bx bx-check-shield'></i>
                    Approve Konsultasi
                    <?php if ($jumlah_notif_konsultasi > 0): ?>
                        <span class="action-badge"><?php echo $jumlah_notif_konsultasi; ?></span>
                    <?php endif; ?>
                </a>
                <a href="sesi_konsultasi.php" class="action-btn">
                    <i class='bx bx-conversation'></i>
                    Sesi Konsultasi
                </a>
                <a href="kelola_jadwal.php" class="action-btn">
                    <i class='bx bx-calendar'></i>
                    Kelola Jadwal
                </a>
                <a href="review_form.php" class="action-btn">
                    <i class='bx bx-clipboard'></i>
                    Review Form
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
            
            setInterval(() => {
                fetch('cek_konsultasi_guru.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.jumlah_baru > 0) {
                            const badgeNav = document.querySelector('.nav-badge .badge');
                            if (badgeNav) {
                                badgeNav.textContent = data.jumlah_baru;
                                badgeNav.style.animation = 'none';
                                setTimeout(() => {
                                    badgeNav.style.animation = 'pulse 2s infinite';
                                }, 10);
                            } else {
                                const navLink = document.querySelector('.nav-badge');
                                if (navLink) {
                                    const newBadge = document.createElement('span');
                                    newBadge.className = 'badge';
                                    newBadge.textContent = data.jumlah_baru;
                                    navLink.appendChild(newBadge);
                                }
                            }
                            
                            const actionBadge = document.querySelector('.action-btn span');
                            if (actionBadge) {
                                actionBadge.textContent = data.jumlah_baru + ' baru';
                            } else {
                                const actionBtn = document.querySelector('.action-btn');
                                if (actionBtn && actionBtn.textContent.includes('Approve Konsultasi')) {
                                    const newBadge = document.createElement('span');
                                    newBadge.style.marginLeft = '8px';
                                    newBadge.style.background = 'white';
                                    newBadge.style.color = '#3182ce';
                                    newBadge.style.padding = '2px 8px';
                                    newBadge.style.borderRadius = '10px';
                                    newBadge.style.fontSize = '12px';
                                    newBadge.textContent = data.jumlah_baru + ' baru';
                                    actionBtn.appendChild(newBadge);
                                }
                            }
                            
                            const welcomeDetails = document.querySelector('.welcome-details p:last-child');
                            if (welcomeDetails) {
                                welcomeDetails.textContent = `📢 Anda memiliki ${data.jumlah_baru} konsultasi yang menunggu persetujuan!`;
                                welcomeDetails.style.color = '#3182ce';
                                welcomeDetails.style.fontWeight = '600';
                            }
                            
                            playNotificationSound();
                        } else {
                            const badgeNav = document.querySelector('.nav-badge .badge');
                            if (badgeNav) {
                                badgeNav.remove();
                            }
                            
                            const actionBadge = document.querySelector('.action-btn span');
                            if (actionBadge && actionBadge.parentElement.textContent.includes('Approve Konsultasi')) {
                                actionBadge.remove();
                            }
                            
                            const welcomeDetails = document.querySelector('.welcome-details p:last-child');
                            if (welcomeDetails && welcomeDetails.textContent.includes('konsultasi')) {
                                welcomeDetails.remove();
                            }
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }, 30000);
            
            function playNotificationSound() {
                try {
                    const audio = new Audio('notification.mp3');
                    audio.volume = 0.3;
                    audio.play().catch(e => console.log('Audio play failed:', e));
                } catch (error) {
                    console.log('Audio error:', error);
                }
            }
            
            function showToast(message, type = 'info') {
                const toast = document.createElement('div');
                toast.className = `toast toast-${type}`;
                toast.innerHTML = `
                    <i class='bx ${type === 'success' ? 'bx-check-circle' : 'bx-info-circle'}'></i>
                    <span>${message}</span>
                `;
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.classList.add('show');
                }, 100);
                
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => {
                        if (toast.parentNode) {
                            document.body.removeChild(toast);
                        }
                    }, 300);
                }, 3000);
            }
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
            
            .toast {
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                padding: 15px 20px;
                border-radius: 12px;
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
                display: flex;
                align-items: center;
                gap: 10px;
                transform: translateX(150%);
                transition: transform 0.3s ease;
                z-index: 10000;
                border-left: 4px solid #3182ce;
            }
            
            .toast.show {
                transform: translateX(0);
            }
            
            .toast-success {
                border-left-color: #38a169;
            }
            
            .toast i {
                font-size: 20px;
            }
            
            .toast-success i {
                color: #38a169;
            }
            
            body.loaded .welcome {
                animation: fadeIn 0.8s ease-out;
            }
            
            body.loaded .stat-card {
                animation: slideUp 0.6s ease-out;
            }
            
            body.loaded .quick-actions {
                animation: fadeIn 0.8s ease-out 0.5s both;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>