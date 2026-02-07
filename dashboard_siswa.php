<?php
session_start();
include 'koneksi.php';
include 'notifikasi_helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Siswa') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Siswa';

$default_photo = 'https://ui-avatars.com/api/?name=' . urlencode($nama_lengkap) . '&background=667eea&color=fff&size=150';
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

$notifikasi = dapatkanNotifikasi($user_id, 5);
$jumlah_notif_belum_dibaca = hitungNotifikasiBelumDibaca($user_id);

if (isset($_GET['lihat_notif'])) {
    tandaiSemuaDibaca($user_id);
    $jumlah_notif_belum_dibaca = 0;
    header("Location: dashboard_siswa.php");
    exit();
}

if (isset($_GET['baca_notif'])) {
    $notif_id = $_GET['baca_notif'];
    tandaiDibaca($notif_id);
    header("Location: dashboard_siswa.php");
    exit();
}

$siswa = null;
$total_konsul = 0;
$menunggu = 0;
$disetujui = 0;
$selesai = 0;
$ditolak = 0;

$sql_siswa = "SELECT s.* FROM siswa s WHERE s.user_id = ?";
$stmt_siswa = $koneksi->prepare($sql_siswa);
if ($stmt_siswa) {
    $stmt_siswa->bind_param("i", $user_id);
    $stmt_siswa->execute();
    $siswa_result = $stmt_siswa->get_result();
    $siswa = $siswa_result->fetch_assoc();
    $stmt_siswa->close();
}

if ($siswa && isset($siswa['ID'])) {
    $sql_konsul = "SELECT COUNT(*) as total, 
                   SUM(CASE WHEN status = 'Menunggu' THEN 1 ELSE 0 END) as menunggu,
                   SUM(CASE WHEN status = 'Disetujui' THEN 1 ELSE 0 END) as disetujui,
                   SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END) as selesai,
                   SUM(CASE WHEN status = 'Ditolak' THEN 1 ELSE 0 END) as ditolak
                   FROM konsultasi WHERE siswa_id = ?";
    $stmt_konsul = $koneksi->prepare($sql_konsul);
    if ($stmt_konsul) {
        $stmt_konsul->bind_param("i", $siswa['ID']);
        $stmt_konsul->execute();
        $konsul_result = $stmt_konsul->get_result();
        $stats = $konsul_result->fetch_assoc();
        
        if ($stats) {
            $total_konsul = $stats['total'] ?? 0;
            $menunggu = $stats['menunggu'] ?? 0;
            $disetujui = $stats['disetujui'] ?? 0;
            $selesai = $stats['selesai'] ?? 0;
            $ditolak = $stats['ditolak'] ?? 0;
        }
        $stmt_konsul->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa - APK BK</title>
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
        
        .floating:nth-child(4) {
            width: 90px;
            height: 90px;
            top: 25%;
            right: 25%;
            background: radial-gradient(circle, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.4) 70%);
            animation-delay: 4.5s;
            box-shadow: 0 0 35px rgba(255,255,255,0.45);
        }
        
        .floating:nth-child(5) {
            width: 130px;
            height: 130px;
            bottom: 35%;
            left: 15%;
            background: radial-gradient(circle, rgba(255,255,255,0.75) 0%, rgba(255,255,255,0.35) 70%);
            animation-delay: 6s;
            box-shadow: 0 0 42px rgba(255,255,255,0.38);
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
            overflow: visible;
            z-index: 1000;
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
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
            position: relative;
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
            border: 3px solid #667eea;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }
        
        .profile-avatar:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
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
        
        .notifikasi-container {
            position: relative;
            margin-right: 15px;
            z-index: 1001;
        }
        
        .notifikasi-btn {
            background: none;
            border: none;
            position: relative;
            cursor: pointer;
            font-size: 24px;
            color: #4a5568;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: transparent;
            z-index: 1002;
        }
        
        .notifikasi-btn:hover {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            transform: scale(1.1);
        }
        
        .notifikasi-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s infinite;
            box-shadow: 0 2px 10px rgba(255, 107, 107, 0.4);
            z-index: 1003;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .notifikasi-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 380px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(102, 126, 234, 0.2);
            z-index: 9999;
            display: none;
            overflow: hidden;
            animation: slideDown 0.3s ease;
            margin-top: 10px;
            opacity: 0;
            transform: translateY(-10px);
            transition: opacity 0.3s, transform 0.3s;
        }
        
        .notifikasi-dropdown.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .notifikasi-header {
            padding: 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .notifikasi-header h4 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        .notifikasi-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .notifikasi-item {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(102, 126, 234, 0.1);
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            gap: 12px;
            text-decoration: none;
            color: inherit;
        }
        
        .notifikasi-item:hover {
            background: rgba(102, 126, 234, 0.05);
        }
        
        .notifikasi-item.belum-dibaca {
            background: rgba(102, 126, 234, 0.08);
            position: relative;
        }
        
        .notifikasi-item.belum-dibaca::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #667eea;
            border-radius: 0 2px 2px 0;
        }
        
        .notifikasi-icon {
            font-size: 20px;
            flex-shrink: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .notifikasi-icon.info { color: #3182ce; }
        .notifikasi-icon.success { color: #38a169; }
        .notifikasi-icon.warning { color: #d69e2e; }
        .notifikasi-icon.danger { color: #e53e3e; }
        
        .notifikasi-content {
            flex: 1;
            min-width: 0;
        }
        
        .notifikasi-judul {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .notifikasi-pesan {
            color: #718096;
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 5px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .notifikasi-waktu {
            color: #a0aec0;
            font-size: 11px;
            font-weight: 500;
        }
        
        .notifikasi-footer {
            padding: 15px 20px;
            text-align: center;
            border-top: 1px solid rgba(102, 126, 234, 0.1);
        }
        
        .lihat-semua-btn {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: color 0.3s;
        }
        
        .lihat-semua-btn:hover {
            color: #764ba2;
        }
        
        .kosong-notifikasi {
            padding: 40px 20px;
            text-align: center;
            color: #a0aec0;
            font-size: 14px;
        }
        
        .logout-btn {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.3);
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
            background: linear-gradient(135deg, #ee5a52, #ff6b6b);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 25px rgba(255, 107, 107, 0.4);
        }
        
        .nav { 
            background: rgba(255, 255, 255, 0.9);
            padding: 18px 40px;
            display: flex;
            gap: 25px;
            border-bottom: 1px solid rgba(102, 126, 234, 0.1);
            backdrop-filter: blur(15px);
            overflow-x: auto;
            scrollbar-width: none;
            z-index: 50;
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
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
            z-index: -1;
        }
        
        .nav a:hover { 
            color: #667eea;
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .nav a:hover::before {
            transform: scaleX(1);
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
            border: 1px solid rgba(102, 126, 234, 0.1);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.1);
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
            background: radial-gradient(circle, rgba(102, 126, 234, 0.05) 0%, transparent 70%);
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
            border: 4px solid #667eea;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
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
            border-top: 1px solid rgba(102, 126, 234, 0.1);
        }
        
        .stats { 
            display: grid; 
            grid-template-columns: repeat(5, 1fr); 
            gap: 30px; 
            margin-bottom: 40px;
        }
        
        .stat-card { 
            background: rgba(255, 253, 208, 0.95);  
            padding: 35px 25px;
            border-radius: 20px;
            text-align: center;
            border: 1px solid rgba(102, 126, 234, 0.1);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.1);
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
        .stat-card:nth-child(5) { animation-delay: 0.5s; }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 20px 45px rgba(102, 126, 234, 0.2);
        }
        
        .stat-card:hover::before {
            transform: scaleX(1);
        }
        
        .stat-card h3 { 
            color: #2d3748; 
            font-size: 3.5em; 
            margin-bottom: 15px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
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
            border: 1px solid rgba(102, 126, 234, 0.1);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.1);
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
            background: linear-gradient(135deg, #667eea, #764ba2);
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
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
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
            background: linear-gradient(135deg, #764ba2, #667eea);
            transform: translateY(-8px) scale(1.03);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
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
        
        @media (max-width: 1200px) {
            .stats {
                grid-template-columns: repeat(3, 1fr);
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
                width: 100%;
                position: static;
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
                grid-template-columns: repeat(2, 1fr);
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
            
            .notifikasi-container {
                margin-right: 0;
                position: static;
                align-self: center;
            }
            
            .notifikasi-dropdown {
                width: 100vw;
                right: -20px;
                position: fixed;
                top: 60px;
                border-radius: 0 0 12px 12px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            }
            
            .notifikasi-dropdown.show {
                display: block;
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
            
            .stats {
                grid-template-columns: 1fr;
                gap: 15px;
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
            
            .notifikasi-dropdown {
                width: 100vw;
                right: -20px;
            }
        }
    </style>
</head>
<body>
    
    <div class="floating"></div>
    <div class="floating"></div>
    <div class="floating"></div>
    <div class="floating"></div>
    <div class="floating"></div>
    
    <div class="header">
        <a href="halaman utama.php" style="text-decoration: none; color: inherit;">
            <h1> 🎓 APK BK - Dashboard Siswa </h1>
        </a>
        <div class="user-info">
            <div class="notifikasi-container">
                <button class="notifikasi-btn" id="notifikasiBtn">
                    <i class='bx bx-bell'></i>
                    <?php if ($jumlah_notif_belum_dibaca > 0): ?>
                        <span class="notifikasi-badge" id="notifikasiBadge">
                            <?php echo $jumlah_notif_belum_dibaca; ?>
                        </span>
                    <?php endif; ?>
                </button>
                
                <div class="notifikasi-dropdown" id="notifikasiDropdown">
                    <div class="notifikasi-header">
                        <h4><i class='bx bx-bell'></i> Notifikasi</h4>
                        <a href="dashboard_siswa.php?lihat_notif=1" class="lihat-semua-btn" style="color: white; font-size: 12px;">
                            <i class='bx bx-check-double'></i> Tandai Semua Dibaca
                        </a>
                    </div>
                    
                    <div class="notifikasi-list">
                        <?php if (count($notifikasi) > 0): ?>
                            <?php foreach ($notifikasi as $notif): ?>
                                <div class="notifikasi-item <?php echo $notif['DIBACA'] == '0' ? 'belum-dibaca' : ''; ?>"
                                     onclick="tandaiNotifikasiDibaca(<?php echo $notif['ID']; ?>, this)">
                                    <div class="notifikasi-icon <?php echo $notif['TIPE']; ?>">
                                        <?php 
                                        $icons = [
                                            'info' => 'bx-info-circle',
                                            'success' => 'bx-check-circle',
                                            'warning' => 'bx-error-circle',
                                            'danger' => 'bx-x-circle'
                                        ];
                                        echo '<i class="bx ' . $icons[$notif['TIPE']] . '"></i>';
                                        ?>
                                    </div>
                                    <div class="notifikasi-content">
                                        <div class="notifikasi-judul"><?php echo htmlspecialchars($notif['JUDUL']); ?></div>
                                        <div class="notifikasi-pesan"><?php echo htmlspecialchars($notif['PESAN']); ?></div>
                                        <div class="notifikasi-waktu">
                                            <?php echo date('d/m/Y H:i', strtotime($notif['CREATED_AT'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="kosong-notifikasi">
                                <i class='bx bx-bell-off' style="font-size: 40px; margin-bottom: 10px;"></i>
                                <p>Tidak ada notifikasi</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                </div>
            </div>
            
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
        <a href="dashboard_siswa.php">
            <i class='bx bx-home'></i>
            Dashboard
        </a>
        <a href="data_diri.php">
            <i class='bx bx-user'></i>
            Data Diri
        </a>
        <a href="form_kepribadian.php">
            <i class='bx bx-brain'></i>
            Form Kepribadian
        </a>
        <a href="form_belajar.php">
            <i class='bx bx-book'></i>
            Form Belajar
        </a>
        <a href="form_karir.php">
            <i class='bx bx-briefcase'></i>
            Form Karir
        </a>
        <a href="form_sosial.php">
            <i class='bx bx-group'></i>
            Form Sosial
        </a>
        <a href="siswa_request_reset.php">
            <i class='bx bx-refresh'></i>
            Reset Password
        </a>
        <a href="jadwal_guru.php">
            <i class='bx bx-calendar'></i>
            Jadwal Guru
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
                        if ($siswa) {
                            echo "Kelas: " . ($siswa['KELAS'] ?? '-') . " | Jurusan: " . ($siswa['JURUSAN'] ?? '-');
                        } else {
                            echo "Data siswa belum lengkap. Silakan lengkapi profil Anda.";
                        }
                        ?>
                    </p>
                </div>
            </div>
            <div class="welcome-details">
                <p><i class='bx bx-info-circle'></i> Gunakan menu di atas untuk mengakses fitur lengkap aplikasi BK.</p>
                <?php if ($jumlah_notif_belum_dibaca > 0): ?>
                    <p style="color: #667eea; font-weight: 600;">
                        <i class='bx bx-bell'></i> Anda memiliki <?php echo $jumlah_notif_belum_dibaca; ?> notifikasi baru!
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
                <p>Menunggu</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $disetujui; ?></h3>
                <p>Disetujui</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $selesai; ?></h3>
                <p>Selesai</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $ditolak; ?></h3>
                <p>Ditolak</p>
            </div>
        </div>
        
        <div class="quick-actions">
            <h3><i class='bx bx-rocket'></i> Aksi Cepat</h3>
            <div class="actions-grid">
                <a href="ajukan_konsultasi.php" class="action-btn">
                    <i class='bx bx-edit-alt'></i>
                    Ajukan Konsultasi
                </a>
                <a href="lihat_konsultasi.php" class="action-btn">
                    <i class='bx bx-show'></i>
                    Lihat Konsultasi
                </a>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const notifBtn = document.getElementById('notifikasiBtn');
        const notifDropdown = document.getElementById('notifikasiDropdown');
        let notifOpen = false;
        
        function closeNotifDropdown() {
            notifDropdown.classList.remove('show');
            notifOpen = false;
        }
        
        function openNotifDropdown() {
            notifDropdown.classList.add('show');
            notifOpen = true;
            
            const rect = notifBtn.getBoundingClientRect();
            const viewportHeight = window.innerHeight;
            const dropdownHeight = notifDropdown.offsetHeight;
            const spaceBelow = viewportHeight - rect.bottom - 20;
            
            if (dropdownHeight > spaceBelow && spaceBelow < 300) {
                notifDropdown.style.top = 'auto';
                notifDropdown.style.bottom = '100%';
                notifDropdown.style.marginTop = '0';
                notifDropdown.style.marginBottom = '10px';
            } else {
                notifDropdown.style.top = '100%';
                notifDropdown.style.bottom = 'auto';
                notifDropdown.style.marginTop = '10px';
                notifDropdown.style.marginBottom = '0';
            }
        }
        
        notifBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            
            if (notifOpen) {
                closeNotifDropdown();
            } else {
                openNotifDropdown();
            }
        });
        
        document.addEventListener('click', function(e) {
            if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
                closeNotifDropdown();
            }
        });
        
        window.addEventListener('scroll', function() {
            if (notifOpen) {
                closeNotifDropdown();
            }
        });
        
        window.addEventListener('resize', function() {
            if (notifOpen) {
                closeNotifDropdown();
            }
        });
        
        fetch('cek_notifikasi.php?user_id=<?php echo $user_id; ?>')
            .then(response => response.json())
            .then(data => {
                if (data.jumlah_baru > 0) {
                    const badge = document.getElementById('notifikasiBadge');
                    if (badge) {
                        badge.textContent = data.jumlah_baru;
                    } else {
                        const newBadge = document.createElement('span');
                        newBadge.className = 'notifikasi-badge';
                        newBadge.id = 'notifikasiBadge';
                        newBadge.textContent = data.jumlah_baru;
                        notifBtn.appendChild(newBadge);
                    }
                    
                    const currentBadge = document.getElementById('notifikasiBadge');
                    if (currentBadge) {
                        currentBadge.style.animation = 'none';
                        setTimeout(() => {
                            currentBadge.style.animation = 'pulse 2s infinite';
                        }, 10);
                    }
                    
                    showToast('Anda memiliki ' + data.jumlah_baru + ' notifikasi baru', 'info');
                    playNotificationSound();
                }
            })
            .catch(error => console.error('Error:', error));
        
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
        
        document.body.classList.add('loaded');
    });

    function tandaiNotifikasiDibaca(notifId, element) {
        fetch('tandai_dibaca.php?id=' + notifId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const badge = document.getElementById('notifikasiBadge');
                    if (badge) {
                        const currentCount = parseInt(badge.textContent);
                        if (currentCount > 1) {
                            badge.textContent = currentCount - 1;
                        } else {
                            badge.remove();
                        }
                    }
                    
                    element.classList.remove('belum-dibaca');
                    element.style.backgroundColor = '';
                    
                    element.style.transform = 'scale(0.98)';
                    setTimeout(() => {
                        element.style.transform = '';
                    }, 300);
                }
            })
            .catch(error => console.error('Error:', error));
    }

    const style = document.createElement('style');
    style.textContent = `
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
            border-left: 4px solid #667eea;
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
        
        body.loaded .quick-actions {
            animation: fadeIn 0.8s ease-out 0.5s both;
        }
    `;
    document.head.appendChild(style);
</script>
</body>
</html>