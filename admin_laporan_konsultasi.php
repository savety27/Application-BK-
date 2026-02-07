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

$filter_tanggal_mulai = $_GET['tanggal_mulai'] ?? '';
$filter_tanggal_akhir = $_GET['tanggal_akhir'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_guru = $_GET['guru_id'] ?? '';
$filter_prioritas = $_GET['prioritas'] ?? '';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10; 

$is_filtering = !empty($filter_tanggal_mulai) || !empty($filter_tanggal_akhir) || 
                !empty($filter_status) || !empty($filter_guru) || !empty($filter_prioritas);

try {
    $sql_base = "SELECT k.*, u.NAMA_LENGKAP as nama_siswa, s.KELAS, s.JURUSAN, 
                       u_guru.NAMA_LENGKAP as nama_guru
                FROM konsultasi k
                JOIN siswa s ON k.SISWA_ID = s.ID
                JOIN users u ON s.USER_ID = u.ID
                LEFT JOIN guru_bk g ON k.GURU_BK_ID = g.ID
                LEFT JOIN users u_guru ON g.USER_ID = u_guru.ID
                WHERE 1=1";
    
    $count_sql = "SELECT COUNT(*) as total
                  FROM konsultasi k
                  JOIN siswa s ON k.SISWA_ID = s.ID
                  JOIN users u ON s.USER_ID = u.ID
                  LEFT JOIN guru_bk g ON k.GURU_BK_ID = g.ID
                  LEFT JOIN users u_guru ON g.USER_ID = u_guru.ID
                  WHERE 1=1";
    
    if (!$is_filtering) {
        $sql_base .= " AND DATE(k.TANGGAL_PENGAJUAN) = CURDATE()";
        $count_sql .= " AND DATE(k.TANGGAL_PENGAJUAN) = CURDATE()";
    }
    
    if (!empty($filter_tanggal_mulai)) {
        $sql_base .= " AND k.TANGGAL_PENGAJUAN >= ?";
        $count_sql .= " AND k.TANGGAL_PENGAJUAN >= ?";
    }
    
    if (!empty($filter_tanggal_akhir)) {
        $sql_base .= " AND k.TANGGAL_PENGAJUAN <= ?";
        $count_sql .= " AND k.TANGGAL_PENGAJUAN <= ?";
    }
    
    if (!empty($filter_status)) {
        $sql_base .= " AND k.STATUS = ?";
        $count_sql .= " AND k.STATUS = ?";
    }
    
    if (!empty($filter_guru)) {
        $sql_base .= " AND k.GURU_BK_ID = ?";
        $count_sql .= " AND k.GURU_BK_ID = ?";
    }
    
    if (!empty($filter_prioritas)) {
        $sql_base .= " AND k.PRIORITAS = ?";
        $count_sql .= " AND k.PRIORITAS = ?";
    }
    
    $sql_base .= " ORDER BY k.CREATED_AT DESC";
    
    $params = [];
    $types = '';
    
    if (!empty($filter_tanggal_mulai)) {
        $params[] = $filter_tanggal_mulai;
        $types .= 's';
    }
    
    if (!empty($filter_tanggal_akhir)) {
        $params[] = $filter_tanggal_akhir;
        $types .= 's';
    }
    
    if (!empty($filter_status)) {
        $params[] = $filter_status;
        $types .= 's';
    }
    
    if (!empty($filter_guru)) {
        $params[] = $filter_guru;
        $types .= 'i';
    }
    
    if (!empty($filter_prioritas)) {
        $params[] = $filter_prioritas;
        $types .= 's';
    }
    
    $stmt_count = $koneksi->prepare($count_sql);
    if (!empty($params)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $count_result = $stmt_count->get_result();
    $total_records = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_records / $per_page);
    
    if ($page < 1) $page = 1;
    if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
    
    $offset = ($page - 1) * $per_page;
    
    $sql_data = $sql_base . " LIMIT ? OFFSET ?";
    
    $stmt_data = $koneksi->prepare($sql_data);
    
    if (!empty($params)) {
        $stmt_data->bind_param($types . "ii", ...array_merge($params, [$per_page, $offset]));
    } else {
        $stmt_data->bind_param("ii", $per_page, $offset);
    }
    
    $stmt_data->execute();
    $konsultasi_list = $stmt_data->get_result();
    
    if (!$konsultasi_list) {
        throw new Exception("Error query: " . $koneksi->error);
    }
    
} catch (Exception $e) {
    $error = "Terjadi kesalahan: " . $e->getMessage();
    $sql_fallback = "SELECT k.*, u.NAMA_LENGKAP as nama_siswa, s.KELAS, s.JURUSAN, 
                            u_guru.NAMA_LENGKAP as nama_guru
                     FROM konsultasi k
                     JOIN siswa s ON k.SISWA_ID = s.ID
                     JOIN users u ON s.USER_ID = u.ID
                     LEFT JOIN guru_bk g ON k.GURU_BK_ID = g.ID
                     LEFT JOIN users u_guru ON g.USER_ID = u_guru.ID
                     WHERE DATE(k.TANGGAL_PENGAJUAN) = CURDATE()
                     ORDER BY k.CREATED_AT DESC";
    $konsultasi_list = $koneksi->query($sql_fallback);
    $total_records = $konsultasi_list->num_rows;
    $total_pages = 1;
}

$sql_guru = "SELECT g.ID, u.NAMA_LENGKAP 
             FROM guru_bk g 
             JOIN users u ON g.USER_ID = u.ID 
             ORDER BY u.NAMA_LENGKAP";
$guru_list = $koneksi->query($sql_guru);

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

$page_title = $is_filtering ? "Hasil Pencarian Data Konsultasi" : "Data Konsultasi Hari Ini";
$page_description = $is_filtering ? "Menampilkan data sesuai filter yang diterapkan" : "Kelola dan pantau data konsultasi hari ini";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Konsultasi - APK BK</title>
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
            background: linear-gradient(135deg, rgba(138, 92, 246, 0.09));
            color: #8b5cf6;
            border-color: #8b5cf6;
            box-shadow: 0 4px 15px rgba(138, 92, 246, 0.2);
        }

        .nav a.active::before {
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
        
        .page-header {
            background: rgba(15, 23, 42, 0.8);
            padding: 35px 40px;
            border-radius: 20px;
            margin-bottom: 35px;
            border: 1px solid rgba(139, 92, 246, 0.2);
            box-shadow: 0 15px 35px rgba(139, 92, 246, 0.15);
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
            background: radial-gradient(circle, rgba(139, 92, 246, 0.05) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.5s;
        }
        
        .page-header:hover::before {
            opacity: 1;
        }
        
        .page-header h2 {
            font-size: 32px;
            margin-bottom: 12px;
            color: #f8fafc;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .page-header p {
            color: #94a3b8;
            font-size: 18px;
            font-weight: 500;
        }
        
        .filter-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 15px;
            padding: 15px;
            background: rgba(139, 92, 246, 0.1);
            border-radius: 12px;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }
        
        .filter-info i {
            color: #8b5cf6;
            font-size: 24px;
        }
        
        .filter-info span {
            color: #cbd5e1;
            font-weight: 500;
        }
        
        .filter-info strong {
            color: #8b5cf6;
            font-size: 18px;
        }
        
        .filter-badge {
            display: inline-block;
            padding: 5px 12px;
            background: rgba(14, 165, 233, 0.2);
            color: #06b6d4;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
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
            gap: 12px;
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
        
        .filter-container {
            background: rgba(15, 23, 42, 0.8);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            border: 1px solid rgba(139, 92, 246, 0.2);
            box-shadow: 0 15px 35px rgba(139, 92, 246, 0.15);
            backdrop-filter: blur(15px);
            animation: fadeIn 0.8s ease-out 0.2s both;
        }
        
        .filter-container h3 {
            color: #f8fafc;
            margin-bottom: 20px;
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #cbd5e1;
            font-weight: 600;
            font-size: 14px;
        }
        
        input, select {
            width: 100%;
            padding: 12px 16px;
            background: rgba(15, 23, 42, 0.9);
            border: 2px solid rgba(139, 92, 246, 0.3);
            border-radius: 10px;
            color: #f8fafc;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #8b5cf6;
            box-shadow: 0 0 20px rgba(139, 92, 246, 0.3);
            background: rgba(15, 23, 42, 0.95);
        }
        
        .filter-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #8b5cf6, #06b6d4);
            color: white;
            box-shadow: 0 6px 20px rgba(139, 92, 246, 0.3);
        }
        
        .btn-secondary {
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
            border: 2px solid rgba(139, 92, 246, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3);
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
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-primary:hover {
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4);
        }
        
        .btn-danger:hover {
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
        }
        
        .table-container {
            background: rgba(15, 23, 42, 0.8);
            border-radius: 20px;
            border: 1px solid rgba(139, 92, 246, 0.2);
            box-shadow: 0 15px 35px rgba(139, 92, 246, 0.15);
            backdrop-filter: blur(15px);
            overflow: hidden;
            animation: fadeIn 0.8s ease-out 0.4s both;
        }
        
        .table-header {
            padding: 25px 30px;
            border-bottom: 1px solid rgba(139, 92, 246, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-header h3 {
            color: #f8fafc;
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .table-actions {
            display: flex;
            gap: 12px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th {
            background: rgba(139, 92, 246, 0.1);
            padding: 18px 20px;
            text-align: left;
            color: #8b5cf6;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(139, 92, 246, 0.2);
        }
        
        .table td {
            padding: 18px 20px;
            border-bottom: 1px solid rgba(139, 92, 246, 0.1);
            color: #cbd5e1;
            font-size: 14px;
        }
        
        .table tbody tr {
            transition: all 0.3s ease;
        }
        
        .table tbody tr:hover {
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
            display: inline-block;
        }
        
        .status-menunggu { 
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        
        .status-disetujui { 
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .status-ditolak { 
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .status-selesai { 
            background: linear-gradient(135deg, #06b6d4, #0891b2);
            color: white;
        }
        
        .priority-badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .priority-rendah { 
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        
        .priority-sedang { 
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        
        .priority-tinggi { 
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .priority-darurat { 
            background: rgba(168, 85, 247, 0.2);
            color: #a855f7;
            border: 1px solid rgba(168, 85, 247, 0.3);
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-small {
            padding: 8px 12px;
            font-size: 12px;
            border-radius: 8px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 40px;
            color: #94a3b8;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .empty-state h4 {
            color: #cbd5e1;
            margin-bottom: 10px;
            font-size: 18px;
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
            
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-actions {
                flex-direction: column;
            }
            
            .table-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .table-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .table {
                display: block;
                overflow-x: auto;
            }
            
            .action-buttons {
                flex-wrap: wrap;
            }
            
            .floating {
                display: none;
            }
            
            .pagination-container {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .pagination {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    
    <div class="floating"></div>
    <div class="floating"></div>
    <div class="floating"></div>
    
    <div class="header">
        <h1>📊 APK BK - Laporan Konsultasi</h1>
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
        <a href="admin_laporan_konsultasi.php" class="active">
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
                <i class='bx bx-check-circle'></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class='bx bx-error-circle'></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="page-header">
            <h2><i class='bx bx-bar-chart-alt'></i> <?php echo $page_title; ?></h2>
            <p><?php echo $page_description; ?></p>
            <div class="filter-info">
                <i class='bx bx-info-circle'></i>
                <span>
                    <?php if ($is_filtering): ?>
                        Menampilkan <strong><?php echo $total_records; ?> data</strong> hasil pencarian
                        <?php if (!empty($filter_tanggal_mulai)): ?>
                            <span class="filter-badge">Dari: <?php echo date('d/m/Y', strtotime($filter_tanggal_mulai)); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($filter_tanggal_akhir)): ?>
                            <span class="filter-badge">Sampai: <?php echo date('d/m/Y', strtotime($filter_tanggal_akhir)); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($filter_status)): ?>
                            <span class="filter-badge">Status: <?php echo $filter_status; ?></span>
                        <?php endif; ?>
                        <?php if (!empty($filter_prioritas)): ?>
                            <span class="filter-badge">Prioritas: <?php echo $filter_prioritas; ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        Menampilkan data konsultasi untuk <strong>Hari Ini (<?php echo date('d/m/Y'); ?>)</strong> • Total: <strong><?php echo $total_records; ?> data</strong>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        
        <div class="filter-container">
            <h3><i class='bx bx-filter'></i> Filter Pencarian</h3>
            <form method="GET" action="">
                <input type="hidden" name="page" value="1">
                <div class="filter-grid">
                    <div class="form-group">
                        <label>Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" value="<?php echo htmlspecialchars($filter_tanggal_mulai); ?>">
                        <small style="color: #94a3b8; font-size: 12px;">Isi untuk mencari data tanggal tertentu</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Tanggal Akhir</label>
                        <input type="date" name="tanggal_akhir" value="<?php echo htmlspecialchars($filter_tanggal_akhir); ?>">
                        <small style="color: #94a3b8; font-size: 12px;">Isi untuk mencari data tanggal tertentu</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="">Semua Status</option>
                            <option value="Menunggu" <?php echo $filter_status == 'Menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                            <option value="Disetujui" <?php echo $filter_status == 'Disetujui' ? 'selected' : ''; ?>>Disetujui</option>
                            <option value="Ditolak" <?php echo $filter_status == 'Ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                            <option value="Selesai" <?php echo $filter_status == 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Guru BK</label>
                        <select name="guru_id">
                            <option value="">Semua Guru</option>
                            <?php while($guru = $guru_list->fetch_assoc()): ?>
                                <option value="<?php echo $guru['ID']; ?>" <?php echo $filter_guru == $guru['ID'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($guru['NAMA_LENGKAP']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Prioritas</label>
                        <select name="prioritas">
                            <option value="">Semua Prioritas</option>
                            <option value="Rendah" <?php echo $filter_prioritas == 'Rendah' ? 'selected' : ''; ?>>Rendah</option>
                            <option value="Sedang" <?php echo $filter_prioritas == 'Sedang' ? 'selected' : ''; ?>>Sedang</option>
                            <option value="Tinggi" <?php echo $filter_prioritas == 'Tinggi' ? 'selected' : ''; ?>>Tinggi</option>
                            <option value="Darurat" <?php echo $filter_prioritas == 'Darurat' ? 'selected' : ''; ?>>Darurat</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-search'></i> Cari Data
                    </button>
                    <a href="admin_laporan_konsultasi.php" class="btn btn-secondary">
                        <i class='bx bx-reset'></i> Reset Filter
                    </a>
                    <?php if ($is_filtering): ?>
                        <a href="export_laporan.php?<?php echo http_build_query(array_merge($_GET, ['page' => 'all'])); ?>" class="btn btn-primary">
                            <i class='bx bx-download'></i> Export Hasil
                        </a>
                        <a href="cetak_laporan.php?<?php echo http_build_query(array_merge($_GET, ['page' => 'all'])); ?>" target="_blank" class="btn btn-primary">
                            <i class='bx bx-printer'></i> Cetak Hasil
                        </a>
                    <?php else: ?>
                        <a href="export_laporan.php" class="btn btn-primary">
                            <i class='bx bx-download'></i> Export Data Hari Ini
                        </a>
                        <a href="cetak_laporan.php" target="_blank" class="btn btn-primary">
                            <i class='bx bx-printer'></i> Cetak Data Hari Ini
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="table-container">
            <div class="table-header">
                <h3>
                    <i class='bx bx-list-ul'></i> 
                    <?php echo $is_filtering ? 'Hasil Pencarian Data Konsultasi' : 'Data Konsultasi Hari Ini'; ?>
                </h3>
                <div class="table-actions">
                    <span style="color: #94a3b8; font-size: 14px;">
                        Total: <?php echo $total_records; ?> data
                    </span>
                </div>
            </div>
            
            <?php if ($konsultasi_list->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Tanggal</th>
                            <th>Siswa</th>
                            <th>Topik</th>
                            <th>Prioritas</th>
                            <th>Guru</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($konsul = $konsultasi_list->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong style="color: #8b5cf6;"><?php echo htmlspecialchars($konsul['KODE_KONSULTASI']); ?></strong>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($konsul['TANGGAL_PENGAJUAN'])); ?></td>
                            <td>
                                <div style="font-weight: 600; color: #f8fafc;"><?php echo htmlspecialchars($konsul['nama_siswa']); ?></div>
                                <div style="font-size: 12px; color: #94a3b8;"><?php echo htmlspecialchars($konsul['KELAS']); ?> - <?php echo htmlspecialchars($konsul['JURUSAN']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($konsul['TOPIK_KONSULTASI']); ?></td>
                            <td>
                                <span class="priority-badge priority-<?php echo strtolower($konsul['PRIORITAS']); ?>">
                                    <?php echo $konsul['PRIORITAS']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($konsul['nama_guru']): ?>
                                    <?php echo htmlspecialchars($konsul['nama_guru']); ?>
                                <?php else: ?>
                                    <span style="color: #94a3b8; font-style: italic;">Belum ditugaskan</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($konsul['STATUS']); ?>">
                                    <?php echo $konsul['STATUS']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="admin_edit_konsultasi.php?id=<?php echo $konsul['ID']; ?>" class="btn btn-primary btn-small">
                                        <i class='bx bx-edit'></i>
                                    </a>
                                    <a href="admin_hapus_konsultasi.php?id=<?php echo $konsul['ID']; ?>" 
                                       class="btn btn-danger btn-small"
                                       onclick="return confirm('Yakin ingin menghapus konsultasi ini?')">
                                        <i class='bx bx-trash'></i>
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
                        Menampilkan <?php echo min(($page - 1) * $per_page + 1, $total_records); ?> - <?php echo min($page * $per_page, $total_records); ?> dari <?php echo $total_records; ?> data
                    </div>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo !empty($filter_tanggal_mulai) ? 'tanggal_mulai=' . urlencode($filter_tanggal_mulai) . '&' : ''; ?><?php echo !empty($filter_tanggal_akhir) ? 'tanggal_akhir=' . urlencode($filter_tanggal_akhir) . '&' : ''; ?><?php echo !empty($filter_status) ? 'status=' . urlencode($filter_status) . '&' : ''; ?><?php echo !empty($filter_guru) ? 'guru_id=' . urlencode($filter_guru) . '&' : ''; ?><?php echo !empty($filter_prioritas) ? 'prioritas=' . urlencode($filter_prioritas) . '&' : ''; ?>page=1" title="Halaman Pertama">
                                <i class='bx bx-chevrons-left'></i>
                            </a>
                            <a href="?<?php echo !empty($filter_tanggal_mulai) ? 'tanggal_mulai=' . urlencode($filter_tanggal_mulai) . '&' : ''; ?><?php echo !empty($filter_tanggal_akhir) ? 'tanggal_akhir=' . urlencode($filter_tanggal_akhir) . '&' : ''; ?><?php echo !empty($filter_status) ? 'status=' . urlencode($filter_status) . '&' : ''; ?><?php echo !empty($filter_guru) ? 'guru_id=' . urlencode($filter_guru) . '&' : ''; ?><?php echo !empty($filter_prioritas) ? 'prioritas=' . urlencode($filter_prioritas) . '&' : ''; ?>page=<?php echo $page - 1; ?>" title="Sebelumnya">
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
                            echo '<a href="?' . (!empty($filter_tanggal_mulai) ? 'tanggal_mulai=' . urlencode($filter_tanggal_mulai) . '&' : '') . (!empty($filter_tanggal_akhir) ? 'tanggal_akhir=' . urlencode($filter_tanggal_akhir) . '&' : '') . (!empty($filter_status) ? 'status=' . urlencode($filter_status) . '&' : '') . (!empty($filter_guru) ? 'guru_id=' . urlencode($filter_guru) . '&' : '') . (!empty($filter_prioritas) ? 'prioritas=' . urlencode($filter_prioritas) . '&' : '') . 'page=1">1</a>';
                            if ($start_page > 2) echo '<span class="disabled">...</span>';
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++): 
                        ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo !empty($filter_tanggal_mulai) ? 'tanggal_mulai=' . urlencode($filter_tanggal_mulai) . '&' : ''; ?><?php echo !empty($filter_tanggal_akhir) ? 'tanggal_akhir=' . urlencode($filter_tanggal_akhir) . '&' : ''; ?><?php echo !empty($filter_status) ? 'status=' . urlencode($filter_status) . '&' : ''; ?><?php echo !empty($filter_guru) ? 'guru_id=' . urlencode($filter_guru) . '&' : ''; ?><?php echo !empty($filter_prioritas) ? 'prioritas=' . urlencode($filter_prioritas) . '&' : ''; ?>page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) echo '<span class="disabled">...</span>';
                            echo '<a href="?' . (!empty($filter_tanggal_mulai) ? 'tanggal_mulai=' . urlencode($filter_tanggal_mulai) . '&' : '') . (!empty($filter_tanggal_akhir) ? 'tanggal_akhir=' . urlencode($filter_tanggal_akhir) . '&' : '') . (!empty($filter_status) ? 'status=' . urlencode($filter_status) . '&' : '') . (!empty($filter_guru) ? 'guru_id=' . urlencode($filter_guru) . '&' : '') . (!empty($filter_prioritas) ? 'prioritas=' . urlencode($filter_prioritas) . '&' : '') . 'page=' . $total_pages . '">' . $total_pages . '</a>';
                        }
                        ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo !empty($filter_tanggal_mulai) ? 'tanggal_mulai=' . urlencode($filter_tanggal_mulai) . '&' : ''; ?><?php echo !empty($filter_tanggal_akhir) ? 'tanggal_akhir=' . urlencode($filter_tanggal_akhir) . '&' : ''; ?><?php echo !empty($filter_status) ? 'status=' . urlencode($filter_status) . '&' : ''; ?><?php echo !empty($filter_guru) ? 'guru_id=' . urlencode($filter_guru) . '&' : ''; ?><?php echo !empty($filter_prioritas) ? 'prioritas=' . urlencode($filter_prioritas) . '&' : ''; ?>page=<?php echo $page + 1; ?>" title="Berikutnya">
                                <i class='bx bx-chevron-right'></i>
                            </a>
                            <a href="?<?php echo !empty($filter_tanggal_mulai) ? 'tanggal_mulai=' . urlencode($filter_tanggal_mulai) . '&' : ''; ?><?php echo !empty($filter_tanggal_akhir) ? 'tanggal_akhir=' . urlencode($filter_tanggal_akhir) . '&' : ''; ?><?php echo !empty($filter_status) ? 'status=' . urlencode($filter_status) . '&' : ''; ?><?php echo !empty($filter_guru) ? 'guru_id=' . urlencode($filter_guru) . '&' : ''; ?><?php echo !empty($filter_prioritas) ? 'prioritas=' . urlencode($filter_prioritas) . '&' : ''; ?>page=<?php echo $total_pages; ?>" title="Halaman Terakhir">
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
                    <?php if ($is_filtering): ?>
                        <i class='bx bx-search'></i>
                        <h4>Data Tidak Ditemukan</h4>
                        <p>Tidak ada data konsultasi yang sesuai dengan kriteria pencarian</p>
                        <p style="color: #8b5cf6; margin-top: 10px;">
                            Coba ubah filter atau <a href="admin_laporan_konsultasi.php" style="color: #06b6d4; text-decoration: none;">lihat data hari ini</a>
                        </p>
                    <?php else: ?>
                        <i class='bx bx-inbox'></i>
                        <h4>Tidak Ada Data Konsultasi Hari Ini</h4>
                        <p>Belum ada data konsultasi yang diajukan hari ini</p>
                        <p style="color: #8b5cf6; margin-top: 10px;">
                            Gunakan filter di atas untuk mencari data dari tanggal lain
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tanggalAkhir = document.querySelector('input[name="tanggal_akhir"]');
            const tanggalMulai = document.querySelector('input[name="tanggal_mulai"]');
        });
    </script>
</body>
</html>