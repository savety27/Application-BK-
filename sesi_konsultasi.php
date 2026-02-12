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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tambah_sesi'])) {
        $konsultasi_id = $_POST['konsultasi_id'];
        $tanggal_sesi = $_POST['tanggal_sesi'];
        $jam_mulai = $_POST['jam_mulai'];
        $jam_selesai = $_POST['jam_selesai'];
        $pokok_pembahasan = trim($_POST['pokok_pembahasan']);
        $catatan_sesi = trim($_POST['catatan_sesi']);
        $tindak_lanjut = trim($_POST['tindak_lanjut']);
        $rekomendasi = trim($_POST['rekomendasi']);
        $saran_guru = trim($_POST['saran_guru'] ?? '');
        $catatan_konsultasi = trim($_POST['catatan_konsultasi'] ?? '');
        $perlu_tindak_lanjut = $_POST['perlu_tindak_lanjut'] ?? 'Tidak';
       
        $sql_sesi_ke = "SELECT COALESCE(MAX(SESI_KE), 0) + 1 as next_sesi FROM sesi_konsultasi WHERE KONSULTASI_ID = ?";
        $stmt_sesi = $koneksi->prepare($sql_sesi_ke);
        $stmt_sesi->bind_param("i", $konsultasi_id);
        $stmt_sesi->execute();
        $next_sesi = $stmt_sesi->get_result()->fetch_assoc()['next_sesi'];
        
        $sql_insert = "INSERT INTO sesi_konsultasi 
                      (KONSULTASI_ID, TANGGAL_SESI, JAM_MULAI, JAM_SELESAI, POKOK_PEMBAHASAN, 
                       CATATAN_SESI, TINDAK_LANJUT, REKOMENDASI, SESI_KE) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $koneksi->prepare($sql_insert);
        $stmt_insert->bind_param("isssssssi", $konsultasi_id, $tanggal_sesi, $jam_mulai, $jam_selesai, 
                               $pokok_pembahasan, $catatan_sesi, $tindak_lanjut, $rekomendasi, $next_sesi);
        
        if ($stmt_insert->execute()) {
            if (isset($_POST['selesai_konsultasi'])) {
                $sql_update = "UPDATE konsultasi SET 
                              STATUS = 'Selesai',
                              SARAN_GURU = ?,
                              CATATAN_KONSULTASI = ?,
                              PERLU_TINDAK_LANJUT = ?,
                              UPDATED_AT = NOW()
                              WHERE ID = ?";
                $stmt_update = $koneksi->prepare($sql_update);
                $stmt_update->bind_param("sssi", $saran_guru, $catatan_konsultasi, $perlu_tindak_lanjut, $konsultasi_id);
                $stmt_update->execute();
            } else {
                $sql_update = "UPDATE konsultasi SET 
                              SARAN_GURU = ?,
                              CATATAN_KONSULTASI = ?,
                              PERLU_TINDAK_LANJUT = ?,
                              UPDATED_AT = NOW()
                              WHERE ID = ?";
                $stmt_update = $koneksi->prepare($sql_update);
                $stmt_update->bind_param("sssi", $saran_guru, $catatan_konsultasi, $perlu_tindak_lanjut, $konsultasi_id);
                $stmt_update->execute();
            }
            
            $success = "Sesi konsultasi berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan sesi konsultasi!";
        }
    } elseif (isset($_POST['selesaikan_konsultasi'])) {
        $konsultasi_id = $_POST['konsultasi_id'];
        $saran_guru = trim($_POST['saran_guru'] ?? '');
        $catatan_konsultasi = trim($_POST['catatan_konsultasi'] ?? '');
        $perlu_tindak_lanjut = $_POST['perlu_tindak_lanjut'] ?? 'Tidak';
        
        $sql_update = "UPDATE konsultasi SET 
                      STATUS = 'Selesai',
                      SARAN_GURU = ?,
                      CATATAN_KONSULTASI = ?,
                      PERLU_TINDAK_LANJUT = ?,
                      UPDATED_AT = NOW()
                      WHERE ID = ?";
        $stmt_update = $koneksi->prepare($sql_update);
        $stmt_update->bind_param("sssi", $saran_guru, $catatan_konsultasi, $perlu_tindak_lanjut, $konsultasi_id);
        
        if ($stmt_update->execute()) {
            $success = "Konsultasi berhasil diselesaikan!";
        } else {
            $error = "Gagal menyelesaikan konsultasi!";
        }
    }
}

$search_active = isset($_GET['search_active']) ? trim($_GET['search_active']) : '';
$filter_tanggal_active = isset($_GET['filter_tanggal_active']) ? $_GET['filter_tanggal_active'] : '';

$search_history = isset($_GET['search_history']) ? trim($_GET['search_history']) : '';
$filter_tanggal_history = isset($_GET['filter_tanggal_history']) ? $_GET['filter_tanggal_history'] : '';

$sql_konsultasi = "SELECT k.*, u.NAMA_LENGKAP as nama_siswa, s.KELAS, s.JURUSAN,
                          k.SARAN_GURU, k.CATATAN_KONSULTASI, k.PERLU_TINDAK_LANJUT
                   FROM konsultasi k
                   JOIN siswa s ON k.SISWA_ID = s.ID
                   JOIN users u ON s.USER_ID = u.ID
                   WHERE k.GURU_BK_ID = ? AND k.STATUS = 'Disetujui'";

if (!empty($search_active)) {
    $sql_konsultasi .= " AND (u.NAMA_LENGKAP LIKE ? OR s.KELAS LIKE ? OR s.JURUSAN LIKE ? OR k.TOPIK_KONSULTASI LIKE ? OR k.DESKRIPSI_MASALAH LIKE ?)";
}

if (!empty($filter_tanggal_active)) {
    $sql_konsultasi .= " AND DATE(k.TANGGAL_KONSULTASI) = ?";
}

$sql_konsultasi .= " ORDER BY k.TANGGAL_KONSULTASI ASC, k.JAM_KONSULTASI ASC";

$stmt_konsultasi = $koneksi->prepare($sql_konsultasi);

if (!empty($search_active) && !empty($filter_tanggal_active)) {
    $search_term = "%" . $search_active . "%";
    $stmt_konsultasi->bind_param("issssss", $guru_id, $search_term, $search_term, $search_term, $search_term, $search_term, $filter_tanggal_active);
} elseif (!empty($search_active)) {
    $search_term = "%" . $search_active . "%";
    $stmt_konsultasi->bind_param("isssss", $guru_id, $search_term, $search_term, $search_term, $search_term, $search_term);
} elseif (!empty($filter_tanggal_active)) {
    $stmt_konsultasi->bind_param("is", $guru_id, $filter_tanggal_active);
} else {
    $stmt_konsultasi->bind_param("i", $guru_id);
}

$stmt_konsultasi->execute();
$konsultasi_list = $stmt_konsultasi->get_result();
$total_konsultasi = $konsultasi_list->num_rows;

$sql_sesi = "SELECT s.*, k.TOPIK_KONSULTASI, u.NAMA_LENGKAP as nama_siswa,
                    k.SARAN_GURU, k.CATATAN_KONSULTASI, k.PERLU_TINDAK_LANJUT
             FROM sesi_konsultasi s
             JOIN konsultasi k ON s.KONSULTASI_ID = k.ID
             JOIN siswa si ON k.SISWA_ID = si.ID
             JOIN users u ON si.USER_ID = u.ID
             WHERE k.GURU_BK_ID = ?";

if (!empty($search_history)) {
    $sql_sesi .= " AND (u.NAMA_LENGKAP LIKE ? OR si.KELAS LIKE ? OR si.JURUSAN LIKE ? OR k.TOPIK_KONSULTASI LIKE ? OR s.POKOK_PEMBAHASAN LIKE ? OR s.CATATAN_SESI LIKE ?)";
}

if (!empty($filter_tanggal_history)) {
    $sql_sesi .= " AND DATE(s.TANGGAL_SESI) = ?";
}

$sql_sesi .= " ORDER BY s.TANGGAL_SESI DESC, s.JAM_MULAI DESC";

$stmt_sesi = $koneksi->prepare($sql_sesi);

if (!empty($search_history) && !empty($filter_tanggal_history)) {
    $search_term_history = "%" . $search_history . "%";
    $stmt_sesi->bind_param("isssssss", $guru_id, $search_term_history, $search_term_history, $search_term_history, $search_term_history, $search_term_history, $search_term_history, $filter_tanggal_history);
} elseif (!empty($search_history)) {
    $search_term_history = "%" . $search_history . "%";
    $stmt_sesi->bind_param("issssss", $guru_id, $search_term_history, $search_term_history, $search_term_history, $search_term_history, $search_term_history, $search_term_history);
} elseif (!empty($filter_tanggal_history)) {
    $stmt_sesi->bind_param("is", $guru_id, $filter_tanggal_history);
} else {
    $stmt_sesi->bind_param("i", $guru_id);
}

$stmt_sesi->execute();
$sesi_list = $stmt_sesi->get_result();
$total_sesi = $sesi_list->num_rows;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sesi Konsultasi - APK BK</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            
            .print-container, .print-container * {
                visibility: visible;
            }
            
            .print-container {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                background: white;
                color: black;
                padding: 20px;
                font-family: 'Arial', sans-serif;
            }
            
            .no-print {
                display: none !important;
            }
        }
        
        .btn-cetak-laporan {
            background: linear-gradient(135deg, #805AD5, #6B46C1);
            color: white;
            padding: 14px 24px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(128, 90, 213, 0.3);
            margin-left: auto;
        }

        .page-header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .btn-cetak-laporan::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-cetak-laporan:hover::before {
            left: 100%;
        }
        
        .btn-cetak-laporan:hover {
            background: linear-gradient(135deg, #6B46C1, #805AD5);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 12px 35px rgba(128, 90, 213, 0.4);
        }
        
        .modal-cetak-content {
            background: rgba(255, 255, 255, 0.95);
            margin: 2% auto;
            padding: 35px;
            border-radius: 20px;
            border: 1px solid rgba(49, 130, 206, 0.1);
            width: 95%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(49, 130, 206, 0.3);
            animation: modalSlideIn 0.3s ease-out;
        }
        
        .modal-cetak-content .modal-header {
            border-bottom: 2px solid rgba(49, 130, 206, 0.1);
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        
        .modal-cetak-content .modal-header h2 {
            color: #2d3748;
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .checkbox-group-cetak {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: rgba(49, 130, 206, 0.05);
            border-radius: 12px;
            border: 2px solid rgba(49, 130, 206, 0.1);
            margin-bottom: 20px;
        }
        
        .sesi-checkbox-container {
            max-height: 200px;
            overflow-y: auto;
            padding: 15px;
            border: 1px solid rgba(49, 130, 206, 0.2);
            border-radius: 12px;
            margin-top: 10px;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .sesi-checkbox-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border-bottom: 1px solid rgba(49, 130, 206, 0.1);
            transition: all 0.3s ease;
        }
        
        .sesi-checkbox-item:hover {
            background: rgba(49, 130, 206, 0.05);
        }
        
        .sesi-checkbox-item:last-child {
            border-bottom: none;
        }
        
        .sesi-checkbox-info {
            flex: 1;
        }
        
        .sesi-checkbox-info .siswa-nama {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 3px;
        }
        
        .sesi-checkbox-info .sesi-detail {
            font-size: 13px;
            color: #718096;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin: 15px 0;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 10px 15px;
            border: 2px solid rgba(49, 130, 206, 0.2);
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .radio-option:hover {
            border-color: #3182ce;
        }
        
        .radio-option input[type="radio"] {
            width: auto;
        }
        
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
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .search-input-group {
            flex: 2;
            min-width: 300px;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .search-buttons {
            flex: 0 0 auto;
            display: flex;
            gap: 10px;
            align-self: end;
        }
        
        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-input-group,
            .filter-group,
            .search-buttons {
                min-width: 100%;
                width: 100%;
            }

            .page-header-top {
                flex-direction: column;
                align-items: flex-start;
            }

            .btn-cetak-laporan {
                margin-left: 0;
                width: 100%;
                justify-content: center;
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
        
        .tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 15px;
            border: 1px solid rgba(49, 130, 206, 0.1);
            box-shadow: 0 8px 25px rgba(49, 130, 206, 0.1);
            backdrop-filter: blur(15px);
        }
        
        .tab {
            padding: 16px 28px;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(49, 130, 206, 0.2);
            border-radius: 12px;
            color: #718096;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }
        
        .tab::before {
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
        
        .tab.active {
            background: linear-gradient(135deg, #3182ce, #2b6cb0);
            color: white;
            border-color: #3182ce;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(49, 130, 206, 0.3);
        }
        
        .tab:hover:not(.active) {
            color: #3182ce;
            border-color: #3182ce;
            transform: translateY(-2px);
        }
        
        .tab:hover::before {
            transform: scaleX(1);
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.5s ease-out;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .konsultasi-grid, .sesi-grid {
            display: grid;
            gap: 30px;
        }
        
        .konsultasi-card, .sesi-card {
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
        
        .konsultasi-card:hover, .sesi-card:hover {
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
        
        .jadwal-info {
            background: rgba(49, 130, 206, 0.05);
            padding: 15px 20px;
            border-radius: 12px;
            border-left: 4px solid #3182ce;
            margin-top: 10px;
        }
        
        .btn {
            padding: 14px 24px;
            background: linear-gradient(135deg, #3182ce, #2b6cb0);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(49, 130, 206, 0.3);
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
            background: linear-gradient(135deg, #2b6cb0, #3182ce);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 12px 35px rgba(49, 130, 206, 0.4);
        }
        
        .btn-selesai {
            background: linear-gradient(135deg, #48bb78, #38a169);
            box-shadow: 0 8px 25px rgba(72, 187, 120, 0.3);
        }
        
        .btn-selesai:hover {
            background: linear-gradient(135deg, #38a169, #48bb78);
            box-shadow: 0 12px 35px rgba(72, 187, 120, 0.4);
        }
        
        .btn-catat {
            background: linear-gradient(135deg, #9C27B0, #7B1FA2);
            box-shadow: 0 8px 25px rgba(156, 39, 176, 0.3);
        }
        
        .btn-catat:hover {
            background: linear-gradient(135deg, #7B1FA2, #9C27B0);
            box-shadow: 0 12px 35px rgba(156, 39, 176, 0.4);
        }
        
        .form-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 35px;
            border-radius: 20px;
            border: 1px solid rgba(49, 130, 206, 0.1);
            box-shadow: 0 15px 35px rgba(49, 130, 206, 0.1);
            backdrop-filter: blur(15px);
            margin-top: 20px;
            animation: fadeIn 0.8s ease-out;
        }
        
        .form-container h3 {
            font-size: 24px;
            margin-bottom: 25px;
            color: #2d3748;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
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
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 16px 20px;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(49, 130, 206, 0.2);
            border-radius: 12px;
            color: #2d3748;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #3182ce;
            box-shadow: 0 0 20px rgba(49, 130, 206, 0.2);
            background: rgba(255, 255, 255, 1);
        }
        
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #3182ce, #2b6cb0);
            color: white;
            padding: 18px 32px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 700;
            width: 100%;
            margin-top: 15px;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(49, 130, 206, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
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
        
        .sesi-info {
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
        
        .content-box {
            background: rgba(49, 130, 206, 0.05);
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #3182ce;
            margin-top: 12px;
            color: #4a5568;
            line-height: 1.6;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: rgba(49, 130, 206, 0.05);
            border-radius: 12px;
            border: 2px solid rgba(49, 130, 206, 0.1);
            margin-bottom: 20px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            transform: scale(1.2);
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
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
            max-width: 800px;
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
            
            .tabs {
                flex-direction: column;
                padding: 15px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .card-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .action-buttons {
                flex-direction: column;
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
            
            .konsultasi-card, .sesi-card {
                padding: 20px;
            }
            
            .form-container {
                padding: 25px;
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

        /* Layout Theme: match approve_konsultasi.php */
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

        .nav-badge {
            position: relative;
        }

        .container {
            margin-left: 270px;
            max-width: none;
            transition: margin-left 0.3s ease;
        }

        .page-header,
        .tabs,
        .search-container,
        .konsultasi-card,
        .sesi-card,
        .form-container,
        .empty-state,
        .no-results,
        .alert,
        .modal-content,
        .modal-cetak-content {
            background: var(--surface-card);
            border-color: var(--border-soft);
        }

        .page-header h2,
        .form-container h3,
        .search-header h3,
        .student-info h3,
        .no-results h3,
        label {
            color: var(--text-main);
        }

        .page-header p,
        .student-details,
        .info-label,
        .search-summary,
        .filter-label,
        .no-results p {
            color: var(--text-muted);
        }

        input,
        select,
        textarea,
        .search-input,
        .filter-select {
            background: rgba(255, 253, 245, 0.9);
            border-color: rgba(102, 126, 234, 0.2);
            color: #2d3748;
        }

        input:focus,
        select:focus,
        textarea:focus,
        .search-input:focus,
        .filter-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.2);
            background: rgba(255, 253, 245, 0.95);
        }

        body.dark-mode .page-header,
        body.dark-mode .tabs,
        body.dark-mode .search-container,
        body.dark-mode .konsultasi-card,
        body.dark-mode .sesi-card,
        body.dark-mode .form-container,
        body.dark-mode .empty-state,
        body.dark-mode .no-results,
        body.dark-mode .alert,
        body.dark-mode .modal-content,
        body.dark-mode .modal-cetak-content,
        body.dark-mode .content-box,
        body.dark-mode .jadwal-info,
        body.dark-mode .total-count,
        body.dark-mode .search-summary {
            background: rgba(17, 24, 39, 0.9);
        }

        body.dark-mode input,
        body.dark-mode select,
        body.dark-mode textarea,
        body.dark-mode .search-input,
        body.dark-mode .filter-select {
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
                <h1><i class='bx bx-conversation'></i> Sesi Konsultasi</h1>
            </a>
        </div>
        <h1>👨‍🏫 APK BK - Sesi Konsultasi</h1>
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
        <a href="approve_konsultasi.php" class="nav-badge">
            <i class='bx bx-check-shield'></i>
            Approve Konsultasi
        <?php if ($jumlah_notif_konsultasi > 0): ?>
            <span class="badge"><?php echo $jumlah_notif_konsultasi; ?></span>
        <?php endif; ?>
    </a>
        <a href="sesi_konsultasi.php" class="active">
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
            <div class="page-header-top">
                <div>
                    <h2><i class='bx bx-conversation'></i> Kelola Sesi Konsultasi</h2>
                    <p>Catat dan kelola sesi konsultasi dengan siswa</p>
                </div>
                <button onclick="showCetakModal()" class="btn-cetak-laporan no-print">
                    <i class='bx bx-printer'></i>
                    Cetak Laporan
                </button>
            </div>
        </div>
        
        <div class="tabs">
            <div class="tab active" onclick="showTab('active')">
                <i class='bx bx-calendar'></i>
                Konsultasi Aktif
            </div>
            <div class="tab" onclick="showTab('history')">
                <i class='bx bx-history'></i>
                Riwayat Sesi
            </div>
            <div class="tab" onclick="showTab('add')">
                <i class='bx bx-plus'></i>
                Catatan Sesi
            </div>
        </div>
        
        <div id="active-search" class="search-container" style="display: block;">
            <div class="search-header">
                <h3><i class='bx bx-search'></i> Pencarian Konsultasi Aktif</h3>
                <div class="total-count">
                    <i class='bx bx-conversation'></i>
                    Total: <?php echo $total_konsultasi; ?> konsultasi
                </div>
            </div>
            
            <form method="GET" action="" class="search-form">
                <input type="hidden" name="tab" value="active">
                <div class="form-group search-input-group">
                    <i class='bx bx-search search-icon'></i>
                    <input type="text" 
                           name="search_active" 
                           class="search-input" 
                           placeholder="Cari berdasarkan nama siswa, kelas, jurusan, topik, atau deskripsi..."
                           value="<?php echo htmlspecialchars($search_active); ?>">
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">
                        <i class='bx bx-calendar'></i>
                        Tanggal Konsultasi
                    </label>
                    <input type="date" 
                           name="filter_tanggal_active" 
                           class="filter-select" 
                           style="padding: 14px 20px;"
                           value="<?php echo htmlspecialchars($filter_tanggal_active); ?>">
                </div>
                
                <div class="search-buttons">
                    <button type="submit" class="btn-search">
                        <i class='bx bx-search-alt'></i>
                        Cari
                    </button>
                    <a href="sesi_konsultasi.php?tab=active" class="btn-reset">
                        <i class='bx bx-reset'></i>
                        Reset
                    </a>
                </div>
            </form>
            
            <?php if (!empty($search_active) || !empty($filter_tanggal_active)): ?>
            <div class="search-summary">
                <i class='bx bx-info-circle'></i>
                Filter aktif:
                <?php 
                $filters = [];
                if (!empty($search_active)) $filters[] = "kata kunci: <strong>" . htmlspecialchars($search_active) . "</strong>";
                if (!empty($filter_tanggal_active)) $filters[] = "tanggal: <strong>" . htmlspecialchars($filter_tanggal_active) . "</strong>";
                
                if (!empty($filters)) {
                    echo implode(', ', $filters);
                }
                ?>
                | Ditemukan: <strong><?php echo $total_konsultasi; ?> konsultasi</strong>
            </div>
            <?php endif; ?>
        </div>
        
        <div id="active-tab" class="tab-content active">
            <?php if ($konsultasi_list->num_rows > 0): ?>
                <div class="konsultasi-grid">
                    <?php while($konsul = $konsultasi_list->fetch_assoc()): ?>
                    <div class="konsultasi-card">
                        <div class="card-header">
                            <div class="student-info">
                                <h3><?php echo htmlspecialchars($konsul['nama_siswa']); ?></h3>
                                <div class="student-details">
                                    <?php echo htmlspecialchars($konsul['KELAS']); ?> - <?php echo htmlspecialchars($konsul['JURUSAN']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="sesi-info">
                            <div class="info-label">
                                <i class='bx bx-target-lock'></i>
                                Topik
                            </div>
                            <div class="info-value"><?php echo htmlspecialchars($konsul['TOPIK_KONSULTASI']); ?></div>
                        </div>
                        
                        <?php 
                        $mode_konsultasi = $konsul['MODE_KONSULTASI'] ?? 'Offline';
                        $meeting_link = $konsul['MEETING_LINK'] ?? '';
                        ?>
                        <div class="sesi-info">
                            <div class="info-label">
                                <i class='bx bx-wifi'></i>
                                Mode
                            </div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($mode_konsultasi); ?>
                                <?php if ($mode_konsultasi === 'Online'): ?>
                                    <?php if (!empty($meeting_link)): ?>
                                        <div style="margin-top: 8px;">
                                            <a class="btn btn-catat" href="<?php echo htmlspecialchars($meeting_link); ?>" target="_blank" rel="noopener">
                                                <i class='bx bx-link'></i>
                                                Join Meeting
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div style="margin-top: 6px; color: #e53e3e; font-weight: 600;">
                                            Link meeting belum tersedia
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($konsul['TANGGAL_KONSULTASI']): ?>
                        <div class="sesi-info">
                            <div class="info-label">
                                <i class='bx bx-time'></i>
                                Jadwal
                            </div>
                            <div class="jadwal-info">
                                <strong>
                                    <?php echo date('d/m/Y', strtotime($konsul['TANGGAL_KONSULTASI'])); ?> 
                                    pukul <?php echo date('H:i', strtotime($konsul['JAM_KONSULTASI'])); ?>
                                </strong>
                                <?php if ($konsul['TEMPAT_KONSULTASI']): ?>
                                <br><i class='bx bx-map'></i> <?php echo htmlspecialchars($konsul['TEMPAT_KONSULTASI']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="sesi-info">
                            <div class="info-label">
                                <i class='bx bx-edit-alt'></i>
                                Deskripsi
                            </div>
                            <div class="content-box">
                                <?php echo nl2br(htmlspecialchars($konsul['DESKRIPSI_MASALAH'])); ?>
                            </div>
                        </div>

                        <?php if ($konsul['SARAN_GURU'] || $konsul['CATATAN_KONSULTASI']): ?>
                        <div class="sesi-info">
                            <div class="info-label">
                                <i class='bx bx-note'></i>
                                Catatan Sebelumnya
                            </div>
                            <?php if ($konsul['SARAN_GURU']): ?>
                            <div class="content-box" style="margin-bottom: 10px;">
                                <strong>Saran:</strong><br>
                                <?php echo nl2br(htmlspecialchars($konsul['SARAN_GURU'])); ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($konsul['CATATAN_KONSULTASI']): ?>
                            <div class="content-box">
                                <strong>Catatan:</strong><br>
                                <?php echo nl2br(htmlspecialchars($konsul['CATATAN_KONSULTASI'])); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="action-buttons">
                            <button class="btn btn-catat" onclick="showAddSesiForm(<?php echo $konsul['ID']; ?>)">
                                <i class='bx bx-plus'></i>
                                Catat Sesi
                            </button>
                            
                            <button class="btn btn-selesai" onclick="showSelesaikanModal(<?php echo $konsul['ID']; ?>, '<?php echo htmlspecialchars($konsul['nama_siswa']); ?>')">
                                <i class='bx bx-check'></i>
                                Selesaikan
                            </button>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <?php if (!empty($search_active) || !empty($filter_tanggal_active)): ?>
                    <div class="no-results">
                        <h3><i class='bx bx-search-alt'></i> Tidak Ditemukan</h3>
                        <p>Tidak ada konsultasi aktif yang sesuai dengan kriteria pencarian Anda.</p>
                        <div style="margin-top: 20px;">
                            <a href="sesi_konsultasi.php?tab=active" class="btn-reset" style="display: inline-flex; text-decoration: none;">
                                <i class='bx bx-reset'></i>
                                Tampilkan Semua Konsultasi
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <h3><i class='bx bx-inbox'></i> Tidak Ada Konsultasi Aktif</h3>
                        <p>Belum ada konsultasi yang disetujui untuk Anda</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div id="history-search" class="search-container" style="display: none;">
            <div class="search-header">
                <h3><i class='bx bx-search'></i> Pencarian Riwayat Sesi</h3>
                <div class="total-count">
                    <i class='bx bx-history'></i>
                    Total: <?php echo $total_sesi; ?> sesi
                </div>
            </div>
            
            <form method="GET" action="" class="search-form">
                <input type="hidden" name="tab" value="history">
                <div class="form-group search-input-group">
                    <i class='bx bx-search search-icon'></i>
                    <input type="text" 
                           name="search_history" 
                           class="search-input" 
                           placeholder="Cari berdasarkan nama siswa, kelas, jurusan, topik, atau catatan..."
                           value="<?php echo htmlspecialchars($search_history); ?>">
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">
                        <i class='bx bx-calendar'></i>
                        Tanggal Sesi
                    </label>
                    <input type="date" 
                           name="filter_tanggal_history" 
                           class="filter-select" 
                           style="padding: 14px 20px;"
                           value="<?php echo htmlspecialchars($filter_tanggal_history); ?>">
                </div>
                
                <div class="search-buttons">
                    <button type="submit" class="btn-search">
                        <i class='bx bx-search-alt'></i>
                        Cari
                    </button>
                    <a href="sesi_konsultasi.php?tab=history" class="btn-reset">
                        <i class='bx bx-reset'></i>
                        Reset
                    </a>
                </div>
            </form>
            
            <?php if (!empty($search_history) || !empty($filter_tanggal_history)): ?>
            <div class="search-summary">
                <i class='bx bx-info-circle'></i>
                Filter aktif:
                <?php 
                $filters = [];
                if (!empty($search_history)) $filters[] = "kata kunci: <strong>" . htmlspecialchars($search_history) . "</strong>";
                if (!empty($filter_tanggal_history)) $filters[] = "tanggal: <strong>" . htmlspecialchars($filter_tanggal_history) . "</strong>";
                
                if (!empty($filters)) {
                    echo implode(', ', $filters);
                }
                ?>
                | Ditemukan: <strong><?php echo $total_sesi; ?> sesi</strong>
            </div>
            <?php endif; ?>
        </div>
        
        <div id="history-tab" class="tab-content">
            <?php if ($sesi_list->num_rows > 0): ?>
                <div class="sesi-grid">
                    <?php while($sesi = $sesi_list->fetch_assoc()): ?>
                    <div class="sesi-card">
                        <div class="card-header">
                            <div>
                                <h3>Sesi #<?php echo $sesi['SESI_KE']; ?> - <?php echo htmlspecialchars($sesi['nama_siswa']); ?></h3>
                                <div class="student-details">
                                    <?php echo date('d/m/Y', strtotime($sesi['TANGGAL_SESI'])); ?> | 
                                    <?php echo date('H:i', strtotime($sesi['JAM_MULAI'])); ?> - <?php echo date('H:i', strtotime($sesi['JAM_SELESAI'])); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="sesi-info">
                            <div class="info-label">
                                <i class='bx bx-target-lock'></i>
                                Topik Konsultasi
                            </div>
                            <div class="info-value"><?php echo htmlspecialchars($sesi['TOPIK_KONSULTASI']); ?></div>
                        </div>
                        
                        <?php if ($sesi['POKOK_PEMBAHASAN']): ?>
                        <div class="sesi-info">
                            <div class="info-label">
                                <i class='bx bx-message-detail'></i>
                                Pokok Pembahasan
                            </div>
                            <div class="content-box"><?php echo nl2br(htmlspecialchars($sesi['POKOK_PEMBAHASAN'])); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($sesi['CATATAN_SESI']): ?>
                        <div class="sesi-info">
                            <div class="info-label">
                                <i class='bx bx-notepad'></i>
                                Catatan Sesi
                            </div>
                            <div class="content-box"><?php echo nl2br(htmlspecialchars($sesi['CATATAN_SESI'])); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($sesi['TINDAK_LANJUT']): ?>
                        <div class="sesi-info">
                            <div class="info-label">
                                <i class='bx bx-trending-up'></i>
                                Tindak Lanjut
                            </div>
                            <div class="content-box"><?php echo nl2br(htmlspecialchars($sesi['TINDAK_LANJUT'])); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($sesi['REKOMENDASI']): ?>
                        <div class="sesi-info">
                            <div class="info-label">
                                <i class='bx bx-bulb'></i>
                                Rekomendasi
                            </div>
                            <div class="content-box"><?php echo nl2br(htmlspecialchars($sesi['REKOMENDASI'])); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if ($sesi['SARAN_GURU'] || $sesi['CATATAN_KONSULTASI']): ?>
                        <div class="sesi-info">
                            <div class="info-label">
                                <i class='bx bx-note'></i>
                                Ringkasan Konsultasi
                            </div>
                            <?php if ($sesi['SARAN_GURU']): ?>
                            <div class="content-box" style="margin-bottom: 10px;">
                                <strong>Saran Guru:</strong><br>
                                <?php echo nl2br(htmlspecialchars($sesi['SARAN_GURU'])); ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($sesi['CATATAN_KONSULTASI']): ?>
                            <div class="content-box">
                                <strong>Catatan Konsultasi:</strong><br>
                                <?php echo nl2br(htmlspecialchars($sesi['CATATAN_KONSULTASI'])); ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($sesi['PERLU_TINDAK_LANJUT']): ?>
                            <div style="margin-top: 10px;">
                                <span class="info-label">
                                    <i class='bx bx-trending-up'></i>
                                    Tindak Lanjut: 
                                </span>
                                <span style="color: <?php echo $sesi['PERLU_TINDAK_LANJUT'] == 'Ya' ? '#e53e3e' : '#38a169'; ?>; font-weight: 600;">
                                    <?php echo $sesi['PERLU_TINDAK_LANJUT']; ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <?php if (!empty($search_history) || !empty($filter_tanggal_history)): ?>
                    <div class="no-results">
                        <h3><i class='bx bx-search-alt'></i> Tidak Ditemukan</h3>
                        <p>Tidak ada riwayat sesi yang sesuai dengan kriteria pencarian Anda.</p>
                        <div style="margin-top: 20px;">
                            <a href="sesi_konsultasi.php?tab=history" class="btn-reset" style="display: inline-flex; text-decoration: none;">
                                <i class='bx bx-reset'></i>
                                Tampilkan Semua Riwayat
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <h3><i class='bx bx-history'></i> Belum Ada Riwayat Sesi</h3>
                        <p>Belum ada sesi konsultasi yang dicatat</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div id="add-tab" class="tab-content">
            <div class="form-container">
                <h3><i class='bx bx-plus'></i> Catatan Sesi Konsultasi</h3>
                <form method="POST" action="">
                    <input type="hidden" name="tambah_sesi" value="1">
                    
                    <div class="form-group">
                        <label>
                            <i class='bx bx-list-check'></i>
                            Pilih Konsultasi *
                        </label>
                        <select name="konsultasi_id" required>
                            <option value="">Pilih Konsultasi</option>
                            <?php 
                            $konsultasi_list->data_seek(0); 
                            while($konsul = $konsultasi_list->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $konsul['ID']; ?>">
                                    <?php echo htmlspecialchars($konsul['nama_siswa']); ?> - <?php echo htmlspecialchars($konsul['TOPIK_KONSULTASI']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>
                                <i class='bx bx-calendar'></i>
                                Tanggal Sesi *
                            </label>
                            <input type="date" name="tanggal_sesi" required>
                        </div>
                        <div class="form-group">
                            <label>
                                <i class='bx bx-time'></i>
                                Jam Mulai *
                            </label>
                            <input type="time" name="jam_mulai" required>
                        </div>
                        <div class="form-group">
                            <label>
                                <i class='bx bx-time-five'></i>
                                Jam Selesai *
                            </label>
                            <input type="time" name="jam_selesai" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <i class='bx bx-message-detail'></i>
                            Pokok Pembahasan
                        </label>
                        <textarea name="pokok_pembahasan" placeholder="Pokok-pokok yang dibahas dalam sesi..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <i class='bx bx-notepad'></i>
                            Catatan Sesi
                        </label>
                        <textarea name="catatan_sesi" placeholder="Catatan detail dari sesi konsultasi..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <i class='bx bx-trending-up'></i>
                            Tindak Lanjut
                        </label>
                        <textarea name="tindak_lanjut" placeholder="Tindak lanjut yang diperlukan..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <i class='bx bx-bulb'></i>
                            Rekomendasi
                        </label>
                        <textarea name="rekomendasi" placeholder="Rekomendasi untuk siswa..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class='bx bx-message-detail'></i>
                            Saran dan Masukan
                        </label>
                        <textarea name="saran_guru" placeholder="Berikan saran dan masukan untuk siswa..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class='bx bx-notepad'></i>
                            Catatan Konsultasi
                        </label>
                        <textarea name="catatan_konsultasi" placeholder="Catatan lengkap dari konsultasi..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class='bx bx-trending-up'></i>
                            Tindak Lanjut
                        </label>
                        <select name="perlu_tindak_lanjut" required>
                            <option value="Tidak">Tidak Perlu Tindak Lanjut</option>
                            <option value="Ya">Perlu Tindak Lanjut</option>
                        </select>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" name="selesai_konsultasi" value="1" id="selesai_konsultasi">
                        <label for="selesai_konsultasi" style="margin: 0; font-weight: 600; color: #2d3748;">
                            Tandai konsultasi sebagai selesai
                        </label>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class='bx bx-save'></i>
                        Simpan Sesi
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div id="selesaikanModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeSelesaikanModal()">&times;</span>
                <h2><i class='bx bx-check'></i> Selesaikan Konsultasi</h2>
                <p id="modalSiswaName"></p>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="selesaikan_konsultasi" value="1">
                <input type="hidden" name="konsultasi_id" id="modalKonsultasiId">
                
                <div class="form-group">
                    <label>
                        <i class='bx bx-message-detail'></i>
                        Saran dan Masukan *
                    </label>
                    <textarea name="saran_guru" placeholder="Berikan saran dan masukan untuk siswa..." required></textarea>
                </div>

                <div class="form-group">
                    <label>
                        <i class='bx bx-notepad'></i>
                        Catatan Konsultasi *
                    </label>
                    <textarea name="catatan_konsultasi" placeholder="Catatan lengkap dari konsultasi..." required></textarea>
                </div>

                <div class="form-group">
                    <label>
                        <i class='bx bx-trending-up'></i>
                        Tindak Lanjut *
                    </label>
                    <select name="perlu_tindak_lanjut" required>
                        <option value="Tidak">Tidak Perlu Tindak Lanjut</option>
                        <option value="Ya">Perlu Tindak Lanjut</option>
                    </select>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class='bx bx-check'></i>
                    Selesaikan Konsultasi
                </button>
            </form>
        </div>
    </div>

    <div id="cetakModal" class="modal">
        <div class="modal-content modal-cetak-content">
            <div class="modal-header">
                <span class="close" onclick="closeCetakModal()">&times;</span>
                <h2><i class='bx bx-printer'></i> Cetak Laporan Konsultasi</h2>
                <p>Pilih filter untuk laporan yang ingin dicetak</p>
            </div>
            
            <form id="formCetak" method="GET" action="cetak_laporan_konsultasi.php" target="_blank">
                <input type="hidden" name="tab" value="history">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>
                            <i class='bx bx-user'></i>
                            Nama Siswa (Opsional)
                        </label>
                        <input type="text" name="siswa_nama" id="modalSiswaNama" 
                               placeholder="Masukkan nama siswa...">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <i class='bx bx-calendar'></i>
                            Tanggal Mulai (Opsional)
                        </label>
                        <input type="date" name="tanggal_mulai" id="modalTanggalMulai">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <i class='bx bx-calendar'></i>
                            Tanggal Selesai (Opsional)
                        </label>
                        <input type="date" name="tanggal_selesai" id="modalTanggalSelesai">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <i class='bx bx-list-check'></i>
                        Pilih Sesi Tertentu (Opsional)
                    </label>
                    <div id="sesiListContainer" style="display: none;">
                        <div class="sesi-checkbox-container" id="sesiCheckboxes">
                        </div>
                        <div style="margin-top: 10px; display: flex; gap: 10px;">
                            <button type="button" class="btn" style="padding: 8px 16px; font-size: 13px;" onclick="selectAllSesi()">
                                <i class='bx bx-check-square'></i> Pilih Semua
                            </button>
                            <button type="button" class="btn-reset" style="padding: 8px 16px; font-size: 13px;" onclick="unselectAllSesi()">
                                <i class='bx bx-square'></i> Batal Semua
                            </button>
                        </div>
                    </div>
                    <div id="noSesiMessage" style="display: none; padding: 15px; background: #f8f9fa; border-radius: 8px; text-align: center; color: #718096;">
                        Tidak ada sesi yang ditemukan dengan filter yang dipilih
                    </div>
                </div>
                
                <div class="checkbox-group-cetak">
                    <input type="checkbox" name="auto_print" value="1" id="autoPrint">
                    <label for="autoPrint" style="margin: 0; font-weight: 600; color: #2d3748;">
                        <i class='bx bx-printer'></i>
                        Auto print setelah generate
                    </label>
                </div>
                
                <div class="action-buttons" style="justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn btn-reset" onclick="closeCetakModal()">
                        <i class='bx bx-x'></i>
                        Batal
                    </button>
                    <button type="submit" class="btn btn-cetak-laporan">
                        <i class='bx bx-printer'></i>
                        Generate & Cetak
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.querySelectorAll('.search-container').forEach(search => {
                search.style.display = 'none';
            });
            
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.getElementById(tabName + '-tab').classList.add('active');
            
            if (tabName === 'active') {
                document.getElementById('active-search').style.display = 'block';
            } else if (tabName === 'history') {
                document.getElementById('history-search').style.display = 'block';
            }
            
            event.currentTarget.classList.add('active');
            
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('tab', tabName);
            
            if (tabName === 'active') {
                urlParams.delete('search_history');
                urlParams.delete('filter_tanggal_history');
            } else if (tabName === 'history') {
                urlParams.delete('search_active');
                urlParams.delete('filter_tanggal_active');
            } else {
                urlParams.delete('search_active');
                urlParams.delete('filter_tanggal_active');
                urlParams.delete('search_history');
                urlParams.delete('filter_tanggal_history');
            }
            
            const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
            window.history.pushState({ path: newUrl }, '', newUrl);
        }
        
        function showAddSesiForm(konsultasiId) {
            showTab('add');
            document.querySelector('select[name="konsultasi_id"]').value = konsultasiId;
        }

        function showSelesaikanModal(konsultasiId, siswaName) {
            document.getElementById('modalKonsultasiId').value = konsultasiId;
            document.getElementById('modalSiswaName').innerHTML = 'Siswa: <strong>' + siswaName + '</strong>';
            document.getElementById('selesaikanModal').style.display = 'block';
        }

        function closeSelesaikanModal() {
            document.getElementById('selesaikanModal').style.display = 'none';
        }

function showCetakModal() {
    document.getElementById('cetakModal').style.display = 'block';
    document.getElementById('modalTanggalMulai').value = '';
    document.getElementById('modalTanggalSelesai').value = '';
    
    loadSesiData();
}
        function closeCetakModal() {
            document.getElementById('cetakModal').style.display = 'none';
        }

        function loadSesiData() {
            const siswaNama = document.getElementById('modalSiswaNama').value;
            const tglMulai = document.getElementById('modalTanggalMulai').value;
            const tglSelesai = document.getElementById('modalTanggalSelesai').value;
            
            const formData = new FormData();
            formData.append('guru_id', '<?php echo $guru_id; ?>');
            if (siswaNama) formData.append('siswa_nama', siswaNama);
            if (tglMulai) formData.append('tanggal_mulai', tglMulai);
            if (tglSelesai) formData.append('tanggal_selesai', tglSelesai);
            
            fetch('get_sesi_data.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('sesiCheckboxes');
                container.innerHTML = '';
                
                if (data.length > 0) {
                    data.forEach(sesi => {
                        const checkboxItem = document.createElement('div');
                        checkboxItem.className = 'sesi-checkbox-item';
                        checkboxItem.innerHTML = `
                            <input type="checkbox" name="sesi_ids[]" value="${sesi.id}" id="sesi_${sesi.id}" class="sesi-checkbox">
                            <div class="sesi-checkbox-info">
                                <div class="siswa-nama">${sesi.nama_siswa}</div>
                                <div class="sesi-detail">
                                    Sesi #${sesi.sesi_ke} | ${sesi.tanggal} | ${sesi.topik}
                                </div>
                            </div>
                        `;
                        container.appendChild(checkboxItem);
                    });
                    document.getElementById('sesiListContainer').style.display = 'block';
                    document.getElementById('noSesiMessage').style.display = 'none';
                } else {
                    document.getElementById('sesiListContainer').style.display = 'none';
                    document.getElementById('noSesiMessage').style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error loading session data:', error);
                document.getElementById('sesiListContainer').style.display = 'none';
                document.getElementById('noSesiMessage').style.display = 'block';
                document.getElementById('noSesiMessage').innerHTML = 'Terjadi kesalahan saat memuat data sesi';
            });
        }

        function selectAllSesi() {
            document.querySelectorAll('.sesi-checkbox').forEach(checkbox => {
                checkbox.checked = true;
            });
        }

        function unselectAllSesi() {
            document.querySelectorAll('.sesi-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
        }

        document.getElementById('formCetak').addEventListener('submit', function(e) {
            const autoPrint = document.getElementById('autoPrint').checked;
            if (autoPrint) {
                this.action = 'cetak_laporan_konsultasi.php?print=true';
            } else {
                this.action = 'cetak_laporan_konsultasi.php';
            }
        });

        document.getElementById('modalSiswaNama').addEventListener('input', loadSesiData);
        document.getElementById('modalTanggalMulai').addEventListener('change', loadSesiData);
        document.getElementById('modalTanggalSelesai').addEventListener('change', loadSesiData);
        
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
            document.querySelector('input[name="tanggal_sesi"]').value = today;
            
            const now = new Date();
            const timeString = now.getHours().toString().padStart(2, '0') + ':' + 
                             now.getMinutes().toString().padStart(2, '0');
            document.querySelector('input[name="jam_mulai"]').value = timeString;
            
            const endTime = new Date(now.getTime() + 60 * 60 * 1000); 
            const endTimeString = endTime.getHours().toString().padStart(2, '0') + ':' + 
                                endTime.getMinutes().toString().padStart(2, '0');
            document.querySelector('input[name="jam_selesai"]').value = endTimeString;
            
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            
            if (tabParam === 'history') {
                document.querySelector('.tab.active').classList.remove('active');
                document.querySelector('.tab-content.active').classList.remove('active');
                document.querySelector('.tab[onclick*="history"]').classList.add('active');
                document.getElementById('history-tab').classList.add('active');
                document.getElementById('active-search').style.display = 'none';
                document.getElementById('history-search').style.display = 'block';
            } else if (tabParam === 'add') {
                document.querySelector('.tab.active').classList.remove('active');
                document.querySelector('.tab-content.active').classList.remove('active');
                document.querySelector('.tab[onclick*="add"]').classList.add('active');
                document.getElementById('add-tab').classList.add('active');
                document.getElementById('active-search').style.display = 'none';
                document.getElementById('history-search').style.display = 'none';
            } else {
                document.getElementById('active-search').style.display = 'block';
                if (!document.querySelector('.tab.active')) {
                    document.querySelector('.tab[onclick*="active"]').classList.add('active');
                    document.getElementById('active-tab').classList.add('active');
                }
            }
            
            const searchActive = urlParams.get('search_active');
            const searchHistory = urlParams.get('search_history');
            
            if (searchActive && tabParam !== 'history' && tabParam !== 'add') {
                const searchInput = document.querySelector('input[name="search_active"]');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
                }
            }
            
            if (searchHistory && tabParam === 'history') {
                const searchInput = document.querySelector('input[name="search_history"]');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
                }
            }
            
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
        });

        window.onclick = function(event) {
            const modal = document.getElementById('selesaikanModal');
            const cetakModal = document.getElementById('cetakModal');
            if (event.target == modal) {
                closeSelesaikanModal();
            }
            if (event.target == cetakModal) {
                closeCetakModal();
            }
        }

        window.addEventListener('popstate', function(event) {
            window.location.reload();
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
