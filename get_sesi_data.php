<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Guru_BK') {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

$guru_id = isset($_POST['guru_id']) ? intval($_POST['guru_id']) : 0;
$siswa_nama = isset($_POST['siswa_nama']) ? $koneksi->real_escape_string($_POST['siswa_nama']) : '';
$tanggal_mulai = isset($_POST['tanggal_mulai']) ? $_POST['tanggal_mulai'] : '';
$tanggal_selesai = isset($_POST['tanggal_selesai']) ? $_POST['tanggal_selesai'] : '';

if ($guru_id === 0) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

$sql = "SELECT sc.ID, sc.SESI_KE, sc.TANGGAL_SESI, k.TOPIK_KONSULTASI, u.NAMA_LENGKAP as nama_siswa
        FROM sesi_konsultasi sc
        JOIN konsultasi k ON sc.KONSULTASI_ID = k.ID
        JOIN siswa s ON k.SISWA_ID = s.ID
        JOIN users u ON s.USER_ID = u.ID
        WHERE k.GURU_BK_ID = ?";

$params = [$guru_id];
$types = "i";

if ($siswa_nama) {
    $sql .= " AND u.NAMA_LENGKAP LIKE ?";
    $params[] = "%$siswa_nama%";
    $types .= "s";
}

if ($tanggal_mulai) {
    $sql .= " AND DATE(sc.TANGGAL_SESI) >= ?";
    $params[] = $tanggal_mulai;
    $types .= "s";
}

if ($tanggal_selesai) {
    $sql .= " AND DATE(sc.TANGGAL_SESI) <= ?";
    $params[] = $tanggal_selesai;
    $types .= "s";
}

$sql .= " ORDER BY sc.TANGGAL_SESI DESC LIMIT 100";

$stmt = $koneksi->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$sesi_data = [];
while ($row = $result->fetch_assoc()) {
    $sesi_data[] = [
        'id' => $row['ID'],
        'sesi_ke' => $row['SESI_KE'],
        'tanggal' => date('d/m/Y', strtotime($row['TANGGAL_SESI'])),
        'nama_siswa' => $row['nama_siswa'],
        'topik' => $row['TOPIK_KONSULTASI']
    ];
}

header('Content-Type: application/json');
echo json_encode($sesi_data);
?>