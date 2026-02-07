<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Guru_BK') {
    header("Location: ../index.php");
    exit();
}

$koneksi = null;
$koneksi_paths = ['koneksi.php', '../koneksi.php', './koneksi.php'];
foreach ($koneksi_paths as $path) {
    if (file_exists($path)) {
        include $path;
        break;
    }
}

if ($koneksi === null) {
    $koneksi = new mysqli("localhost", "root", "", "db_bk_skaju");
    if ($koneksi->connect_error) {
        die("Koneksi database gagal: " . $koneksi->connect_error);
    }
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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_review'])) {
    $siswa_id = $_POST['siswa_id'];
    $catatan_review = trim($_POST['catatan_review']);
    $rekomendasi = trim($_POST['rekomendasi']);
    
    $sql_insert = "INSERT INTO review_siswa (SISWA_ID, GURU_BK_ID, CATATAN_REVIEW, REKOMENDASI) 
                  VALUES (?, ?, ?, ?) 
                  ON DUPLICATE KEY UPDATE 
                  CATATAN_REVIEW = ?, REKOMENDASI = ?, TANGGAL_REVIEW = NOW()";
    $stmt_insert = $koneksi->prepare($sql_insert);
    $stmt_insert->bind_param("iissss", $siswa_id, $guru_id, $catatan_review, $rekomendasi, $catatan_review, $rekomendasi);
    
    if ($stmt_insert->execute()) {
        $success = "Review berhasil disimpan!";
    } else {
        $error = "Gagal menyimpan review!";
    }
}

$search_keyword = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_completed = isset($_GET['filter_completed']) ? $_GET['filter_completed'] : '';
$filter_reviewed = isset($_GET['filter_reviewed']) ? $_GET['filter_reviewed'] : '';

$sql_siswa = "SELECT s.*, u.NAMA_LENGKAP, u.EMAIL, u.NO_TELEPON,
                     (SELECT COUNT(*) FROM form_kepribadian fk WHERE fk.SISWA_ID = s.ID) as has_kepribadian,
                     (SELECT COUNT(*) FROM form_belajar fb WHERE fb.SISWA_ID = s.ID) as has_belajar,
                     (SELECT COUNT(*) FROM form_karir fkr WHERE fkr.SISWA_ID = s.ID) as has_karir,
                     (SELECT COUNT(*) FROM form_sosial fs WHERE fs.SISWA_ID = s.ID) as has_sosial,
                     rs.CATATAN_REVIEW, rs.REKOMENDASI, rs.TANGGAL_REVIEW
              FROM siswa s 
              JOIN users u ON s.USER_ID = u.ID 
              LEFT JOIN review_siswa rs ON s.ID = rs.SISWA_ID AND rs.GURU_BK_ID = ?
              WHERE s.NIS IS NOT NULL 
              AND s.NISN IS NOT NULL 
              AND s.JENIS_KELAMIN IS NOT NULL";

$search_conditions = [];
$params = [$guru_id];
$types = "i";

if (!empty($search_keyword)) {
    $sql_siswa .= " AND (u.NAMA_LENGKAP LIKE ? OR s.NIS LIKE ? OR s.NISN LIKE ? OR s.KELAS LIKE ? OR s.JURUSAN LIKE ?)";
    $search_term = "%" . $search_keyword . "%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term, $search_term]);
    $types .= "sssss";
}

if ($filter_completed === 'yes') {
    $sql_siswa .= " HAVING (has_kepribadian + has_belajar + has_karir + has_sosial) = 4";
} elseif ($filter_completed === 'no') {
    $sql_siswa .= " HAVING (has_kepribadian + has_belajar + has_karir + has_sosial) < 4";
}

if ($filter_reviewed === 'yes') {
    $sql_siswa .= (strpos($sql_siswa, 'HAVING') !== false ? " AND " : " HAVING ") . "rs.CATATAN_REVIEW IS NOT NULL";
} elseif ($filter_reviewed === 'no') {
    $sql_siswa .= (strpos($sql_siswa, 'HAVING') !== false ? " AND " : " HAVING ") . "rs.CATATAN_REVIEW IS NULL";
}

$sql_siswa .= " ORDER BY u.NAMA_LENGKAP";

$stmt_siswa = $koneksi->prepare($sql_siswa);

if (!empty($search_keyword)) {
    $stmt_siswa->bind_param($types, ...$params);
} else {
    $stmt_siswa->bind_param("i", $guru_id);
}

$stmt_siswa->execute();
$siswa_list = $stmt_siswa->get_result();
$total_siswa = $siswa_list->num_rows;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Form - APK BK</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        
        .search-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            border: 1px solid rgba(49, 130, 206, 0.1);
            box-shadow: 0 10px 30px rgba(49, 130, 206, 0.1);
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
            color: #2d3748;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .total-count {
            background: linear-gradient(135deg, #3182ce, #2b6cb0);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .search-form {
            display: grid;
            grid-template-columns: 1fr auto auto auto;
            gap: 15px;
            align-items: end;
        }
        
        @media (max-width: 992px) {
            .search-form {
                grid-template-columns: 1fr;
            }
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .search-input-group {
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 16px 20px 16px 50px;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(49, 130, 206, 0.2);
            border-radius: 12px;
            color: #2d3748;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #3182ce;
            box-shadow: 0 0 20px rgba(49, 130, 206, 0.2);
            background: rgba(255, 255, 255, 1);
        }
        
        .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #718096;
            font-size: 20px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .filter-label {
            font-size: 13px;
            color: #4a5568;
            font-weight: 600;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .filter-select {
            padding: 14px 20px;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(49, 130, 206, 0.2);
            border-radius: 12px;
            color: #2d3748;
            font-size: 15px;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23718096' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
            padding-right: 45px;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: #3182ce;
            box-shadow: 0 0 15px rgba(49, 130, 206, 0.2);
            background-color: rgba(255, 255, 255, 1);
        }
        
        .search-buttons {
            display: flex;
            gap: 10px;
            align-self: end;
        }
        
        .btn-search {
            background: linear-gradient(135deg, #3182ce, #2b6cb0);
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
            background: linear-gradient(135deg, #2b6cb0, #3182ce);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(49, 130, 206, 0.3);
        }
        
        .btn-reset {
            background: linear-gradient(135deg, #718096, #4a5568);
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
        
        .btn-reset::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-reset:hover::before {
            left: 100%;
        }
        
        .btn-reset:hover {
            background: linear-gradient(135deg, #4a5568, #718096);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(113, 128, 150, 0.3);
        }
        
        .no-results {
            text-align: center;
            padding: 60px 40px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            border: 2px dashed rgba(49, 130, 206, 0.3);
            margin-top: 20px;
            backdrop-filter: blur(15px);
        }
        
        .no-results h3 {
            color: #718096;
            font-size: 24px;
            margin-bottom: 15px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        
        .no-results p {
            color: #a0aec0;
            font-size: 16px;
            font-weight: 500;
        }
        
        .search-summary {
            margin-top: 15px;
            padding: 12px 20px;
            background: rgba(49, 130, 206, 0.05);
            border-radius: 12px;
            font-size: 14px;
            color: #4a5568;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: fadeIn 0.5s ease-out;
        }
        
        .search-summary i {
            color: #3182ce;
            font-size: 18px;
        }
        
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
        
        .siswa-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(420px, 1fr));
            gap: 30px;
            animation: fadeIn 0.8s ease-out;
        }
        
        .siswa-card {
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
        
        .siswa-card:hover {
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
        
        .siswa-name {
            font-size: 22px;
            font-weight: 700;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .siswa-details {
            color: #718096;
            font-size: 15px;
            font-weight: 500;
            margin-top: 6px;
        }
        
        .completion-badge {
            padding: 10px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            border: 2px solid;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .complete {
            background: rgba(72, 187, 120, 0.1);
            color: #38a169;
            border-color: rgba(72, 187, 120, 0.3);
        }
        
        .incomplete {
            background: rgba(255, 152, 0, 0.1);
            color: #FF9800;
            border-color: rgba(255, 152, 0, 0.3);
        }
        
        .form-progress {
            margin-bottom: 25px;
        }
        
        .progress-label {
            font-size: 13px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin-bottom: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .form-items {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        
        .form-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            background: rgba(49, 130, 206, 0.05);
            border-radius: 12px;
            border: 2px solid rgba(49, 130, 206, 0.1);
            transition: all 0.3s ease;
        }
        
        .form-item.completed {
            background: rgba(72, 187, 120, 0.1);
            border-color: rgba(72, 187, 120, 0.2);
            transform: translateY(-2px);
        }
        
        .form-icon {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }
        
        .form-name {
            font-size: 14px;
            color: #4a5568;
            font-weight: 600;
            flex: 1;
        }
        
        .form-status {
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 700;
        }
        
        .status-completed {
            background: rgba(72, 187, 120, 0.2);
            color: #38a169;
        }
        
        .status-pending {
            background: rgba(255, 152, 0, 0.2);
            color: #FF9800;
        }
        
        .review-info {
            background: rgba(49, 130, 206, 0.05);
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #3182ce;
            margin-bottom: 20px;
        }
        
        .review-date {
            font-size: 12px;
            color: #718096;
            margin-top: 10px;
            font-weight: 500;
        }
        
        .action-buttons {
            display: flex;
            gap: 12px;
        }
        
        .btn {
            padding: 14px 20px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.4s ease;
            font-size: 14px;
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
        
        .btn-review {
            background: linear-gradient(135deg, #3182ce, #2b6cb0);
            color: white;
            box-shadow: 0 6px 20px rgba(49, 130, 206, 0.3);
        }
        
        .btn-detail {
            background: linear-gradient(135deg, #9C27B0, #7B1FA2);
            color: white;
            box-shadow: 0 6px 20px rgba(156, 39, 176, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-3px) scale(1.05);
        }
        
        .btn-review:hover {
            box-shadow: 0 10px 25px rgba(49, 130, 206, 0.4);
        }
        
        .btn-detail:hover {
            box-shadow: 0 10px 25px rgba(156, 39, 176, 0.4);
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
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background: rgba(255, 255, 255, 0.95);
            margin: 2% auto;
            padding: 35px;
            border-radius: 20px;
            border: 1px solid rgba(49, 130, 206, 0.1);
            width: 95%;
            max-width: 1200px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(49, 130, 206, 0.3);
            animation: modalSlideIn 0.3s ease-out;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .close {
            color: #718096;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .close:hover {
            color: #3182ce;
            background: rgba(49, 130, 206, 0.1);
        }
        
        .modal-header {
            border-bottom: 2px solid rgba(49, 130, 206, 0.1);
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        
        .modal-header h2 {
            color: #2d3748;
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .modal-header p {
            color: #718096;
            margin-top: 8px;
            font-weight: 500;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #4a5568;
            font-weight: 600;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 16px 20px;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(49, 130, 206, 0.2);
            border-radius: 12px;
            color: #2d3748;
            font-size: 16px;
            resize: vertical;
            min-height: 150px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }
        
        .form-group textarea:focus {
            outline: none;
            border-color: #3182ce;
            box-shadow: 0 0 20px rgba(49, 130, 206, 0.2);
            background: rgba(255, 255, 255, 1);
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #3182ce, #2b6cb0);
            color: white;
            padding: 16px 32px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 700;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(49, 130, 206, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
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
            background: linear-gradient(135deg, #2b6cb0, #3182ce);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 12px 35px rgba(49, 130, 206, 0.4);
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
            
            .siswa-grid {
                grid-template-columns: 1fr;
            }
            
            .form-items {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .modal-content {
                width: 98%;
                margin: 5% auto;
                padding: 25px;
            }
            
            .floating {
                display: none;
            }
            
            .search-buttons {
                flex-direction: column;
            }
            
            .btn-search, .btn-reset {
                width: 100%;
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
            
            .siswa-card {
                padding: 20px;
            }
            
            .modal-content {
                padding: 20px;
            }
            
            .empty-state {
                padding: 60px 20px;
            }
            
            .empty-state h3 {
                font-size: 24px;
            }
            
            .search-container {
                padding: 20px;
            }
            
            .search-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .total-count {
                align-self: flex-start;
            }
        }
    </style>
</head>
<body>
    
    <div class="floating"></div>
    <div class="floating"></div>
    <div class="floating"></div>
    
    <div class="header">
        <h1>👨‍🏫 APK BK - Review Form</h1>
        <div class="user-info">
            <span>Halo, <strong><?php echo htmlspecialchars($nama_lengkap); ?></strong> 👋</span>
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
        <a href="approve_konsultasi.php">
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
        <a href="review_form.php" class="active">
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
            <h2><i class='bx bx-clipboard'></i> Review Form Assesmen Siswa</h2>
            <p>Tinjau dan analisis form assesmen yang telah diisi siswa</p>
        </div>
        
        <div class="search-container">
            <div class="search-header">
                <h3><i class='bx bx-search'></i> Pencarian Siswa</h3>
                <div class="total-count">
                    <i class='bx bx-user'></i>
                    Total: <?php echo $total_siswa; ?> siswa
                </div>
            </div>
            
            <form method="GET" action="" class="search-form">
                <div class="form-group search-input-group">
                    <i class='bx bx-search search-icon'></i>
                    <input type="text" 
                           name="search" 
                           class="search-input" 
                           placeholder="Cari berdasarkan nama, NIS, NISN, kelas, atau jurusan..."
                           value="<?php echo htmlspecialchars($search_keyword); ?>">
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">
                        <i class='bx bx-check-circle'></i>
                        Form Lengkap
                    </label>
                    <select name="filter_completed" class="filter-select">
                        <option value="">Semua</option>
                        <option value="yes" <?php echo $filter_completed === 'yes' ? 'selected' : ''; ?>>Sudah Lengkap</option>
                        <option value="no" <?php echo $filter_completed === 'no' ? 'selected' : ''; ?>>Belum Lengkap</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">
                        <i class='bx bx-edit'></i>
                        Status Review
                    </label>
                    <select name="filter_reviewed" class="filter-select">
                        <option value="">Semua</option>
                        <option value="yes" <?php echo $filter_reviewed === 'yes' ? 'selected' : ''; ?>>Sudah Direview</option>
                        <option value="no" <?php echo $filter_reviewed === 'no' ? 'selected' : ''; ?>>Belum Direview</option>
                    </select>
                </div>
                
                <div class="search-buttons">
                    <button type="submit" class="btn-search">
                        <i class='bx bx-search-alt'></i>
                        Cari
                    </button>
                    <a href="review_form.php" class="btn-reset">
                        <i class='bx bx-reset'></i>
                        Reset
                    </a>
                </div>
            </form>
            
            <?php if (!empty($search_keyword) || !empty($filter_completed) || !empty($filter_reviewed)): ?>
            <div class="search-summary">
                <i class='bx bx-info-circle'></i>
                Hasil pencarian:
                <?php 
                $filters = [];
                if (!empty($search_keyword)) $filters[] = "kata kunci: <strong>" . htmlspecialchars($search_keyword) . "</strong>";
                if ($filter_completed === 'yes') $filters[] = "<strong>form lengkap</strong>";
                if ($filter_completed === 'no') $filters[] = "<strong>form belum lengkap</strong>";
                if ($filter_reviewed === 'yes') $filters[] = "<strong>sudah direview</strong>";
                if ($filter_reviewed === 'no') $filters[] = "<strong>belum direview</strong>";
                
                if (!empty($filters)) {
                    echo "Filter: " . implode(', ', $filters);
                }
                ?>
                | Ditemukan: <strong><?php echo $total_siswa; ?> siswa</strong>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($siswa_list->num_rows > 0): ?>
            <div class="siswa-grid">
                <?php while($siswa = $siswa_list->fetch_assoc()): 
                    $total_forms = 4;
                    $completed_forms = $siswa['has_kepribadian'] + $siswa['has_belajar'] + $siswa['has_karir'] + $siswa['has_sosial'];
                    $completion_percentage = ($completed_forms / $total_forms) * 100;
                    $is_complete = $completed_forms == $total_forms;
                    $has_review = !empty($siswa['CATATAN_REVIEW']);
                ?>
                <div class="siswa-card">
                    <div class="card-header">
                        <div>
                            <div class="siswa-name">
                                <i class='bx bx-user'></i>
                                <?php echo htmlspecialchars($siswa['NAMA_LENGKAP']); ?>
                            </div>
                            <div class="siswa-details">
                                <?php echo htmlspecialchars($siswa['KELAS'] ?? '-'); ?> - <?php echo htmlspecialchars($siswa['JURUSAN'] ?? '-'); ?> | 
                                NIS: <?php echo htmlspecialchars($siswa['NIS']); ?>
                            </div>
                        </div>
                        <div class="completion-badge <?php echo $is_complete ? 'complete' : 'incomplete'; ?>">
                            <i class='bx <?php echo $is_complete ? 'bx-check-circle' : 'bx-time'; ?>'></i>
                            <?php echo $completed_forms; ?>/<?php echo $total_forms; ?>
                            <?php if ($has_review): ?> <i class='bx bx-edit'></i><?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-progress">
                        <div class="progress-label">
                            <i class='bx bx-task'></i>
                            Progress Pengisian Form
                        </div>
                        <div class="form-items">
                            <div class="form-item <?php echo $siswa['has_kepribadian'] ? 'completed' : ''; ?>">
                                <span class="form-icon">👨‍👩‍👧‍👦</span>
                                <span class="form-name">Kepribadian</span>
                                <span class="form-status <?php echo $siswa['has_kepribadian'] ? 'status-completed' : 'status-pending'; ?>">
                                    <?php echo $siswa['has_kepribadian'] ? '✓' : '✗'; ?>
                                </span>
                            </div>
                            <div class="form-item <?php echo $siswa['has_belajar'] ? 'completed' : ''; ?>">
                                <span class="form-icon">📚</span>
                                <span class="form-name">Belajar</span>
                                <span class="form-status <?php echo $siswa['has_belajar'] ? 'status-completed' : 'status-pending'; ?>">
                                    <?php echo $siswa['has_belajar'] ? '✓' : '✗'; ?>
                                </span>
                            </div>
                            <div class="form-item <?php echo $siswa['has_karir'] ? 'completed' : ''; ?>">
                                <span class="form-icon">💼</span>
                                <span class="form-name">Karir</span>
                                <span class="form-status <?php echo $siswa['has_karir'] ? 'status-completed' : 'status-pending'; ?>">
                                    <?php echo $siswa['has_karir'] ? '✓' : '✗'; ?>
                                </span>
                            </div>
                            <div class="form-item <?php echo $siswa['has_sosial'] ? 'completed' : ''; ?>">
                                <span class="form-icon">👥</span>
                                <span class="form-name">Sosial</span>
                                <span class="form-status <?php echo $siswa['has_sosial'] ? 'status-completed' : 'status-pending'; ?>">
                                    <?php echo $siswa['has_sosial'] ? '✓' : '✗'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($has_review): ?>
                    <div class="review-info">
                        <strong><i class='bx bx-note'></i> Review Terakhir:</strong><br>
                        <?php echo nl2br(htmlspecialchars($siswa['CATATAN_REVIEW'])); ?>
                        <div class="review-date">
                            <i class='bx bx-calendar'></i>
                            Tanggal: <?php echo date('d/m/Y H:i', strtotime($siswa['TANGGAL_REVIEW'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="action-buttons">
                        <button class="btn btn-review" onclick="showReviewModal(<?php echo $siswa['ID']; ?>, '<?php echo htmlspecialchars($siswa['NAMA_LENGKAP']); ?>', `<?php echo htmlspecialchars($siswa['CATATAN_REVIEW'] ?? ''); ?>`, `<?php echo htmlspecialchars($siswa['REKOMENDASI'] ?? ''); ?>`)">
                            <i class='bx <?php echo $has_review ? 'bx-edit' : 'bx-clipboard'; ?>'></i>
                            <?php echo $has_review ? 'Edit Review' : 'Buat Review'; ?>
                        </button>
                        <button class="btn btn-detail" onclick="showDetailModal(<?php echo $siswa['ID']; ?>, '<?php echo htmlspecialchars($siswa['NAMA_LENGKAP']); ?>')">
                            <i class='bx bx-show'></i>
                            Lihat Detail
                        </button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <?php if (!empty($search_keyword) || !empty($filter_completed) || !empty($filter_reviewed)): ?>
                <div class="no-results">
                    <h3><i class='bx bx-search-alt'></i> Tidak Ditemukan</h3>
                    <p>Tidak ada siswa yang sesuai dengan kriteria pencarian Anda.</p>
                    <div style="margin-top: 20px;">
                        <a href="review_form.php" class="btn-reset" style="display: inline-flex; text-decoration: none;">
                            <i class='bx bx-reset'></i>
                            Tampilkan Semua Siswa
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3><i class='bx bx-inbox'></i> Belum Ada Data Siswa</h3>
                    <p>Belum ada siswa yang melengkapi data diri</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeReviewModal()">&times;</span>
                <h2><i class='bx bx-clipboard'></i> Review Siswa</h2>
                <p id="modalSiswaName"></p>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="siswa_id" id="modalSiswaId">
                <input type="hidden" name="simpan_review" value="1">
                
                <div class="form-group">
                    <label>
                        <i class='bx bx-edit-alt'></i>
                        Catatan Review:
                    </label>
                    <textarea name="catatan_review" id="modalCatatanReview" placeholder="Tuliskan analisis dan review lengkap untuk siswa ini..." required></textarea>
                </div>
                
                <div class="form-group">
                    <label>
                        <i class='bx bx-bulb'></i>
                        Rekomendasi:
                    </label>
                    <textarea name="rekomendasi" id="modalRekomendasi" placeholder="Berikan rekomendasi untuk pengembangan siswa..." required></textarea>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class='bx bx-save'></i>
                    Simpan Review
                </button>
            </form>
        </div>
    </div>

    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeDetailModal()">&times;</span>
                <h2><i class='bx bx-show'></i> Detail Data Siswa</h2>
                <p id="detailSiswaName"></p>
            </div>
            <div id="detailContent">
            </div>
        </div>
    </div>

    <script>
        function showReviewModal(siswaId, siswaName, catatan, rekomendasi) {
            document.getElementById('modalSiswaId').value = siswaId;
            document.getElementById('modalSiswaName').innerHTML = 'Siswa: <strong>' + siswaName + '</strong>';
            document.getElementById('modalCatatanReview').value = catatan || '';
            document.getElementById('modalRekomendasi').value = rekomendasi || '';
            document.getElementById('reviewModal').style.display = 'block';
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').style.display = 'none';
        }

        function showDetailModal(siswaId, siswaName) {
            document.getElementById('detailSiswaName').innerHTML = 'Siswa: <strong>' + siswaName + '</strong>';
            document.getElementById('detailModal').style.display = 'block';
            
            loadSiswaDetail(siswaId);
        }

        function closeDetailModal() {
            document.getElementById('detailModal').style.display = 'none';
        }

        function loadSiswaDetail(siswaId) {
            const detailContent = document.getElementById('detailContent');
            detailContent.innerHTML = '<div style="text-align: center; padding: 40px; color: #718096;"><i class="bx bx-loader-circle bx-spin" style="font-size: 48px;"></i><br>Memuat data siswa...</div>';
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'get_siswa_detail.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    detailContent.innerHTML = xhr.responseText;
                } else {
                    detailContent.innerHTML = '<div class="no-data" style="text-align: center; padding: 40px; color: #e53e3e;"><i class="bx bx-error-circle" style="font-size: 48px;"></i><br>Gagal memuat data. Silakan coba lagi.</div>';
                }
            };
            
            xhr.onerror = function() {
                detailContent.innerHTML = '<div class="no-data" style="text-align: center; padding: 40px; color: #e53e3e;"><i class="bx bx-wifi-off" style="font-size: 48px;"></i><br>Terjadi kesalahan. Periksa koneksi internet Anda.</div>';
            };
            
            xhr.send('siswa_id=' + siswaId);
        }

        window.onclick = function(event) {
            const reviewModal = document.getElementById('reviewModal');
            const detailModal = document.getElementById('detailModal');
            
            if (event.target == reviewModal) {
                closeReviewModal();
            }
            if (event.target == detailModal) {
                closeDetailModal();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('.btn, .submit-btn, .btn-search, .btn-reset');
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

            const container = document.querySelector('.siswa-grid');
            if (container) {
                const cards = Array.from(container.children);
                cards.sort((a, b) => {
                    const aComplete = a.querySelector('.completion-badge').classList.contains('complete');
                    const bComplete = b.querySelector('.completion-badge').classList.contains('complete');
                    const aHasReview = a.querySelector('.review-info') !== null;
                    const bHasReview = b.querySelector('.review-info') !== null;
                    
                    if (!aComplete && !aHasReview) return -1;
                    if (!bComplete && !bHasReview) return 1;
                    if (aComplete && !aHasReview) return -1;
                    if (bComplete && !bHasReview) return 1;
                    return 0;
                });
                cards.forEach(card => container.appendChild(card));
            }

            const searchInput = document.querySelector('input[name="search"]');
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('search') && searchInput) {
                searchInput.focus();
                searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
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
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>