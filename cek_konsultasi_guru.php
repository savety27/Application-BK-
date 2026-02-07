<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Guru_BK') {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['jumlah_baru' => 0]);
    exit();
}

$user_id = $_SESSION['user_id'];

$sql_guru = "SELECT g.ID FROM guru_bk g WHERE g.user_id = ?";
$stmt_guru = $koneksi->prepare($sql_guru);
$stmt_guru->bind_param("i", $user_id);
$stmt_guru->execute();
$guru_result = $stmt_guru->get_result();

if ($guru_result->num_rows > 0) {
    $guru = $guru_result->fetch_assoc();
    $guru_id = $guru['ID'];
    
    $sql_menunggu = "SELECT COUNT(*) as jumlah FROM konsultasi 
                    WHERE STATUS = 'Menunggu' 
                    AND (PILIHAN_GURU_1 = ? OR PILIHAN_GURU_2 = ?)";
    $stmt_menunggu = $koneksi->prepare($sql_menunggu);
    $stmt_menunggu->bind_param("ii", $guru_id, $guru_id);
    $stmt_menunggu->execute();
    $result_menunggu = $stmt_menunggu->get_result()->fetch_assoc();
    
    $jumlah_baru = $result_menunggu['jumlah'] ?? 0;
    echo json_encode(['jumlah_baru' => $jumlah_baru]);
} else {
    echo json_encode(['jumlah_baru' => 0]);
}
?>