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

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;

if (isset($_GET['toggle_status'])) {
    $user_id = $_GET['toggle_status'];
    
    $sql_status = "SELECT STATUS FROM users WHERE ID = ?";
    $stmt_status = $koneksi->prepare($sql_status);
    $stmt_status->bind_param("i", $user_id);
    $stmt_status->execute();
    $result = $stmt_status->get_result();
    
    if ($result->num_rows > 0) {
        $current_status = $result->fetch_assoc()['STATUS'];
        $new_status = $current_status == 'Aktif' ? 'Tidak_Aktif' : 'Aktif';
        
        $sql_toggle = "UPDATE users SET STATUS = ? WHERE ID = ?";
        $stmt_toggle = $koneksi->prepare($sql_toggle);
        $stmt_toggle->bind_param("si", $new_status, $user_id);
        
        if ($stmt_toggle->execute()) {
            $status_text = $new_status == 'Aktif' ? 'diaktifkan' : 'dinonaktifkan';
            $success = "Akun berhasil $status_text!";
        } else {
            $error = "Gagal mengubah status akun!";
        }
    } else {
        $error = "User tidak ditemukan!";
    }
}

if (isset($_GET['reset_password'])) {
    $user_id = $_GET['reset_password'];
    
    $new_password = password_hash('password123', PASSWORD_DEFAULT);
    
    $sql_reset = "UPDATE users SET PASSWORD = ? WHERE ID = ?";
    $stmt_reset = $koneksi->prepare($sql_reset);
    $stmt_reset->bind_param("si", $new_password, $user_id);
    
    if ($stmt_reset->execute()) {
        $success = "Password berhasil direset ke 'password123'!";
    } else {
        $error = "Gagal mereset password!";
    }
}

try {
    $check_table = $koneksi->query("SHOW TABLES LIKE 'siswa'");
    
    if ($check_table->num_rows > 0) {
        $sql_base = "SELECT u.*, s.NIS, s.NISN, s.KELAS, s.JURUSAN 
                      FROM users u 
                      LEFT JOIN siswa s ON u.ID = s.USER_ID 
                      WHERE u.ROLE = 'Siswa'";
        
        $count_sql = "SELECT COUNT(*) as total 
                      FROM users u 
                      LEFT JOIN siswa s ON u.ID = s.USER_ID 
                      WHERE u.ROLE = 'Siswa'";
    } else {
        $sql_base = "SELECT u.*, NULL as NIS, NULL as NISN, NULL as KELAS, NULL as JURUSAN 
                      FROM users u 
                      WHERE u.ROLE = 'Siswa'";
        
        $count_sql = "SELECT COUNT(*) as total 
                      FROM users u 
                      WHERE u.ROLE = 'Siswa'";
    }

    if (!empty($search)) {
        $search_like = "%" . $search . "%";
        $sql_base .= " AND (u.NAMA_LENGKAP LIKE ? OR u.USERNAME LIKE ? OR u.EMAIL LIKE ? OR s.KELAS LIKE ? OR s.JURUSAN LIKE ? OR s.NIS LIKE ? OR s.NISN LIKE ?)";
        $count_sql .= " AND (u.NAMA_LENGKAP LIKE ? OR u.USERNAME LIKE ? OR u.EMAIL LIKE ? OR s.KELAS LIKE ? OR s.JURUSAN LIKE ? OR s.NIS LIKE ? OR s.NISN LIKE ?)";
    }
    
    if (!empty($search) && $check_table->num_rows > 0) {
        $stmt_count = $koneksi->prepare($count_sql);
        $stmt_count->bind_param("sssssss", $search_like, $search_like, $search_like, $search_like, $search_like, $search_like, $search_like);
    } elseif (!empty($search)) {
        $stmt_count = $koneksi->prepare($count_sql);
        $stmt_count->bind_param("ss", $search_like, $search_like);
    } else {
        $stmt_count = $koneksi->prepare($count_sql);
    }
    
    if (!empty($search)) {
        $stmt_count->execute();
    } else {
        $stmt_count->execute();
    }
    $count_result = $stmt_count->get_result();
    $total_records = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_records / $per_page);
    
    if ($page < 1) $page = 1;
    if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
    
    $offset = ($page - 1) * $per_page;
    
    $sql_siswa = $sql_base . " ORDER BY u.NAMA_LENGKAP LIMIT ? OFFSET ?";
    
    $stmt_siswa = $koneksi->prepare($sql_siswa);
    
    if (!empty($search) && $check_table->num_rows > 0) {
        $stmt_siswa->bind_param("sssssssii", $search_like, $search_like, $search_like, $search_like, $search_like, $search_like, $search_like, $per_page, $offset);
    } elseif (!empty($search)) {
        $stmt_siswa->bind_param("ssii", $search_like, $search_like, $per_page, $offset);
    } else {
        $stmt_siswa->bind_param("ii", $per_page, $offset);
    }
    
    $stmt_siswa->execute();
    $siswa_list = $stmt_siswa->get_result();
    
    if (!$siswa_list) {
        throw new Exception("Error query: " . $koneksi->error);
    }
    
} catch (Exception $e) {
    $error = "Terjadi kesalahan: " . $e->getMessage();
    $sql_siswa = "SELECT * FROM users WHERE ROLE = 'Siswa' ORDER BY NAMA_LENGKAP";
    $siswa_list = $koneksi->query($sql_siswa);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Siswa - APK BK</title>
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
        
        .nav a span {
            background: #ef4444;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            margin-left: 5px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
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
        
        .stats-card {
            background: rgba(15, 23, 42, 0.8);
            padding: 25px 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            border: 1px solid rgba(139, 92, 246, 0.2);
            box-shadow: 0 15px 35px rgba(139, 92, 246, 0.15);
            backdrop-filter: blur(15px);
        }
        
        .stats-card h3 {
            color: #8b5cf6;
            font-size: 18px;
            margin-bottom: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .stats-value {
            font-size: 2.5em;
            font-weight: 800;
            background: linear-gradient(135deg, #8b5cf6, #06b6d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .search-container {
            background: rgba(15, 23, 42, 0.8);
            padding: 25px 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            border: 1px solid rgba(139, 92, 246, 0.2);
            box-shadow: 0 10px 30px rgba(139, 92, 246, 0.15);
            backdrop-filter: blur(15px);
            animation: fadeIn 0.6s ease-out;
        }
        
        .search-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .search-header h3 {
            font-size: 20px;
            color: #f8fafc;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .search-form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .search-input-group {
            flex: 1;
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 16px 20px 16px 50px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(139, 92, 246, 0.3);
            border-radius: 12px;
            color: #f8fafc;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }
        
        .search-input::placeholder {
            color: #94a3b8;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #8b5cf6;
            box-shadow: 0 0 20px rgba(139, 92, 246, 0.3);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 20px;
        }
        
        .search-buttons {
            display: flex;
            gap: 10px;
            align-self: end;
        }
        
        .btn-search {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.4s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
            white-space: nowrap;
        }
        
        .btn-search::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-search:hover::before {
            left: 100%;
        }
        
        .btn-search:hover {
            background: linear-gradient(135deg, #7c3aed, #8b5cf6);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(139, 92, 246, 0.3);
        }
        
        .btn-reset-search {
            background: linear-gradient(135deg, #64748b, #475569);
            color: white;
            padding: 14px 20px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.4s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-reset-search::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-reset-search:hover::before {
            left: 100%;
        }
        
        .btn-reset-search:hover {
            background: linear-gradient(135deg, #475569, #64748b);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(100, 116, 139, 0.3);
        }
        
        .search-summary {
            margin-top: 15px;
            padding: 12px 20px;
            background: rgba(139, 92, 246, 0.1);
            border-radius: 12px;
            font-size: 14px;
            color: #cbd5e1;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: fadeIn 0.5s ease-out;
        }
        
        .search-summary i {
            color: #8b5cf6;
            font-size: 18px;
        }
        
        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-input-group,
            .search-buttons {
                width: 100%;
            }
        }
        
        .siswa-table-container {
            background: rgba(15, 23, 42, 0.8);
            border-radius: 20px;
            border: 1px solid rgba(139, 92, 246, 0.2);
            box-shadow: 0 15px 35px rgba(139, 92, 246, 0.15);
            backdrop-filter: blur(15px);
            overflow: hidden;
            animation: fadeIn 0.8s ease-out;
        }
        
        .table-header {
            background: rgba(139, 92, 246, 0.1);
            padding: 25px 30px;
            border-bottom: 2px solid rgba(139, 92, 246, 0.2);
        }
        
        .table-header h3 {
            color: #8b5cf6;
            font-size: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: rgba(139, 92, 246, 0.1);
            padding: 18px 20px;
            text-align: left;
            font-weight: 700;
            color: #8b5cf6;
            border-bottom: 2px solid rgba(139, 92, 246, 0.2);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        td {
            padding: 18px 20px;
            border-bottom: 1px solid rgba(139, 92, 246, 0.1);
            color: #f8fafc;
            font-weight: 500;
        }
        
        tr {
            transition: all 0.3s ease;
        }
        
        tr:hover {
            background: rgba(139, 92, 246, 0.05);
            transform: translateX(5px);
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-width: 90px; 
            justify-content: center;
            white-space: nowrap; 
            box-sizing: border-box;
        }
        
        .status-aktif {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .status-tidak_aktif {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-toggle {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }
        
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            background: rgba(139, 92, 246, 0.05);
            border-top: 2px solid rgba(139, 92, 246, 0.1);
        }
        
        .pagination-info {
            color: #94a3b8;
            font-size: 14px;
        }
        
        .pagination {
            display: flex;
            gap: 8px;
        }
        
        .pagination a, .pagination span {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .pagination a {
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
            border: 2px solid transparent;
        }
        
        .pagination a:hover {
            background: rgba(139, 92, 246, 0.2);
            border-color: #8b5cf6;
            transform: translateY(-2px);
        }
        
        .pagination .active {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
        }
        
        .pagination .disabled {
            background: rgba(100, 116, 139, 0.1);
            color: #64748b;
            cursor: not-allowed;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 40px;
            background: rgba(15, 23, 42, 0.8);
            border-radius: 20px;
            border: 3px dashed rgba(139, 92, 246, 0.3);
            backdrop-filter: blur(15px);
            margin: 20px;
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
        
        .student-name {
            font-weight: 700;
            color: #8b5cf6;
            font-size: 16px;
        }
        
        .student-details {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 4px;
        }
        
        .contact-info {
            font-size: 13px;
            color: #cbd5e1;
        }
        
        .contact-info i {
            color: #8b5cf6;
            margin-right: 5px;
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
            
            .siswa-table-container {
                overflow-x: auto;
            }
            
            table {
                min-width: 1000px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .floating {
                display: none;
            }
            
            th, td {
                padding: 12px 15px;
            }
            
            .pagination-container {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
        
        @media (max-width: 480px) {
            .page-header {
                padding: 25px 20px;
            }
            
            .page-header h2 {
                font-size: 24px;
            }
            
            .stats-card {
                padding: 20px;
            }
            
            .stats-value {
                font-size: 2em;
            }
            
            .search-container {
                padding: 20px;
            }
            
            .pagination {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
        .btn-reset {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 8px 14px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 11px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }
        
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                font-size: 10px;
                padding: 6px 10px;
            }
        }
    </style>
</head>
<body>
    
    <div class="floating"></div>
    <div class="floating"></div>
    <div class="floating"></div>
    
    <div class="header">
        <h1>⚡ APK BK - Kelola Siswa</h1>
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
        <a href="admin_manage_siswa.php" class="active">
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
        
        <div class="page-header">
            <h2><i class='bx bx-group'></i> Management Data Siswa</h2>
            <p>Kelola akun dan data siswa yang terdaftar dalam sistem</p>
        </div>
        
        <div class="stats-card">
            <h3><i class='bx bx-stats'></i> Total Siswa Terdaftar</h3>
            <div class="stats-value"><?php echo $total_records ?? '0'; ?></div>
            <p style="color: #94a3b8; margin-top: 10px; font-size: 14px;">Siswa aktif dalam sistem</p>
        </div>
        
        <div class="search-container">
            <div class="search-header">
                <h3><i class='bx bx-search'></i> Cari Data Siswa</h3>
            </div>
            
            <form method="GET" action="" class="search-form">
                <div class="form-group search-input-group">
                    <i class='bx bx-search search-icon'></i>
                    <input type="text" 
                           name="search" 
                           class="search-input" 
                           placeholder="Cari berdasarkan nama, username, email, kelas, jurusan, NIS, atau NISN..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="search-buttons">
                    <button type="submit" class="btn-search">
                        <i class='bx bx-search-alt'></i>
                        Cari
                    </button>
                    <a href="admin_manage_siswa.php" class="btn-reset-search">
                        <i class='bx bx-reset'></i>
                        Reset
                    </a>
                </div>
            </form>
            
            <?php if (!empty($search)): ?>
            <div class="search-summary">
                <i class='bx bx-info-circle'></i>
                Hasil pencarian untuk: <strong><?php echo htmlspecialchars($search); ?></strong>
                | Ditemukan: <strong><?php echo $total_records; ?> siswa</strong>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="siswa-table-container">
            <div class="table-header">
                <h3><i class='bx bx-list-ul'></i> Daftar Seluruh Siswa</h3>
            </div>
            
            <?php if (isset($siswa_list) && $siswa_list->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nama Siswa</th>
                            <th>Username</th>
                            <th>Kelas & Jurusan</th>
                            <th>NIS/NISN</th>
                            <th>Kontak</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($siswa = $siswa_list->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="student-name"><?php echo htmlspecialchars($siswa['NAMA_LENGKAP']); ?></div>
                                <div class="student-details">Terdaftar sejak: <?php echo date('d/m/Y', strtotime($siswa['CREATED_AT'] ?? 'now')); ?></div>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($siswa['USERNAME']); ?></strong>
                            </td>
                            <td>
                                <?php if (!empty($siswa['KELAS']) && !empty($siswa['JURUSAN'])): ?>
                                    <strong><?php echo htmlspecialchars($siswa['KELAS']); ?></strong><br>
                                    <span style="color: #94a3b8; font-size: 13px;"><?php echo htmlspecialchars($siswa['JURUSAN']); ?></span>
                                <?php else: ?>
                                    <span style="color: #f59e0b; font-style: italic; font-size: 13px;">
                                        <i class='bx bx-info-circle'></i> Belum lengkap
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($siswa['NIS'])): ?>
                                    <strong>NIS:</strong> <?php echo htmlspecialchars($siswa['NIS']); ?><br>
                                    <?php if (!empty($siswa['NISN'])): ?>
                                    <strong>NISN:</strong> <?php echo htmlspecialchars($siswa['NISN']); ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #f59e0b; font-style: italic; font-size: 13px;">
                                        <i class='bx bx-info-circle'></i> Belum diisi
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="contact-info">
                                    <?php if (!empty($siswa['EMAIL'])): ?>
                                        <div><i class='bx bx-envelope'></i> <?php echo htmlspecialchars($siswa['EMAIL']); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($siswa['NO_TELEPON'])): ?>
                                        <div><i class='bx bx-phone'></i> <?php echo htmlspecialchars($siswa['NO_TELEPON']); ?></div>
                                    <?php endif; ?>
                                    <?php if (empty($siswa['EMAIL']) && empty($siswa['NO_TELEPON'])): ?>
                                        <span style="color: #f59e0b; font-style: italic; font-size: 13px;">
                                            <i class='bx bx-info-circle'></i> Tidak ada kontak
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($siswa['STATUS']); ?>">
                                    <i class='bx bx-<?php echo $siswa['STATUS'] == 'Aktif' ? 'check-circle' : 'x-circle'; ?>'></i>
                                    <?php echo $siswa['STATUS'] == 'Aktif' ? 'Aktif' : 'Nonaktif'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?<?php echo !empty($search) ? 'search=' . urlencode($search) . '&' : ''; ?>page=<?php echo $page; ?>&toggle_status=<?php echo $siswa['ID']; ?>" class="btn btn-toggle">
                                        <i class='bx bx-<?php echo $siswa['STATUS'] == 'Aktif' ? 'user-x' : 'user-check'; ?>'></i>
                                        <?php echo $siswa['STATUS'] == 'Aktif' ? 'Nonaktif' : 'Aktifkan'; ?>
                                    </a>
                                    <a href="?<?php echo !empty($search) ? 'search=' . urlencode($search) . '&' : ''; ?>page=<?php echo $page; ?>&reset_password=<?php echo $siswa['ID']; ?>" class="btn btn-reset" onclick="return confirm('Reset password <?php echo htmlspecialchars($siswa['NAMA_LENGKAP']); ?> menjadi password123?')">
                                        <i class='bx bx-key'></i>
                                        Reset PW
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <div class="pagination-info">
                        Menampilkan <?php echo min(($page - 1) * $per_page + 1, $total_records); ?> - <?php echo min($page * $per_page, $total_records); ?> dari <?php echo $total_records; ?> siswa
                    </div>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo !empty($search) ? 'search=' . urlencode($search) . '&' : ''; ?>page=1" title="Halaman Pertama">
                                <i class='bx bx-chevrons-left'></i>
                            </a>
                            <a href="?<?php echo !empty($search) ? 'search=' . urlencode($search) . '&' : ''; ?>page=<?php echo $page - 1; ?>" title="Sebelumnya">
                                <i class='bx bx-chevron-left'></i>
                            </a>
                        <?php else: ?>
                            <span class="disabled"><i class='bx bx-chevrons-left'></i></span>
                            <span class="disabled"><i class='bx bx-chevron-left'></i></span>
                        <?php endif; ?>
                        
                        <?php 
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1) {
                            echo '<a href="?' . (!empty($search) ? 'search=' . urlencode($search) . '&' : '') . 'page=1">1</a>';
                            if ($start_page > 2) echo '<span class="disabled">...</span>';
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++): 
                        ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo !empty($search) ? 'search=' . urlencode($search) . '&' : ''; ?>page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) echo '<span class="disabled">...</span>';
                            echo '<a href="?' . (!empty($search) ? 'search=' . urlencode($search) . '&' : '') . 'page=' . $total_pages . '">' . $total_pages . '</a>';
                        }
                        ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo !empty($search) ? 'search=' . urlencode($search) . '&' : ''; ?>page=<?php echo $page + 1; ?>" title="Berikutnya">
                                <i class='bx bx-chevron-right'></i>
                            </a>
                            <a href="?<?php echo !empty($search) ? 'search=' . urlencode($search) . '&' : ''; ?>page=<?php echo $total_pages; ?>" title="Halaman Terakhir">
                                <i class='bx bx-chevrons-right'></i>
                            </a>
                        <?php else: ?>
                            <span class="disabled"><i class='bx bx-chevron-right'></i></span>
                            <span class="disabled"><i class='bx bx-chevrons-right'></i></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <?php if (!empty($search)): ?>
                        <h3><i class='bx bx-search-alt'></i> Tidak Ditemukan</h3>
                        <p>Tidak ada siswa yang sesuai dengan kriteria pencarian "<?php echo htmlspecialchars($search); ?>"</p>
                        <div style="margin-top: 20px;">
                            <a href="admin_manage_siswa.php" class="btn-search" style="display: inline-flex; text-decoration: none;">
                                <i class='bx bx-reset'></i>
                                Tampilkan Semua Siswa
                            </a>
                        </div>
                    <?php else: ?>
                        <h3><i class='bx bx-user-x'></i> Belum Ada Siswa Terdaftar</h3>
                        <p>Siswa dapat mendaftar melalui halaman registrasi</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(5px)';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                });
            });
        });
    </script>
</body>
</html>