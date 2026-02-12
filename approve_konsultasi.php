<?php
session_start();
include 'koneksi.php';
include 'notifikasi_helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Guru_BK') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Guru BK';
$success = '';
$error = '';

$sql_guru = "SELECT g.ID FROM guru_bk g WHERE g.USER_ID = ?";
$stmt_guru = $koneksi->prepare($sql_guru);
$stmt_guru->bind_param("i", $user_id);
$stmt_guru->execute();
$guru = $stmt_guru->get_result()->fetch_assoc();
$guru_id = $guru['ID'];

$jumlah_notif_konsultasi = 0;
if ($guru_id) {
    $sql_menunggu = "SELECT COUNT(*) as jumlah FROM konsultasi 
                    WHERE STATUS = 'Menunggu' 
                    AND (PILIHAN_GURU_1 = ? OR PILIHAN_GURU_2 = ?)";
    $stmt_menunggu = $koneksi->prepare($sql_menunggu);
    $stmt_menunggu->bind_param("ii", $guru_id, $guru_id);
    $stmt_menunggu->execute();
    $result_menunggu = $stmt_menunggu->get_result()->fetch_assoc();
    $jumlah_notif_konsultasi = $result_menunggu['jumlah'] ?? 0;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $konsultasi_id = $_POST['konsultasi_id'];
    $action = $_POST['action'];
    $komentar = trim($_POST['komentar'] ?? '');
    $tanggal_konsultasi = $_POST['tanggal_konsultasi'] ?? null;
    $jam_konsultasi = $_POST['jam_konsultasi'] ?? null;
    $tempat_konsultasi = trim($_POST['tempat_konsultasi'] ?? 'Ruang BK SMK 7 Batam');
    $meeting_link = trim($_POST['meeting_link'] ?? '');

    $sql_konsul = "SELECT k.*, s.USER_ID as siswa_user_id, u.NAMA_LENGKAP as nama_siswa
                   FROM konsultasi k
                   JOIN siswa s ON k.SISWA_ID = s.ID
                   JOIN users u ON s.USER_ID = u.ID
                   WHERE k.ID = ?";
    $stmt_konsul = $koneksi->prepare($sql_konsul);
    $stmt_konsul->bind_param("i", $konsultasi_id);
    $stmt_konsul->execute();
    $konsultasi_data = $stmt_konsul->get_result()->fetch_assoc();
    
    if (!$konsultasi_data) {
        $error = "Konsultasi tidak ditemukan!";
    } else if ($action == 'approve') {
        $mode_konsultasi = $konsultasi_data['MODE_KONSULTASI'] ?? 'Offline';
        if ($mode_konsultasi === 'Online') {
            if ($meeting_link === '') {
                $error = "Link meeting wajib diisi untuk konsultasi online!";
            }
            $tempat_konsultasi = 'Online';
        }

        if ($error) {
        } else {
        $sql = "UPDATE konsultasi SET 
                STATUS = 'Disetujui', 
                GURU_BK_ID = ?, 
                TANGGAL_DISETUJUI = NOW(), 
                TANGGAL_KONSULTASI = ?,
                JAM_KONSULTASI = ?,
                TEMPAT_KONSULTASI = ?,
                MEETING_LINK = ?,
                KOMENTAR_GURU = ? 
                WHERE ID = ?";
        $stmt = $koneksi->prepare($sql);
        $meeting_link_param = $mode_konsultasi === 'Online' ? $meeting_link : null;
        $stmt->bind_param("isssssi", $guru_id, $tanggal_konsultasi, $jam_konsultasi, $tempat_konsultasi, $meeting_link_param, $komentar, $konsultasi_id);
        
        if ($stmt->execute()) {
            $judul = "Konsultasi Disetujui! ✅";
            $pesan = "Konsultasi Anda dengan topik '" . $konsultasi_data['TOPIK_KONSULTASI'] . "' telah disetujui oleh " . $nama_lengkap . ". Tanggal : " . date('d/m/Y', strtotime($tanggal_konsultasi)) . " Jam : $jam_konsultasi.";
            if ($mode_konsultasi === 'Online') {
                $pesan .= " Mode: Online. Link meeting: " . $meeting_link_param . ".";
            } else {
                $pesan .= " Mode: Offline. Tempat: $tempat_konsultasi.";
            }
            
            $notif_result = buatNotifikasi($konsultasi_data['siswa_user_id'], $judul, $pesan, 'success');
            
            if ($notif_result) {
                $success = "Konsultasi berhasil disetujui! Notifikasi telah dikirim ke siswa.";
            } else {
                $success = "Konsultasi berhasil disetujui! (Gagal mengirim notifikasi)";
            }
        } else {
            $error = "Gagal menyetujui konsultasi! Error: " . $stmt->error;
        }
        }
    } else if ($action == 'reject') {
        $sql = "UPDATE konsultasi SET STATUS = 'Ditolak', KOMENTAR_GURU = ? WHERE ID = ?";
        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("si", $komentar, $konsultasi_id);
        
        if ($stmt->execute()) {
            $judul = "Konsultasi Ditolak ❌";
            $pesan = "Konsultasi Anda dengan topik '" . $konsultasi_data['TOPIK_KONSULTASI'] . "' telah ditolak oleh " . $nama_lengkap . ". " . ($komentar ? "Komentar : " . $komentar : "Tidak ada komentar");
            
            $notif_result = buatNotifikasi($konsultasi_data['siswa_user_id'], $judul, $pesan, 'danger');
            
            if ($notif_result) {
                $success = "Konsultasi berhasil ditolak! Notifikasi telah dikirim ke siswa.";
            } else {
                $success = "Konsultasi berhasil ditolak! (Gagal mengirim notifikasi)";
            }
        } else {
            $error = "Gagal menolak konsultasi! Error: " . $stmt->error;
        }
    }
}

$sql_pending = "SELECT k.*, u.NAMA_LENGKAP as nama_siswa, s.KELAS, s.JURUSAN,
                       g1.NAMA_LENGKAP as guru_pilihan_1, g2.NAMA_LENGKAP as guru_pilihan_2
                FROM konsultasi k
                JOIN siswa s ON k.SISWA_ID = s.ID
                JOIN users u ON s.USER_ID = u.ID
                JOIN guru_bk gb1 ON k.PILIHAN_GURU_1 = gb1.ID
                JOIN users g1 ON gb1.USER_ID = g1.ID
                JOIN guru_bk gb2 ON k.PILIHAN_GURU_2 = gb2.ID
                JOIN users g2 ON gb2.USER_ID = g2.ID
                WHERE k.STATUS = 'Menunggu' 
                AND (k.PILIHAN_GURU_1 = ? OR k.PILIHAN_GURU_2 = ?)
                ORDER BY 
                    CASE k.PRIORITAS 
                        WHEN 'Darurat' THEN 1
                        WHEN 'Tinggi' THEN 2
                        WHEN 'Sedang' THEN 3
                        WHEN 'Rendah' THEN 4
                    END,
                    k.CREATED_AT ASC";
$stmt_pending = $koneksi->prepare($sql_pending);
$stmt_pending->bind_param("ii", $guru_id, $guru_id);
$stmt_pending->execute();
$pending_konsultasi = $stmt_pending->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Konsultasi - APK BK</title>
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
        
        .nav a.active {
            background: linear-gradient(135deg, #3182ce, #2b6cb0);
            color: white;
            border-color: #3182ce;
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
        
        .container { 
            padding: 40px; 
            max-width: 1200px; 
            margin: 0 auto;
            position: relative;
            z-index: 5;
        }
        
        .alert {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 600;
            border: 2px solid transparent;
            backdrop-filter: blur(15px);
            animation: slideUp 0.5s ease-out;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: rgba(72, 187, 120, 0.15);
            border-color: rgba(72, 187, 120, 0.3);
            color: #38a169;
        }
        
        .alert-error {
            background: rgba(245, 101, 101, 0.15);
            border-color: rgba(245, 101, 101, 0.3);
            color: #e53e3e;
        }
        
        .page-header {
            background: rgba(255, 255, 255, 0.95);
            padding: 35px 40px;
            border-radius: 20px;
            margin-bottom: 35px;
            border: 1px solid rgba(49, 130, 206, 0.1);
            box-shadow: 0 15px 35px rgba(49, 130, 206, 0.1);
            backdrop-filter: blur(15px);
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.8s ease-out;
        }
        
        .page-header::before {
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
        
        .page-header:hover::before {
            opacity: 1;
        }
        
        .page-header h2 {
            font-size: 32px;
            margin-bottom: 12px;
            color: #2d3748;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .page-header p {
            color: #718096;
            font-size: 18px;
            font-weight: 500;
        }
        
        .konsultasi-grid {
            display: grid;
            gap: 30px;
        }
        
        .konsultasi-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            border: 1px solid rgba(49, 130, 206, 0.1);
            box-shadow: 0 15px 35px rgba(49, 130, 206, 0.1);
            backdrop-filter: blur(15px);
            padding: 30px;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
        }
        
        .konsultasi-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 45px rgba(49, 130, 206, 0.2);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(49, 130, 206, 0.1);
        }
        
        .student-info h3 {
            color: #2d3748;
            margin-bottom: 8px;
            font-size: 22px;
            font-weight: 700;
        }
        
        .student-details {
            color: #718096;
            font-size: 15px;
            font-weight: 500;
        }
        
        .priority-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 14px;
            border: 2px solid;
        }
        
        .priority-darurat { 
            background: rgba(156, 39, 176, 0.1); 
            color: #9C27B0; 
            border-color: rgba(156, 39, 176, 0.3); 
        }
        .priority-tinggi { 
            background: rgba(244, 67, 54, 0.1); 
            color: #F44336; 
            border-color: rgba(244, 67, 54, 0.3); 
        }
        .priority-sedang { 
            background: rgba(255, 152, 0, 0.1); 
            color: #FF9800; 
            border-color: rgba(255, 152, 0, 0.3); 
        }
        .priority-rendah { 
            background: rgba(33, 150, 243, 0.1); 
            color: #2196F3; 
            border-color: rgba(33, 150, 243, 0.3); 
        }
        
        .card-content {
            margin-bottom: 25px;
        }
        
        .info-group {
            margin-bottom: 20px;
        }
        
        .info-label {
            font-size: 13px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin-bottom: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .info-value {
            font-size: 17px;
            color: #2d3748;
            font-weight: 600;
        }
        
        .deskripsi-masalah {
            background: rgba(49, 130, 206, 0.05);
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #3182ce;
            margin-top: 12px;
            color: #4a5568;
            line-height: 1.6;
        }
        
        .guru-pilihan {
            display: flex;
            gap: 20px;
            margin-top: 15px;
        }
        
        .guru-item {
            flex: 1;
            background: rgba(49, 130, 206, 0.05);
            padding: 15px;
            border-radius: 12px;
            border: 2px solid rgba(49, 130, 206, 0.1);
            transition: all 0.3s ease;
        }
        
        .guru-item.active {
            background: rgba(49, 130, 206, 0.1);
            border-color: #3182ce;
            transform: translateY(-2px);
        }
        
        .guru-name {
            font-weight: 700;
            color: #3182ce;
            font-size: 16px;
        }
        
        .guru-label {
            font-size: 13px;
            color: #718096;
            font-weight: 600;
        }
        
        .action-form {
            background: rgba(49, 130, 206, 0.05);
            padding: 25px;
            border-radius: 15px;
            border: 2px solid rgba(49, 130, 206, 0.1);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 10px;
            color: #4a5568;
            font-weight: 600;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        input, textarea {
            width: 100%;
            padding: 16px 20px;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(49, 130, 206, 0.2);
            border-radius: 12px;
            color: #2d3748;
            font-size: 16px;
            resize: vertical;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }
        
        input:focus, textarea:focus {
            outline: none;
            border-color: #3182ce;
            box-shadow: 0 0 20px rgba(49, 130, 206, 0.2);
            background: rgba(255, 255, 255, 1);
        }
        
        textarea {
            min-height: 100px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
        }
        
        .btn {
            padding: 16px 28px;
            border: none;                         
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            font-size: 15px;
            transition: all 0.4s ease;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-approve {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
            box-shadow: 0 8px 25px rgba(72, 187, 120, 0.3);
        }
        
        .btn-reject {
            background: linear-gradient(135deg, #f56565, #e53e3e);
            color: white;
            box-shadow: 0 8px 25px rgba(245, 101, 101, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 12px 35px rgba(49, 130, 206, 0.4);
        }
        
        .btn-approve:hover {
            box-shadow: 0 12px 35px rgba(72, 187, 120, 0.4);
        }
        
        .btn-reject:hover {
            box-shadow: 0 12px 35px rgba(245, 101, 101, 0.4);
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 40px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            border: 2px dashed rgba(49, 130, 206, 0.3);
            backdrop-filter: blur(15px);
        }
        
        .empty-state h3 {
            color: #3182ce;
            font-size: 28px;
            margin-bottom: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        
        .empty-state p {
            color: #718096;
            font-size: 18px;
            font-weight: 500;
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
            
            .nav {
                padding: 12px 20px;
                flex-wrap: wrap;
                justify-content: center;
                gap: 12px;
            }
            
            .container {
                padding: 20px;
            }
            
            .page-header {
                padding: 25px;
            }
            
            .page-header h2 {
                font-size: 24px;
            }
            
            .guru-pilihan {
                flex-direction: column;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .card-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .floating {
                display: none;
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
            
            .page-header {
                padding: 20px;
            }
            
            .konsultasi-card {
                padding: 20px;
            }
            
            .empty-state {
                padding: 60px 20px;
            }
            
            .empty-state h3 {
                font-size: 24px;
            }
        }

        /* Layout Theme: match form_karir.php */
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
        }

        .header h1 {
            background: linear-gradient(135deg, var(--accent), #1d4ed8);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header > h1 {
            display: none;
        }

        .user-info > span {
            display: none;
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
            text-decoration: none;
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

        .logout-btn {
            box-shadow: none;
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
            width: 100%;
            border: 1px solid transparent;
            justify-content: flex-start;
            text-decoration: none;
        }

        .nav a::before {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.12), rgba(59, 130, 246, 0.12));
        }

        .nav a:hover {
            color: var(--accent);
            border-color: var(--border-soft);
            transform: translateX(4px);
        }

        .nav a.active,
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

        .page-header,
        .konsultasi-card,
        .action-form,
        .empty-state,
        .alert {
            background: var(--surface-card);
            border-color: var(--border-soft);
        }

        .page-header h2,
        .student-info h3,
        .info-value,
        label {
            color: var(--text-main);
        }

        .page-header p,
        .student-details,
        .info-label,
        .guru-label,
        .empty-state p {
            color: var(--text-muted);
        }

        input,
        textarea {
            background: rgba(255, 253, 245, 0.9);
            border-color: rgba(102, 126, 234, 0.2);
            color: #2d3748;
        }

        input:focus,
        textarea:focus {
            border-color: #667eea;
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.2);
            background: rgba(255, 253, 245, 0.95);
            transform: translateY(-2px);
        }

        body.dark-mode .page-header,
        body.dark-mode .konsultasi-card,
        body.dark-mode .action-form,
        body.dark-mode .empty-state,
        body.dark-mode .alert,
        body.dark-mode .deskripsi-masalah,
        body.dark-mode .guru-item {
            background: rgba(17, 24, 39, 0.9);
        }

        body.dark-mode input,
        body.dark-mode textarea {
            background: rgba(15, 23, 42, 0.75);
            color: #e5e7eb;
        }

        body.dark-mode input::placeholder,
        body.dark-mode textarea::placeholder {
            color: #9ca3af;
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
                <h1><i class='bx bx-check-shield'></i> Approve Konsultasi</h1>
            </a>
        </div>
        <h1>👨‍🏫 APK BK - Approve Konsultasi</h1>
        <div class="user-info">
            <button class="theme-toggle" id="themeToggle" type="button" aria-label="Ganti mode tema">
                <i class='bx bx-moon'></i>
                <span>Mode</span>
            </button>
            <span>Halo, <strong><?php echo htmlspecialchars($nama_lengkap); ?></strong> 👋</span>
            <a href="logout.php" class="theme-toggle">
                <i class='bx bx-log-out'></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
    
    <div class="nav">
        <div class="sidebar-top">
            <h4>Menu Guru BK</h4>
            <div class="sidebar-icons">
                <span class="sidebar-icon"><i class='bx bx-home-heart'></i></span>
                <span class="sidebar-icon"><i class='bx bx-check-shield'></i></span>
                <span class="sidebar-icon"><i class='bx bx-calendar-star'></i></span>
                <span class="sidebar-icon"><i class='bx bx-clipboard'></i></span>
            </div>
        </div>
        <a href="dashboard_guru.php">
            <i class='bx bx-home'></i>
            Dashboard
        </a>
        <a href="approve_konsultasi.php" class="active nav-badge">
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
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class='bx bx-check-circle'></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class='bx bx-error-circle'></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="page-header">
            <h2><i class='bx bx-check-shield'></i> Approve Konsultasi Siswa</h2>
            <p>Tinjau dan setujui atau tolak pengajuan konsultasi dari siswa</p>
        </div>
        
        <?php if ($pending_konsultasi->num_rows > 0): ?>
            <div class="konsultasi-grid">
                <?php while($konsul = $pending_konsultasi->fetch_assoc()): ?>
                <div class="konsultasi-card">
                    <div class="card-header">
                        <div class="student-info">
                            <h3><?php echo htmlspecialchars($konsul['nama_siswa']); ?></h3>
                            <div class="student-details">
                                <?php echo htmlspecialchars($konsul['KELAS']); ?> - <?php echo htmlspecialchars($konsul['JURUSAN']); ?> | 
                                <?php echo date('d/m/Y H:i', strtotime($konsul['CREATED_AT'])); ?>
                            </div>
                        </div>
                        <div class="priority-badge priority-<?php echo strtolower($konsul['PRIORITAS']); ?>">
                            <?php 
                            $priority_icons = [
                                'Darurat' => '🔴',
                                'Tinggi' => '🟠', 
                                'Sedang' => '🟡',
                                'Rendah' => '🔵'
                            ];
                            echo $priority_icons[$konsul['PRIORITAS']] . ' ' . $konsul['PRIORITAS'];
                            ?>
                        </div>
                    </div>
                    
                    <div class="card-content">
                        <?php $mode_konsultasi = $konsul['MODE_KONSULTASI'] ?? 'Offline'; ?>
                        <div class="info-group">
                            <div class="info-label">
                                <i class='bx bx-target-lock'></i>
                                Topik Konsultasi
                            </div>
                            <div class="info-value"><?php echo htmlspecialchars($konsul['TOPIK_KONSULTASI']); ?></div>
                        </div>
                        
                        <div class="info-group">
                            <div class="info-label">
                                <i class='bx bx-edit-alt'></i>
                                Deskripsi Masalah
                            </div>
                            <div class="deskripsi-masalah">
                                <?php echo nl2br(htmlspecialchars($konsul['DESKRIPSI_MASALAH'])); ?>
                            </div>
                        </div>
                        
                        <div class="info-group">
                            <div class="info-label">
                                <i class='bx bx-wifi'></i>
                                Mode Konsultasi
                            </div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($mode_konsultasi); ?>
                            </div>
                        </div>
                        
                        <div class="info-group">
                            <div class="info-label">
                                <i class='bx bx-user-voice'></i>
                                Guru Pilihan Siswa
                            </div>
                            <div class="guru-pilihan">
                                <div class="guru-item <?php echo $konsul['PILIHAN_GURU_1'] == $guru_id ? 'active' : ''; ?>">
                                    <div class="guru-name"><?php echo htmlspecialchars($konsul['guru_pilihan_1']); ?></div>
                                    <div class="guru-label">Pilihan 1</div>
                                </div>
                                <div class="guru-item <?php echo $konsul['PILIHAN_GURU_2'] == $guru_id ? 'active' : ''; ?>">
                                    <div class="guru-name"><?php echo htmlspecialchars($konsul['guru_pilihan_2']); ?></div>
                                    <div class="guru-label">Pilihan 2</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="action-form">
                        <form method="POST" action="">
                            <input type="hidden" name="konsultasi_id" value="<?php echo $konsul['ID']; ?>">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>
                                        <i class='bx bx-calendar'></i>
                                        Tanggal Konsultasi *
                                    </label>
                                    <input type="date" name="tanggal_konsultasi" required 
                                           min="<?php echo date('Y-m-d'); ?>"
                                           value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="form-group">
                                    <label>
                                        <i class='bx bx-time'></i>
                                        Jam Konsultasi *
                                    </label>
                                    <?php
                                    $next_hour = date('H:00', strtotime('+1 hour'));
                                    ?>
                                    <input type="time" name="jam_konsultasi" required
                                           value="<?php echo $next_hour; ?>">
                                </div>
                            </div>
                            
                            <?php if ($mode_konsultasi === 'Online'): ?>
                            <div class="form-group">
                                <label>
                                    <i class='bx bx-link'></i>
                                    Link Meeting *
                                </label>
                                <input type="url" name="meeting_link" 
                                       placeholder="https://meet.google.com/..." required>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($mode_konsultasi !== 'Online'): ?>
                            <div class="form-group">
                                <label>
                                    <i class='bx bx-map'></i>
                                    Tempat Konsultasi
                                </label>
                                <input type="text" name="tempat_konsultasi" 
                                       value="Ruang BK SMK 7 Batam"
                                       placeholder="Tempat konsultasi...">
                            </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label>
                                    <i class='bx bx-message-detail'></i>
                                    Komentar (Opsional):
                                </label>
                                <textarea name="komentar" placeholder="Berikan komentar atau catatan untuk siswa..."></textarea>
                            </div>
                            
                            <div class="action-buttons">
                                <button type="submit" name="action" value="approve" class="btn btn-approve">
                                    <i class='bx bx-check-circle'></i>
                                    Setujui Konsultasi
                                </button>
                                <button type="submit" name="action" value="reject" class="btn btn-reject" 
                                        onclick="return confirm('Yakin ingin menolak konsultasi ini?')">
                                    <i class='bx bx-x-circle'></i>
                                    Tolak Konsultasi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h3><i class='bx bx-party'></i> 🎉 Tidak Ada Konsultasi yang Menunggu</h3>
                <p>Semua pengajuan konsultasi telah diproses</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
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

            const today = new Date().toISOString().split('T')[0];
            document.querySelectorAll('input[name="tanggal_konsultasi"]').forEach(input => {
                input.min = today;
                if (!input.value) {
                    input.value = today;
                }
            });

            const nextHour = new Date();
            nextHour.setHours(nextHour.getHours() + 1);
            const timeString = nextHour.getHours().toString().padStart(2, '0') + ':00';
            document.querySelectorAll('input[name="jam_konsultasi"]').forEach(input => {
                if (!input.value) {
                    input.value = timeString;
                }
            });

            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
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

            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(alert => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 300);
                });
            }, 5000);
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
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
