<?php
session_start();
include 'koneksi.php';
include 'notifikasi_helper.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['jumlah_baru' => 0]);
    exit();
}

$user_id = $_SESSION['user_id'];
$jumlah_baru = hitungNotifikasiBelumDibaca($user_id);

echo json_encode(['jumlah_baru' => $jumlah_baru]);
?>