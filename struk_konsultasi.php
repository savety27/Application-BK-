<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Siswa') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['kode'])) {
    header("Location: ajukan_konsultasi.php");
    exit();
}

$kode_konsultasi = $_GET['kode'];
$konsultasi = null;
$error = '';

try {
    $sql_konsultasi = "SELECT 
        k.*, 
        u_siswa.NAMA_LENGKAP as NAMA_SISWA, 
        s.KELAS, 
        s.JURUSAN,
        u_guru1.NAMA_LENGKAP as GURU_1, 
        u_guru2.NAMA_LENGKAP as GURU_2,
        u_guru_penanggung.NAMA_LENGKAP as GURU_PENANGGUNG_JAWAB
    FROM konsultasi k
    JOIN siswa s ON k.SISWA_ID = s.ID
    JOIN users u_siswa ON s.USER_ID = u_siswa.ID
    LEFT JOIN guru_bk g1 ON k.PILIHAN_GURU_1 = g1.ID
    LEFT JOIN users u_guru1 ON g1.USER_ID = u_guru1.ID
    LEFT JOIN guru_bk g2 ON k.PILIHAN_GURU_2 = g2.ID
    LEFT JOIN users u_guru2 ON g2.USER_ID = u_guru2.ID
    LEFT JOIN guru_bk g_penanggung ON k.GURU_BK_ID = g_penanggung.ID
    LEFT JOIN users u_guru_penanggung ON g_penanggung.USER_ID = u_guru_penanggung.ID
    WHERE k.KODE_KONSULTASI = ?";
    
    $stmt_konsultasi = $koneksi->prepare($sql_konsultasi);
    $stmt_konsultasi->bind_param("s", $kode_konsultasi);
    $stmt_konsultasi->execute();
    $result = $stmt_konsultasi->get_result();
    $konsultasi = $result->fetch_assoc();

    if (!$konsultasi) {
        $sql_alternative = "SELECT 
            k.*, 
            s.KELAS, 
            s.JURUSAN
        FROM konsultasi k
        JOIN siswa s ON k.SISWA_ID = s.ID
        WHERE k.KODE_KONSULTASI = ?";
        
        $stmt_alt = $koneksi->prepare($sql_alternative);
        $stmt_alt->bind_param("s", $kode_konsultasi);
        $stmt_alt->execute();
        $result_alt = $stmt_alt->get_result();
        $konsultasi = $result_alt->fetch_assoc();

        if ($konsultasi) {
            $sql_siswa = "SELECT u.NAMA_LENGKAP 
                          FROM siswa s 
                          JOIN users u ON s.USER_ID = u.ID 
                          WHERE s.ID = ?";
            $stmt_siswa = $koneksi->prepare($sql_siswa);
            $stmt_siswa->bind_param("i", $konsultasi['SISWA_ID']);
            $stmt_siswa->execute();
            $siswa_data = $stmt_siswa->get_result()->fetch_assoc();
            $konsultasi['NAMA_SISWA'] = $siswa_data ? $siswa_data['NAMA_LENGKAP'] : 'Tidak Diketahui';

            $sql_guru1 = "SELECT u.NAMA_LENGKAP 
                          FROM guru_bk g 
                          JOIN users u ON g.USER_ID = u.ID 
                          WHERE g.ID = ?";
            $stmt_guru1 = $koneksi->prepare($sql_guru1);
            $stmt_guru1->bind_param("i", $konsultasi['PILIHAN_GURU_1']);
            $stmt_guru1->execute();
            $guru1_data = $stmt_guru1->get_result()->fetch_assoc();
            $konsultasi['GURU_1'] = $guru1_data ? $guru1_data['NAMA_LENGKAP'] : 'Tidak Diketahui';

            $sql_guru2 = "SELECT u.NAMA_LENGKAP 
                          FROM guru_bk g 
                          JOIN users u ON g.USER_ID = u.ID 
                          WHERE g.ID = ?";
            $stmt_guru2 = $koneksi->prepare($sql_guru2);
            $stmt_guru2->bind_param("i", $konsultasi['PILIHAN_GURU_2']);
            $stmt_guru2->execute();
            $guru2_data = $stmt_guru2->get_result()->fetch_assoc();
            $konsultasi['GURU_2'] = $guru2_data ? $guru2_data['NAMA_LENGKAP'] : 'Tidak Diketahui';
        }
    }

    if (!$konsultasi) {
        $error = "Konsultasi dengan kode '{$kode_konsultasi}' tidak ditemukan.";
    }

} catch (Exception $e) {
    $error = "Terjadi kesalahan: " . $e->getMessage();
}

if ($error || !$konsultasi) {
    $_SESSION['error'] = $error ?: "Data konsultasi tidak ditemukan.";
    header("Location: lihat_konsultasi.php");
    exit();
}

function getKonsultasiValue($data, $key, $default = '') {
    return isset($data[$key]) && !empty($data[$key]) ? $data[$key] : $default;
}

function getStatusBadge($status) {
    $badges = [
        'Menunggu' => 'status-pending',
        'Disetujui' => 'status-approved', 
        'Ditolak' => 'status-rejected',
        'Selesai' => 'status-selesai',
        'Dibatalkan' => 'status-cancelled'
    ];
    $icons = [
        'Menunggu' => 'bx bx-time',
        'Disetujui' => 'bx bx-check-circle',
        'Ditolak' => 'bx bx-x-circle',
        'Selesai' => 'bx bx-party',
        'Dibatalkan' => 'bx bx-block'
    ];
    
    $class = $badges[$status] ?? 'status-pending';
    $icon = $icons[$status] ?? 'bx bx-time';
    
    return "<span class='status-badge $class'><i class='$icon'></i> " . ($status ?: 'Menunggu') . "</span>";
}

function getPriorityBadge($prioritas) {
    $classes = [
        'Rendah' => 'priority-rendah',
        'Sedang' => 'priority-sedang',
        'Tinggi' => 'priority-tinggi',
        'Darurat' => 'priority-darurat'
    ];
    $icons = [
        'Rendah' => 'bx bx-chevron-down',
        'Sedang' => 'bx bx-minus',
        'Tinggi' => 'bx bx-chevron-up', 
        'Darurat' => 'bx bx-error'
    ];
    
    $prioritas = $prioritas ?: 'Rendah';
    $class = $classes[$prioritas] ?? 'priority-rendah';
    $icon = $icons[$prioritas] ?? 'bx bx-chevron-down';
    
    return "<span class='priority-badge $class'><i class='$icon'></i> $prioritas</span>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Konsultasi - APK BK</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Poppins', 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #434190 0%, #553c9a 100%);
            min-height: 100vh;
            color: #2d3748;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: auto;
        }
        
        .floating {
            position: fixed;
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
            z-index: 0;
            filter: blur(1px);
            opacity: 0.6;
        }
        
        .floating:nth-child(1) {
            width: 120px;
            height: 120px;
            top: 10%;
            left: 10%;
            background: radial-gradient(circle, rgba(255,255,255,0.8) 0%, rgba(255,255,255,0.3) 70%);
            animation-delay: 0s;
        }
        
        .floating:nth-child(2) {
            width: 150px;
            height: 150px;
            top: 70%;
            right: 10%;
            background: radial-gradient(circle, rgba(255,255,255,0.7) 0%, rgba(255,255,255,0.2) 70%);
            animation-delay: 2s;
        }
        
        .floating:nth-child(3) {
            width: 180px;
            height: 180px;
            bottom: 10%;
            left: 40%;
            background: radial-gradient(circle, rgba(255,255,255,0.6) 0%, rgba(255,255,255,0.25) 70%);
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% { 
                transform: translateY(0px) translateX(0px) rotate(0deg) scale(1);
            }
            33% { 
                transform: translateY(-20px) translateX(10px) rotate(120deg) scale(1.05);
            }
            66% { 
                transform: translateY(-10px) translateX(-5px) rotate(240deg) scale(1.1);
            }
        }
        
        .struk-container {
            max-width: 500px;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            border: 1px solid rgba(102, 126, 234, 0.1);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.2);
            overflow: hidden;
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 10;
            animation: fadeIn 0.8s ease-out;
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
        
        .struk-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .struk-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            animation: shimmer 3s ease-in-out infinite;
        }
        
        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .struk-header h1 {
            font-size: 24px;
            margin-bottom: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .kode-konsultasi {
            font-size: 18px;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.2);
            padding: 12px 24px;
            border-radius: 25px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }
        
        .struk-content {
            padding: 30px;
            background: #ffffff;
        }
        
        .info-item {
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid rgba(102, 126, 234, 0.1);
            transition: transform 0.3s ease;
        }
        
        .info-item:hover {
            transform: translateX(5px);
        }
        
        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .info-label {
            font-weight: 600;
            color: #667eea;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-value {
            font-size: 15px;
            color: #2d3748;
            line-height: 1.5;
            font-weight: 500;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            border: 1px solid;
        }
        
        .status-pending {
            background: rgba(255, 152, 0, 0.1);
            color: #ed8936;
            border-color: rgba(255, 152, 0, 0.3);
            animation: pulse 2s infinite;
        }
        
        .status-approved {
            background: rgba(72, 187, 120, 0.1);
            color: #38a169;
            border-color: rgba(72, 187, 120, 0.3);
        }
        
        .status-rejected {
            background: rgba(245, 101, 101, 0.1);
            color: #e53e3e;
            border-color: rgba(245, 101, 101, 0.3);
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .struk-footer {
            background: rgba(102, 126, 234, 0.05);
            padding: 25px;
            text-align: center;
            border-top: 2px dashed rgba(102, 126, 234, 0.3);
        }
        
        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        
        .btn {
            padding: 14px 20px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            flex: 1;
            transition: all 0.3s ease;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
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
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-secondary {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            border: 1px solid rgba(102, 126, 234, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .print-only {
            display: none;
        }
        
        .deskripsi-masalah {
            background: rgba(102, 126, 234, 0.05);
            padding: 18px;
            border-radius: 12px;
            border-left: 4px solid #667eea;
            margin-top: 10px;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }
        
        .priority-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            border: 1px solid;
            transition: transform 0.3s ease;
        }
        
        .priority-badge:hover {
            transform: scale(1.05);
        }
        
        .priority-rendah { 
            background: rgba(66, 153, 225, 0.1); 
            color: #3182ce; 
            border-color: rgba(66, 153, 225, 0.3); 
        }
        .priority-sedang { 
            background: rgba(255, 152, 0, 0.1); 
            color: #ed8936; 
            border-color: rgba(255, 152, 0, 0.3); 
        }
        .priority-tinggi { 
            background: rgba(245, 101, 101, 0.1); 
            color: #e53e3e; 
            border-color: rgba(245, 101, 101, 0.3); 
        }
        .priority-darurat { 
            background: rgba(159, 122, 234, 0.1); 
            color: #9f7aea; 
            border-color: rgba(159, 122, 234, 0.3); 
        }
        
        .guru-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(102, 126, 234, 0.1);
            margin-bottom: 10px;
            transition: transform 0.3s ease;
        }
        
        .guru-info:hover {
            transform: translateX(8px);
        }
        
        .guru-info:last-child {
            margin-bottom: 0;
        }
        
        .guru-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }
        
        .guru-details {
            flex: 1;
        }
        
        .guru-name {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 2px;
        }
        
        .guru-role {
            font-size: 12px;
            color: #718096;
        }
        
        .auto-refresh-info {
            margin-top: 15px;
            font-size: 13px;
            color: #718096;
            background: rgba(102, 126, 234, 0.05);
            padding: 12px;
            border-radius: 8px;
            border: 1px solid rgba(102, 126, 234, 0.1);
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }
        
        @media print {
            body {
                background: white !important;
                padding: 0;
            }
            .floating {
                display: none;
            }
            .struk-container {
                box-shadow: none;
                max-width: 100%;
                margin: 0;
                border: none;
                animation: none;
                background: white;
            }
            .btn-group, .auto-refresh-info {
                display: none;
            }
            .print-only {
                display: block;
                text-align: center;
                margin-top: 20px;
                font-size: 12px;
                color: #666;
            }
            .struk-header {
                background: #667eea !important;
            }
            .struk-header::before {
                display: none;
            }
        }
        
        @media (max-width: 576px) {
            body {
                padding: 15px;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .struk-content {
                padding: 20px;
            }
            
            .struk-header {
                padding: 25px 20px;
            }
            
            .struk-header h1 {
                font-size: 20px;
            }
            
            .struk-footer {
                padding: 20px;
            }
            
            .guru-info {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    
    <div class="floating"></div>
    <div class="floating"></div>
    <div class="floating"></div>
    
    <div class="struk-container">
        <div class="struk-header">
            <h1><i class='bx bx-message-square-check'></i> BUKTI PENGAJUAN KONSULTASI</h1>
            <div class="kode-konsultasi">
                <i class='bx bx-hash'></i>
                <?php echo htmlspecialchars($konsultasi['KODE_KONSULTASI']); ?>
            </div>
        </div>
        
        <div class="struk-content">
            <div class="info-item">
                <div class="info-label"><i class='bx bx-stats'></i> Status Pengajuan</div>
                <div class="info-value">
                    <?php if ($konsultasi['STATUS'] == 'Menunggu'): ?>
                        <span class="status-badge status-pending">
                            <i class='bx bx-time'></i> MENUNGGU APPROVAL
                        </span>
                    <?php elseif ($konsultasi['STATUS'] == 'Disetujui'): ?>
                        <span class="status-badge status-approved">
                            <i class='bx bx-check-circle'></i> DISETUJUI
                        </span>
                    <?php elseif ($konsultasi['STATUS'] == 'Ditolak'): ?>
                        <span class="status-badge status-rejected">
                            <i class='bx bx-x-circle'></i> DITOLAK
                        </span>
                    <?php else: ?>
                        <span class="status-badge status-pending">
                            <i class='bx bx-time'></i> <?php echo htmlspecialchars($konsultasi['STATUS']); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label"><i class='bx bx-calendar'></i> Tanggal Pengajuan</div>
                <div class="info-value"><?php echo date('d/m/Y', strtotime($konsultasi['TANGGAL_PENGAJUAN'])); ?></div>
            </div>
            
            <div class="info-item">
                <div class="info-label"><i class='bx bx-user'></i> Informasi Siswa</div>
                <div class="info-value">
                    <strong><?php echo htmlspecialchars($konsultasi['NAMA_SISWA']); ?></strong><br>
                    <span style="color: #718096; font-size: 14px;">
                        <?php echo htmlspecialchars($konsultasi['KELAS']); ?> - <?php echo htmlspecialchars($konsultasi['JURUSAN']); ?>
                    </span>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label"><i class='bx bx-target-lock'></i> Topik Konsultasi</div>
                <div class="info-value"><?php echo htmlspecialchars($konsultasi['TOPIK_KONSULTASI']); ?></div>
            </div>
            
            <div class="info-item">
                <div class="info-label"><i class='bx bx-bolt'></i> Prioritas</div>
                <div class="info-value">
                    <?php 
                    $prioritas_classes = [
                        'Rendah' => 'priority-rendah',
                        'Sedang' => 'priority-sedang', 
                        'Tinggi' => 'priority-tinggi',
                        'Darurat' => 'priority-darurat'
                    ];
                    $prioritas_icons = [
                        'Rendah' => 'bx bx-chevron-down',
                        'Sedang' => 'bx bx-minus', 
                        'Tinggi' => 'bx bx-chevron-up',
                        'Darurat'=> 'bx bx-error'
                    ];
                    $prioritas = $konsultasi['PRIORITAS'];
                    $icon = isset($prioritas_icons[$prioritas]) ? $prioritas_icons[$prioritas] : 'bx bx-chevron-down';
                    $class = isset($prioritas_classes[$prioritas]) ? $prioritas_classes[$prioritas] : 'priority-rendah';
                    ?>
                    <span class="priority-badge <?php echo $class; ?>">
                        <i class='<?php echo $icon; ?>'></i> <?php echo $prioritas; ?>
                    </span>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label"><i class='bx bx-note'></i> Deskripsi Masalah</div>
                <div class="deskripsi-masalah">
                    <div class="info-value"><?php echo nl2br(htmlspecialchars($konsultasi['DESKRIPSI_MASALAH'])); ?></div>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-label"><i class='bx bx-group'></i> Guru Pilihan</div>
                <div class="info-value">
                    <div class="guru-info">
                        <div class="guru-avatar">1</div>
                        <div class="guru-details">
                            <div class="guru-name"><?php echo htmlspecialchars($konsultasi['GURU_1']); ?></div>
                            <div class="guru-role">Pilihan Pertama</div>
                        </div>
                    </div>
                    <div class="guru-info">
                        <div class="guru-avatar">2</div>
                        <div class="guru-details">
                            <div class="guru-name"><?php echo htmlspecialchars($konsultasi['GURU_2']); ?></div>
                            <div class="guru-role">Pilihan Kedua</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($konsultasi['TANGGAL_KONSULTASI'])): ?>
            <div class="info-item">
                <div class="info-label"><i class='bx bx-time'></i> Jadwal Konsultasi</div>
                <div class="info-value">
                    <strong>
                        <?php echo date('d/m/Y', strtotime($konsultasi['TANGGAL_KONSULTASI'])); ?> 
                        pukul <?php echo date('H:i', strtotime($konsultasi['JAM_KONSULTASI'])); ?>
                    </strong>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($konsultasi['TEMPAT_KONSULTASI'])): ?>
            <div class="info-item">
                <div class="info-label"><i class='bx bx-map'></i> Tempat</div>
                <div class="info-value"><?php echo htmlspecialchars($konsultasi['TEMPAT_KONSULTASI']); ?></div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($konsultasi['KOMENTAR_GURU'])): ?>
            <div class="info-item">
                <div class="info-label"><i class='bx bx-message-detail'></i> Komentar Guru</div>
                <div class="deskripsi-masalah">
                    <div class="info-value"><?php echo nl2br(htmlspecialchars($konsultasi['KOMENTAR_GURU'])); ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="struk-footer">
            <div class="print-only">
                Dicetak pada: <?php echo date('d/m/Y H:i:s'); ?>
            </div>
            <div class="btn-group">
                <button onclick="window.print()" class="btn btn-secondary">
                    <i class='bx bx-printer'></i> Cetak Struk
                </button>
                <a href="dashboard_siswa.php" class="btn btn-primary">
                    <i class='bx bx-home'></i> Dashboard
                </a>
            </div>
            
            <?php if ($konsultasi['STATUS'] == 'Menunggu'): ?>
            <div class="auto-refresh-info">
                <i class='bx bx-refresh'></i>
                <em>Status akan diperbarui otomatis setiap 10 detik</em>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        <?php if ($konsultasi['STATUS'] == 'Menunggu'): ?>
        setInterval(function() {
            console.log('Auto-refreshing status...');
            location.reload();
        }, 10000);
        
        let countdown = 10;
        setInterval(function() {
            countdown--;
            if (countdown <= 0) countdown = 10;
            const timerElement = document.querySelector('.auto-refresh-info em');
            if (timerElement) {
                timerElement.innerHTML = `Status akan diperbarui dalam ${countdown} detik`;
            }
        }, 1000);
        <?php endif; ?>
        
        window.onbeforeprint = function() {
            const buttons = document.querySelector('.btn-group');
            const timer = document.querySelector('.auto-refresh-info');
            if (buttons) buttons.style.display = 'none';
            if (timer) timer.style.display = 'none';
        };
        
        window.onafterprint = function() {
            const buttons = document.querySelector('.btn-group');
            const timer = document.querySelector('.auto-refresh-info');
            if (buttons) buttons.style.display = 'flex';
            if (timer) timer.style.display = 'flex';
        };
    </script>
</body>
</html>