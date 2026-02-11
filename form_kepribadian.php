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

$sql_kepribadian = "SELECT * FROM form_kepribadian WHERE SISWA_ID = ?";
$stmt_kepribadian = $koneksi->prepare($sql_kepribadian);
$stmt_kepribadian->bind_param("i", $siswa_id);
$stmt_kepribadian->execute();
$kepribadian = $stmt_kepribadian->get_result()->fetch_assoc();

$data_sudah_lengkap = false;
if ($kepribadian) {
    if (!empty($kepribadian['NAMA_AYAH']) && !empty($kepribadian['PEKERJAAN_AYAH']) && 
        !empty($kepribadian['PENDIDIKAN_AYAH']) && !empty($kepribadian['NAMA_IBU']) &&
        !empty($kepribadian['PEKERJAAN_IBU']) && !empty($kepribadian['PENDIDIKAN_IBU']) &&
        !empty($kepribadian['STATUS_RUMAH']) && !empty($kepribadian['STATUS_KELUARGA']) &&
        !empty($kepribadian['JUMLAH_ANGGOTA_KELUARGA']) && !empty($kepribadian['ANAK_KE']) &&
        !empty($kepribadian['HUBUNGAN_DENGAN_ORTU'])) {
        $data_sudah_lengkap = true;
    }
}

$status_rumah_asal = $kepribadian['STATUS_RUMAH'] ?? '';
$status_rumah_lainnya_value = '';
$status_rumah_display = '';

if ($status_rumah_asal) {
    $dropdown_status = ['Milik Sendiri', 'Kontrak', 'Kost', 'Lainnya'];
    if (in_array($status_rumah_asal, $dropdown_status)) {
        $status_rumah_display = $status_rumah_asal;
        $status_rumah_lainnya_value = '';
    } else {
        $status_rumah_display = 'Lainnya';
        $status_rumah_lainnya_value = $status_rumah_asal;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$data_sudah_lengkap) {
    $nama_ayah = $_POST['nama_ayah'];
    $pekerjaan_ayah = $_POST['pekerjaan_ayah'];
    $pendidikan_ayah = $_POST['pendidikan_ayah'];
    $penghasilan_ayah = $_POST['penghasilan_ayah'];
    $nama_ibu = $_POST['nama_ibu'];
    $pekerjaan_ibu = $_POST['pekerjaan_ibu'];
    $pendidikan_ibu = $_POST['pendidikan_ibu'];
    $penghasilan_ibu = $_POST['penghasilan_ibu'];
    $status_rumah = $_POST['status_rumah'];
    $status_rumah_lainnya = $_POST['status_rumah_lainnya'] ?? '';
    $kendaraan = $_POST['kendaraan'];
    $status_keluarga = $_POST['status_keluarga'];
    $jumlah_anggota_keluarga = $_POST['jumlah_anggota_keluarga'];
    $anak_ke = $_POST['anak_ke'];
    $hubungan_dengan_ortu = $_POST['hubungan_dengan_ortu'];
    $masalah_keluarga = $_POST['masalah_keluarga'];
    
    if ($status_rumah === 'Lainnya' && !empty($status_rumah_lainnya)) {
        $status_rumah_final = $status_rumah_lainnya;
    } else {
        $status_rumah_final = $status_rumah;
    }
    
    if ($kepribadian) {
        $sql_update = "UPDATE form_kepribadian SET 
                      NAMA_AYAH = ?, PEKERJAAN_AYAH = ?, PENDIDIKAN_AYAH = ?, PENGHASILAN_AYAH = ?,
                      NAMA_IBU = ?, PEKERJAAN_IBU = ?, PENDIDIKAN_IBU = ?, PENGHASILAN_IBU = ?,
                      STATUS_RUMAH = ?, KENDARAAN = ?, STATUS_KELUARGA = ?, JUMLAH_ANGGOTA_KELUARGA = ?,
                      ANAK_KE = ?, HUBUNGAN_DENGAN_ORTU = ?, MASALAH_KELUARGA = ?
                      WHERE SISWA_ID = ?";
        $stmt_update = $koneksi->prepare($sql_update);
        $stmt_update->bind_param("ssssssssssssissi", 
            $nama_ayah, $pekerjaan_ayah, $pendidikan_ayah, $penghasilan_ayah,
            $nama_ibu, $pekerjaan_ibu, $pendidikan_ibu, $penghasilan_ibu,
            $status_rumah_final, $kendaraan, $status_keluarga, $jumlah_anggota_keluarga,
            $anak_ke, $hubungan_dengan_ortu, $masalah_keluarga, $siswa_id
        );
        
        if ($stmt_update->execute()) {
            $success = "Data kepribadian berhasil diperbarui!";
            $stmt_kepribadian->execute();
            $kepribadian = $stmt_kepribadian->get_result()->fetch_assoc();
            $data_sudah_lengkap = true;
            
            if ($status_rumah === 'Lainnya') {
                $status_rumah_display = 'Lainnya';
                $status_rumah_lainnya_value = $status_rumah_lainnya;
            } else {
                $status_rumah_display = $status_rumah;
                $status_rumah_lainnya_value = '';
            }
        } else {
            $error = "Gagal memperbarui data kepribadian!";
        }
    } else {
        $sql_insert = "INSERT INTO form_kepribadian 
                      (SISWA_ID, NAMA_AYAH, PEKERJAAN_AYAH, PENDIDIKAN_AYAH, PENGHASILAN_AYAH,
                       NAMA_IBU, PEKERJAAN_IBU, PENDIDIKAN_IBU, PENGHASILAN_IBU,
                       STATUS_RUMAH, KENDARAAN, STATUS_KELUARGA, JUMLAH_ANGGOTA_KELUARGA,
                       ANAK_KE, HUBUNGAN_DENGAN_ORTU, MASALAH_KELUARGA) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $koneksi->prepare($sql_insert);
        $stmt_insert->bind_param("isssssssssssiiss", 
            $siswa_id, $nama_ayah, $pekerjaan_ayah, $pendidikan_ayah, $penghasilan_ayah,
            $nama_ibu, $pekerjaan_ibu, $pendidikan_ibu, $penghasilan_ibu,
            $status_rumah_final, $kendaraan, $status_keluarga, $jumlah_anggota_keluarga,
            $anak_ke, $hubungan_dengan_ortu, $masalah_keluarga
        );
        
        if ($stmt_insert->execute()) {
            $success = "Data kepribadian berhasil disimpan!";
            $stmt_kepribadian->execute();
            $kepribadian = $stmt_kepribadian->get_result()->fetch_assoc();
            $data_sudah_lengkap = true;
            
            if ($status_rumah === 'Lainnya') {
                $status_rumah_display = 'Lainnya';
                $status_rumah_lainnya_value = $status_rumah_lainnya;
            } else {
                $status_rumah_display = $status_rumah;
                $status_rumah_lainnya_value = '';
            }
        } else {
            $error = "Gagal menyimpan data kepribadian!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Kepribadian Siswa - APK BK</title>
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
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
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
        
        .student-info {
            background: rgba(102, 126, 234, 0.05);
            padding: 25px;
            border-radius: 16px;
            margin: 20px;
            border: 1px solid rgba(102, 126, 234, 0.15);
            animation: fadeIn 0.8s ease-out;
        }
        
        .student-info h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .student-info p {
            color: #718096;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .data-locked {
            text-align: center;
            padding: 40px;
            background: rgba(102, 126, 234, 0.05);
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
            color: #718096;
            margin-bottom: 10px;
            line-height: 1.6;
        }
        
        .form-content {
            padding: 30px;
        }
        
        .form-section {
            margin-bottom: 40px;
            animation: slideUp 0.6s ease-out;
            background: rgba(255, 251, 240, 0.8);
            border-radius: 16px;
            padding: 25px;
            border: 1px solid rgba(102, 126, 234, 0.15);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.08);
        }
        
        .section-title {
            color: #2d3748;
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
            color: #4a5568;
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
            resize: vertical;
            min-height: 120px;
            line-height: 1.6;
        }
        
        .info-text {
            color: #718096;
            font-size: 13px;
            margin-top: 8px;
            font-style: italic;
        }
        
        input[readonly], select[readonly], textarea[readonly],
        input:disabled, select:disabled, textarea:disabled {
            background: rgba(248, 246, 240, 0.8);
            color: #a0aec0;
            border-color: rgba(102, 126, 234, 0.1);
            cursor: not-allowed;
        }
        
        .lainnya-input {
            margin-top: 15px;
            display: none;
            animation: fadeIn 0.3s ease;
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
            border-bottom-color: var(--accent);
            box-shadow: var(--shadow-soft);
            overflow: visible;
            z-index: 1000;
            transition: margin-left 0.3s ease, background 0.3s ease;
        }

        .header h1 {
            background: linear-gradient(135deg, var(--accent), #1d4ed8);
            -webkit-background-clip: text;
            background-clip: text;
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
        }

        .content,
        .form-section,
        .data-locked,
        .student-info,
        .alert,
        input,
        select,
        textarea {
            border-color: var(--border-soft);
        }

        .content,
        .form-section,
        .data-locked,
        .student-info {
            background: var(--surface-card);
        }

        .section-title,
        label,
        .data-locked h3,
        .student-info h3 {
            color: var(--text-main);
        }

        .info-text,
        .data-locked p,
        .student-info p {
            color: var(--text-muted);
        }

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
        body.dark-mode .student-info,
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

            .form-content {
                padding: 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .form-section {
                margin-bottom: 30px;
            }

            .section-title {
                font-size: 20px;
            }

            .student-info,
            .data-locked {
                padding: 30px 20px;
                margin: 15px;
            }

            .floating {
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

            input,
            select,
            textarea {
                padding: 12px 15px;
                font-size: 14px;
            }

            .submit-btn {
                padding: 15px 25px;
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
                <h1><i class='bx bx-brain'></i> Form Kepribadian Siswa</h1>
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
            
            <div class="student-info">
                <h3><i class='bx bx-user'></i> Informasi Siswa</h3>
                <p><strong>Nama:</strong> <?php echo htmlspecialchars($siswa['NAMA_LENGKAP']); ?></p>
                <p><strong>NIS:</strong> <?php echo htmlspecialchars($siswa['NIS'] ?? '-'); ?></p>
                <p><strong>Kelas:</strong> <?php echo htmlspecialchars($siswa['KELAS'] ?? '-'); ?></p>
                <p><strong>Jurusan:</strong> <?php echo htmlspecialchars($siswa['JURUSAN'] ?? '-'); ?></p>
            </div>
            
            <?php if ($data_sudah_lengkap): ?>
                <div class="data-locked">
                    <h3><i class='bx bx-lock-alt'></i> Data Kepribadian Sudah Lengkap</h3>
                    <p>Data kepribadian Anda tidak dapat diubah lagi untuk menjaga keaslian data.</p>
                    <p>Jika ada kesalahan data, silakan hubungi Konselor BK.</p>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="formKepribadian" class="form-content">
                <div class="form-section">
                    <h3 class="section-title"><i class='bx bx-group'></i> Data Keluarga</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Status Keluarga *</label>
                            <select name="status_keluarga" <?php echo $data_sudah_lengkap ? 'disabled' : 'required'; ?>>
                                <option value="">Pilih Status Keluarga</option>
                                <option value="Lengkap" <?php echo ($kepribadian['STATUS_KELUARGA'] ?? '') == 'Lengkap' ? 'selected' : ''; ?>>Lengkap</option>
                                <option value="Orang Tua Bercerai" <?php echo ($kepribadian['STATUS_KELUARGA'] ?? '') == 'Orang Tua Bercerai' ? 'selected' : ''; ?>>Orang Tua Bercerai</option>
                                <option value="Yatim" <?php echo ($kepribadian['STATUS_KELUARGA'] ?? '') == 'Yatim' ? 'selected' : ''; ?>>Yatim</option>
                                <option value="Piatu" <?php echo ($kepribadian['STATUS_KELUARGA'] ?? '') == 'Piatu' ? 'selected' : ''; ?>>Piatu</option>
                                <option value="Yatim Piatu" <?php echo ($kepribadian['STATUS_KELUARGA'] ?? '') == 'Yatim Piatu' ? 'selected' : ''; ?>>Yatim Piatu</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Jumlah Anggota Keluarga *</label>
                            <input type="number" name="jumlah_anggota_keluarga" 
                                   value="<?php echo htmlspecialchars($kepribadian['JUMLAH_ANGGOTA_KELUARGA'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : 'required'; ?> 
                                   min="1" max="20" placeholder="Contoh: 4">
                        </div>
                        
                        <div class="form-group">
                            <label>Anak Ke- *</label>
                            <input type="number" name="anak_ke" 
                                   value="<?php echo htmlspecialchars($kepribadian['ANAK_KE'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : 'required'; ?> 
                                   min="1" max="20" placeholder="Contoh: 2">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title"><i class='bx bx-male'></i> Data Ayah</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nama Ayah *</label>
                            <input type="text" name="nama_ayah" 
                                   value="<?php echo htmlspecialchars($kepribadian['NAMA_AYAH'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : 'required'; ?> 
                                   placeholder="Masukkan nama lengkap ayah">
                        </div>
                        
                        <div class="form-group">
                            <label>Pekerjaan Ayah *</label>
                            <input type="text" name="pekerjaan_ayah" 
                                   value="<?php echo htmlspecialchars($kepribadian['PEKERJAAN_AYAH'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : 'required'; ?> 
                                   placeholder="Contoh: PNS, Wiraswasta, Buruh">
                        </div>
                        
                        <div class="form-group">
                            <label>Pendidikan Ayah *</label>
                            <select name="pendidikan_ayah" <?php echo $data_sudah_lengkap ? 'disabled' : 'required'; ?>>
                                <option value="">Pilih Pendidikan</option>
                                <option value="SD" <?php echo ($kepribadian['PENDIDIKAN_AYAH'] ?? '') == 'SD' ? 'selected' : ''; ?>>SD</option>
                                <option value="SMP" <?php echo ($kepribadian['PENDIDIKAN_AYAH'] ?? '') == 'SMP' ? 'selected' : ''; ?>>SMP</option>
                                <option value="SMA" <?php echo ($kepribadian['PENDIDIKAN_AYAH'] ?? '') == 'SMA' ? 'selected' : ''; ?>>SMA</option>
                                <option value="D1/D2/D3" <?php echo ($kepribadian['PENDIDIKAN_AYAH'] ?? '') == 'D1/D2/D3' ? 'selected' : ''; ?>>D1/D2/D3</option>
                                <option value="S1" <?php echo ($kepribadian['PENDIDIKAN_AYAH'] ?? '') == 'S1' ? 'selected' : ''; ?>>S1</option>
                                <option value="S2" <?php echo ($kepribadian['PENDIDIKAN_AYAH'] ?? '') == 'S2' ? 'selected' : ''; ?>>S2</option>
                                <option value="S3" <?php echo ($kepribadian['PENDIDIKAN_AYAH'] ?? '') == 'S3' ? 'selected' : ''; ?>>S3</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Penghasilan Ayah (Rp)</label>
                            <input type="number" name="penghasilan_ayah" 
                                   value="<?php echo htmlspecialchars($kepribadian['PENGHASILAN_AYAH'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : ''; ?> 
                                   min="0" step="100000" placeholder="Contoh: 5000000">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title"><i class='bx bx-female'></i> Data Ibu</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nama Ibu *</label>
                            <input type="text" name="nama_ibu" 
                                   value="<?php echo htmlspecialchars($kepribadian['NAMA_IBU'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : 'required'; ?> 
                                   placeholder="Masukkan nama lengkap ibu">
                        </div>
                        
                        <div class="form-group">
                            <label>Pekerjaan Ibu *</label>
                            <input type="text" name="pekerjaan_ibu" 
                                   value="<?php echo htmlspecialchars($kepribadian['PEKERJAAN_IBU'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : 'required'; ?> 
                                   placeholder="Contoh: Ibu Rumah Tangga, PNS, Wiraswasta">
                        </div>
                        
                        <div class="form-group">
                            <label>Pendidikan Ibu *</label>
                            <select name="pendidikan_ibu" <?php echo $data_sudah_lengkap ? 'disabled' : 'required'; ?>>
                                <option value="">Pilih Pendidikan</option>
                                <option value="SD" <?php echo ($kepribadian['PENDIDIKAN_IBU'] ?? '') == 'SD' ? 'selected' : ''; ?>>SD</option>
                                <option value="SMP" <?php echo ($kepribadian['PENDIDIKAN_IBU'] ?? '') == 'SMP' ? 'selected' : ''; ?>>SMP</option>
                                <option value="SMA" <?php echo ($kepribadian['PENDIDIKAN_IBU'] ?? '') == 'SMA' ? 'selected' : ''; ?>>SMA</option>
                                <option value="D1/D2/D3" <?php echo ($kepribadian['PENDIDIKAN_IBU'] ?? '') == 'D1/D2/D3' ? 'selected' : ''; ?>>D1/D2/D3</option>
                                <option value="S1" <?php echo ($kepribadian['PENDIDIKAN_IBU'] ?? '') == 'S1' ? 'selected' : ''; ?>>S1</option>
                                <option value="S2" <?php echo ($kepribadian['PENDIDIKAN_IBU'] ?? '') == 'S2' ? 'selected' : ''; ?>>S2</option>
                                <option value="S3" <?php echo ($kepribadian['PENDIDIKAN_IBU'] ?? '') == 'S3' ? 'selected' : ''; ?>>S3</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Penghasilan Ibu (Rp)</label>
                            <input type="number" name="penghasilan_ibu" 
                                   value="<?php echo htmlspecialchars($kepribadian['PENGHASILAN_IBU'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : ''; ?> 
                                   min="0" step="100000" placeholder="Contoh: 3000000">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title"><i class='bx bx-home'></i> Kondisi Tempat Tinggal</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Status Rumah *</label>
                            <select name="status_rumah" id="status_rumah" <?php echo $data_sudah_lengkap ? 'disabled' : 'required'; ?>>
                                <option value="">Pilih Status Rumah</option>
                                <option value="Milik Sendiri" <?php echo ($status_rumah_display == 'Milik Sendiri') ? 'selected' : ''; ?>>Milik Sendiri</option>
                                <option value="Kontrak" <?php echo ($status_rumah_display == 'Kontrak') ? 'selected' : ''; ?>>Kontrak</option>
                                <option value="Kost" <?php echo ($status_rumah_display == 'Kost') ? 'selected' : ''; ?>>Kost</option>
                                <option value="Lainnya" <?php echo ($status_rumah_display == 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                            </select>
                            
                            <div class="lainnya-input" id="status_rumah_lainnya_container">
                                <label>Status Rumah Lainnya *</label>
                                <input type="text" name="status_rumah_lainnya" id="status_rumah_lainnya"
                                       value="<?php echo htmlspecialchars($status_rumah_lainnya_value); ?>"
                                       <?php echo $data_sudah_lengkap ? 'readonly' : ''; ?>
                                       placeholder="Tulis status rumah lainnya">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Kendaraan yang Dimiliki</label>
                            <textarea name="kendaraan" <?php echo $data_sudah_lengkap ? 'readonly' : ''; ?> 
                                      placeholder="Contoh: Motor 2, Mobil 1, Sepeda 1"><?php echo htmlspecialchars($kepribadian['KENDARAAN'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title"><i class='bx bx-heart'></i> Hubungan Keluarga</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Hubungan dengan Orang Tua *</label>
                            <select name="hubungan_dengan_ortu" <?php echo $data_sudah_lengkap ? 'disabled' : 'required'; ?>>
                                <option value="">Pilih Tingkat Hubungan</option>
                                <option value="Sangat Baik" <?php echo ($kepribadian['HUBUNGAN_DENGAN_ORTU'] ?? '') == 'Sangat Baik' ? 'selected' : ''; ?>>Sangat Baik</option>
                                <option value="Baik" <?php echo ($kepribadian['HUBUNGAN_DENGAN_ORTU'] ?? '') == 'Baik' ? 'selected' : ''; ?>>Baik</option>
                                <option value="Cukup" <?php echo ($kepribadian['HUBUNGAN_DENGAN_ORTU'] ?? '') == 'Cukup' ? 'selected' : ''; ?>>Cukup</option>
                                <option value="Kurang" <?php echo ($kepribadian['HUBUNGAN_DENGAN_ORTU'] ?? '') == 'Kurang' ? 'selected' : ''; ?>>Kurang</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Masalah Keluarga (Jika Ada)</label>
                            <textarea name="masalah_keluarga" <?php echo $data_sudah_lengkap ? 'readonly' : ''; ?> 
                                      placeholder="Ceritakan masalah keluarga yang mungkin mempengaruhi kondisi Anda (opsional)"><?php echo htmlspecialchars($kepribadian['MASALAH_KELUARGA'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <?php if (!$data_sudah_lengkap): ?>
                    <button type="submit" class="submit-btn">
                        <i class='bx bx-save'></i>
                        Simpan Data Kepribadian
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
        function toggleStatusRumahLainnya() {
            const statusRumahSelect = document.getElementById('status_rumah');
            const statusRumahLainnyaContainer = document.getElementById('status_rumah_lainnya_container');
            const statusRumahLainnyaInput = document.getElementById('status_rumah_lainnya');

            if (!statusRumahSelect || !statusRumahLainnyaContainer || !statusRumahLainnyaInput) {
                return;
            }

            if (statusRumahSelect.value === 'Lainnya') {
                statusRumahLainnyaContainer.style.display = 'block';
                statusRumahLainnyaInput.required = true;
            } else {
                statusRumahLainnyaContainer.style.display = 'none';
                statusRumahLainnyaInput.required = false;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('formKepribadian');
            const statusRumahSelect = document.getElementById('status_rumah');
            const statusRumahLainnyaInput = document.getElementById('status_rumah_lainnya');
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

            if (statusRumahSelect) {
                statusRumahSelect.addEventListener('change', toggleStatusRumahLainnya);
            }

            <?php if ($status_rumah_display == 'Lainnya'): ?>
                if (statusRumahLainnyaInput) {
                    document.getElementById('status_rumah_lainnya_container').style.display = 'block';
                    statusRumahLainnyaInput.required = true;
                }
            <?php endif; ?>

            <?php if ($data_sudah_lengkap): ?>
                document.querySelectorAll('input, select, textarea').forEach(function(element) {
                    if (element.tagName === 'SELECT') {
                        element.setAttribute('disabled', 'disabled');
                    } else if (!element.hasAttribute('readonly')) {
                        element.setAttribute('readonly', 'readonly');
                    }
                });
            <?php endif; ?>

            if (form) {
                form.addEventListener('submit', function(e) {
                    <?php if ($data_sudah_lengkap): ?>
                        e.preventDefault();
                        alert('Data sudah lengkap dan tidak dapat diubah lagi. Silakan hubungi konselor BK jika ada kesalahan.');
                        return;
                    <?php endif; ?>

                    if (statusRumahSelect && statusRumahLainnyaInput && statusRumahSelect.value === 'Lainnya' && statusRumahLainnyaInput.value.trim() === '') {
                        e.preventDefault();
                        alert('Silakan isi status rumah lainnya.');
                        statusRumahLainnyaInput.focus();
                    }
                });
            }

            const formSections = document.querySelectorAll('.form-section');
            formSections.forEach((section, index) => {
                section.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>
