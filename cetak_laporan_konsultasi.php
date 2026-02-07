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

$siswa_id = isset($_GET['siswa_id']) ? intval($_GET['siswa_id']) : 0;
$konsultasi_id = isset($_GET['konsultasi_id']) ? intval($_GET['konsultasi_id']) : 0;
$siswa_nama = isset($_GET['siswa_nama']) ? $koneksi->real_escape_string($_GET['siswa_nama']) : '';
$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : '';
$tanggal_selesai = isset($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : '';
$jenis_laporan = isset($_GET['jenis_laporan']) ? $_GET['jenis_laporan'] : 'semua'; 
$sesi_ids = isset($_GET['sesi_ids']) ? $_GET['sesi_ids'] : [];
if (!is_array($sesi_ids)) {
    $sesi_ids = explode(',', $sesi_ids);
}
$sesi_ids = array_filter($sesi_ids, 'is_numeric');

$sql = "SELECT 
            sc.ID as sesi_id,
            sc.SESI_KE,
            sc.TANGGAL_SESI,
            sc.JAM_MULAI,
            sc.JAM_SELESAI,
            sc.POKOK_PEMBAHASAN,
            sc.CATATAN_SESI,
            sc.TINDAK_LANJUT,
            sc.REKOMENDASI,
            sc.STATUS_SESI,
            k.ID as konsultasi_id,
            k.TOPIK_KONSULTASI,
            k.DESKRIPSI_MASALAH,
            k.TANGGAL_KONSULTASI,
            k.JAM_KONSULTASI,
            k.TEMPAT_KONSULTASI,
            k.STATUS,
            k.SARAN_GURU,
            k.CATATAN_KONSULTASI,
            k.PERLU_TINDAK_LANJUT,
            k.CREATED_AT as tanggal_pengajuan,
            u.NAMA_LENGKAP as nama_siswa,
            s.KELAS,
            s.JURUSAN,
            s.NIS
        FROM sesi_konsultasi sc
        JOIN konsultasi k ON sc.KONSULTASI_ID = k.ID
        JOIN siswa s ON k.SISWA_ID = s.ID
        JOIN users u ON s.USER_ID = u.ID
        WHERE 1=1";

$conditions = [];
$params = [];
$types = '';

if ($siswa_id > 0) {
    $conditions[] = "k.SISWA_ID = ?";
    $params[] = $siswa_id;
    $types .= 'i';
} elseif (!empty($siswa_nama)) {
    $conditions[] = "u.NAMA_LENGKAP LIKE ?";
    $params[] = "%$siswa_nama%";
    $types .= 's';
}

if ($konsultasi_id > 0) {
    $conditions[] = "k.ID = ?";
    $params[] = $konsultasi_id;
    $types .= 'i';
}

if (!empty($sesi_ids)) {
    $placeholders = implode(',', array_fill(0, count($sesi_ids), '?'));
    $conditions[] = "sc.ID IN ($placeholders)";
    $params = array_merge($params, $sesi_ids);
    $types .= str_repeat('i', count($sesi_ids));
}

if (!empty($tanggal_mulai) && !empty($tanggal_selesai)) {
    $conditions[] = "DATE(sc.TANGGAL_SESI) BETWEEN ? AND ?";
    $params[] = $tanggal_mulai;
    $params[] = $tanggal_selesai;
    $types .= 'ss';
} elseif (!empty($tanggal_mulai)) {
    $conditions[] = "DATE(sc.TANGGAL_SESI) >= ?";
    $params[] = $tanggal_mulai;
    $types .= 's';
} elseif (!empty($tanggal_selesai)) {
    $conditions[] = "DATE(sc.TANGGAL_SESI) <= ?";
    $params[] = $tanggal_selesai;
    $types .= 's';
}

if (!empty($conditions)) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY sc.TANGGAL_SESI DESC, sc.JAM_MULAI DESC";

$stmt = $koneksi->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$data_laporan = $result->fetch_all(MYSQLI_ASSOC);
$total_sesi = count($data_laporan);

$siswa_unik = [];
$konsultasi_unik = [];
$total_detik_konsultasi = 0;

foreach ($data_laporan as $sesi) {
    $siswa_unik[$sesi['nama_siswa']] = true;
    $konsultasi_unik[$sesi['konsultasi_id']] = true;
    
    $jam_mulai = strtotime($sesi['JAM_MULAI']);
    $jam_selesai = strtotime($sesi['JAM_SELESAI']);
    if ($jam_mulai && $jam_selesai) {
        $durasi_detik = $jam_selesai - $jam_mulai;
        $total_detik_konsultasi += $durasi_detik;
    }
}

function formatDurasi($detik) {
    $jam = floor($detik / 3600);
    $menit = floor(($detik % 3600) / 60);
    
    $result = [];
    if ($jam > 0) {
        $result[] = $jam . " jam";
    }
    if ($menit > 0) {
        $result[] = $menit . " menit";
    }
    
    if (empty($result)) {
        return "0 menit";
    }
    
    return implode(' ', $result);
}

$total_durasi_formatted = formatDurasi($total_detik_konsultasi);

$user_id = $_SESSION['user_id'];
$nama_guru_cetak = $_SESSION['nama_lengkap'] ?? 'Guru BK';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Konsultasi - APK BK</title>
    <style>
        @media screen {
            body {
                font-family: 'Arial', sans-serif;
                background: #f5f5f5;
                padding: 20px;
                color: #333;
            }
            
            .print-container {
                max-width: 210mm;
                margin: 0 auto;
                background: white;
                padding: 20mm;
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
                border-radius: 8px;
            }
            
            .no-print {
                text-align: center;
                margin-bottom: 30px;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 8px;
                border: 1px solid #dee2e6;
            }
            
            .print-controls {
                display: flex;
                gap: 15px;
                justify-content: center;
                margin-bottom: 20px;
                flex-wrap: wrap;
            }
            
            .btn {
                padding: 12px 24px;
                background: #3182ce;
                color: white;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                font-weight: 600;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: all 0.3s ease;
            }
            
            .btn:hover {
                background: #2b6cb0;
                transform: translateY(-2px);
            }
            
            .btn-print {
                background: #48bb78;
            }
            
            .btn-print:hover {
                background: #38a169;
            }
            
            .btn-back {
                background: #718096;
            }
            
            .btn-back:hover {
                background: #4a5568;
            }
            
            .filter-info {
                background: #e6f7ff;
                padding: 15px;
                border-radius: 8px;
                border-left: 4px solid #1890ff;
                margin-bottom: 20px;
            }
            
            .filter-info h4 {
                margin-top: 0;
                color: #1890ff;
            }
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                background: white;
                font-family: 'Arial', sans-serif;
                color: #000;
            }
            
            .no-print {
                display: none !important;
            }
            
            .print-container {
                width: 100%;
                padding: 15mm;
                margin: 0;
                box-shadow: none;
                border-radius: 0;
            }
            
            .page-break {
                page-break-before: always;
            }
        }
        
        .print-container {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
        }
        
        .header-laporan {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #3182ce;
        }
        
        .header-laporan h1 {
            color: #3182ce;
            margin: 10px 0;
            font-size: 24px;
        }
        
        .header-laporan h2 {
            color: #2d3748;
            margin: 5px 0;
            font-size: 18px;
            font-weight: normal;
        }
        
        .info-laporan {
            margin-bottom: 30px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 12px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        
        .info-value {
            font-size: 14px;
            font-weight: 600;
            color: #2d3748;
        }
        
        .statistik-laporan {
            background: linear-gradient(135deg, #3182ce, #2b6cb0);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .statistik-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            text-align: center;
        }
        
        .stat-item {
            display: flex;
            flex-direction: column;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .data-laporan {
            margin-top: 30px;
        }
        
        .section-title {
            color: #3182ce;
            border-bottom: 2px solid #3182ce;
            padding-bottom: 10px;
            margin: 30px 0 20px 0;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            font-size: 24px;
        }
        
        .konsultasi-group {
            margin-bottom: 40px;
            page-break-inside: avoid;
        }
        
        .konsultasi-header {
            background: #e6f7ff;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #1890ff;
            margin-bottom: 20px;
        }
        
        .konsultasi-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .sesi-item {
            margin-bottom: 25px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
        }
        
        .sesi-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .sesi-title {
            color: #2d3748;
            font-weight: 600;
            font-size: 16px;
            margin: 0;
        }
        
        .sesi-meta {
            color: #718096;
            font-size: 14px;
        }
        
        .content-box {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            margin-bottom: 10px;
        }
        
        .content-label {
            font-size: 13px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .content-value {
            font-size: 14px;
            color: #2d3748;
            line-height: 1.6;
        }
        
        .footer-laporan {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #718096;
            font-size: 12px;
        }
        
        .ttd-section {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
        }
        
        .ttd-item {
            text-align: center;
            width: 200px;
        }
        
        .ttd-line {
            margin-top: 60px;
            border-top: 1px solid #000;
            width: 100%;
        }
        
        .ttd-name {
            margin-top: 5px;
            font-weight: 600;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 14px;
        }
        
        th {
            background: #f7fafc;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #e2e8f0;
            color: #4a5568;
            font-weight: 600;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        
        tr:hover {
            background: #f7fafc;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-selesai {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .badge-proses {
            background: #fed7d7;
            color: #742a2a;
        }
        
        .badge-terlaksana {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .badge-dibatalkan {
            background: #fed7d7;
            color: #742a2a;
        }
        
        .badge-ditunda {
            background: #fef3c7;
            color: #92400e;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #a0aec0;
            font-style: italic;
        }
    </style>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
    <div class="no-print">
        <div class="print-controls">
            <button onclick="window.print()" class="btn btn-print">
                <i class='bx bx-printer'></i>
                Cetak Laporan
            </button>
            <button onclick="window.history.back()" class="btn btn-back">
                <i class='bx bx-arrow-back'></i>
                Kembali
            </button>
            <button onclick="window.location.href='sesi_konsultasi.php'" class="btn">
                <i class='bx bx-conversation'></i>
                Ke Sesi Konsultasi
            </button>
        </div>
        
        <div class="filter-info">
            <h4><i class='bx bx-filter-alt'></i> Filter yang Digunakan:</h4>
            <div class="info-grid">
                <?php if (!empty($siswa_nama)): ?>
                <div class="info-item">
                    <span class="info-label">Nama Siswa</span>
                    <span class="info-value"><?php echo htmlspecialchars($siswa_nama); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($siswa_id > 0): ?>
                <div class="info-item">
                    <span class="info-label">ID Siswa</span>
                    <span class="info-value"><?php echo $siswa_id; ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($konsultasi_id > 0): ?>
                <div class="info-item">
                    <span class="info-label">ID Konsultasi</span>
                    <span class="info-value"><?php echo $konsultasi_id; ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($tanggal_mulai) && !empty($tanggal_selesai)): ?>
                <div class="info-item">
                    <span class="info-label">Periode</span>
                    <span class="info-value"><?php echo date('d/m/Y', strtotime($tanggal_mulai)); ?> - <?php echo date('d/m/Y', strtotime($tanggal_selesai)); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="info-item">
                    <span class="info-label">Jumlah Data</span>
                    <span class="info-value"><?php echo $total_sesi; ?> sesi ditemukan</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="print-container">
        <div class="header-laporan">
            <h1>LAPORAN KONSULTASI BIMBINGAN KONSELING</h1>
            <h2>APLIKASI BIMBINGAN KONSELING - SEKOLAH</h2>
            <h2>Tahun Ajaran <?php echo date('Y'); ?>/<?php echo date('Y') + 1; ?></h2>
        </div>
        
        <div class="info-laporan">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Dicetak Oleh</span>
                    <span class="info-value"><?php echo htmlspecialchars($nama_guru_cetak); ?> (Guru BK)</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tanggal Cetak</span>
                    <span class="info-value"><?php echo date('d/m/Y H:i:s'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Jenis Laporan</span>
                    <span class="info-value">Laporan Sesi Konsultasi</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Jumlah Halaman</span>
                    <span class="info-value">1</span>
                </div>
            </div>
        </div>
        
        <div class="statistik-laporan">
            <div class="statistik-grid">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_sesi; ?></div>
                    <div class="stat-label">Total Sesi</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($siswa_unik); ?></div>
                    <div class="stat-label">Jumlah Siswa</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($konsultasi_unik); ?></div>
                    <div class="stat-label">Konsultasi</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_durasi_formatted; ?></div>
                    <div class="stat-label">Total Durasi</div>
                </div>
            </div>
        </div>
        
        <div class="data-laporan">
            <?php if ($total_sesi > 0): ?>
                <?php
                $grouped_data = [];
                foreach ($data_laporan as $sesi) {
                    $konsultasi_id = $sesi['konsultasi_id'];
                    if (!isset($grouped_data[$konsultasi_id])) {
                        $grouped_data[$konsultasi_id] = [
                            'info' => $sesi,
                            'sesi' => []
                        ];
                    }
                    $grouped_data[$konsultasi_id]['sesi'][] = $sesi;
                }
                
                $counter = 0;
                foreach ($grouped_data as $konsultasi_id => $group):
                    $counter++;
                    if ($counter > 1): ?>
                        <div class="page-break"></div>
                    <?php endif; ?>
                    
                    <div class="konsultasi-group">
                        <div class="section-title">
                            <i class='bx bx-conversation'></i>
                            KONSULTASI #<?php echo $group['info']['konsultasi_id']; ?>
                        </div>
                        
                        <div class="konsultasi-header">
                            <div class="konsultasi-info">
                                <div class="info-item">
                                    <span class="info-label">Nama Siswa</span>
                                    <span class="info-value"><?php echo htmlspecialchars($group['info']['nama_siswa']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Kelas/Jurusan</span>
                                    <span class="info-value"><?php echo htmlspecialchars($group['info']['KELAS']); ?> - <?php echo htmlspecialchars($group['info']['JURUSAN']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">NIS</span>
                                    <span class="info-value"><?php echo htmlspecialchars($group['info']['NIS'] ?? '-'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Guru BK</span>
                                    <span class="info-value"><?php echo htmlspecialchars($nama_guru_cetak); ?></span>
                                </div>
                            </div>
                            
                            <div class="konsultasi-info">
                                <div class="info-item">
                                    <span class="info-label">Topik Konsultasi</span>
                                    <span class="info-value"><?php echo htmlspecialchars($group['info']['TOPIK_KONSULTASI']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Tanggal Pengajuan</span>
                                    <span class="info-value"><?php echo date('d/m/Y', strtotime($group['info']['tanggal_pengajuan'])); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Status Konsultasi</span>
                                    <span class="badge <?php echo $group['info']['STATUS'] == 'Selesai' ? 'badge-selesai' : 'badge-proses'; ?>">
                                        <?php echo $group['info']['STATUS']; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if ($group['info']['DESKRIPSI_MASALAH']): ?>
                            <div class="content-box" style="margin-top: 10px;">
                                <div class="content-label">
                                    <i class='bx bx-edit-alt'></i>
                                    Deskripsi Masalah
                                </div>
                                <div class="content-value">
                                    <?php echo nl2br(htmlspecialchars($group['info']['DESKRIPSI_MASALAH'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="section-title">
                            <i class='bx bx-list-check'></i>
                            SESI KONSULTASI (<?php echo count($group['sesi']); ?> Sesi)
                        </div>
                        
                        <?php foreach ($group['sesi'] as $sesi): 
                            $jam_mulai_sesi = strtotime($sesi['JAM_MULAI']);
                            $jam_selesai_sesi = strtotime($sesi['JAM_SELESAI']);
                            $durasi_sesi = '';
                            if ($jam_mulai_sesi && $jam_selesai_sesi) {
                                $durasi_detik_sesi = $jam_selesai_sesi - $jam_mulai_sesi;
                                $durasi_sesi = formatDurasi($durasi_detik_sesi);
                            }
                        ?>
                            <div class="sesi-item">
                                <div class="sesi-header">
                                    <div>
                                        <h4 class="sesi-title">
                                            Sesi #<?php echo $sesi['SESI_KE']; ?>
                                            <span class="badge <?php 
                                                if ($sesi['STATUS_SESI'] == 'Terlaksana') echo 'badge-terlaksana';
                                                elseif ($sesi['STATUS_SESI'] == 'Dibatalkan') echo 'badge-dibatalkan';
                                                else echo 'badge-ditunda';
                                            ?>">
                                                <?php echo $sesi['STATUS_SESI']; ?>
                                            </span>
                                        </h4>
                                        <?php if ($durasi_sesi): ?>
                                        <div style="font-size: 13px; color: #4a5568; margin-top: 3px;">
                                            <i class='bx bx-time'></i> Durasi: <?php echo $durasi_sesi; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="sesi-meta">
                                        <?php echo date('d/m/Y', strtotime($sesi['TANGGAL_SESI'])); ?> | 
                                        <?php echo date('H:i', strtotime($sesi['JAM_MULAI'])); ?> - <?php echo date('H:i', strtotime($sesi['JAM_SELESAI'])); ?>
                                    </div>
                                </div>
                                
                                <?php if ($sesi['POKOK_PEMBAHASAN']): ?>
                                <div class="content-box">
                                    <div class="content-label">
                                        <i class='bx bx-message-detail'></i>
                                        Pokok Pembahasan
                                    </div>
                                    <div class="content-value">
                                        <?php echo nl2br(htmlspecialchars($sesi['POKOK_PEMBAHASAN'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($sesi['CATATAN_SESI']): ?>
                                <div class="content-box">
                                    <div class="content-label">
                                        <i class='bx bx-notepad'></i>
                                        Catatan Sesi
                                    </div>
                                    <div class="content-value">
                                        <?php echo nl2br(htmlspecialchars($sesi['CATATAN_SESI'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($sesi['TINDAK_LANJUT']): ?>
                                <div class="content-box">
                                    <div class="content-label">
                                        <i class='bx bx-trending-up'></i>
                                        Tindak Lanjut
                                    </div>
                                    <div class="content-value">
                                        <?php echo nl2br(htmlspecialchars($sesi['TINDAK_LANJUT'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($sesi['REKOMENDASI']): ?>
                                <div class="content-box">
                                    <div class="content-label">
                                        <i class='bx bx-bulb'></i>
                                        Rekomendasi
                                    </div>
                                    <div class="content-value">
                                        <?php echo nl2br(htmlspecialchars($sesi['REKOMENDASI'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if ($group['info']['SARAN_GURU'] || $group['info']['CATATAN_KONSULTASI']): ?>
                        <div class="section-title">
                            <i class='bx bx-note'></i>
                            RINGKASAN KONSULTASI
                        </div>
                        
                        <div class="sesi-item">
                            <?php if ($group['info']['SARAN_GURU']): ?>
                            <div class="content-box">
                                <div class="content-label">
                                    <i class='bx bx-message-detail'></i>
                                    Saran dan Masukan Guru
                                </div>
                                <div class="content-value">
                                    <?php echo nl2br(htmlspecialchars($group['info']['SARAN_GURU'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($group['info']['CATATAN_KONSULTASI']): ?>
                            <div class="content-box">
                                <div class="content-label">
                                    <i class='bx bx-notepad'></i>
                                    Catatan Konsultasi
                                </div>
                                <div class="content-value">
                                    <?php echo nl2br(htmlspecialchars($group['info']['CATATAN_KONSULTASI'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($group['info']['PERLU_TINDAK_LANJUT']): ?>
                            <div class="content-box">
                                <div class="content-label">
                                    <i class='bx bx-trending-up'></i>
                                    Status Tindak Lanjut
                                </div>
                                <div class="content-value">
                                    <strong><?php echo $group['info']['PERLU_TINDAK_LANJUT'] == 'Ya' ? 'PERLU TINDAK LANJUT' : 'TIDAK PERLU TINDAK LANJUT'; ?></strong>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
            <?php else: ?>
                <div class="no-data">
                    <h3><i class='bx bx-inbox'></i> Tidak Ada Data</h3>
                    <p>Tidak ditemukan data sesi konsultasi dengan filter yang dipilih.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="footer-laporan">
            <div class="ttd-section">
                <div class="ttd-item">
                    <div class="ttd-line"></div>
                    <div class="ttd-name"><?php echo htmlspecialchars($nama_guru_cetak); ?></div>
                    <div>Guru Bimbingan Konseling</div>
                </div>
                
                <div class="ttd-item">
                    <div class="ttd-line"></div>
                    <div class="ttd-name">Kepala Sekolah</div>
                    <div>(_______________________)</div>
                </div>
            </div>
            
            <div style="margin-top: 30px; font-size: 10px; color: #a0aec0;">
                Dokumen ini dicetak secara otomatis dari Sistem Aplikasi Bimbingan Konseling<br>
                <?php echo date('d/m/Y H:i:s'); ?> | Halaman 1
            </div>
        </div>
    </div>
    
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('print') === 'true') {
            window.print();
        }
        
        window.onbeforeprint = function() {
            const totalPages = Math.ceil(document.querySelectorAll('.konsultasi-group').length / 1);
            const pageElements = document.querySelectorAll('.footer-laporan div:last-child');
            pageElements.forEach(el => {
                el.innerHTML = `Dokumen ini dicetak secara otomatis dari Sistem Aplikasi Bimbingan Konseling<br>
                              ${new Date().toLocaleDateString('id-ID')} ${new Date().toLocaleTimeString('id-ID')} | Halaman 1 dari ${totalPages}`;
            });
        };
    </script>
</body>
</html>