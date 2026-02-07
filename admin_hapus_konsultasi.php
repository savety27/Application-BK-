<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $sql = "DELETE FROM konsultasi WHERE ID = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: admin_laporan_konsultasi.php?success=Data konsultasi berhasil dihapus");
        exit();
    } else {
        header("Location: admin_laporan_konsultasi.php?error=Gagal menghapus data konsultasi");
        exit();
    }
} else {
    header("Location: admin_laporan_konsultasi.php?error=ID tidak valid");
    exit();
}
?>