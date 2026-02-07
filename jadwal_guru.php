<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Siswa') {
    header("Location: index.php");
    exit();
}

date_default_timezone_set('Asia/Jakarta');

$hari_ini_inggris = date('l');
$hari_indonesia_map = [
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu', 
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu',
    'Sunday' => 'Minggu'
];
$hari_ini_nama = $hari_indonesia_map[$hari_ini_inggris] ?? '';
$current_time = date('H:i:s');

error_log("Hari ini: $hari_ini_inggris -> $hari_ini_nama");
error_log("Waktu sekarang: $current_time");

if (!empty($hari_ini_nama)) {
    $sql_delete_today = "DELETE FROM jadwal_konsultasi 
                         WHERE HARI = ? AND JAM_SELESAI < ? AND AKTIF = 'Ya'";
    $stmt_delete_today = $koneksi->prepare($sql_delete_today);
    $stmt_delete_today->bind_param("ss", $hari_ini_nama, $current_time);
    
    if ($stmt_delete_today->execute()) {
        $deleted_today = $stmt_delete_today->affected_rows;
        error_log("Jadwal hari ini dihapus: $deleted_today");
    } else {
        error_log("Error hapus jadwal hari ini: " . $koneksi->error);
    }
}

$hari_order = ['Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4, 'Jumat' => 5];
$hari_ini_index = isset($hari_order[$hari_ini_nama]) ? $hari_order[$hari_ini_nama] : 0;

foreach ($hari_order as $hari => $index) {
    if ($index < $hari_ini_index) {
        $sql_delete_past = "DELETE FROM jadwal_konsultasi 
                           WHERE HARI = ? AND AKTIF = 'Ya'";
        $stmt_delete_past = $koneksi->prepare($sql_delete_past);
        $stmt_delete_past->bind_param("s", $hari);
        
        if ($stmt_delete_past->execute()) {
            $deleted_past = $stmt_delete_past->affected_rows;
            error_log("Jadwal $hari dihapus: $deleted_past");
        }
    }
}

$hari_map = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

$sql_jadwal = "SELECT j.*, u.NAMA_LENGKAP as nama_guru 
               FROM jadwal_konsultasi j 
               JOIN guru_bk g ON j.GURU_BK_ID = g.ID 
               JOIN users u ON g.USER_ID = u.ID 
               WHERE j.AKTIF = 'Ya' 
               ORDER BY 
                   CASE j.HARI 
                       WHEN 'Senin' THEN 1
                       WHEN 'Selasa' THEN 2  
                       WHEN 'Rabu' THEN 3
                       WHEN 'Kamis' THEN 4
                       WHEN 'Jumat' THEN 5
                   END,
                   j.JAM_MULAI";
$result_jadwal = $koneksi->query($sql_jadwal);

$jadwal_list = [];
$jadwal_per_hari = [];

while($jadwal = $result_jadwal->fetch_assoc()) {
    $hari_jadwal = $jadwal['HARI'];
    
    $index_hari = array_search($hari_jadwal, $hari_map);
    $hari_ini_index = array_search($hari_ini_nama, $hari_map);
    
    if ($index_hari !== false) {
        if ($index_hari >= $hari_ini_index) {
            $jadwal_list[] = $jadwal;
            
            if (!isset($jadwal_per_hari[$hari_jadwal])) {
                $jadwal_per_hari[$hari_jadwal] = [];
            }
            $jadwal_per_hari[$hari_jadwal][] = $jadwal;
        }
    }
}

error_log("Total jadwal setelah filter: " . count($jadwal_list));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Guru BK - APK BK</title>
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
            overflow-x: hidden;
            position: relative;
        }
        
        .floating {
            position: fixed;
            border-radius: 50%;
            animation: float 4s ease-in-out infinite;
            z-index: 0;
            filter: blur(1px);
            opacity: 0.7;
        }
        
        .floating:nth-child(1) {
            width: 150px;
            height: 150px;
            top: 15%;
            left: 8%;
            background: radial-gradient(circle, rgba(255,255,255,0.8) 0%, rgba(255,255,255,0.3) 70%);
            animation-delay: 0s;
            box-shadow: 0 0 40px rgba(255,255,255,0.4);
        }
        
        .floating:nth-child(2) {
            width: 180px;
            height: 180px;
            top: 65%;
            right: 7%;
            background: radial-gradient(circle, rgba(255,255,255,0.7) 0%, rgba(255,255,255,0.2) 70%);
            animation-delay: 1.5s;
            box-shadow: 0 0 50px rgba(255,255,255,0.3);
        }
        
        .floating:nth-child(3) {
            width: 200px;
            height: 200px;
            bottom: 15%;
            left: 35%;
            background: radial-gradient(circle, rgba(255,255,255,0.6) 0%, rgba(255,255,255,0.25) 70%);
            animation-delay: 3s;
            box-shadow: 0 0 45px rgba(255,255,255,0.35);
        }
        
        @keyframes float {
            0%, 100% { 
                transform: translateY(0px) translateX(0px) rotate(0deg) scale(1);
                opacity: 0.6;
            }
            25% { 
                transform: translateY(-30px) translateX(15px) rotate(90deg) scale(1.05);
                opacity: 0.8;
            }
            50% { 
                transform: translateY(-15px) translateX(-10px) rotate(180deg) scale(1.1);
                opacity: 0.7;
            }
            75% { 
                transform: translateY(-25px) translateX(5px) rotate(270deg) scale(1.05);
                opacity: 0.9;
            }
        }
        
        .header { 
            background: rgba(255, 255, 255, 0.95);
            color: #2d3748; 
            padding: 20px 40px; 
            display: flex; 
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #667eea;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.1);
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
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.05), transparent);
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
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .back-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }
        
        .back-btn:hover::before {
            left: 100%;
        }
        
        .back-btn:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            position: relative;
            z-index: 5;
        }
        
        .content {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            border: 1px solid rgba(102, 126, 234, 0.1);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.1);
            backdrop-filter: blur(15px);
            overflow: hidden;
            animation: fadeIn 0.8s ease-out;
        }
        
        .page-header {
            background: rgba(102, 126, 234, 0.05);
            padding: 30px;
            border-radius: 16px;
            margin: 20px;
            border: 1px solid rgba(102, 126, 234, 0.15);
            animation: fadeIn 0.8s ease-out;
        }
        
        .page-header h2 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
        }
        
        .page-header p {
            color: #718096;
            font-size: 16px;
            line-height: 1.6;
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin: 20px;
            padding: 20px;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 16px;
            border: 1px solid rgba(102, 126, 234, 0.15);
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.8);
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 10px;
            color: #718096;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 14px;
        }
        
        .filter-tab:hover {
            border-color: #667eea;
            color: #667eea;
        }
        
        .filter-tab.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: #667eea;
        }
        
        .hari-section {
            margin: 20px;
        }
        
        .hari-header {
            background: rgba(102, 126, 234, 0.1);
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }
        
        .hari-header h3 {
            color: #667eea;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .jadwal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .jadwal-card {
            background: rgba(255, 251, 240, 0.8);
            border-radius: 16px;
            border: 1px solid rgba(102, 126, 234, 0.15);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.08);
            padding: 25px;
            transition: all 0.3s ease;
            animation: slideUp 0.6s ease-out;
        }
        
        .jadwal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.15);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(102, 126, 234, 0.1);
        }
        
        .nama-guru {
            font-size: 20px;
            font-weight: 600;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .jadwal-info {
            margin-bottom: 15px;
        }
        
        .info-label {
            font-size: 12px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .info-value {
            font-size: 16px;
            color: #2d3748;
            font-weight: 500;
        }
        
        .btn-konsultasi {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 12px;
            text-decoration: none;
            text-align: center;
            display: block;
            margin-top: 20px;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 16px;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-konsultasi::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-konsultasi:hover::before {
            left: 100%;
        }
        
        .btn-konsultasi:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .status-hari-ini {
            background: rgba(72, 187, 120, 0.1);
            color: #38a169;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
            border: 1px solid rgba(72, 187, 120, 0.3);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 40px;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 16px;
            margin: 20px;
            border: 2px dashed rgba(102, 126, 234, 0.3);
            animation: fadeIn 0.8s ease-out;
        }
        
        .empty-state h3 {
            color: #667eea;
            font-size: 24px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .empty-state p {
            color: #718096;
            margin-bottom: 10px;
            line-height: 1.6;
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
            
            .header h1 {
                font-size: 24px;
            }
            
            .container {
                margin: 20px auto;
                padding: 0 15px;
            }
            
            .jadwal-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .jadwal-card {
                padding: 20px;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .page-header {
                padding: 25px 20px;
                margin: 15px;
            }
            
            .page-header h2 {
                font-size: 24px;
            }
            
            .filter-tabs {
                flex-direction: column;
                padding: 15px;
            }
            
            .filter-tab {
                width: 100%;
                text-align: center;
            }
            
            .floating {
                display: none;
            }
        }
        
        @media (max-width: 480px) {
            .header {
                padding: 12px 15px;
            }
            
            .header h1 {
                font-size: 20px;
            }
            
            .back-btn {
                padding: 10px 18px;
                font-size: 14px;
            }
            
            .jadwal-card {
                padding: 15px;
            }
            
            .btn-konsultasi {
                padding: 12px 20px;
                font-size: 14px;
            }
            
            .page-header {
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>

    <div class="floating"></div>
    <div class="floating"></div>
    <div class="floating"></div>
    
    <div class="header">
        <h1><i class='bx bx-calendar'></i> Jadwal Guru BK</h1>
        <a href="dashboard_siswa.php" class="back-btn">
            <i class='bx bx-arrow-back'></i>
            Kembali ke Dashboard
        </a>
    </div>
    
    <div class="container">
        <div class="content">
            <div class="page-header">
                <h2><i class='bx bx-time'></i> Jadwal Konsultasi Guru BK</h2>
                <p>Pilih jadwal konsultasi yang tersedia dari guru BK. Sistem otomatis menghapus jadwal yang sudah lewat.</p>
                <p style="color: #718096; font-size: 14px; margin-top: 8px;">
                    <i class='bx bx-info-circle'></i> Jadwal yang ditampilkan hanya untuk hari ini dan hari-hari mendatang.
                </p>
            </div>
            
            <?php if (count($jadwal_list) > 0): ?>
                <div class="filter-tabs">
                    <div class="filter-tab active" onclick="showAllJadwal()">Semua Jadwal</div>
                    <?php foreach ($hari_map as $hari): ?>
                        <?php 
                        $hari_index = array_search($hari, $hari_map);
                        $hari_ini_index = array_search($hari_ini_nama, $hari_map);
                        $is_past_day = ($hari_index < $hari_ini_index);
                        ?>
                        <?php if (!$is_past_day): ?>
                        <div class="filter-tab" onclick="filterByDay('<?php echo $hari; ?>')">
                            <?php echo $hari; ?>
                            <?php if ($hari === $hari_ini_nama): ?>
                                <span class="status-hari-ini" style="margin-left: 8px;">(Hari Ini)</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <div id="all-jadwal">
                    <?php foreach ($jadwal_per_hari as $hari => $jadwal_hari): ?>
                    <div class="hari-section" id="hari-<?php echo $hari; ?>">
                        <div class="hari-header">
                            <h3>
                                <i class='bx bx-calendar'></i>
                                <?php echo $hari; ?>
                                <?php if ($hari === $hari_ini_nama): ?>
                                    <span class="status-hari-ini">HARI INI</span>
                                <?php endif; ?>
                            </h3>
                        </div>
                        <div class="jadwal-grid">
                            <?php foreach ($jadwal_hari as $jadwal): ?>
                            <div class="jadwal-card">
                                <div class="card-header">
                                    <div class="nama-guru">
                                        <i class='bx bx-user'></i>
                                        <?php echo htmlspecialchars($jadwal['nama_guru']); ?>
                                    </div>
                                    <div style="color: #667eea; font-weight: 600;">
                                        <?php echo date('H:i', strtotime($jadwal['JAM_MULAI'])); ?> - <?php echo date('H:i', strtotime($jadwal['JAM_SELESAI'])); ?>
                                    </div>
                                </div>
                                
                                <div class="jadwal-info">
                                    <div class="info-label"><i class='bx bx-time'></i> Jam Konsultasi</div>
                                    <div class="info-value">
                                        <?php echo date('H:i', strtotime($jadwal['JAM_MULAI'])); ?> - <?php echo date('H:i', strtotime($jadwal['JAM_SELESAI'])); ?>
                                    </div>
                                </div>
                                
                                <?php if ($jadwal['KETERANGAN']): ?>
                                <div class="jadwal-info">
                                    <div class="info-label"><i class='bx bx-note'></i> Keterangan</div>
                                    <div class="info-value"><?php echo htmlspecialchars($jadwal['KETERANGAN']); ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <a href="ajukan_konsultasi.php?guru_id=<?php echo $jadwal['GURU_BK_ID']; ?>&jadwal_id=<?php echo $jadwal['ID']; ?>" class="btn-konsultasi">
                                    <i class='bx bx-edit'></i>
                                    Ajukan Konsultasi
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($jadwal_per_hari) === 0): ?>
                    <div class="empty-state">
                        <h3><i class='bx bx-calendar-x'></i> Tidak Ada Jadwal Aktif</h3>
                        <p>Tidak ada jadwal konsultasi yang aktif saat ini.</p>
                        <p style="color: #718096; margin-top: 10px;">
                            Silakan hubungi guru BK untuk informasi jadwal konsultasi
                        </p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3><i class='bx bx-calendar-x'></i> Belum Ada Jadwal Tersedia</h3>
                    <p>Tidak ada jadwal konsultasi yang tersedia saat ini.</p>
                    <p style="color: #718096; margin-top: 10px;">
                        Silakan hubungi guru BK untuk informasi jadwal konsultasi
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function showAllJadwal() {
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            
            document.querySelectorAll('.hari-section').forEach(section => {
                section.style.display = 'block';
            });
        }
        
        function filterByDay(day) {
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            
            document.querySelectorAll('.hari-section').forEach(section => {
                if (section.id === 'hari-' + day) {
                    section.style.display = 'block';
                } else {
                    section.style.display = 'none';
                }
            });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.jadwal-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>