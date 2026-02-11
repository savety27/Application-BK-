<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Siswa') {
    header("Location: index.php");
    exit();
}

date_default_timezone_set('Asia/Jakarta');

$hari_ini_inggris = date('l');
$hari_indonesia_map = [
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu',
    'Sunday' => 'Minggu'
];
$hari_ini_nama = $hari_indonesia_map[$hari_ini_inggris] ?? '';
$current_time = date('H:i:s');

error_log("Hari ini: $hari_ini_inggris -> $hari_ini_nama");
error_log("Waktu sekarang: $current_time");

if (!empty($hari_ini_nama)) {
    $sql_delete_today = "DELETE FROM jadwal_konsultasi 
                         WHERE HARI = ? AND JAM_SELESAI < ? AND AKTIF = 'Ya'";
    $stmt_delete_today = $koneksi->prepare($sql_delete_today);
    $stmt_delete_today->bind_param("ss", $hari_ini_nama, $current_time);

    if ($stmt_delete_today->execute()) {
        $deleted_today = $stmt_delete_today->affected_rows;
        error_log("Jadwal hari ini dihapus: $deleted_today");
    } else {
        error_log("Error hapus jadwal hari ini: " . $koneksi->error);
    }
}

$hari_order = ['Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4, 'Jumat' => 5];
$hari_ini_index = isset($hari_order[$hari_ini_nama]) ? $hari_order[$hari_ini_nama] : 0;

foreach ($hari_order as $hari => $index) {
    if ($index < $hari_ini_index) {
        $sql_delete_past = "DELETE FROM jadwal_konsultasi 
                           WHERE HARI = ? AND AKTIF = 'Ya'";
        $stmt_delete_past = $koneksi->prepare($sql_delete_past);
        $stmt_delete_past->bind_param("s", $hari);

        if ($stmt_delete_past->execute()) {
            $deleted_past = $stmt_delete_past->affected_rows;
            error_log("Jadwal $hari dihapus: $deleted_past");
        }
    }
}

$hari_map = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

$sql_jadwal = "SELECT j.*, u.NAMA_LENGKAP as nama_guru 
               FROM jadwal_konsultasi j 
               JOIN guru_bk g ON j.GURU_BK_ID = g.ID 
               JOIN users u ON g.USER_ID = u.ID 
               WHERE j.AKTIF = 'Ya' 
               ORDER BY 
                   CASE j.HARI 
                       WHEN 'Senin' THEN 1
                       WHEN 'Selasa' THEN 2  
                       WHEN 'Rabu' THEN 3
                       WHEN 'Kamis' THEN 4
                       WHEN 'Jumat' THEN 5
                   END,
                   j.JAM_MULAI";
$result_jadwal = $koneksi->query($sql_jadwal);

$jadwal_list = [];
$jadwal_per_hari = [];

while ($jadwal = $result_jadwal->fetch_assoc()) {
    $hari_jadwal = $jadwal['HARI'];

    $index_hari = array_search($hari_jadwal, $hari_map);
    $hari_ini_index = array_search($hari_ini_nama, $hari_map);

    if ($index_hari !== false) {
        if ($index_hari >= $hari_ini_index) {
            $jadwal_list[] = $jadwal;

            if (!isset($jadwal_per_hari[$hari_jadwal])) {
                $jadwal_per_hari[$hari_jadwal] = [];
            }
            $jadwal_per_hari[$hari_jadwal][] = $jadwal;
        }
    }
}

error_log("Total jadwal setelah filter: " . count($jadwal_list));
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Guru BK - APK BK</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-gradient: linear-gradient(135deg, #1e40af 0%, #2563eb 55%, #3b82f6 100%);
            --surface: rgba(255, 255, 255, 0.94);
            --surface-soft: rgba(255, 255, 255, 0.9);
            --surface-card: rgba(255, 255, 255, 0.95);
            --text-main: #2d3748;
            --text-muted: #718096;
            --accent: #2563eb;
            --border-soft: rgba(37, 99, 235, 0.18);
            --shadow-soft: 0 12px 28px rgba(16, 24, 40, 0.12);
        }

        body {
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            background: var(--bg-gradient);
            color: var(--text-main);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
            transition: background 0.35s ease, color 0.35s ease;
        }

        body.dark-mode {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #111827 45%, #1f2937 100%);
            --surface: rgba(17, 24, 39, 0.92);
            --surface-soft: rgba(17, 24, 39, 0.88);
            --surface-card: rgba(17, 24, 39, 0.9);
            --text-main: #e5e7eb;
            --text-muted: #9ca3af;
            --accent: #60a5fa;
            --border-soft: rgba(96, 165, 250, 0.26);
            --shadow-soft: 0 12px 28px rgba(2, 6, 23, 0.5);
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
            background: radial-gradient(circle, rgba(255, 255, 255, 0.8) 0%, rgba(255, 255, 255, 0.3) 70%);
            animation-delay: 0s;
            box-shadow: 0 0 40px rgba(255, 255, 255, 0.4);
        }

        .floating:nth-child(2) {
            width: 180px;
            height: 180px;
            top: 65%;
            right: 7%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.7) 0%, rgba(255, 255, 255, 0.2) 70%);
            animation-delay: 1.5s;
            box-shadow: 0 0 50px rgba(255, 255, 255, 0.3);
        }

        .floating:nth-child(3) {
            width: 200px;
            height: 200px;
            bottom: 15%;
            left: 35%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.6) 0%, rgba(255, 255, 255, 0.25) 70%);
            animation-delay: 3s;
            box-shadow: 0 0 45px rgba(255, 255, 255, 0.35);
        }

        @keyframes float {

            0%,
            100% {
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

        /* Sidebar & Header Styles matching data_diri.php */
        body.sidebar-open {
            overflow: hidden;
        }

        .brand-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-left>a {
            text-decoration: none;
            color: inherit;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header {
            margin-left: 270px;
            background: var(--surface);
            border-bottom: 3px solid var(--accent);
            box-shadow: var(--shadow-soft);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(20px);
            position: relative;
            overflow: visible;
            z-index: 1000;
            transition: margin-left 0.3s ease, background 0.3s ease;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 0.5px;
            background: linear-gradient(135deg, var(--accent), #1d4ed8);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
        }

        .sidebar-toggle,
        .theme-toggle {
            border: 1px solid var(--border-soft);
            background: rgba(255, 255, 255, 0.4);
            color: var(--text-main);
            border-radius: 12px;
            height: 42px;
            padding: 0 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.25s ease;
            font-weight: 600;
        }

        body.dark-mode .sidebar-toggle,
        body.dark-mode .theme-toggle {
            background: rgba(31, 41, 55, 0.85);
        }

        .sidebar-toggle:hover,
        .theme-toggle:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .theme-toggle:hover {
            transform: translateY(-1px);
        }

        .sidebar-toggle {
            display: none;
            font-size: 22px;
            padding: 0 12px;
            min-width: 46px;
            min-height: 46px;
            touch-action: manipulation;
            position: relative;
            z-index: 1405;
        }

        .nav {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 270px;
            background: var(--surface-soft);
            border-right: 1px solid var(--border-soft);
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 16px 18px 18px;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 1300;
            backdrop-filter: blur(18px);
            transition: transform 0.3s ease, background 0.3s ease;
        }

        .sidebar-top {
            margin-bottom: 14px;
            padding: 12px;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.2), rgba(59, 130, 246, 0.18));
            border: 1px solid var(--border-soft);
        }

        .sidebar-top h4 {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.4px;
            margin-bottom: 10px;
            color: var(--text-main);
            text-transform: uppercase;
        }

        .sidebar-icons {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }

        .sidebar-icon {
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.35);
            background: rgba(255, 255, 255, 0.45);
            color: var(--text-main);
            font-size: 18px;
        }

        body.dark-mode .sidebar-icon {
            background: rgba(31, 41, 55, 0.72);
            border-color: rgba(129, 140, 248, 0.35);
        }

        .nav a {
            color: var(--text-muted);
            text-decoration: none;
            width: 100%;
            border: 1px solid transparent;
            padding: 14px 22px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            white-space: nowrap;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: flex-start;
        }

        .nav a::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.12), rgba(59, 130, 246, 0.12));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
            z-index: -1;
        }

        .nav a:hover {
            color: var(--accent);
            border-color: var(--border-soft);
            transform: translateX(4px);
        }

        .nav a:hover::before {
            transform: scaleX(1);
        }

        .nav a.active {
            color: var(--accent);
            border-color: var(--border-soft);
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.14), rgba(59, 130, 246, 0.14));
        }

        .container {
            margin-left: 270px;
            max-width: none;
            padding: 40px 20px;
            position: relative;
            z-index: 5;
            transition: margin-left 0.3s ease;
        }

        .content {
            background: var(--surface-card);
            border-radius: 20px;
            border: 1px solid var(--border-soft);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.1);
            backdrop-filter: blur(15px);
            overflow: hidden;
            animation: fadeIn 0.8s ease-out;
            padding: 30px;
        }

        body.dark-mode .content {
            background: rgba(17, 24, 39, 0.9);
        }

        /* Specific Jadwal Styles */
        .page-header {
            background: rgba(102, 126, 234, 0.05);
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            border: 1px solid var(--border-soft);
            animation: fadeIn 0.8s ease-out;
        }

        body.dark-mode .page-header {
            background: rgba(59, 130, 246, 0.08);
        }

        .page-header h2 {
            color: var(--accent);
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
        }

        .page-header p {
            color: var(--text-muted);
            font-size: 16px;
            line-height: 1.6;
        }

        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 16px;
            border: 1px solid var(--border-soft);
            flex-wrap: wrap;
        }

        body.dark-mode .filter-tabs {
            background: rgba(59, 130, 246, 0.08);
        }

        .filter-tab {
            padding: 12px 24px;
            background: var(--surface);
            border: 2px solid var(--border-soft);
            border-radius: 10px;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 14px;
        }

        .filter-tab:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .filter-tab.active {
            background: linear-gradient(135deg, var(--accent), #1d4ed8);
            color: white;
            border-color: transparent;
        }

        .hari-section {
            margin-bottom: 30px;
        }

        .hari-header {
            background: rgba(102, 126, 234, 0.1);
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            border-left: 4px solid var(--accent);
        }

        body.dark-mode .hari-header {
            background: rgba(59, 130, 246, 0.15);
        }

        .hari-header h3 {
            color: var(--accent);
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .jadwal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .jadwal-card {
            background: var(--surface-card);
            border-radius: 16px;
            border: 1px solid var(--border-soft);
            box-shadow: var(--shadow-soft);
            padding: 25px;
            transition: all 0.3s ease;
            animation: slideUp 0.6s ease-out;
        }

        body.dark-mode .jadwal-card {
            background: rgba(30, 41, 59, 0.5);
        }

        .jadwal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            border-color: var(--accent);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-soft);
        }

        .nama-guru {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .jadwal-info {
            margin-bottom: 15px;
        }

        .info-label {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .info-value {
            font-size: 16px;
            color: var(--text-main);
            font-weight: 500;
        }

        .btn-konsultasi {
            background: linear-gradient(135deg, var(--accent), #1d4ed8);
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 12px;
            text-decoration: none;
            text-align: center;
            display: block;
            margin-top: 20px;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 16px;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.3);
        }

        .btn-konsultasi:hover {
            background: linear-gradient(135deg, #1d4ed8, var(--accent));
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.4);
        }

        .status-hari-ini {
            background: rgba(72, 187, 120, 0.1);
            color: #38a169;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
            border: 1px solid rgba(72, 187, 120, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 60px 40px;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 16px;
            margin: 20px 0;
            border: 2px dashed var(--border-soft);
            animation: fadeIn 0.8s ease-out;
        }

        body.dark-mode .empty-state {
            background: rgba(59, 130, 246, 0.05);
        }

        .empty-state h3 {
            color: var(--accent);
            font-size: 24px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .empty-state p {
            color: var(--text-muted);
            margin-bottom: 10px;
            line-height: 1.6;
        }

        .sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.25s ease, visibility 0.25s ease;
            z-index: 1200;
        }

        .sidebar-overlay.show {
            opacity: 1;
            visibility: visible;
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

        @media (max-width: 1024px) {
            .header {
                margin-left: 0;
                padding: 12px 14px;
                display: block;
            }

            .container {
                margin-left: 0;
                padding: 20px;
            }

            .brand-left {
                width: 100%;
                justify-content: space-between;
                margin-bottom: 10px;
                position: relative;
                z-index: 1405;
            }

            .sidebar-toggle {
                display: inline-flex;
            }

            .nav {
                transform: translateX(-105%);
                width: 280px;
                padding-top: 16px;
                box-shadow: 0 10px 30px rgba(2, 6, 23, 0.35);
            }

            .nav.open {
                transform: translateX(0);
            }

            .user-info {
                flex-direction: row;
                align-items: center;
                gap: 10px;
                flex-wrap: nowrap;
                justify-content: flex-end;
                width: 100%;
            }

            .theme-toggle span {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 12px;
            }

            .brand-left {
                justify-content: center;
                margin-bottom: 8px;
            }

            .brand-left a {
                margin: 0 auto;
                text-align: center;
            }

            .sidebar-toggle {
                position: absolute;
                left: 0;
                top: 100%;
                transform: translateY(-50%);
            }

            .header h1 {
                font-size: 18px;
                margin: 0;
                text-align: center;
            }

            .user-info {
                gap: 8px;
                justify-content: center;
                flex-wrap: wrap;
                margin-top: 2px;
            }

            .jadwal-grid {
                grid-template-columns: 1fr;
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .filter-tabs {
                flex-direction: column;
            }

            .filter-tab {
                width: 100%;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .theme-toggle {
                height: 36px;
                padding: 0 9px;
            }

            .brand-left a h1 {
                font-size: 16px;
                text-align: center;
            }

            .content {
                padding: 20px;
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
        <div class="brand-left">
            <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Buka menu">
                <i class='bx bx-menu'></i>
            </button>
            <a href="jadwal_guru.php">
                <h1><i class='bx bx-calendar'></i> Jadwal Guru BK</h1>
            </a>
        </div>
        <div class="user-info">
            <button class="theme-toggle" id="themeToggle" type="button" aria-label="Ganti mode tema">
                <i class='bx bx-moon'></i>
                <span>Mode</span>
            </button>
        </div>
    </div>

    <div class="nav">
        <div class="sidebar-top">
            <h4>Menu Siswa</h4>
            <div class="sidebar-icons">
                <span class="sidebar-icon"><i class='bx bx-home-heart'></i></span>
                <span class="sidebar-icon"><i class='bx bx-book-open'></i></span>
                <span class="sidebar-icon"><i class='bx bx-brain'></i></span>
                <span class="sidebar-icon"><i class='bx bx-calendar-star'></i></span>
            </div>
        </div>
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
        <a href="jadwal_guru.php" class="active">
            <i class='bx bx-calendar'></i>
            Jadwal Guru
        </a>
        <a href="profil.php">
            <i class='bx bx-face'></i>
            Profil
        </a>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="container">
        <div class="content">
            <div class="page-header">
                <h2><i class='bx bx-time'></i> Jadwal Konsultasi</h2>
                <p>Pilih jadwal konsultasi yang tersedia dari guru BK. Sistem otomatis menghapus jadwal yang sudah
                    lewat.</p>
                <p style="color: var(--text-muted); font-size: 14px; margin-top: 8px;">
                    <i class='bx bx-info-circle'></i> Jadwal yang ditampilkan hanya untuk hari ini dan hari-hari
                    mendatang.
                </p>
            </div>

            <?php if (count($jadwal_list) > 0): ?>
                <div class="filter-tabs">
                    <div class="filter-tab active" onclick="showAllJadwal()">Semua Jadwal</div>
                    <?php foreach ($hari_map as $hari): ?>
                        <?php
                        $hari_index = array_search($hari, $hari_map);
                        $hari_ini_index = array_search($hari_ini_nama, $hari_map);
                        $is_past_day = ($hari_index < $hari_ini_index);
                        ?>
                        <?php if (!$is_past_day): ?>
                            <div class="filter-tab" onclick="filterByDay('<?php echo $hari; ?>')">
                                <?php echo $hari; ?>
                                <?php if ($hari === $hari_ini_nama): ?>
                                    <span class="status-hari-ini" style="margin-left: 8px;">(Hari Ini)</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <div id="all-jadwal">
                    <?php foreach ($jadwal_per_hari as $hari => $jadwal_hari): ?>
                        <div class="hari-section" id="hari-<?php echo $hari; ?>">
                            <div class="hari-header">
                                <h3>
                                    <i class='bx bx-calendar'></i>
                                    <?php echo $hari; ?>
                                    <?php if ($hari === $hari_ini_nama): ?>
                                        <span class="status-hari-ini">HARI INI</span>
                                    <?php endif; ?>
                                </h3>
                            </div>
                            <div class="jadwal-grid">
                                <?php foreach ($jadwal_hari as $jadwal): ?>
                                    <div class="jadwal-card">
                                        <div class="card-header">
                                            <div class="nama-guru">
                                                <i class='bx bx-user'></i>
                                                <?php echo htmlspecialchars($jadwal['nama_guru']); ?>
                                            </div>
                                            <div style="color: var(--accent); font-weight: 600;">
                                                <?php echo date('H:i', strtotime($jadwal['JAM_MULAI'])); ?> -
                                                <?php echo date('H:i', strtotime($jadwal['JAM_SELESAI'])); ?>
                                            </div>
                                        </div>

                                        <div class="jadwal-info">
                                            <div class="info-label"><i class='bx bx-time'></i> Jam Konsultasi</div>
                                            <div class="info-value">
                                                <?php echo date('H:i', strtotime($jadwal['JAM_MULAI'])); ?> -
                                                <?php echo date('H:i', strtotime($jadwal['JAM_SELESAI'])); ?>
                                            </div>
                                        </div>

                                        <?php if ($jadwal['KETERANGAN']): ?>
                                            <div class="jadwal-info">
                                                <div class="info-label"><i class='bx bx-note'></i> Keterangan</div>
                                                <div class="info-value"><?php echo htmlspecialchars($jadwal['KETERANGAN']); ?></div>
                                            </div>
                                        <?php endif; ?>

                                        <a href="ajukan_konsultasi.php?guru_id=<?php echo $jadwal['GURU_BK_ID']; ?>&jadwal_id=<?php echo $jadwal['ID']; ?>"
                                            class="btn-konsultasi">
                                            <i class='bx bx-edit'></i>
                                            Ajukan Konsultasi
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (count($jadwal_per_hari) === 0): ?>
                    <div class="empty-state">
                        <h3><i class='bx bx-calendar-x'></i> Tidak Ada Jadwal Aktif</h3>
                        <p>Tidak ada jadwal konsultasi yang aktif saat ini.</p>
                        <p style="color: var(--text-muted); margin-top: 10px;">
                            Silakan hubungi guru BK untuk informasi jadwal konsultasi
                        </p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3><i class='bx bx-calendar-x'></i> Belum Ada Jadwal Tersedia</h3>
                    <p>Tidak ada jadwal konsultasi yang tersedia saat ini.</p>
                    <p style="color: var(--text-muted); margin-top: 10px;">
                        Silakan hubungi guru BK untuk informasi jadwal konsultasi
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.querySelector('.nav');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const themeToggle = document.getElementById('themeToggle');

            function closeSidebar() {
                if (sidebar && sidebarOverlay) {
                    sidebar.classList.remove('open');
                    sidebarOverlay.classList.remove('show');
                    document.body.classList.remove('sidebar-open');
                }
            }

            function toggleSidebar() {
                if (sidebar && sidebarOverlay) {
                    sidebar.classList.toggle('open');
                    sidebarOverlay.classList.toggle('show');
                    document.body.classList.toggle('sidebar-open');
                }
            }

            const savedTheme = localStorage.getItem('dashboard_theme');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-mode');
                if (themeToggle) {
                    themeToggle.innerHTML = "<i class='bx bx-sun'></i><span>Mode</span>";
                }
            }

            if (themeToggle) {
                themeToggle.addEventListener('click', function () {
                    const isDark = document.body.classList.toggle('dark-mode');
                    localStorage.setItem('dashboard_theme', isDark ? 'dark' : 'light');
                    this.innerHTML = isDark
                        ? "<i class='bx bx-sun'></i><span>Mode</span>"
                        : "<i class='bx bx-moon'></i><span>Mode</span>";
                });
            }

            if (sidebarToggle) {
                let lastSidebarToggle = 0;
                const handleSidebarToggle = function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const now = Date.now();
                    if (now - lastSidebarToggle < 250) {
                        return;
                    }
                    lastSidebarToggle = now;
                    toggleSidebar();
                };

                sidebarToggle.addEventListener('click', handleSidebarToggle);
                sidebarToggle.addEventListener('touchend', handleSidebarToggle, { passive: false });
            }

            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', closeSidebar);
            }

            document.querySelectorAll('.nav a').forEach(link => {
                link.addEventListener('touchstart', function () {
                    this.classList.add('tap-active');
                }, { passive: true });

                link.addEventListener('touchend', function () {
                    setTimeout(() => {
                        this.classList.remove('tap-active');
                    }, 140);
                }, { passive: true });

                link.addEventListener('touchcancel', function () {
                    this.classList.remove('tap-active');
                }, { passive: true });

                link.addEventListener('click', function () {
                    if (window.innerWidth <= 1024) {
                        closeSidebar();
                    }
                });
            });

            window.addEventListener('resize', function () {
                if (window.innerWidth > 1024) {
                    closeSidebar();
                }
            });

            const cards = document.querySelectorAll('.jadwal-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });

        function showAllJadwal() {
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.currentTarget.classList.add('active');

            document.querySelectorAll('.hari-section').forEach(section => {
                section.style.display = 'block';
            });
        }

        function filterByDay(day) {
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.currentTarget.classList.add('active');

            document.querySelectorAll('.hari-section').forEach(section => {
                if (section.id === 'hari-' + day) {
                    section.style.display = 'block';
                } else {
                    section.style.display = 'none';
                }
            });
        }
    </script>
</body>

</html>