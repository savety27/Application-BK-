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
$editing_guru_id = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_guru'])) {
    $username = trim($_POST['username']);           
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $no_telepon = trim($_POST['no_telepon']);
    $nip = trim($_POST['nip']);
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $pengalaman_mengajar = trim($_POST['pengalaman_mengajar']);
    $deskripsi = trim($_POST['deskripsi']);
    $alamat = trim($_POST['alamat']);

    $check_sql = "SELECT ID FROM users WHERE USERNAME = ?";
    $check_stmt = $koneksi->prepare($check_sql);
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $error = "Username sudah digunakan!";
    } else {
        $user_sql = "INSERT INTO users (USERNAME, PASSWORD, ROLE, NAMA_LENGKAP, EMAIL, NO_TELEPON) 
                     VALUES (?, ?, 'Guru_BK', ?, ?, ?)";
        $user_stmt = $koneksi->prepare($user_sql);
        $user_stmt->bind_param("sssss", $username, $password, $nama_lengkap, $email, $no_telepon);
        
        if ($user_stmt->execute()) {
            $user_id = $koneksi->insert_id;
            
            $guru_sql = "INSERT INTO guru_bk (USER_ID, NIP, JENIS_KELAMIN, PENGALAMAN_MENGAJAR, DESKRIPSI, NO_TELEPON, ALAMAT) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
            $guru_stmt = $koneksi->prepare($guru_sql);
            $guru_stmt->bind_param("issssss", $user_id, $nip, $jenis_kelamin, $pengalaman_mengajar, $deskripsi, $no_telepon, $alamat);
            
            if ($guru_stmt->execute()) {
                $success = "Guru BK berhasil ditambahkan!";
            } else {
                $koneksi->query("DELETE FROM users WHERE ID = $user_id");
                $error = "Gagal menambahkan data guru!";
            }
        } else {
            $error = "Gagal membuat user!";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_guru'])) {
    $user_id = intval($_POST['user_id']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $no_telepon = trim($_POST['no_telepon']);
    $nip = trim($_POST['nip']);
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $pengalaman_mengajar = trim($_POST['pengalaman_mengajar']);
    $deskripsi = trim($_POST['deskripsi']);
    $alamat = trim($_POST['alamat']);
    
    try {
        $user_sql = "UPDATE users SET NAMA_LENGKAP = ?, EMAIL = ?, NO_TELEPON = ? WHERE ID = ?";
        $user_stmt = $koneksi->prepare($user_sql);
        $user_stmt->bind_param("sssi", $nama_lengkap, $email, $no_telepon, $user_id);
        
        if ($user_stmt->execute()) {
            $guru_sql = "UPDATE guru_bk SET NIP = ?, JENIS_KELAMIN = ?, PENGALAMAN_MENGAJAR = ?, DESKRIPSI = ?, ALAMAT = ? WHERE USER_ID = ?";
            $guru_stmt = $koneksi->prepare($guru_sql);
            $guru_stmt->bind_param("sssssi", $nip, $jenis_kelamin, $pengalaman_mengajar, $deskripsi, $alamat, $user_id);
            
            if ($guru_stmt->execute()) {
                $success = "Data guru berhasil diperbarui!";
                $editing_guru_id = null; 
            } else {
                $error = "Gagal memperbarui data guru!";
            }
        } else {
            $error = "Gagal memperbarui data user!";
        }
    } catch (Exception $e) {
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
}

if (isset($_GET['hapus'])) {
    $user_id = $_GET['hapus'];
    
    $check_konsul = "SELECT g.ID FROM guru_bk g 
                     JOIN konsultasi k ON g.ID = k.GURU_BK_ID OR g.ID = k.PILIHAN_GURU_1 OR g.ID = k.PILIHAN_GURU_2 
                     WHERE g.USER_ID = ? LIMIT 1";
    $check_stmt = $koneksi->prepare($check_konsul);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $error = "Tidak dapat menghapus guru yang memiliki riwayat konsultasi!";
    } else {
        $get_guru_id = "SELECT ID FROM guru_bk WHERE USER_ID = ?";
        $guru_stmt = $koneksi->prepare($get_guru_id);
        $guru_stmt->bind_param("i", $user_id);
        $guru_stmt->execute();
        $guru_id = $guru_stmt->get_result()->fetch_assoc()['ID'];
        
        $delete_guru = "DELETE FROM guru_bk WHERE ID = ?";
        $del_guru_stmt = $koneksi->prepare($delete_guru);
        $del_guru_stmt->bind_param("i", $guru_id);
        $del_guru_stmt->execute();
        
        $delete_user = "DELETE FROM users WHERE ID = ?";
        $del_user_stmt = $koneksi->prepare($delete_user);
        $del_user_stmt->bind_param("i", $user_id);
        
        if ($del_user_stmt->execute()) {
            $success = "Guru BK berhasil dihapus!";
        } else {
            $error = "Gagal menghapus guru!";
        }
    }
}

if (isset($_GET['edit'])) {
    $editing_guru_id = intval($_GET['edit']);
}

if (isset($_GET['cancel_edit'])) {
    $editing_guru_id = null;
}

if (!$editing_guru_id) {
    $sql_guru = "SELECT u.*, g.ID as guru_id, g.NIP, g.JENIS_KELAMIN, g.PENGALAMAN_MENGAJAR, g.DESKRIPSI, g.ALAMAT 
                 FROM users u 
                 JOIN guru_bk g ON u.ID = g.USER_ID 
                 WHERE u.ROLE = 'Guru_BK' 
                 ORDER BY u.NAMA_LENGKAP";
    $guru_list = $koneksi->query($sql_guru);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Guru BK - APK BK</title>
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
            max-width: 1400px; 
            margin: 0 auto;
            position: relative;
            z-index: 5;
        }
        
        .alert {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 600;
            border: 2px solid;
            backdrop-filter: blur(10px);
            animation: slideIn 0.5s ease-out;
        }
        
        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            border-color: rgba(34, 197, 94, 0.4);
            color: #22c55e;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.4);
            color: #ef4444;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
        
        .tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            background: rgba(15, 23, 42, 0.8);
            padding: 15px;
            border-radius: 15px;
            border: 1px solid rgba(139, 92, 246, 0.2);
            backdrop-filter: blur(15px);
        }
        
        .tab {
            padding: 15px 25px;
            background: rgba(139, 92, 246, 0.1);
            border: 2px solid rgba(139, 92, 246, 0.3);
            border-radius: 12px;
            color: #cbd5e1;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tab:hover {
            background: rgba(139, 92, 246, 0.2);
            transform: translateY(-2px);
        }
        
        .tab.active {
            background: linear-gradient(135deg, #8b5cf6, #06b6d4);
            color: #ffffff;
            border-color: #8b5cf6;
            box-shadow: 0 5px 20px rgba(139, 92, 246, 0.4);
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.5s ease-out;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-container {
            background: rgba(15, 23, 42, 0.8);
            padding: 35px;
            border-radius: 20px;
            border: 1px solid rgba(139, 92, 246, 0.2);
            box-shadow: 0 15px 35px rgba(139, 92, 246, 0.15);
            backdrop-filter: blur(15px);
            margin-bottom: 30px;
        }
        
        .form-container h3 {
            color: #8b5cf6;
            margin-bottom: 25px;
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 10px;
            color: #cbd5e1;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 14px 18px;
            background: rgba(255, 255, 255, 0.08);
            border: 2px solid rgba(139, 92, 246, 0.3);
            border-radius: 10px;
            color: #f8fafc;
            font-size: 16px;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        select option {
            background-color: #1e293b;
            color: #f8fafc;
            padding: 12px;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #8b5cf6;
            box-shadow: 0 0 20px rgba(139, 92, 246, 0.4);
            background: rgba(255, 255, 255, 0.12);
        }
        
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #8b5cf6, #06b6d4);
            color: white;
            padding: 16px 32px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
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
            box-shadow: 0 12px 30px rgba(139, 92, 246, 0.4);
        }
        
        .guru-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 30px;
        }
        
        .guru-card {
            background: rgba(15, 23, 42, 0.8);
            border-radius: 20px;
            border: 1px solid rgba(139, 92, 246, 0.2);
            box-shadow: 0 15px 35px rgba(139, 92, 246, 0.15);
            backdrop-filter: blur(15px);
            padding: 30px;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }
        
        .guru-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #8b5cf6, #06b6d4);
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }
        
        .guru-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 45px rgba(139, 92, 246, 0.25);
            border-color: #8b5cf6;
        }
        
        .guru-card:hover::before {
            transform: scaleX(1);
        }
        
        .guru-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(139, 92, 246, 0.2);
        }
        
        .guru-name {
            font-size: 22px;
            font-weight: 700;
            color: #8b5cf6;
            margin-bottom: 5px;
        }
        
        .guru-nip {
            color: #94a3b8;
            font-size: 14px;
            font-weight: 600;
        }
        
        .guru-info {
            margin-bottom: 18px;
        }
        
        .info-label {
            font-size: 12px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin-bottom: 6px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .info-value {
            font-size: 15px;
            color: #f8fafc;
            font-weight: 500;
        }
        
        .guru-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            justify-content: center;
        }
        
        .btn-edit {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            border: 2px solid rgba(59, 130, 246, 0.3);
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: 2px solid rgba(239, 68, 68, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 40px;
            background: rgba(15, 23, 42, 0.8);
            border-radius: 20px;
            border: 3px dashed rgba(139, 92, 246, 0.3);
            backdrop-filter: blur(15px);
        }
        
        .empty-state h3 {
            color: #8b5cf6;
            font-size: 28px;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .empty-state p {
            color: #94a3b8;
            font-size: 16px;
        }
        
        .edit-mode-container {
            background: rgba(15, 23, 42, 0.95);
            padding: 40px;
            border-radius: 20px;
            border: 2px solid #8b5cf6;
            box-shadow: 0 20px 50px rgba(139, 92, 246, 0.3);
            backdrop-filter: blur(15px);
            margin-bottom: 30px;
            animation: slideDown 0.4s ease-out;
        }
        
        .edit-mode-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 25px;
            border-bottom: 2px solid rgba(139, 92, 246, 0.3);
        }
        
        .edit-mode-title {
            color: #8b5cf6;
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .back-to-list-btn {
            background: rgba(148, 163, 184, 0.2);
            color: #94a3b8;
            padding: 12px 24px;
            border: 2px solid rgba(148, 163, 184, 0.3);
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-to-list-btn:hover {
            background: rgba(148, 163, 184, 0.3);
            transform: translateY(-2px);
        }
        
        .edit-form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn-update {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 2;
            justify-content: center;
            font-size: 16px;
        }
        
        .btn-cancel {
            background: rgba(148, 163, 184, 0.2);
            color: #94a3b8;
            padding: 15px 30px;
            border: 2px solid rgba(148, 163, 184, 0.3);
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            justify-content: center;
            text-align: center;
            font-size: 16px;
        }
        
        .btn-update:hover {
            background: linear-gradient(135deg, #059669, #10b981);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        }
        
        .btn-cancel:hover {
            background: rgba(148, 163, 184, 0.3);
            transform: translateY(-2px);
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            
            .guru-grid {
                grid-template-columns: 1fr;
            }
            
            .form-grid, .edit-form-grid {
                grid-template-columns: 1fr;
            }
            
            .tabs {
                flex-direction: column;
            }
            
            .guru-actions {
                flex-direction: column;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .floating {
                display: none;
            }
            
            .edit-mode-header {
                flex-direction: column;
                gap: 15px;
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
        <h1>⚡ APK BK - Kelola Guru BK</h1>
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
        <a href="admin_manage_guru.php" class="active">
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
        
        <?php if ($editing_guru_id): ?>
            <?php 
            $edit_sql = "SELECT u.*, g.ID as guru_id, g.NIP, g.JENIS_KELAMIN, g.PENGALAMAN_MENGAJAR, g.DESKRIPSI, g.ALAMAT 
                        FROM users u 
                        JOIN guru_bk g ON u.ID = g.USER_ID 
                        WHERE u.ID = ?";
            $edit_stmt = $koneksi->prepare($edit_sql);
            $edit_stmt->bind_param("i", $editing_guru_id);
            $edit_stmt->execute();
            $editing_guru = $edit_stmt->get_result()->fetch_assoc();
            ?>
            
            <?php if ($editing_guru): ?>
            <div class="edit-mode-container">
                <div class="edit-mode-header">
                    <div class="edit-mode-title">
                        <i class='bx bx-edit'></i> Edit Data Guru: <?php echo htmlspecialchars($editing_guru['NAMA_LENGKAP']); ?>
                    </div>
                    <a href="?cancel_edit=1" class="back-to-list-btn">
                        <i class='bx bx-arrow-back'></i> Kembali ke Daftar
                    </a>
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="update_guru" value="1">
                    <input type="hidden" name="user_id" value="<?php echo $editing_guru['ID']; ?>">
                    
                    <div class="edit-form-grid">
                        <div class="form-group">
                            <label>Nama Lengkap *</label>
                            <input type="text" name="nama_lengkap" value="<?php echo htmlspecialchars($editing_guru['NAMA_LENGKAP']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($editing_guru['EMAIL'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>No. Telepon</label>
                            <input type="tel" name="no_telepon" value="<?php echo htmlspecialchars($editing_guru['NO_TELEPON'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>NIP *</label>
                            <input type="text" name="nip" value="<?php echo htmlspecialchars($editing_guru['NIP']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Jenis Kelamin *</label>
                            <select name="jenis_kelamin" required>
                                <option value="L" <?php echo $editing_guru['JENIS_KELAMIN'] == 'L' ? 'selected' : ''; ?>>Laki-laki</option>
                                <option value="P" <?php echo $editing_guru['JENIS_KELAMIN'] == 'P' ? 'selected' : ''; ?>>Perempuan</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Pengalaman Mengajar</label>
                            <input type="text" name="pengalaman_mengajar" value="<?php echo htmlspecialchars($editing_guru['PENGALAMAN_MENGAJAR'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" placeholder="Deskripsi singkat tentang guru..."><?php echo htmlspecialchars($editing_guru['DESKRIPSI'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="alamat" placeholder="Alamat lengkap..."><?php echo htmlspecialchars($editing_guru['ALAMAT'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-update">
                            <i class='bx bx-save'></i> Update Data Guru
                        </button>
                        <a href="?cancel_edit=1" class="btn-cancel">
                            <i class='bx bx-x'></i> Batal Edit
                        </a>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        
        <?php else: ?>
        
        <div class="page-header">
            <h2><i class='bx bx-user-plus'></i> Management Guru BK</h2>
            <p>Tambah, edit, dan kelola data guru Bimbingan Konseling</p>
        </div>
        
        <div class="tabs">
            <div class="tab active" onclick="showTab('list')">
                <i class='bx bx-list-ul'></i> Daftar Guru
            </div>
            <div class="tab" onclick="showTab('add')">
                <i class='bx bx-user-plus'></i> Tambah Guru
            </div>
        </div>
        
        <div id="list-tab" class="tab-content active">
            <?php if ($guru_list->num_rows > 0): ?>
                <div class="guru-grid">
                    <?php while($guru = $guru_list->fetch_assoc()): ?>
                    <div class="guru-card">
                        <div class="guru-header">
                            <div>
                                <div class="guru-name"><?php echo htmlspecialchars($guru['NAMA_LENGKAP']); ?></div>
                                <div class="guru-nip">NIP: <?php echo htmlspecialchars($guru['NIP']); ?></div>
                            </div>
                        </div>
                        
                        <div class="guru-info">
                            <div class="info-label"><i class='bx bx-envelope'></i> EMAIL</div>
                            <div class="info-value"><?php echo htmlspecialchars($guru['EMAIL'] ?? '-'); ?></div>
                        </div>
                        
                        <div class="guru-info">
                            <div class="info-label"><i class='bx bx-phone'></i> TELEPON</div>
                            <div class="info-value"><?php echo htmlspecialchars($guru['NO_TELEPON'] ?? '-'); ?></div>
                        </div>
                        
                        <div class="guru-info">
                            <div class="info-label"><i class='bx bx-male-female'></i> JENIS KELAMIN</div>
                            <div class="info-value"><?php echo $guru['JENIS_KELAMIN'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?></div>
                        </div>
                        
                        <?php if ($guru['PENGALAMAN_MENGAJAR']): ?>
                        <div class="guru-info">
                            <div class="info-label"><i class='bx bx-time'></i> PENGALAMAN</div>
                            <div class="info-value"><?php echo htmlspecialchars($guru['PENGALAMAN_MENGAJAR']); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($guru['DESKRIPSI']): ?>
                        <div class="guru-info">
                            <div class="info-label"><i class='bx bx-note'></i> DESKRIPSI</div>
                            <div class="info-value"><?php echo htmlspecialchars($guru['DESKRIPSI']); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="guru-actions">
                            <a href="?edit=<?php echo $guru['ID']; ?>" class="btn btn-edit">
                                <i class='bx bx-edit'></i> Edit
                            </a>
                            <a href="?hapus=<?php echo $guru['ID']; ?>" class="btn btn-delete" 
                               onclick="return confirm('Yakin ingin menghapus guru <?php echo htmlspecialchars($guru['NAMA_LENGKAP']); ?>?')">
                                <i class='bx bx-trash'></i> Hapus
                            </a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3><i class='bx bx-user-x'></i> Belum Ada Guru BK</h3>
                    <p>Tambahkan guru BK pertama untuk memulai sistem</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div id="add-tab" class="tab-content">
            <div class="form-container">
                <h3><i class='bx bx-user-plus'></i> Tambah Guru BK Baru</h3>
                <form method="POST" action="">
                    <input type="hidden" name="tambah_guru" value="1">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Username *</label>
                            <input type="text" name="username" required placeholder="username.guru">
                        </div>
                        <div class="form-group">
                            <label>Password *</label>
                            <input type="password" name="password" required placeholder="Minimal 6 karakter">
                        </div>
                        <div class="form-group">
                            <label>Nama Lengkap *</label>
                            <input type="text" name="nama_lengkap" required placeholder="Nama lengkap guru">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" placeholder="email@sekolah.sch.id">
                        </div>
                        <div class="form-group">
                            <label>No. Telepon</label>
                            <input type="tel" name="no_telepon" placeholder="08xxxxxxxxxx">
                        </div>
                        <div class="form-group">
                            <label>NIP *</label>
                            <input type="text" name="nip" required placeholder="Nomor Induk Pegawai">
                        </div>
                        <div class="form-group">
                            <label>Jenis Kelamin *</label>
                            <select name="jenis_kelamin" required>
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Pengalaman Mengajar</label>
                            <input type="text" name="pengalaman_mengajar" placeholder="Contoh: 5 tahun">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" placeholder="Deskripsi singkat tentang guru..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="alamat" placeholder="Alamat lengkap..."></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class='bx bx-save'></i> Simpan Data Guru
                    </button>
                </form>
            </div>
        </div>
        
        <?php endif; ?>
    </div>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>