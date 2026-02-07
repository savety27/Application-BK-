<?php
session_start();

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
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Guru_BK') {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['konsultasi_id'])) {
    $konsultasi_id = $_POST['konsultasi_id'];
    $user_id = $_SESSION['user_id'];
    
    $sql_guru = "SELECT g.ID FROM guru_bk g WHERE g.USER_ID = ?";
    $stmt_guru = $koneksi->prepare($sql_guru);
    $stmt_guru->bind_param("i", $user_id);
    $stmt_guru->execute();
    $guru = $stmt_guru->get_result()->fetch_assoc();
    $guru_id = $guru['ID'];
    
    $sql_update = "UPDATE konsultasi SET STATUS = 'Selesai' WHERE ID = ? AND GURU_BK_ID = ? AND STATUS = 'Disetujui'";
    $stmt_update = $koneksi->prepare($sql_update);
    $stmt_update->bind_param("ii", $konsultasi_id, $guru_id);
    
    if ($stmt_update->execute()) {
        $_SESSION['success'] = "Konsultasi berhasil ditandai sebagai selesai!";
    } else {
        $_SESSION['error'] = "Gagal menandai konsultasi sebagai selesai!";
    }
    
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
} else {
    header("Location: sesi_konsultasi.php");
    exit();
}
?>