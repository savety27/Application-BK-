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

function deleteAllExpiredJadwal($koneksi) {
    date_default_timezone_set('Asia/Jakarta');
    
    $hari_indonesia_map = [
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
        'Sunday' => 'Minggu'
    ];
    
    $hari_ini_inggris = date('l');
    $hari_ini_indonesia = $hari_indonesia_map[$hari_ini_inggris] ?? '';
    $current_time = date('H:i:s');
    
    $deleted_total = 0;
    
    error_log("=== PENGHAPUSAN OTOMATIS JADWAL ===");
    error_log("Hari ini: $hari_ini_inggris -> $hari_ini_indonesia");
    error_log("Waktu sekarang: $current_time");

    if (!empty($hari_ini_indonesia)) {
        $sql_delete_today = "DELETE FROM jadwal_konsultasi 
                            WHERE HARI = ? AND JAM_SELESAI < ? AND AKTIF = 'Ya'";
        $stmt_delete_today = $koneksi->prepare($sql_delete_today);
        $stmt_delete_today->bind_param("ss", $hari_ini_indonesia, $current_time);
        
        if ($stmt_delete_today->execute()) {
            $deleted_today = $stmt_delete_today->affected_rows;
            $deleted_total += $deleted_today;
            error_log("Jadwal hari ini ($hari_ini_indonesia) dihapus: $deleted_today");
        } else {
            error_log("Error hapus jadwal hari ini: " . $koneksi->error);
        }
    }

    $hari_order = ['Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4, 'Jumat' => 5];
    $hari_ini_index = isset($hari_order[$hari_ini_indonesia]) ? $hari_order[$hari_ini_indonesia] : 0;
    
    foreach ($hari_order as $hari => $index) {
        if ($index < $hari_ini_index) {
            $sql_delete_past = "DELETE FROM jadwal_konsultasi 
                               WHERE HARI = ? AND AKTIF = 'Ya'";
            $stmt_delete_past = $koneksi->prepare($sql_delete_past);
            $stmt_delete_past->bind_param("s", $hari);
            
            if ($stmt_delete_past->execute()) {
                $deleted_past = $stmt_delete_past->affected_rows;
                $deleted_total += $deleted_past;
                error_log("Jadwal $hari dihapus: $deleted_past");
            } else {
                error_log("Error hapus jadwal $hari: " . $koneksi->error);
            }
        }
    }
    
    error_log("Total jadwal dihapus: $deleted_total");
    return $deleted_total;
}

$deleted_count = deleteAllExpiredJadwal($koneksi);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tambah_jadwal'])) {
        $hari = $_POST['hari'];
        $jam_mulai = $_POST['jam_mulai'];
        $jam_selesai = $_POST['jam_selesai'];
        $kuota = $_POST['kuota'];
        $keterangan = trim($_POST['keterangan']);
        
        if ($jam_mulai >= $jam_selesai) {
            $error = "Jam selesai harus setelah jam mulai!";
        } else {
            $check_sql = "SELECT ID FROM jadwal_konsultasi WHERE GURU_BK_ID = ? AND HARI = ?";
            $check_stmt = $koneksi->prepare($check_sql);
            $check_stmt->bind_param("is", $guru_id, $hari);
            $check_stmt->execute();
            
            if ($check_stmt->get_result()->num_rows > 0) {
                $error = "Jadwal untuk hari $hari sudah ada!";
            } else {
                $sql_insert = "INSERT INTO jadwal_konsultasi (GURU_BK_ID, HARI, JAM_MULAI, JAM_SELESAI, KUOTA, KETERANGAN, AKTIF) 
                              VALUES (?, ?, ?, ?, ?, ?, 'Ya')";
                $stmt_insert = $koneksi->prepare($sql_insert);
                $stmt_insert->bind_param("isssis", $guru_id, $hari, $jam_mulai, $jam_selesai, $kuota, $keterangan);
                
                if ($stmt_insert->execute()) {
                    $success = "Jadwal berhasil ditambahkan!";
                } else {
                    $error = "Gagal menambahkan jadwal!";
                }
            }
        }
    }
    
    if (isset($_POST['edit_jadwal'])) {
        $jadwal_id = $_POST['jadwal_id'];
        $jam_mulai = $_POST['jam_mulai'];
        $jam_selesai = $_POST['jam_selesai'];
        $kuota = $_POST['kuota'];
        $aktif = $_POST['aktif'];
        $keterangan = trim($_POST['keterangan']);
        
        if ($jam_mulai >= $jam_selesai) {
            $error = "Jam selesai harus setelah jam mulai!";
        } else {
            $sql_update = "UPDATE jadwal_konsultasi SET JAM_MULAI = ?, JAM_SELESAI = ?, KUOTA = ?, AKTIF = ?, KETERANGAN = ? 
                          WHERE ID = ? AND GURU_BK_ID = ?";
            $stmt_update = $koneksi->prepare($sql_update);
            $stmt_update->bind_param("ssisssi", $jam_mulai, $jam_selesai, $kuota, $aktif, $keterangan, $jadwal_id, $guru_id);
            
            if ($stmt_update->execute()) {
                $success = "Jadwal berhasil diperbarui!";
            } else {
                $error = "Gagal memperbarui jadwal!";
            }
        }
    }
    
    if (isset($_POST['hapus_jadwal'])) {
        $jadwal_id = $_POST['jadwal_id'];
        
        $sql_delete = "DELETE FROM jadwal_konsultasi WHERE ID = ? AND GURU_BK_ID = ?";
        $stmt_delete = $koneksi->prepare($sql_delete);
        $stmt_delete->bind_param("ii", $jadwal_id, $guru_id);
        
        if ($stmt_delete->execute()) {
            $success = "Jadwal berhasil dihapus!";
        } else {
            $error = "Gagal menghapus jadwal!";
        }
    }
}

date_default_timezone_set('Asia/Jakarta');
$hari_ini_inggris = date('l');
$hari_indonesia_map = [
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu', 
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat'
];
$hari_ini_nama = $hari_indonesia_map[$hari_ini_inggris] ?? '';
$hari_order = ['Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4, 'Jumat' => 5];
$hari_ini_index = isset($hari_order[$hari_ini_nama]) ? $hari_order[$hari_ini_nama] : 0;

$valid_hari_conditions = [];
foreach ($hari_order as $hari => $index) {
    if ($index >= $hari_ini_index) {
        $valid_hari_conditions[] = "'$hari'";
    }
}
$valid_hari_list = !empty($valid_hari_conditions) ? implode(',', $valid_hari_conditions) : "'Senin','Selasa','Rabu','Kamis','Jumat'";

$sql_jadwal = "SELECT j.*, u.NAMA_LENGKAP 
               FROM jadwal_konsultasi j 
               JOIN guru_bk g ON j.GURU_BK_ID = g.ID 
               JOIN users u ON g.USER_ID = u.ID 
               WHERE j.GURU_BK_ID = ? 
               AND j.HARI IN ($valid_hari_list)
               ORDER BY FIELD(j.HARI, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'), j.JAM_MULAI";
$stmt_jadwal = $koneksi->prepare($sql_jadwal);
$stmt_jadwal->bind_param("i", $guru_id);
$stmt_jadwal->execute();
$jadwal_list = $stmt_jadwal->get_result();

$sql_all_jadwal = "SELECT j.*, u.NAMA_LENGKAP 
                   FROM jadwal_konsultasi j 
                   JOIN guru_bk g ON j.GURU_BK_ID = g.ID 
                   JOIN users u ON g.USER_ID = u.ID 
                   WHERE j.AKTIF = 'Ya' 
                   AND j.HARI IN ($valid_hari_list)
                   ORDER BY u.NAMA_LENGKAP, FIELD(j.HARI, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'), j.JAM_MULAI";
$all_jadwal_result = $koneksi->query($sql_all_jadwal);

$hari_terisi = [];
$jadwal_data = [];
while ($jadwal = $jadwal_list->fetch_assoc()) {
    $hari_terisi[] = $jadwal['HARI'];
    $jadwal_data[$jadwal['ID']] = $jadwal;
}
$jadwal_list->data_seek(0);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jadwal - APK BK</title>
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
        
        .alert-info {
            background: rgba(49, 130, 206, 0.15);
            border-color: rgba(49, 130, 206, 0.3);
            color: #3182ce;
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
        
        .jadwal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 30px;
        }
        
        .jadwal-card {
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
        
        .jadwal-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 45px rgba(49, 130, 206, 0.2);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(49, 130, 206, 0.1);
        }
        
        .hari {
            font-size: 22px;
            font-weight: 700;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .guru-name {
            font-size: 14px;
            color: #718096;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            border: 2px solid;
        }
        
        .status-aktif {
            background: rgba(72, 187, 120, 0.1);
            color: #38a169;
            border-color: rgba(72, 187, 120, 0.3);
        }
        
        .status-nonaktif {
            background: rgba(245, 101, 101, 0.1);
            color: #e53e3e;
            border-color: rgba(245, 101, 101, 0.3);
        }
        
        .jadwal-info {
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
        
        .jadwal-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.4s ease;
            font-size: 14px;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
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
        
        .btn-edit {
            background: linear-gradient(135deg, #3182ce, #2b6cb0);
            color: white;
            box-shadow: 0 6px 20px rgba(49, 130, 206, 0.3);
        }
        
        .btn-hapus {
            background: linear-gradient(135deg, #e53e3e, #c53030);
            color: white;
            box-shadow: 0 6px 20px rgba(229, 62, 62, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-3px) scale(1.05);
        }
        
        .btn-edit:hover {
            box-shadow: 0 10px 25px rgba(49, 130, 206, 0.4);
        }
        
        .btn-hapus:hover {
            box-shadow: 0 10px 25px rgba(229, 62, 62, 0.4);
        }
        
        .form-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 35px;
            border-radius: 20px;
            border: 1px solid rgba(49, 130, 206, 0.1);
            box-shadow: 0 15px 35px rgba(49, 130, 206, 0.1);
            backdrop-filter: blur(15px);
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
            min-height: 100px;
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
        
        .info-text {
            font-size: 13px;
            color: #718096;
            margin-top: 6px;
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
            
            .tabs {
                flex-direction: column;
                padding: 15px;
            }
            
            .jadwal-grid {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .jadwal-actions {
                flex-direction: column;
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
            
            .jadwal-card {
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
        }
    </style>
</head>
<body>
    
    <div class="floating"></div>
    <div class="floating"></div>
    <div class="floating"></div>
    
    <div class="header">
        <h1>👨‍🏫 APK BK - Kelola Jadwal</h1>
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
        <a href="kelola_jadwal.php" class="active">
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
    
    <div class="container">
        <?php if ($deleted_count > 0): ?>
            <div class="alert alert-info">
                <i class='bx bx-trash'></i>
                <?php echo $deleted_count; ?> jadwal yang sudah lewat telah dihapus otomatis dari sistem.
            </div>
        <?php endif; ?>
        
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
            <h2><i class='bx bx-calendar'></i> Kelola Jadwal Konsultasi</h2>
            <p>Atur jadwal availability untuk konsultasi dengan siswa</p>
            <p style="color: #718096; font-size: 14px; margin-top: 8px;">
                <i class='bx bx-info-circle'></i> Sistem otomatis menghapus jadwal yang sudah lewat hari dan jamnya.
            </p>
        </div>
        
        <div class="tabs">
            <div class="tab active" onclick="showTab('list')">
                <i class='bx bx-list-ul'></i>
                Jadwal Saya
            </div>
            <div class="tab" onclick="showTab('all')">
                <i class='bx bx-calendar-alt'></i>
                Semua Jadwal Guru
            </div>
            <div class="tab" onclick="showTab('add')">
                <i class='bx bx-plus'></i>
                Tambah Jadwal
            </div>
        </div>
        
        <div id="list-tab" class="tab-content active">
            <?php if ($jadwal_list->num_rows > 0): ?>
                <div class="jadwal-grid">
                    <?php while($jadwal = $jadwal_list->fetch_assoc()): ?>
                    <div class="jadwal-card">
                        <div class="card-header">
                            <div>
                                <div class="hari">
                                    <i class='bx bx-calendar'></i>
                                    <?php echo $jadwal['HARI']; ?>
                                </div>
                                <div class="guru-name">
                                    <i class='bx bx-user'></i>
                                    <?php echo htmlspecialchars($jadwal['NAMA_LENGKAP']); ?>
                                </div>
                            </div>
                            <div class="status-badge status-<?php echo strtolower($jadwal['AKTIF'] == 'Ya' ? 'aktif' : 'nonaktif'); ?>">
                                <?php echo $jadwal['AKTIF'] == 'Ya' ? 'Aktif' : 'Nonaktif'; ?>
                            </div>
                        </div>
                        
                        <div class="jadwal-info">
                            <div class="info-label">
                                <i class='bx bx-time'></i>
                                Jam Konsultasi
                            </div>
                            <div class="info-value">
                                <?php echo date('H:i', strtotime($jadwal['JAM_MULAI'])); ?> - <?php echo date('H:i', strtotime($jadwal['JAM_SELESAI'])); ?>
                            </div>
                        </div>
                        
                        <div class="jadwal-info">
                            <div class="info-label">
                                <i class='bx bx-user'></i>
                                Kuota Siswa
                            </div>
                            <div class="info-value"><?php echo $jadwal['KUOTA']; ?> siswa</div>
                        </div>
                        
                        <?php if ($jadwal['KETERANGAN']): ?>
                        <div class="jadwal-info">
                            <div class="info-label">
                                <i class='bx bx-note'></i>
                                Keterangan
                            </div>
                            <div class="info-value"><?php echo htmlspecialchars($jadwal['KETERANGAN']); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="jadwal-actions">
                            <button class="btn btn-edit" onclick="editJadwal(<?php echo $jadwal['ID']; ?>)">
                                <i class='bx bx-edit'></i>
                                Edit
                            </button>
                            <form method="POST" style="flex: 1;" onsubmit="return confirm('Yakin ingin menghapus jadwal ini?')">
                                <input type="hidden" name="jadwal_id" value="<?php echo $jadwal['ID']; ?>">
                                <input type="hidden" name="hapus_jadwal" value="1">
                                <button type="submit" class="btn btn-hapus">
                                    <i class='bx bx-trash'></i>
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3><i class='bx bx-calendar'></i> Belum Ada Jadwal</h3>
                    <p>Tambahkan jadwal konsultasi untuk memulai</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div id="all-tab" class="tab-content">
            <?php if ($all_jadwal_result->num_rows > 0): ?>
                <div class="jadwal-grid">
                    <?php while($jadwal = $all_jadwal_result->fetch_assoc()): 
                        $is_my_jadwal = ($jadwal['GURU_BK_ID'] == $guru_id);
                    ?>
                    <div class="jadwal-card">
                        <div class="card-header">
                            <div>
                                <div class="hari">
                                    <i class='bx bx-calendar'></i>
                                    <?php echo $jadwal['HARI']; ?>
                                    <?php if ($is_my_jadwal): ?>
                                        <span style="color: #3182ce; font-size: 12px;">(Jadwal Saya)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="guru-name">
                                    <i class='bx bx-user'></i>
                                    <?php echo htmlspecialchars($jadwal['NAMA_LENGKAP']); ?>
                                </div>
                            </div>
                            <div class="status-badge status-aktif">
                                Aktif
                            </div>
                        </div>
                        
                        <div class="jadwal-info">
                            <div class="info-label">
                                <i class='bx bx-time'></i>
                                Jam Konsultasi
                            </div>
                            <div class="info-value">
                                <?php echo date('H:i', strtotime($jadwal['JAM_MULAI'])); ?> - <?php echo date('H:i', strtotime($jadwal['JAM_SELESAI'])); ?>
                            </div>
                        </div>
                        
                        <div class="jadwal-info">
                            <div class="info-label">
                                <i class='bx bx-user'></i>
                                Kuota Siswa
                            </div>
                            <div class="info-value"><?php echo $jadwal['KUOTA']; ?> siswa</div>
                        </div>
                        
                        <?php if ($jadwal['KETERANGAN']): ?>
                        <div class="jadwal-info">
                            <div class="info-label">
                                <i class='bx bx-note'></i>
                                Keterangan
                            </div>
                            <div class="info-value"><?php echo htmlspecialchars($jadwal['KETERANGAN']); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($is_my_jadwal): ?>
                        <div class="jadwal-actions">
                            <button class="btn btn-edit" onclick="editJadwal(<?php echo $jadwal['ID']; ?>)">
                                <i class='bx bx-edit'></i>
                                Edit
                            </button>
                            <form method="POST" style="flex: 1;" onsubmit="return confirm('Yakin ingin menghapus jadwal ini?')">
                                <input type="hidden" name="jadwal_id" value="<?php echo $jadwal['ID']; ?>">
                                <input type="hidden" name="hapus_jadwal" value="1">
                                <button type="submit" class="btn btn-hapus">
                                    <i class='bx bx-trash'></i>
                                    Hapus
                                </button>
                            </form>
                        </div>
                        <?php else: ?>
                        <div style="padding: 15px; text-align: center; color: #718096; font-style: italic;">
                            Jadwal konsultasi <?php echo htmlspecialchars($jadwal['NAMA_LENGKAP']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3><i class='bx bx-calendar'></i> Tidak Ada Jadwal Tersedia</h3>
                    <p>Belum ada guru yang membuat jadwal konsultasi</p>
                </div>
            <?php endif; ?>
        </div>

        <div id="add-tab" class="tab-content">
            <div class="form-container">
                <h3><i class='bx bx-plus'></i> Tambah Jadwal Baru</h3>
                <form method="POST" action="">
                    <input type="hidden" name="tambah_jadwal" value="1">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>
                                <i class='bx bx-calendar'></i>
                                Hari *
                            </label>
                            <select name="hari" required>
                                <option value="">Pilih Hari</option>
                                <option value="Senin" <?php echo in_array('Senin', $hari_terisi) ? 'disabled' : ''; ?>>Senin</option>
                                <option value="Selasa" <?php echo in_array('Selasa', $hari_terisi) ? 'disabled' : ''; ?>>Selasa</option>
                                <option value="Rabu" <?php echo in_array('Rabu', $hari_terisi) ? 'disabled' : ''; ?>>Rabu</option>
                                <option value="Kamis" <?php echo in_array('Kamis', $hari_terisi) ? 'disabled' : ''; ?>>Kamis</option>
                                <option value="Jumat" <?php echo in_array('Jumat', $hari_terisi) ? 'disabled' : ''; ?>>Jumat</option>
                            </select>
                            <div class="info-text">Hari yang sudah ada jadwal akan dinonaktifkan</div>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <i class='bx bx-time'></i>
                                Jam Mulai *
                            </label>
                            <input type="time" name="jam_mulai" required value="08:00">
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <i class='bx bx-time-five'></i>
                                Jam Selesai *
                            </label>
                            <input type="time" name="jam_selesai" required value="10:00">
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <i class='bx bx-user'></i>
                                Kuota *
                            </label>
                            <input type="number" name="kuota" value="5" min="1" max="20" required>
                            <div class="info-text">Jumlah maksimal siswa per sesi</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <i class='bx bx-note'></i>
                            Keterangan (Opsional)
                        </label>
                        <textarea name="keterangan" placeholder="Contoh: Konsultasi khusus masalah karir..."></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class='bx bx-save'></i>
                        Simpan Jadwal
                    </button>
                </form>
            </div>
        </div>
        
        <div id="edit-form" class="form-container" style="display: none; margin-top: 30px;">
            <h3><i class='bx bx-edit'></i> Edit Jadwal</h3>
            <form method="POST" action="" id="editJadwalForm">
                <input type="hidden" name="edit_jadwal" value="1">
                <input type="hidden" name="jadwal_id" id="edit_jadwal_id">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>
                            <i class='bx bx-calendar'></i>
                            Hari
                        </label>
                        <input type="text" id="edit_hari" readonly style="background: rgba(49, 130, 206, 0.05);">
                        <div class="info-text">Hari tidak dapat diubah</div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <i class='bx bx-time'></i>
                            Jam Mulai *
                        </label>
                        <input type="time" name="jam_mulai" id="edit_jam_mulai" required>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <i class='bx bx-time-five'></i>
                            Jam Selesai *
                        </label>
                        <input type="time" name="jam_selesai" id="edit_jam_selesai" required>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <i class='bx bx-user'></i>
                            Kuota *
                        </label>
                        <input type="number" name="kuota" id="edit_kuota" min="1" max="20" required>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <i class='bx bx-power-off'></i>
                            Status *
                        </label>
                        <select name="aktif" id="edit_aktif" required>
                            <option value="Ya">Aktif</option>
                            <option value="Tidak">Nonaktif</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <i class='bx bx-note'></i>
                        Keterangan (Opsional)
                    </label>
                    <textarea name="keterangan" id="edit_keterangan" placeholder="Keterangan jadwal..."></textarea>
                </div>
                
                <div style="display: flex; gap: 12px;">
                    <button type="submit" class="submit-btn" style="flex: 1;">
                        <i class='bx bx-save'></i>
                        Update Jadwal
                    </button>
                    <button type="button" class="btn btn-hapus" onclick="hideEditForm()" style="flex: 0.5;">
                        <i class='bx bx-x'></i>
                        Batal
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
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.getElementById(tabName + '-tab').classList.add('active');
            event.currentTarget.classList.add('active');
        }
        
        const jadwalData = <?php echo json_encode($jadwal_data); ?>;
        
        function editJadwal(jadwalId) {
            const jadwal = jadwalData[jadwalId];
            
            if (jadwal) {
                document.getElementById('edit_jadwal_id').value = jadwal.ID;
                document.getElementById('edit_hari').value = jadwal.HARI;
                document.getElementById('edit_jam_mulai').value = jadwal.JAM_MULAI.substring(0, 5);
                document.getElementById('edit_jam_selesai').value = jadwal.JAM_SELESAI.substring(0, 5);
                document.getElementById('edit_kuota').value = jadwal.KUOTA;
                document.getElementById('edit_aktif').value = jadwal.AKTIF;
                document.getElementById('edit_keterangan').value = jadwal.KETERANGAN || '';
                
                document.getElementById('edit-form').style.display = 'block';
                document.getElementById('edit-form').scrollIntoView({ behavior: 'smooth' });
            } else {
                alert('Data jadwal tidak ditemukan!');
            }
        }
        
        function hideEditForm() {
            document.getElementById('edit-form').style.display = 'none';
        }
    </script>
</body>
</html>