<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Siswa') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

$sql_siswa = "SELECT s.ID as SISWA_ID, s.*, u.NAMA_LENGKAP 
              FROM siswa s 
              JOIN users u ON s.USER_ID = u.ID 
              WHERE s.USER_ID = ?";
$stmt_siswa = $koneksi->prepare($sql_siswa);
$stmt_siswa->bind_param("i", $user_id);
$stmt_siswa->execute();
$siswa = $stmt_siswa->get_result()->fetch_assoc();

if (!$siswa) {
    header("Location: data_diri.php");
    exit();
}

$siswa_id = $siswa['SISWA_ID'];

$sql_karir = "SELECT * FROM form_karir WHERE SISWA_ID = ?";
$stmt_karir = $koneksi->prepare($sql_karir);
$stmt_karir->bind_param("i", $siswa_id);
$stmt_karir->execute();
$karir = $stmt_karir->get_result()->fetch_assoc();

$data_sudah_lengkap = false;
if ($karir) {
    if (!empty($karir['MINAT_KARIR']) && !empty($karir['BIDANG_KARIR']) && 
        !empty($karir['DUKUNGAN_ORANG_TUA']) && !empty($karir['INFORMASI_KARIR_DARI'])) {
        $data_sudah_lengkap = true;
    }
}

$bidang_karir_asal = '';
$informasi_karir_asal = '';
$bidang_karir_lainnya_value = '';
$informasi_karir_lainnya_value = '';

if ($karir) {
    $dropdown_bidang = ['Teknik', 'Kesehatan', 'Ekonomi/Bisnis', 'Sosial', 'Seni', 'Pendidikan', 'Lainnya'];
    if (in_array($karir['BIDANG_KARIR'], $dropdown_bidang)) {
        $bidang_karir_asal = $karir['BIDANG_KARIR'];
        $bidang_karir_lainnya_value = '';
    } else {
        $bidang_karir_asal = 'Lainnya';
        $bidang_karir_lainnya_value = $karir['BIDANG_KARIR'];
    }
    
    $dropdown_informasi = ['Sekolah', 'Orang Tua', 'Teman', 'Internet', 'Lainnya'];
    if (in_array($karir['INFORMASI_KARIR_DARI'], $dropdown_informasi)) {
        $informasi_karir_asal = $karir['INFORMASI_KARIR_DARI'];
        $informasi_karir_lainnya_value = '';
    } else {
        $informasi_karir_asal = 'Lainnya';
        $informasi_karir_lainnya_value = $karir['INFORMASI_KARIR_DARI'];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$data_sudah_lengkap) {
    $minat_karir = $_POST['minat_karir'];
    $bidang_karir = $_POST['bidang_karir'];
    $bidang_karir_lainnya = $_POST['bidang_karir_lainnya'] ?? '';
    $alasan_pemilihan_karir = $_POST['alasan_pemilihan_karir'];
    $keterampilan_yang_dimiliki = $_POST['keterampilan_yang_dimiliki'];
    $kursus_pelatihan = $_POST['kursus_pelatihan'];
    $rencana_pendidikan_lanjut = $_POST['rencana_pendidikan_lanjut'];
    $dukungan_orang_tua = $_POST['dukungan_orang_tua'];
    $informasi_karir_dari = $_POST['informasi_karir_dari'];
    $informasi_karir_lainnya = $_POST['informasi_karir_lainnya'] ?? '';
    
    if ($bidang_karir === 'Lainnya' && !empty($bidang_karir_lainnya)) {
        $bidang_karir_final = $bidang_karir_lainnya;
        $bidang_karir_asal = 'Lainnya';
        $bidang_karir_lainnya_value = $bidang_karir_lainnya;
    } else {
        $bidang_karir_final = $bidang_karir;
        $bidang_karir_asal = $bidang_karir;
        $bidang_karir_lainnya_value = '';
    }
    
    if ($informasi_karir_dari === 'Lainnya' && !empty($informasi_karir_lainnya)) {
        $informasi_karir_final = $informasi_karir_lainnya;
        $informasi_karir_asal = 'Lainnya';
        $informasi_karir_lainnya_value = $informasi_karir_lainnya;
    } else {
        $informasi_karir_final = $informasi_karir_dari;
        $informasi_karir_asal = $informasi_karir_dari;
        $informasi_karir_lainnya_value = '';
    }
    
    if ($karir) {
        $sql_update = "UPDATE form_karir SET 
                      MINAT_KARIR = ?, BIDANG_KARIR = ?, ALASAN_PEMILIHAN_KARIR = ?,
                      KETERAMPILAN_YANG_DIMILIKI = ?, KURSUS_PELATIHAN = ?, RENCANA_PENDIDIKAN_LANJUT = ?,
                      DUKUNGAN_ORANG_TUA = ?, INFORMASI_KARIR_DARI = ?
                      WHERE SISWA_ID = ?";
        $stmt_update = $koneksi->prepare($sql_update);
        $stmt_update->bind_param("ssssssssi", 
            $minat_karir, $bidang_karir_final, $alasan_pemilihan_karir,
            $keterampilan_yang_dimiliki, $kursus_pelatihan, $rencana_pendidikan_lanjut,
            $dukungan_orang_tua, $informasi_karir_final, $siswa_id
        );
        
        if ($stmt_update->execute()) {
            $success = "Data karir berhasil diperbarui!";
            $stmt_karir->execute();
            $karir = $stmt_karir->get_result()->fetch_assoc();
            $data_sudah_lengkap = true;
            
            $bidang_karir_asal = ($bidang_karir === 'Lainnya') ? 'Lainnya' : $bidang_karir;
            $informasi_karir_asal = ($informasi_karir_dari === 'Lainnya') ? 'Lainnya' : $informasi_karir_dari;
        } else {
            $error = "Gagal memperbarui data karir!";
        }
    } else {
        $sql_insert = "INSERT INTO form_karir 
                      (SISWA_ID, MINAT_KARIR, BIDANG_KARIR, ALASAN_PEMILIHAN_KARIR,
                       KETERAMPILAN_YANG_DIMILIKI, KURSUS_PELATIHAN, RENCANA_PENDIDIKAN_LANJUT,
                       DUKUNGAN_ORANG_TUA, INFORMASI_KARIR_DARI) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $koneksi->prepare($sql_insert);
        $stmt_insert->bind_param("issssssss", 
            $siswa_id, $minat_karir, $bidang_karir_final, $alasan_pemilihan_karir,
            $keterampilan_yang_dimiliki, $kursus_pelatihan, $rencana_pendidikan_lanjut,
            $dukungan_orang_tua, $informasi_karir_final
        );
        
        if ($stmt_insert->execute()) {
            $success = "Data karir berhasil disimpan!";
            $stmt_karir->execute();
            $karir = $stmt_karir->get_result()->fetch_assoc();
            $data_sudah_lengkap = true;
            
            $bidang_karir_asal = ($bidang_karir === 'Lainnya') ? 'Lainnya' : $bidang_karir;
            $informasi_karir_asal = ($informasi_karir_dari === 'Lainnya') ? 'Lainnya' : $informasi_karir_dari;
        } else {
            $error = "Gagal menyimpan data karir!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Karir Siswa - APK BK</title>
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
        
        /* Header & Sidebar Styles from data_diri.php */
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
            background: var(--bg-gradient);
            color: var(--text-main);
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

        body.sidebar-open {
            overflow: hidden;
        }

        .brand-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-left > a {
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
            overflow: visible;
            z-index: 1000;
            transition: margin-left 0.3s ease, background 0.3s ease;
            padding: 20px 40px; 
            display: flex; 
            justify-content: space-between;
            align-items: center;
            position: relative;
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

        .theme-toggle i {
            font-size: 18px;
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

        .nav a:active,
        .nav a.tap-active {
            color: var(--accent);
            border-color: var(--border-soft);
            transform: translateX(4px) scale(0.98);
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.14), rgba(59, 130, 246, 0.14));
        }

        .container {
            margin-left: 270px;
            max-width: none;
            transition: margin-left 0.3s ease;
            padding: 40px;
            position: relative;
            z-index: 5;
        }

        .content {
            background: var(--surface-card);
            border-radius: 20px;
            border: 1px solid var(--border-soft);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.1);
            backdrop-filter: blur(15px);
            overflow: hidden;
            animation: fadeIn 0.8s ease-out;
        }

        .alert {
            padding: 20px;
            border-radius: 12px;
            margin: 20px;
            font-weight: 500;
            border: 2px solid transparent;
            animation: slideUp 0.6s ease-out;
        }
        
        .alert-success {
            background: rgba(72, 187, 120, 0.1);
            border-color: rgba(72, 187, 120, 0.3);
            color: #38a169;
        }
        
        .alert-error {
            background: rgba(245, 101, 101, 0.1);
            border-color: rgba(245, 101, 101, 0.3);
            color: #e53e3e;
        }
        
        .alert-info {
            background: rgba(66, 153, 225, 0.1);
            border-color: rgba(66, 153, 225, 0.3);
            color: #3182ce;
        }

        .data-locked {
            text-align: center;
            padding: 40px;
            background: var(--surface-card);
            border-radius: 16px;
            margin: 20px;
            border: 2px dashed rgba(102, 126, 234, 0.3);
            animation: fadeIn 0.8s ease-out;
        }
        
        .data-locked h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .data-locked p {
            color: var(--text-muted);
            margin-bottom: 10px;
            line-height: 1.6;
        }

        .form-content {
            padding: 30px;
        }
        
        .form-section {
            margin-bottom: 40px;
            animation: slideUp 0.6s ease-out;
            background: var(--surface-card);
            border-radius: 16px;
            padding: 25px;
            border: 1px solid var(--border-soft);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.08);
        }
        
        .section-title {
            color: var(--text-main);
            font-size: 22px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(102, 126, 234, 0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 10px;
            color: var(--text-main);
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 15px 20px;
            background: rgba(255, 253, 245, 0.9);
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 12px;
            color: #2d3748;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }
        
        select option {
            background-color: white;
            color: #2d3748;
            padding: 12px;
        }
        
        ::placeholder {
            color: #a0aec0;
            opacity: 1;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.2);
            background: rgba(255, 253, 245, 0.95);
            transform: translateY(-2px);
        }
        
        textarea {
            resize: none;
            min-height: 120px;
            line-height: 1.6;
        }
        
        .info-text {
            color: var(--text-muted);
            font-size: 13px;
            margin-top: 8px;
            font-style: italic;
        }
        
        .lainnya-input {
            margin-top: 15px;
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        input[readonly], select[readonly], textarea[readonly],
        input:disabled, select:disabled, textarea:disabled {
            background: rgba(247, 250, 252, 0.6);
            color: #a0aec0;
            border-color: rgba(102, 126, 234, 0.1);
            cursor: not-allowed;
        }

        .submit-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 18px 40px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.4s ease;
            width: 100%;
            margin-top: 20px;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
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
        
        .submit-btn:hover:not(:disabled) {
            background: linear-gradient(135deg, #764ba2, #667eea);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4);
        }
        
        .submit-btn:disabled {
            background: linear-gradient(135deg, #a0aec0, #718096);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Dark Mode Adjustments */
        body.dark-mode input,
        body.dark-mode select,
        body.dark-mode textarea {
            background: rgba(15, 23, 42, 0.75);
            color: #e5e7eb;
        }

        body.dark-mode input::placeholder,
        body.dark-mode textarea::placeholder {
            color: #9ca3af;
        }

        body.dark-mode select option {
            background-color: #0f172a;
            color: #e5e7eb;
        }

        body.dark-mode .content,
        body.dark-mode .form-section,
        body.dark-mode .data-locked,
        body.dark-mode .alert {
            background: rgba(17, 24, 39, 0.9);
        }

        body.dark-mode .form-section:hover {
            background: rgba(30, 41, 59, 0.92);
            box-shadow: 0 12px 30px rgba(2, 6, 23, 0.45);
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
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive Styles matching data_diri.php */
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
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .floating {
                display: none;
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
            
            .form-content {
                padding: 15px;
            }
            
            input, select, textarea {
                padding: 12px 15px;
                font-size: 14px;
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
            <a href="halaman utama.php">
                <h1><i class='bx bx-briefcase'></i> Form Karir Siswa</h1>
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
        <a href="jadwal_guru.php">
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
            
            <?php if ($data_sudah_lengkap): ?>
                <div class="data-locked">
                    <h3><i class='bx bx-lock-alt'></i> Data Karir Sudah Lengkap</h3>
                    <p>Data karir Anda tidak dapat diubah lagi untuk menjaga keaslian data.</p>
                    <p>Jika ada kesalahan data, silakan hubungi Konselor BK.</p>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="formKarir" class="form-content">
                <div class="form-section">
                    <h3 class="section-title"><i class='bx bx-user'></i> Informasi Siswa</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" value="<?php echo htmlspecialchars($siswa['NAMA_LENGKAP']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Kelas</label>
                            <input type="text" value="<?php echo htmlspecialchars($siswa['KELAS'] ?? '-'); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Jurusan</label>
                            <input type="text" value="<?php echo htmlspecialchars($siswa['JURUSAN'] ?? '-'); ?>" readonly>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title"><i class='bx bx-target-lock'></i> Minat & Bidang Karir</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Minat Karir *</label>
                            <input type="text" name="minat_karir" 
                                   value="<?php echo htmlspecialchars($karir['MINAT_KARIR'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : 'required'; ?> 
                                   placeholder="Contoh: Programmer, Dokter, Akuntan, Desainer">
                            <div class="info-text">Jabatan atau profesi yang diminati</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Bidang Karir *</label>
                            <select name="bidang_karir" id="bidang_karir" <?php echo $data_sudah_lengkap ? 'disabled' : 'required'; ?>>
                                <option value="">Pilih Bidang Karir</option>
                                <option value="Teknik" <?php echo ($bidang_karir_asal == 'Teknik') ? 'selected' : ''; ?>>Teknik</option>
                                <option value="Kesehatan" <?php echo ($bidang_karir_asal == 'Kesehatan') ? 'selected' : ''; ?>>Kesehatan</option>
                                <option value="Ekonomi/Bisnis" <?php echo ($bidang_karir_asal == 'Ekonomi/Bisnis') ? 'selected' : ''; ?>>Ekonomi/Bisnis</option>
                                <option value="Sosial" <?php echo ($bidang_karir_asal == 'Sosial') ? 'selected' : ''; ?>>Sosial</option>
                                <option value="Seni" <?php echo ($bidang_karir_asal == 'Seni') ? 'selected' : ''; ?>>Seni</option>
                                <option value="Pendidikan" <?php echo ($bidang_karir_asal == 'Pendidikan') ? 'selected' : ''; ?>>Pendidikan</option>
                                <option value="Lainnya" <?php echo ($bidang_karir_asal == 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                            </select>
                            
                            <div class="lainnya-input" id="bidang_karir_lainnya_container">
                                <label>Bidang Karir Lainnya *</label>
                                <input type="text" name="bidang_karir_lainnya" id="bidang_karir_lainnya"
                                       value="<?php echo htmlspecialchars($bidang_karir_lainnya_value); ?>"
                                       <?php echo $data_sudah_lengkap ? 'readonly' : ''; ?>
                                       placeholder="Tulis bidang karir lainnya">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Alasan Pemilihan Karir</label>
                        <textarea name="alasan_pemilihan_karir" <?php echo $data_sudah_lengkap ? 'readonly' : ''; ?> 
                                  placeholder="Jelaskan mengapa Anda memilih karir tersebut (opsional)"><?php echo htmlspecialchars($karir['ALASAN_PEMILIHAN_KARIR'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title"><i class='bx bx-cog'></i> Keterampilan & Pelatihan</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Keterampilan yang Dimiliki</label>
                            <textarea name="keterampilan_yang_dimiliki" <?php echo $data_sudah_lengkap ? 'readonly' : ''; ?> 
                                      placeholder="Contoh: Programming, Desain Grafis, Public Speaking, Leadership"><?php echo htmlspecialchars($karir['KETERAMPILAN_YANG_DIMILIKI'] ?? ''); ?></textarea>
                            <div class="info-text">Keterampilan yang mendukung karir Anda</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Kursus/Pelatihan yang Diikuti</label>
                            <textarea name="kursus_pelatihan" <?php echo $data_sudah_lengkap ? 'readonly' : ''; ?> 
                                      placeholder="Contoh: Kursus Programming, Pelatihan Desain, Sertifikasi Bahasa Inggris"><?php echo htmlspecialchars($karir['KURSUS_PELATIHAN'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title"><i class='bx bx-graduation'></i> Rencana Pendidikan Lanjut</h3>
                    <div class="form-group">
                        <label>Rencana Pendidikan Lanjut</label>
                        <input type="text" name="rencana_pendidikan_lanjut" 
                               value="<?php echo htmlspecialchars($karir['RENCANA_PENDIDIKAN_LANJUT'] ?? ''); ?>" 
                               <?php echo $data_sudah_lengkap ? 'readonly' : ''; ?> 
                               placeholder="Contoh: Kuliah di Universitas Indonesia, Kursus Programming, Bekerja langsung">
                        <div class="info-text">Rencana setelah lulus SMK</div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title"><i class='bx bx-group'></i> Dukungan & Informasi</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Dukungan Orang Tua *</label>
                            <select name="dukungan_orang_tua" <?php echo $data_sudah_lengkap ? 'disabled' : 'required'; ?>>
                                <option value="">Pilih Tingkat Dukungan</option>
                                <option value="Sangat Mendukung" <?php echo ($karir['DUKUNGAN_ORANG_TUA'] ?? '') == 'Sangat Mendukung' ? 'selected' : ''; ?>>Sangat Mendukung</option>
                                <option value="Mendukung" <?php echo ($karir['DUKUNGAN_ORANG_TUA'] ?? '') == 'Mendukung' ? 'selected' : ''; ?>>Mendukung</option>
                                <option value="Netral" <?php echo ($karir['DUKUNGAN_ORANG_TUA'] ?? '') == 'Netral' ? 'selected' : ''; ?>>Netral</option>
                                <option value="Tidak Mendukung" <?php echo ($karir['DUKUNGAN_ORANG_TUA'] ?? '') == 'Tidak Mendukung' ? 'selected' : ''; ?>>Tidak Mendukung</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Informasi Karir Dari *</label>
                            <select name="informasi_karir_dari" id="informasi_karir_dari" <?php echo $data_sudah_lengkap ? 'disabled' : 'required'; ?>>
                                <option value="">Pilih Sumber Informasi</option>
                                <option value="Sekolah" <?php echo ($informasi_karir_asal == 'Sekolah') ? 'selected' : ''; ?>>Sekolah</option>
                                <option value="Orang Tua" <?php echo ($informasi_karir_asal == 'Orang Tua') ? 'selected' : ''; ?>>Orang Tua</option>
                                <option value="Teman" <?php echo ($informasi_karir_asal == 'Teman') ? 'selected' : ''; ?>>Teman</option>
                                <option value="Internet" <?php echo ($informasi_karir_asal == 'Internet') ? 'selected' : ''; ?>>Internet</option>
                                <option value="Lainnya" <?php echo ($informasi_karir_asal == 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                            </select>
                            
                            <div class="lainnya-input" id="informasi_karir_lainnya_container">
                                <label>Informasi Karir Lainnya *</label>
                                <input type="text" name="informasi_karir_lainnya" id="informasi_karir_lainnya"
                                       value="<?php echo htmlspecialchars($informasi_karir_lainnya_value); ?>"
                                       <?php echo $data_sudah_lengkap ? 'readonly' : ''; ?>
                                       placeholder="Tulis sumber informasi lainnya">
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (!$data_sudah_lengkap): ?>
                    <button type="submit" class="submit-btn">
                        <i class='bx bx-save'></i>
                        Simpan Data Karir
                    </button>
                    <div class="alert alert-info">
                        <i class='bx bx-info-circle'></i>
                        <strong>Perhatian:</strong> Data yang sudah disimpan tidak dapat diubah lagi. Pastikan semua data sudah benar sebelum menyimpan.
                    </div>
                <?php else: ?>
                    <button type="button" class="submit-btn" disabled>
                        <i class='bx bx-lock'></i>
                        Data Sudah Terkunci
                    </button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar Logics
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

            // Theme Toggle Logic
            const savedTheme = localStorage.getItem('dashboard_theme');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-mode');
                if (themeToggle) {
                    themeToggle.innerHTML = "<i class='bx bx-sun'></i><span>Mode</span>";
                }
            }

            if (themeToggle) {
                themeToggle.addEventListener('click', function() {
                    const isDark = document.body.classList.toggle('dark-mode');
                    localStorage.setItem('dashboard_theme', isDark ? 'dark' : 'light');
                    this.innerHTML = isDark
                        ? "<i class='bx bx-sun'></i><span>Mode</span>"
                        : "<i class='bx bx-moon'></i><span>Mode</span>";
                });
            }

            if (sidebarToggle) {
                let lastSidebarToggle = 0;
                const handleSidebarToggle = function(e) {
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
                link.addEventListener('touchstart', function() {
                    this.classList.add('tap-active');
                }, { passive: true });

                link.addEventListener('touchend', function() {
                    setTimeout(() => {
                        this.classList.remove('tap-active');
                    }, 140);
                }, { passive: true });

                link.addEventListener('touchcancel', function() {
                    this.classList.remove('tap-active');
                }, { passive: true });

                link.addEventListener('click', function() {
                    if (window.innerWidth <= 1024) {
                        closeSidebar();
                    }
                });
            });

            window.addEventListener('resize', function() {
                if (window.innerWidth > 1024) {
                    closeSidebar();
                }
            });

            // Form Animation
            const formSections = document.querySelectorAll('.form-section');
            formSections.forEach((section, index) => {
                section.style.animationDelay = `${index * 0.1}s`;
            });
            
            // Form Specific Logic from form_karir.php
             const bidangKarirSelect = document.getElementById('bidang_karir');
            const informasiKarirSelect = document.getElementById('informasi_karir_dari');
            
            <?php if ($bidang_karir_asal == 'Lainnya'): ?>
                const bkContainer = document.getElementById('bidang_karir_lainnya_container');
                const bkInput = document.getElementById('bidang_karir_lainnya');
                if(bkContainer) bkContainer.style.display = 'block';
                if(bkInput) bkInput.required = true;
            <?php endif; ?>
            
            <?php if ($informasi_karir_asal == 'Lainnya'): ?>
                const ikContainer = document.getElementById('informasi_karir_lainnya_container');
                const ikInput = document.getElementById('informasi_karir_lainnya');
                if(ikContainer) ikContainer.style.display = 'block';
                if(ikInput) ikInput.required = true;
            <?php endif; ?>
            
            if(bidangKarirSelect) {
                bidangKarirSelect.addEventListener('change', function() {
                    toggleLainnyaInput(
                        this, 
                        'bidang_karir_lainnya_container', 
                        'bidang_karir_lainnya'
                    );
                });
            }
            
            if(informasiKarirSelect) {
                informasiKarirSelect.addEventListener('change', function() {
                    toggleLainnyaInput(
                        this, 
                        'informasi_karir_lainnya_container', 
                        'informasi_karir_lainnya'
                    );
                });
            }
        });

        // Helper function for form logic
        function toggleLainnyaInput(selectElement, containerId, inputElement) {
            const container = document.getElementById(containerId);
            const input = document.getElementById(inputElement);
            
            if (selectElement.value === 'Lainnya') {
                container.style.display = 'block';
                input.required = true;
            } else {
                container.style.display = 'none';
                input.required = false;
            }
        }
        
        document.getElementById('formKarir').addEventListener('submit', function(e) {
            <?php if ($data_sudah_lengkap): ?>
                e.preventDefault();
                alert('Data sudah lengkap dan tidak dapat diubah lagi. Silakan hubungi konselor BK jika ada kesalahan.');
            <?php endif; ?>
        });

        <?php if ($data_sudah_lengkap): ?>
            document.querySelectorAll('input, select, textarea').forEach(function(element) {
                if (element.tagName === 'SELECT') {
                    element.setAttribute('disabled', 'disabled');
                } else if (!element.hasAttribute('readonly')) {
                    element.setAttribute('readonly', 'readonly');
                }
            });
        <?php endif; ?>
    </script>
</body>
</html>