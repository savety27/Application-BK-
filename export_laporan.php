<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: index.php");
    exit();
}

$filter_tanggal_mulai = $_GET['tanggal_mulai'] ?? '';
$filter_tanggal_akhir = $_GET['tanggal_akhir'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_guru = $_GET['guru_id'] ?? '';
$filter_prioritas = $_GET['prioritas'] ?? '';

$sql = "SELECT k.*, u.NAMA_LENGKAP as nama_siswa, s.KELAS, s.JURUSAN, 
               u_guru.NAMA_LENGKAP as nama_guru
        FROM konsultasi k
        JOIN siswa s ON k.SISWA_ID = s.ID
        JOIN users u ON s.USER_ID = u.ID
        LEFT JOIN guru_bk g ON k.GURU_BK_ID = g.ID
        LEFT JOIN users u_guru ON g.USER_ID = u_guru.ID
        WHERE 1=1";

$params = [];
$types = '';

if (!empty($filter_tanggal_mulai)) {
    $sql .= " AND k.TANGGAL_PENGAJUAN >= ?";
    $params[] = $filter_tanggal_mulai;
    $types .= 's';
}

if (!empty($filter_tanggal_akhir)) {
    $sql .= " AND k.TANGGAL_PENGAJUAN <= ?";
    $params[] = $filter_tanggal_akhir;
    $types .= 's';
}

if (!empty($filter_status)) {
    $sql .= " AND k.STATUS = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if (!empty($filter_guru)) {
    $sql .= " AND k.GURU_BK_ID = ?";
    $params[] = $filter_guru;
    $types .= 'i';
}

if (!empty($filter_prioritas)) {
    $sql .= " AND k.PRIORITAS = ?";
    $params[] = $filter_prioritas;
    $types .= 's';
}

$sql .= " ORDER BY k.CREATED_AT DESC";

$stmt = $koneksi->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$konsultasi_list = $stmt->get_result();

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"laporan_konsultasi_".date('Y-m-d').".xls\"");
header("Pragma: no-cache");
header("Expires: 0");

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        .table { border-collapse: collapse; width: 100%; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; }
        .table th { background-color: #f2f2f2; font-weight: bold; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <h2>LAPORAN KONSULTASI BK</h2>
    <p>Tanggal Export: <?php echo date('d/m/Y H:i:s'); ?></p>
    
    <?php if (!empty($filter_tanggal_mulai) || !empty($filter_tanggal_akhir)): ?>
    <p>Periode: 
        <?php echo !empty($filter_tanggal_mulai) ? date('d/m/Y', strtotime($filter_tanggal_mulai)) : 'Awal' ?> 
        - 
        <?php echo !empty($filter_tanggal_akhir) ? date('d/m/Y', strtotime($filter_tanggal_akhir)) : 'Akhir' ?>
    </p>
    <?php endif; ?>

    <table class="table">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Tanggal Pengajuan</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th>Topik</th>
                <th>Prioritas</th>
                <th>Guru BK</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            while($konsul = $konsultasi_list->fetch_assoc()): 
            ?>
            <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td><?php echo htmlspecialchars($konsul['KODE_KONSULTASI']); ?></td>
                <td><?php echo date('d/m/Y', strtotime($konsul['TANGGAL_PENGAJUAN'])); ?></td>
                <td><?php echo htmlspecialchars($konsul['nama_siswa']); ?></td>
                <td><?php echo htmlspecialchars($konsul['KELAS']); ?> - <?php echo htmlspecialchars($konsul['JURUSAN']); ?></td>
                <td><?php echo htmlspecialchars($konsul['TOPIK_KONSULTASI']); ?></td>
                <td><?php echo $konsul['PRIORITAS']; ?></td>
                <td><?php echo $konsul['nama_guru'] ? htmlspecialchars($konsul['nama_guru']) : 'Belum ditugaskan'; ?></td>
                <td><?php echo $konsul['STATUS']; ?></td>
            </tr>
            <?php endwhile; ?>
            
            <?php if ($konsultasi_list->num_rows == 0): ?>
            <tr>
                <td colspan="10" class="text-center">Tidak ada data konsultasi</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <p>Total Data: <?php echo $konsultasi_list->num_rows; ?> konsultasi</p>
</body>
</html>