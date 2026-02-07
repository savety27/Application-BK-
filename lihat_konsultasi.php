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

$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5; 
$offset = ($page - 1) * $limit;

$base_query = "SELECT k.*, 
                      u_guru.NAMA_LENGKAP as NAMA_GURU,
                      ug1.NAMA_LENGKAP as GURU_PILIHAN_1,
                      ug2.NAMA_LENGKAP as GURU_PILIHAN_2
               FROM konsultasi k
               LEFT JOIN guru_bk g ON k.GURU_BK_ID = g.ID
               LEFT JOIN users u_guru ON g.USER_ID = u_guru.ID
               LEFT JOIN guru_bk gb1 ON k.PILIHAN_GURU_1 = gb1.ID
               LEFT JOIN users ug1 ON gb1.USER_ID = ug1.ID
               LEFT JOIN guru_bk gb2 ON k.PILIHAN_GURU_2 = gb2.ID
               LEFT JOIN users ug2 ON gb2.USER_ID = ug2.ID
               WHERE k.SISWA_ID = ?";

$count_query = "SELECT COUNT(*) as total FROM konsultasi k WHERE k.SISWA_ID = ?";

$conditions = [];
$params = [$siswa_id];
$types = "i";

$count_conditions = [];
$count_params = [$siswa_id];
$count_types = "i";

if (!empty($search)) {
    $search_guru_sql = "SELECT g.ID FROM guru_bk g 
                       JOIN users u ON g.USER_ID = u.ID 
                       WHERE u.NAMA_LENGKAP LIKE ?";
    $stmt_guru = $koneksi->prepare($search_guru_sql);
    $search_term = "%$search%";
    $stmt_guru->bind_param("s", $search_term);
    $stmt_guru->execute();
    $guru_ids_result = $stmt_guru->get_result();
    $guru_ids = [];
    while ($row = $guru_ids_result->fetch_assoc()) {
        $guru_ids[] = $row['ID'];
    }
    
    $conditions[] = "(k.KODE_KONSULTASI LIKE ? OR k.TOPIK_KONSULTASI LIKE ? OR k.DESKRIPSI_MASALAH LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "sss";
    
    $count_conditions[] = "(k.KODE_KONSULTASI LIKE ? OR k.TOPIK_KONSULTASI LIKE ? OR k.DESKRIPSI_MASALAH LIKE ?)";
    $count_params[] = "%$search%";
    $count_params[] = "%$search%";
    $count_params[] = "%$search%";
    $count_types .= "sss";
}

if (!empty($status_filter) && $status_filter != 'all') {
    $conditions[] = "k.STATUS = ?";
    $params[] = $status_filter;
    $types .= "s";
    
    $count_conditions[] = "k.STATUS = ?";
    $count_params[] = $status_filter;
    $count_types .= "s";
}

if (!empty($priority_filter) && $priority_filter != 'all') {
    $conditions[] = "k.PRIORITAS = ?";
    $params[] = $priority_filter;
    $types .= "s";
    
    $count_conditions[] = "k.PRIORITAS = ?";
    $count_params[] = $priority_filter;
    $count_types .= "s";
}

if (!empty($date_from)) {
    $conditions[] = "DATE(k.TANGGAL_PENGAJUAN) >= ?";
    $params[] = $date_from;
    $types .= "s";
    
    $count_conditions[] = "DATE(k.TANGGAL_PENGAJUAN) >= ?";
    $count_params[] = $date_from;
    $count_types .= "s";
}

if (!empty($date_to)) {
    $conditions[] = "DATE(k.TANGGAL_PENGAJUAN) <= ?";
    $params[] = $date_to;
    $types .= "s";
    
    $count_conditions[] = "DATE(k.TANGGAL_PENGAJUAN) <= ?";
    $count_params[] = $date_to;
    $count_types .= "s";
}

if (!empty($conditions)) {
    $base_query .= " AND " . implode(" AND ", $conditions);
}

if (!empty($count_conditions)) {
    $count_query .= " AND " . implode(" AND ", $count_conditions);
}

$stmt_count = $koneksi->prepare($count_query);
if ($count_params) {
    $stmt_count->bind_param($count_types, ...$count_params);
}
$stmt_count->execute();
$count_result = $stmt_count->get_result()->fetch_assoc();
$total_records = $count_result['total'];
$total_pages = ceil($total_records / $limit);

if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}

$base_query .= " ORDER BY k.CREATED_AT DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt_konsultasi = $koneksi->prepare($base_query);
if ($params) {
    $stmt_konsultasi->bind_param($types, ...$params);
}
$stmt_konsultasi->execute();
$konsultasi_list = $stmt_konsultasi->get_result()->fetch_all(MYSQLI_ASSOC);

if (!empty($konsultasi_list)) {
    $konsultasi_ids = array_column($konsultasi_list, 'ID');
    $placeholders = implode(',', array_fill(0, count($konsultasi_ids), '?'));
    
    $sql_sesi = "SELECT s.*, k.KODE_KONSULTASI, k.TOPIK_KONSULTASI, u.NAMA_LENGKAP as NAMA_GURU
                 FROM sesi_konsultasi s
                 JOIN konsultasi k ON s.KONSULTASI_ID = k.ID
                 JOIN guru_bk g ON k.GURU_BK_ID = g.ID
                 JOIN users u ON g.USER_ID = u.ID
                 WHERE k.SISWA_ID = ? AND k.ID IN ($placeholders)
                 ORDER BY s.TANGGAL_SESI DESC, s.JAM_MULAI DESC";
    
    $stmt_sesi = $koneksi->prepare($sql_sesi);
    $stmt_sesi->bind_param("i" . str_repeat("i", count($konsultasi_ids)), $siswa_id, ...$konsultasi_ids);
    $stmt_sesi->execute();
    $sesi_list = $stmt_sesi->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $sesi_list = [];
}

$sesi_by_konsultasi = [];
foreach ($sesi_list as $sesi) {
    $konsultasi_id = $sesi['KONSULTASI_ID'];
    if (!isset($sesi_by_konsultasi[$konsultasi_id])) {
        $sesi_by_konsultasi[$konsultasi_id] = [];
    }
    $sesi_by_konsultasi[$konsultasi_id][] = $sesi;
}

function getStatusBadge($status) {
    $badges = [
        'Menunggu' => 'status-pending',
        'Disetujui' => 'status-approved', 
        'Ditolak' => 'status-rejected',
        'Selesai' => 'status-selesai',
        'Dibatalkan' => 'status-cancelled'
    ];
    $icons = [
        'Menunggu' => 'bx bx-time',
        'Disetujui' => 'bx bx-check-circle',
        'Ditolak' => 'bx bx-x-circle',
        'Selesai' => 'bx bx-party',
        'Dibatalkan' => 'bx bx-block'
    ];
    
    $class = $badges[$status] ?? 'status-pending';
    $icon = $icons[$status] ?? 'bx bx-time';
    
    return "<span class='status-badge $class'><i class='$icon'></i> $status</span>";
}

function getPriorityBadge($prioritas) {
    $classes = [
        'Rendah' => 'priority-rendah',
        'Sedang' => 'priority-sedang',
        'Tinggi' => 'priority-tinggi',
        'Darurat' => 'priority-darurat'
    ];
    $icons = [
        'Rendah' => 'bx bx-chevron-down',
        'Sedang' => 'bx bx-minus',
        'Tinggi' => 'bx bx-chevron-up', 
        'Darurat' => 'bx bx-error'
    ];
    
    $class = $classes[$prioritas] ?? 'priority-rendah';
    $icon = $icons[$prioritas] ?? 'bx bx-chevron-down';
    
    return "<span class='priority-badge $class'><i class='$icon'></i> $prioritas</span>";
}

function buildQueryString($exclude = []) {
    $params = [];
    $allowed = ['search', 'status', 'priority', 'date_from', 'date_to'];
    
    foreach ($allowed as $key) {
        if (!in_array($key, $exclude) && isset($_GET[$key]) && $_GET[$key] != '') {
            $params[$key] = $_GET[$key];
        }
    }
    
    return !empty($params) ? '&' . http_build_query($params) : '';
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lihat Konsultasi - APK BK</title>
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
        
        .page-header {
            background: rgba(102, 126, 234, 0.05);
            padding: 30px;
            border-radius: 16px;
            margin: 20px;
            border: 1px solid rgba(102, 126, 234, 0.15);
            animation: fadeIn 0.8s ease-out;
        }
        
        .page-header h2 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
        }
        
        .page-header p {
            color: #718096;
            font-size: 16px;
            line-height: 1.6;
        }
        
        .filter-section {
            background: rgba(102, 126, 234, 0.05);
            padding: 25px;
            border-radius: 16px;
            margin: 20px;
            border: 1px solid rgba(102, 126, 234, 0.15);
            animation: fadeIn 0.8s ease-out;
        }
        
        .filter-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .filter-title {
            font-size: 20px;
            font-weight: 600;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-label {
            font-size: 14px;
            font-weight: 600;
            color: #4a5568;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .form-input {
            padding: 12px 15px;
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 12px;
            font-size: 14px;
            color: #2d3748;
            background: white;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-select {
            padding: 12px 15px;
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 12px;
            font-size: 14px;
            color: #2d3748;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .form-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .filter-actions {
            display: grid;
            grid-template-columns: 10fr 7fr;
            gap: 15px;
            margin-top: 20px;
        }
        
        .filter-actions .btn {
            width: 100%;
            justify-content: center;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            border: 1px solid rgba(102, 126, 234, 0.3);
        }
        
        .btn-danger {
            background: rgba(245, 101, 101, 0.1);
            color: #e53e3e;
            border: 1px solid rgba(245, 101, 101, 0.3);
        }
        
        .btn-success {
            background: rgba(72, 187, 120, 0.1);
            color: #38a169;
            border: 1px solid rgba(72, 187, 120, 0.3);
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
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-disabled {
            background: #e2e8f0;
            color: #718096;
            cursor: not-allowed;
            pointer-events: none;
            box-shadow: none;
        }
        
        .results-info {
            background: rgba(102, 126, 234, 0.05);
            padding: 15px 25px;
            border-radius: 12px;
            margin: 0 20px 20px 20px;
            border: 1px solid rgba(102, 126, 234, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            animation: fadeIn 0.8s ease-out;
        }
        
        .results-text {
            font-size: 14px;
            color: #4a5568;
            font-weight: 500;
        }
        
        .results-count {
            font-weight: 600;
            color: #667eea;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            padding: 30px 20px;
            border-top: 1px solid rgba(102, 126, 234, 0.1);
            margin-top: 20px;
            animation: fadeIn 0.8s ease-out;
        }
        
        .pagination-info {
            font-size: 14px;
            color: #4a5568;
            margin-right: 20px;
        }
        
        .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            padding: 0 15px;
            border-radius: 12px;
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid rgba(102, 126, 234, 0.2);
        }
        
        .page-link:hover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .page-link.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .page-link.disabled {
            background: #e2e8f0;
            color: #a0aec0;
            cursor: not-allowed;
            pointer-events: none;
            box-shadow: none;
        }
        
        .konsultasi-grid {
            display: grid;
            gap: 25px;
            padding: 20px;
        }
        
        .konsultasi-card {
            background: rgba(255, 251, 240, 0.8);
            border-radius: 16px;
            border: 1px solid rgba(102, 126, 234, 0.15);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.08);
            padding: 25px;
            transition: all 0.3s ease;
            animation: slideUp 0.6s ease-out;
        }
        
        .konsultasi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.15);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(102, 126, 234, 0.1);
        }
        
        .kode-konsultasi {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            background: rgba(102, 126, 234, 0.1);
            padding: 8px 16px;
            border-radius: 12px;
            border: 1px solid rgba(102, 126, 234, 0.2);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            border: 1px solid;
        }
        
        .status-pending {
            background: rgba(255, 152, 0, 0.1);
            color: #ed8936;
            border-color: rgba(255, 152, 0, 0.3);
        }
        
        .status-approved {
            background: rgba(72, 187, 120, 0.1);
            color: #38a169;
            border-color: rgba(72, 187, 120, 0.3);
        }
        
        .status-rejected {
            background: rgba(245, 101, 101, 0.1);
            color: #e53e3e;
            border-color: rgba(245, 101, 101, 0.3);
        }
        
        .status-selesai {
            background: rgba(66, 153, 225, 0.1);
            color: #3182ce;
            border-color: rgba(66, 153, 225, 0.3);
        }
        
        .status-cancelled {
            background: rgba(160, 174, 192, 0.1);
            color: #a0aec0;
            border-color: rgba(160, 174, 192, 0.3);
        }
        
        .card-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-group {
            margin-bottom: 15px;
        }
        
        .info-label {
            font-size: 12px;
            color: #718096;
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
            color: #2d3748;
            font-weight: 500;
        }
        
        .priority-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 12px;
            border: 1px solid;
        }
        
        .priority-rendah { 
            background: rgba(66, 153, 225, 0.1); 
            color: #3182ce; 
            border-color: rgba(66, 153, 225, 0.3); 
        }
        .priority-sedang { 
            background: rgba(255, 152, 0, 0.1); 
            color: #ed8936; 
            border-color: rgba(255, 152, 0, 0.3); 
        }
        .priority-tinggi { 
            background: rgba(245, 101, 101, 0.1); 
            color: #e53e3e; 
            border-color: rgba(245, 101, 101, 0.3); 
        }
        .priority-darurat { 
            background: rgba(159, 122, 234, 0.1); 
            color: #9f7aea; 
            border-color: rgba(159, 122, 234, 0.3); 
        }
        
        .deskripsi-masalah {
            background: rgba(102, 126, 234, 0.05);
            padding: 15px;
            border-radius: 12px;
            border-left: 4px solid #667eea;
            margin-top: 10px;
            color: #4a5568;
            line-height: 1.6;
        }
        
        .sesi-konsultasi {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px dashed rgba(102, 126, 234, 0.2);
        }
        
        .sesi-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .sesi-title {
            font-size: 20px;
            font-weight: 600;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .sesi-grid {
            display: grid;
            gap: 15px;
        }
        
        .sesi-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            border: 1px solid rgba(102, 126, 234, 0.1);
            padding: 20px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.08);
            transition: all 0.3s ease;
        }
        
        .sesi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.12);
        }
        
        .sesi-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(102, 126, 234, 0.1);
        }
        
        .sesi-number {
            font-size: 16px;
            font-weight: 600;
            color: #667eea;
            background: rgba(102, 126, 234, 0.1);
            padding: 6px 12px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .sesi-date {
            font-size: 14px;
            color: #718096;
            font-weight: 500;
        }
        
        .sesi-content {
            display: grid;
            gap: 12px;
        }
        
        .sesi-item {
            margin-bottom: 10px;
        }
        
        .sesi-label {
            font-size: 12px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .sesi-value {
            font-size: 14px;
            color: #2d3748;
            line-height: 1.5;
        }
        
        .sesi-box {
            background: rgba(102, 126, 234, 0.05);
            padding: 12px;
            border-radius: 8px;
            border-left: 3px solid #667eea;
            margin-top: 5px;
        }
        
        .no-sesi {
            text-align: center;
            padding: 30px;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 12px;
            border: 2px dashed rgba(102, 126, 234, 0.3);
            color: #718096;
        }
        
        .card-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(102, 126, 234, 0.1);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 40px;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 16px;
            margin: 20px;
            border: 2px dashed rgba(102, 126, 234, 0.3);
            animation: fadeIn 0.8s ease-out;
        }
        
        .empty-state h3 {
            color: #667eea;
            font-size: 24px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .empty-state p {
            color: #718096;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        
        .btn-large {
            padding: 15px 30px;
            font-size: 16px;
            font-weight: 600;
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
            
            .header h1 {
                font-size: 24px;
            }
            
            .container {
                margin: 20px auto;
                padding: 0 15px;
            }
            
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .filter-actions {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .results-info {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .pagination {
                flex-wrap: wrap;
            }
            
            .konsultasi-grid {
                padding: 15px;
                gap: 20px;
            }
            
            .konsultasi-card {
                padding: 20px;
            }
            
            .card-header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
            .card-content {
                grid-template-columns: 1fr;
            }
            
            .card-actions {
                flex-direction: column;
            }
            
            .page-header {
                padding: 25px 20px;
                margin: 15px;
            }
            
            .page-header h2 {
                font-size: 24px;
            }
            
            .floating {
                display: none;
            }
        }
        
        @media (max-width: 480px) {
            .header {
                padding: 12px 15px;
            }
            
            .header h1 {
                font-size: 20px;
            }
            
            .back-btn {
                padding: 10px 18px;
                font-size: 14px;
            }
            
            .konsultasi-card {
                padding: 15px;
            }
            
            .btn {
                padding: 10px 16px;
                font-size: 13px;
            }
            
            .page-header {
                padding: 20px 15px;
            }
            
            .page-link {
                min-width: 35px;
                height: 35px;
                padding: 0 10px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
  
    <div class="floating"></div>
    <div class="floating"></div>
    <div class="floating"></div>
    
    <div class="header">
        <h1><i class='bx bx-history'></i> Riwayat Konsultasi</h1>
        <a href="dashboard_siswa.php" class="back-btn">
            <i class='bx bx-arrow-back'></i>
            Kembali ke Dashboard
        </a>
    </div>
    
    <div class="container">
        <div class="content">
            <div class="page-header">
                <h2><i class='bx bx-list-check'></i> Riwayat Konsultasi</h2>
                <p>Lihat status dan riwayat semua konsultasi yang telah Anda ajukan</p>
            </div>
            
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
            
            <div class="filter-section">
                <div class="filter-header">
                    <h3 class="filter-title">
                        <i class='bx bx-filter-alt'></i>
                        Filter & Pencarian
                    </h3>
                </div>
                
                <form method="GET" action="" class="filter-form">
                    <div class="form-group">
                        <label class="form-label">
                            <i class='bx bx-search'></i>
                            Cari Konsultasi
                        </label>
                        <input type="text" 
                               name="search" 
                               class="form-input" 
                               placeholder="Cari berdasarkan kode, topik, guru..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class='bx bx-category'></i>
                            Status
                        </label>
                        <select name="status" class="form-select">
                            <option value="all" <?php echo $status_filter == 'all' || empty($status_filter) ? 'selected' : ''; ?>>Semua Status</option>
                            <option value="Menunggu" <?php echo $status_filter == 'Menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                            <option value="Disetujui" <?php echo $status_filter == 'Disetujui' ? 'selected' : ''; ?>>Disetujui</option>
                            <option value="Ditolak" <?php echo $status_filter == 'Ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                            <option value="Selesai" <?php echo $status_filter == 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class='bx bx-bolt'></i>
                            Prioritas
                        </label>
                        <select name="priority" class="form-select">
                            <option value="all" <?php echo $priority_filter == 'all' || empty($priority_filter) ? 'selected' : ''; ?>>Semua Prioritas</option>
                            <option value="Rendah" <?php echo $priority_filter == 'Rendah' ? 'selected' : ''; ?>>Rendah</option>
                            <option value="Sedang" <?php echo $priority_filter == 'Sedang' ? 'selected' : ''; ?>>Sedang</option>
                            <option value="Tinggi" <?php echo $priority_filter == 'Tinggi' ? 'selected' : ''; ?>>Tinggi</option>
                            <option value="Darurat" <?php echo $priority_filter == 'Darurat' ? 'selected' : ''; ?>>Darurat</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class='bx bx-calendar'></i>
                            Dari Tanggal
                        </label>
                        <input type="date" 
                               name="date_from" 
                               class="form-input" 
                               value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class='bx bx-calendar'></i>
                            Sampai Tanggal
                        </label>
                        <input type="date" 
                               name="date_to" 
                               class="form-input" 
                               value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-search'></i>
                            Terapkan 
                        </button>
                        <a href="lihat_konsultasi.php" class="btn btn-secondary">
                            <i class='bx bx-reset'></i>
                            Reset 
                        </a>
                    </div>
                </form>
            </div>
            
            <?php if (!empty($konsultasi_list) || !empty($search) || !empty($status_filter) || !empty($priority_filter) || !empty($date_from) || !empty($date_to)): ?>
            <div class="results-info">
                <div class="results-text">
                    <?php if (!empty($konsultasi_list)): ?>
                        Menampilkan 
                        <span class="results-count"><?php echo count($konsultasi_list); ?></span> 
                        dari 
                        <span class="results-count"><?php echo $total_records; ?></span> 
                        konsultasi
                    <?php else: ?>
                        Tidak ada hasil ditemukan untuk filter yang dipilih
                    <?php endif; ?>
                </div>
                <div class="results-text">
                    Halaman 
                    <span class="results-count"><?php echo $page; ?></span> 
                    dari 
                    <span class="results-count"><?php echo $total_pages; ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (empty($konsultasi_list)): ?>
                <div class="empty-state">
                    <h3><i class='bx bx-inbox'></i> 
                        <?php if (empty($search) && empty($status_filter) && empty($priority_filter) && empty($date_from) && empty($date_to)): ?>
                            Belum Ada Konsultasi
                        <?php else: ?>
                            Tidak Ada Hasil
                        <?php endif; ?>
                    </h3>
                    <p>
                        <?php if (empty($search) && empty($status_filter) && empty($priority_filter) && empty($date_from) && empty($date_to)): ?>
                            Anda belum mengajukan konsultasi apapun. Ajukan konsultasi pertama Anda sekarang!
                        <?php else: ?>
                            Tidak ada konsultasi yang sesuai dengan filter pencarian Anda.
                        <?php endif; ?>
                    </p>
                    <?php if (empty($search) && empty($status_filter) && empty($priority_filter) && empty($date_from) && empty($date_to)): ?>
                    <a href="ajukan_konsultasi.php" class="btn btn-primary btn-large">
                        <i class='bx bx-message-square-add'></i>
                        Ajukan Konsultasi Pertama
                    </a>
                    <?php else: ?>
                    <a href="lihat_konsultasi.php" class="btn btn-primary btn-large">
                        <i class='bx bx-reset'></i>
                        Tampilkan Semua Konsultasi
                    </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="konsultasi-grid">
                    <?php foreach ($konsultasi_list as $konsultasi): ?>
                    <div class="konsultasi-card">
                        <div class="card-header">
                            <div class="kode-konsultasi">
                                <i class='bx bx-hash'></i>
                                <?php echo htmlspecialchars($konsultasi['KODE_KONSULTASI']); ?>
                            </div>
                            <?php echo getStatusBadge($konsultasi['STATUS']); ?>
                        </div>
                        
                        <div class="card-content">
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-calendar'></i> Tanggal Pengajuan</div>
                                <div class="info-value"><?php echo date('d/m/Y', strtotime($konsultasi['TANGGAL_PENGAJUAN'])); ?></div>
                            </div>
                            
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-target-lock'></i> Topik</div>
                                <div class="info-value"><?php echo htmlspecialchars($konsultasi['TOPIK_KONSULTASI']); ?></div>
                            </div>
                            
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-bolt'></i> Prioritas</div>
                                <div class="info-value"><?php echo getPriorityBadge($konsultasi['PRIORITAS']); ?></div>
                            </div>
                            
                            <?php 
                            $mode_konsultasi = $konsultasi['MODE_KONSULTASI'] ?? 'Offline';
                            $meeting_link = $konsultasi['MEETING_LINK'] ?? '';
                            $status_konsultasi = $konsultasi['STATUS'] ?? 'Menunggu';
                            $is_expired = in_array($status_konsultasi, ['Selesai', 'Dibatalkan', 'Ditolak'], true);
                            $can_join = ($mode_konsultasi === 'Online' && !empty($meeting_link) && $status_konsultasi === 'Disetujui' && !$is_expired);
                            ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-wifi'></i> Mode Konsultasi</div>
                                <div class="info-value">
                                    <?php echo htmlspecialchars($mode_konsultasi); ?>
                                    <?php if ($mode_konsultasi === 'Online'): ?>
                                        <?php if ($can_join): ?>
                                            <div style="margin-top: 8px;">
                                                <a href="<?php echo htmlspecialchars($meeting_link); ?>" target="_blank" rel="noopener" class="btn btn-primary">
                                                    <i class='bx bx-link'></i>
                                                    Join Meeting
                                                </a>
                                            </div>
                                        <?php elseif (!empty($meeting_link) && $is_expired): ?>
                                            <div style="margin-top: 8px;">
                                                <span class="btn btn-disabled">
                                                    <i class='bx bx-block'></i>
                                                    Meeting Kedaluwarsa
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <div style="margin-top: 6px; color: #e53e3e; font-weight: 600;">
                                                Link meeting belum tersedia
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($konsultasi['NAMA_GURU']): ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-user-check'></i> Guru Penanggung Jawab</div>
                                <div class="info-value"><?php echo htmlspecialchars($konsultasi['NAMA_GURU']); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($konsultasi['TANGGAL_KONSULTASI']): ?>
                            <div class="info-group">
                                <div class="info-label"><i class='bx bx-time'></i> Jadwal Konsultasi</div>
                                <div class="info-value">
                                    <?php echo date('d/m/Y', strtotime($konsultasi['TANGGAL_KONSULTASI'])); ?>
                                    <?php if ($konsultasi['JAM_KONSULTASI']): ?>
                                    pukul <?php echo date('H:i', strtotime($konsultasi['JAM_KONSULTASI'])); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="info-group">
                            <div class="info-label"><i class='bx bx-note'></i> Deskripsi Masalah</div>
                            <div class="deskripsi-masalah">
                                <?php echo nl2br(htmlspecialchars($konsultasi['DESKRIPSI_MASALAH'])); ?>
                            </div>
                        </div>
                        
                        <?php if ($konsultasi['GURU_PILIHAN_1'] || $konsultasi['GURU_PILIHAN_2']): ?>
                        <div class="info-group">
                            <div class="info-label"><i class='bx bx-group'></i> Guru Pilihan</div>
                            <div class="info-value">
                                <?php if ($konsultasi['GURU_PILIHAN_1']): ?>
                                <div style="margin-bottom: 5px;">
                                    <i class='bx bx-user' style="color: #667eea;"></i>
                                    <?php echo htmlspecialchars($konsultasi['GURU_PILIHAN_1']); ?> (Pilihan 1)
                                </div>
                                <?php endif; ?>
                                <?php if ($konsultasi['GURU_PILIHAN_2']): ?>
                                <div>
                                    <i class='bx bx-user' style="color: #667eea;"></i>
                                    <?php echo htmlspecialchars($konsultasi['GURU_PILIHAN_2']); ?> (Pilihan 2)
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($konsultasi['KOMENTAR_GURU']): ?>
                        <div class="info-group">
                            <div class="info-label"><i class='bx bx-message-detail'></i> Komentar Guru</div>
                            <div class="deskripsi-masalah">
                                <?php echo nl2br(htmlspecialchars($konsultasi['KOMENTAR_GURU'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="sesi-konsultasi">
                            <div class="sesi-header">
                                <h3 class="sesi-title">
                                    <i class='bx bx-conversation'></i>
                                    Sesi Konsultasi
                                </h3>
                            </div>
                            
                            <?php 
                            $konsultasi_id = $konsultasi['ID'];
                            $sesi_konsultasi = $sesi_by_konsultasi[$konsultasi_id] ?? [];
                            ?>
                            
                            <?php if (!empty($sesi_konsultasi)): ?>
                                <div class="sesi-grid">
                                    <?php foreach ($sesi_konsultasi as $sesi): ?>
                                    <div class="sesi-card">
                                        <div class="sesi-card-header">
                                            <div class="sesi-number">
                                                <i class='bx bx-hash'></i>
                                                Sesi #<?php echo $sesi['SESI_KE']; ?>
                                            </div>
                                            <div class="sesi-date">
                                                <?php echo date('d/m/Y', strtotime($sesi['TANGGAL_SESI'])); ?> | 
                                                <?php echo date('H:i', strtotime($sesi['JAM_MULAI'])); ?> - <?php echo date('H:i', strtotime($sesi['JAM_SELESAI'])); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="sesi-content">
                                            <?php if ($sesi['POKOK_PEMBAHASAN']): ?>
                                            <div class="sesi-item">
                                                <div class="sesi-label">
                                                    <i class='bx bx-message-detail'></i>
                                                    Pokok Pembahasan
                                                </div>
                                                <div class="sesi-box">
                                                    <?php echo nl2br(htmlspecialchars($sesi['POKOK_PEMBAHASAN'])); ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($sesi['CATATAN_SESI']): ?>
                                            <div class="sesi-item">
                                                <div class="sesi-label">
                                                    <i class='bx bx-notepad'></i>
                                                    Catatan Sesi
                                                </div>
                                                <div class="sesi-box">
                                                    <?php echo nl2br(htmlspecialchars($sesi['CATATAN_SESI'])); ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($sesi['TINDAK_LANJUT']): ?>
                                            <div class="sesi-item">
                                                <div class="sesi-label">
                                                    <i class='bx bx-trending-up'></i>
                                                    Tindak Lanjut
                                                </div>
                                                <div class="sesi-box">
                                                    <?php echo nl2br(htmlspecialchars($sesi['TINDAK_LANJUT'])); ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($sesi['REKOMENDASI']): ?>
                                            <div class="sesi-item">
                                                <div class="sesi-label">
                                                    <i class='bx bx-bulb'></i>
                                                    Rekomendasi
                                                </div>
                                                <div class="sesi-box">
                                                    <?php echo nl2br(htmlspecialchars($sesi['REKOMENDASI'])); ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="no-sesi">
                                    <i class='bx bx-time' style="font-size: 48px; margin-bottom: 15px; color: #a0aec0;"></i>
                                    <p style="color: #718096; font-weight: 500;">Belum ada sesi konsultasi yang dicatat oleh guru BK.</p>
                                    <p style="color: #a0aec0; font-size: 14px; margin-top: 5px;">Sesi akan muncul di sini setelah guru BK mencatat sesi konsultasi.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-actions">
                            <a href="struk_konsultasi.php?kode=<?php echo $konsultasi['KODE_KONSULTASI']; ?>" class="btn btn-primary">
                                <i class='bx bx-printer'></i>
                                Lihat Struk
                            </a>
                            <?php if ($konsultasi['STATUS'] == 'Menunggu'): ?>
                            <button class="btn btn-secondary" onclick="refreshPage()">
                                <i class='bx bx-refresh'></i>
                                Refresh Status
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <div class="pagination-info">
                        Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?>
                    </div>
                    
                    <?php if ($page > 1): ?>
                    <a href="?page=1<?php echo buildQueryString(['page']); ?>" class="page-link" title="Halaman Pertama">
                        <i class='bx bx-first-page'></i>
                    </a>
                    <a href="?page=<?php echo $page - 1; ?><?php echo buildQueryString(['page']); ?>" class="page-link" title="Sebelumnya">
                        <i class='bx bx-chevron-left'></i>
                    </a>
                    <?php else: ?>
                    <span class="page-link disabled">
                        <i class='bx bx-first-page'></i>
                    </span>
                    <span class="page-link disabled">
                        <i class='bx bx-chevron-left'></i>
                    </span>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1) {
                        echo '<span class="page-link disabled">...</span>';
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        if ($i == $page) {
                            echo '<span class="page-link active">' . $i . '</span>';
                        } else {
                            echo '<a href="?page=' . $i . buildQueryString(['page']) . '" class="page-link">' . $i . '</a>';
                        }
                    }
                    
                    if ($end_page < $total_pages) {
                        echo '<span class="page-link disabled">...</span>';
                    }
                    ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo buildQueryString(['page']); ?>" class="page-link" title="Berikutnya">
                        <i class='bx bx-chevron-right'></i>
                    </a>
                    <a href="?page=<?php echo $total_pages; ?><?php echo buildQueryString(['page']); ?>" class="page-link" title="Halaman Terakhir">
                        <i class='bx bx-last-page'></i>
                    </a>
                    <?php else: ?>
                    <span class="page-link disabled">
                        <i class='bx bx-chevron-right'></i>
                    </span>
                    <span class="page-link disabled">
                        <i class='bx bx-last-page'></i>
                    </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
            <?php endif; ?>
        </div>
    </div>

    <script>
        function refreshPage() {
            location.reload();
        }
        
        setTimeout(function() {
            const hasPending = document.querySelector('.status-pending');
            if (hasPending) {
                location.reload();
            }
        }, 30000); 
        
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.konsultasi-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
            
            const today = new Date().toISOString().split('T')[0];
            document.querySelector('input[name="date_to"]').max = today;
            document.querySelector('input[name="date_from"]').max = today;
            
            const dateFrom = document.querySelector('input[name="date_from"]');
            const dateTo = document.querySelector('input[name="date_to"]');
            
            dateFrom.addEventListener('change', function() {
                dateTo.min = this.value;
            });
            
            dateTo.addEventListener('change', function() {
                dateFrom.max = this.value;
            });
        });
    </script>
</body>
</html>