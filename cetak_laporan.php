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

$is_filtering = !empty($filter_tanggal_mulai) || !empty($filter_tanggal_akhir) || 
                !empty($filter_status) || !empty($filter_guru) || !empty($filter_prioritas);

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

$nama_guru_filter = '';
if (!empty($filter_guru)) {
    $sql_guru_nama = "SELECT u.NAMA_LENGKAP FROM guru_bk g JOIN users u ON g.USER_ID = u.ID WHERE g.ID = ?";
    $stmt_guru = $koneksi->prepare($sql_guru_nama);
    $stmt_guru->bind_param("i", $filter_guru);
    $stmt_guru->execute();
    $result_guru = $stmt_guru->get_result();
    if ($guru = $result_guru->fetch_assoc()) {
        $nama_guru_filter = $guru['NAMA_LENGKAP'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Konsultasi</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .filter-info {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            font-size: 11px;
        }
        .filter-info strong {
            color: #333;
        }
        .filter-item {
            display: inline-block;
            margin-right: 15px;
            padding: 2px 8px;
            background: #e0e0e0;
            border-radius: 3px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .table th, .table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }
        .table th {
            background-color: #f0f0f0;
        }
        .summary {
            margin-top: 20px;
            text-align: right;
            font-weight: bold;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1>LAPORAN KONSULTASI BIMBINGAN KONSELING</h1>
        <p>Sistem Aplikasi BK - <?php echo date('d/m/Y H:i:s'); ?></p>
        
        <?php if ($is_filtering): ?>
        <div class="filter-info">
            <strong>FILTER YANG DITERAPKAN:</strong><br>
            <?php if (!empty($filter_tanggal_mulai)): ?>
                <span class="filter-item">Dari: <?php echo date('d/m/Y', strtotime($filter_tanggal_mulai)); ?></span>
            <?php endif; ?>
            <?php if (!empty($filter_tanggal_akhir)): ?>
                <span class="filter-item">Sampai: <?php echo date('d/m/Y', strtotime($filter_tanggal_akhir)); ?></span>
            <?php endif; ?>
            <?php if (!empty($filter_status)): ?>
                <span class="filter-item">Status: <?php echo htmlspecialchars($filter_status); ?></span>
            <?php endif; ?>
            <?php if (!empty($nama_guru_filter)): ?>
                <span class="filter-item">Guru BK: <?php echo htmlspecialchars($nama_guru_filter); ?></span>
            <?php endif; ?>
            <?php if (!empty($filter_prioritas)): ?>
                <span class="filter-item">Prioritas: <?php echo htmlspecialchars($filter_prioritas); ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Tanggal</th>
                <th>Siswa</th>
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
                <td><?php echo $no++; ?></td>
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
                <td colspan="9" style="text-align: center;">Tidak ada data konsultasi <?php echo $is_filtering ? 'dengan filter yang diberikan' : ''; ?></td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="summary">
        <p>Total Data: <?php echo $konsultasi_list->num_rows; ?> konsultasi</p>
        <?php if ($is_filtering): ?>
        <p style="font-size: 11px; color: #666; margin-top: 5px;">
            *Data sesuai dengan filter yang diterapkan
        </p>
        <?php endif; ?>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()">🖨️ Print Ulang</button>
        <button onclick="window.close()">❌ Tutup</button>
    </div>

    <script>
        window.onafterprint = function() {
        };
    </script>
</body>
</html>