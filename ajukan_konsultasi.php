<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Siswa') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

$sql_siswa = "SELECT s.ID as SISWA_ID, s.*, u.NAMA_LENGKAP 
              FROM siswa s 
              JOIN users u ON s.USER_ID = u.ID 
              WHERE s.USER_ID = ?";
$stmt_siswa = $koneksi->prepare($sql_siswa);
$stmt_siswa->bind_param("i", $user_id);
$stmt_siswa->execute();
$siswa = $stmt_siswa->get_result()->fetch_assoc();

if (!$siswa) {
    header("Location: data_diri.php");
    exit();
}

$siswa_id = $siswa['SISWA_ID'];

$sql_guru = "SELECT g.*, u.NAMA_LENGKAP 
             FROM guru_bk g 
             JOIN users u ON g.USER_ID = u.ID 
             WHERE u.STATUS = 'Aktif'";
$stmt_guru = $koneksi->prepare($sql_guru);
$stmt_guru->execute();
$guru_list = $stmt_guru->get_result()->fetch_all(MYSQLI_ASSOC);

function generateKodeKonsultasi($koneksi) {
    $prefix = "KONS";
    $date = date('Ymd');
    
    $sql = "SELECT COUNT(*) as total FROM konsultasi WHERE DATE(CREATED_AT) = CURDATE()";
    $result = $koneksi->query($sql);
    $row = $result->fetch_assoc();
    $sequence = $row['total'] + 1;
    
    return $prefix . $date . str_pad($sequence, 3, '0', STR_PAD_LEFT);
}

function isGuruValid($guru_id, $guru_list) {
    foreach ($guru_list as $guru) {
        if ($guru['ID'] == $guru_id) {
            return true;
        }
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $topik_konsultasi = trim($_POST['topik_konsultasi']);
    $deskripsi_masalah = trim($_POST['deskripsi_masalah']);
    $prioritas = $_POST['prioritas'];
    $mode_konsultasi = $_POST['mode_konsultasi'] ?? '';
    $pilihan_guru_1 = $_POST['pilihan_guru_1'];
    $pilihan_guru_2 = $_POST['pilihan_guru_2'];
    
    if (empty($topik_konsultasi) || empty($deskripsi_masalah) || empty($prioritas) || empty($mode_konsultasi) ||
        empty($pilihan_guru_1) || empty($pilihan_guru_2)) {
        $error = "Semua field wajib diisi!";
    } 
    else if (!in_array($mode_konsultasi, ['Offline', 'Online'], true)) {
        $error = "Mode konsultasi tidak valid!";
    }
    else if ($pilihan_guru_1 == $pilihan_guru_2) {
        $error = "Pilihan guru 1 dan guru 2 tidak boleh sama!";
    } 
    else if (!isGuruValid($pilihan_guru_1, $guru_list) || !isGuruValid($pilihan_guru_2, $guru_list)) {
        $error = "Pilihan guru tidak valid!";
    }
    else {
        $kode_konsultasi = generateKodeKonsultasi($koneksi);
        $tanggal_pengajuan = date('Y-m-d');
        
        $sql_insert = "INSERT INTO konsultasi 
                      (SISWA_ID, KODE_KONSULTASI, TANGGAL_PENGAJUAN, TOPIK_KONSULTASI,
                       DESKRIPSI_MASALAH, PRIORITAS, MODE_KONSULTASI, PILIHAN_GURU_1, PILIHAN_GURU_2, STATUS) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Menunggu')";
        $stmt_insert = $koneksi->prepare($sql_insert);
        $stmt_insert->bind_param("issssssii", 
            $siswa_id, $kode_konsultasi, $tanggal_pengajuan, $topik_konsultasi,
            $deskripsi_masalah, $prioritas, $mode_konsultasi, $pilihan_guru_1, $pilihan_guru_2
        );
        
        if ($stmt_insert->execute()) {
            header("Location: struk_konsultasi.php?kode=" . $kode_konsultasi);
            exit();
        } else {
            $error = "Gagal mengajukan konsultasi! Error: " . $koneksi->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajukan Konsultasi - APK BK</title>
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
            max-width: 1000px;
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
        
        .alert {
            padding: 20px;
            border-radius: 12px;
            margin: 20px;
            font-weight: 500;
            border: 2px solid transparent;
            animation: slideUp 0.6s ease-out;
        }
        
        .alert-success {
            background: rgba(72, 187, 120, 0.1);
            border-color: rgba(72, 187, 120, 0.3);
            color: #38a169;
        }
        
        .alert-error {
            background: rgba(245, 101, 101, 0.1);
            border-color: rgba(245, 101, 101, 0.3);
            color: #e53e3e;
        }
        
        .alert-info {
            background: rgba(66, 153, 225, 0.1);
            border-color: rgba(66, 153, 225, 0.3);
            color: #3182ce;
        }
        
        .student-info {
            background: rgba(102, 126, 234, 0.05);
            padding: 25px;
            border-radius: 16px;
            margin: 20px;
            border: 1px solid rgba(102, 126, 234, 0.15);
            animation: fadeIn 0.8s ease-out;
        }
        
        .student-info h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .student-info p {
            color: #718096;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-content {
            padding: 30px;
        }
        
        .form-section {
            margin-bottom: 40px;
            animation: slideUp 0.6s ease-out;
            background: rgba(255, 251, 240, 0.8);
            border-radius: 16px;
            padding: 25px;
            border: 1px solid rgba(102, 126, 234, 0.15);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.08);
        }
        
        .section-title {
            color: #2d3748;
            font-size: 22px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(102, 126, 234, 0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 10px;
            color: #4a5568;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 15px 20px;
            background: rgba(255, 253, 245, 0.9);
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 12px;
            color: #2d3748;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }
        
        select option {
            background-color: white;
            color: #2d3748;
            padding: 12px;
        }
        
        ::placeholder {
            color: #a0aec0;
            opacity: 1;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.2);
            background: rgba(255, 253, 245, 0.95);
            transform: translateY(-2px);
        }
        
        textarea {
            resize: vertical;
            min-height: 120px;
            line-height: 1.6;
        }
        
        .info-text {
            color: #718096;
            font-size: 13px;
            margin-top: 8px;
            font-style: italic;
        }
        
        .guru-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .guru-option {
            background: rgba(102, 126, 234, 0.05);
            padding: 15px;
            border-radius: 12px;
            border-left: 4px solid #667eea;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }
        
        .guru-option strong {
            color: #667eea;
            font-size: 14px;
        }
        
        .guru-option small {
            color: #718096;
            font-size: 12px;
        }
        
        .selection-error {
            background: rgba(245, 101, 101, 0.1);
            border: 1px solid rgba(245, 101, 101, 0.3);
            color: #e53e3e;
            padding: 12px;
            border-radius: 8px;
            margin-top: 10px;
            display: none;
            font-size: 14px;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 18px 40px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.4s ease;
            width: 100%;
            margin-top: 20px;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .submit-btn:hover::before {
            left: 100%;
        }
        
        .submit-btn:hover:not(:disabled) {
            background: linear-gradient(135deg, #764ba2, #667eea);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4);
        }
        
        .submit-btn:disabled {
            background: linear-gradient(135deg, #a0aec0, #718096);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
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
            
            .form-content {
                padding: 20px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .form-section {
                margin-bottom: 30px;
                padding: 20px;
            }
            
            .section-title {
                font-size: 20px;
            }
            
            .student-info {
                padding: 20px;
                margin: 15px;
            }
            
            .guru-list {
                grid-template-columns: 1fr;
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
            
            .form-content {
                padding: 15px;
            }
            
            input, select, textarea {
                padding: 12px 15px;
                font-size: 14px;
            }
            
            .submit-btn {
                padding: 15px 25px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
 
    <div class="floating"></div>
    <div class="floating"></div>
    <div class="floating"></div>
    
    <div class="header">
        <h1><i class='bx bx-message-square-add'></i> Ajukan Konsultasi</h1>
        <a href="jadwal_guru.php" class="back-btn">
            <i class='bx bx-arrow-back'></i>
            Kembali ke Jadwal
        </a>
    </div>
    
    <div class="container">
        <div class="content">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class='bx bx-error-circle'></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="student-info">
                <h3><i class='bx bx-user'></i> Informasi Siswa</h3>
                <p><strong>Nama:</strong> <?php echo htmlspecialchars($siswa['NAMA_LENGKAP']); ?></p>
                <p><strong>Kelas:</strong> <?php echo htmlspecialchars($siswa['KELAS'] ?? '-'); ?></p>
                <p><strong>Jurusan:</strong> <?php echo htmlspecialchars($siswa['JURUSAN'] ?? '-'); ?></p>
            </div>

            <div class="alert alert-info">
                <i class='bx bx-info-circle'></i>
                <strong>Informasi:</strong> Silakan isi form di bawah untuk mengajukan konsultasi dengan guru BK. 
                Pilih 2 guru BK yang Anda inginkan dan jelaskan masalah yang ingin dikonsultasikan.
            </div>
            
            <form method="POST" action="" id="formKonsultasi" class="form-content">
                <div class="form-section">
                    <h3 class="section-title"><i class='bx bx-edit-alt'></i> Informasi Konsultasi</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Topik Konsultasi *</label>
                            <input type="text" name="topik_konsultasi" 
                                   value="<?php echo htmlspecialchars($_POST['topik_konsultasi'] ?? ''); ?>" 
                                   required 
                                   placeholder="Contoh: Masalah Belajar, Konflik Teman, Rencana Karir">
                            <div class="info-text">Jelaskan secara singkat topik konsultasi</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Prioritas *</label>
                            <select name="prioritas" required>
                                <option value="">Pilih Prioritas</option>
                                <option value="Rendah" <?php echo ($_POST['prioritas'] ?? '') == 'Rendah' ? 'selected' : ''; ?>>Rendah</option>
                                <option value="Sedang" <?php echo ($_POST['prioritas'] ?? '') == 'Sedang' ? 'selected' : ''; ?>>Sedang</option>
                                <option value="Tinggi" <?php echo ($_POST['prioritas'] ?? '') == 'Tinggi' ? 'selected' : ''; ?>>Tinggi</option>
                                <option value="Darurat" <?php echo ($_POST['prioritas'] ?? '') == 'Darurat' ? 'selected' : ''; ?>>Darurat</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Mode Konsultasi *</label>
                            <select name="mode_konsultasi" required>
                                <option value="">Pilih Mode</option>
                                <option value="Offline" <?php echo ($_POST['mode_konsultasi'] ?? '') == 'Offline' ? 'selected' : ''; ?>>Offline (Tatap Muka)</option>
                                <option value="Online" <?php echo ($_POST['mode_konsultasi'] ?? '') == 'Online' ? 'selected' : ''; ?>>Online (Google Meet/Zoom)</option>
                            </select>
                            <div class="info-text">Jika memilih online, link meeting akan diberikan setelah disetujui</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Deskripsi Masalah *</label>
                        <textarea name="deskripsi_masalah" required 
                                  placeholder="Jelaskan secara detail masalah yang ingin dikonsultasikan..."><?php echo htmlspecialchars($_POST['deskripsi_masalah'] ?? ''); ?></textarea>
                        <div class="info-text">Ceritakan masalah Anda dengan jelas dan detail</div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title"><i class='bx bx-user-voice'></i> Pilihan Guru BK</h3>
                    <div class="alert alert-info">
                        <i class='bx bx-info-circle'></i>
                        <strong>Perhatian:</strong> Pilih 2 guru BK yang berbeda. Konselor akan menyesuaikan dengan jadwal dan ketersediaan.
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Pilihan Guru 1 *</label>
                            <select name="pilihan_guru_1" required id="pilihan_guru_1">
                                <option value="">Pilih Guru BK</option>
                                <?php foreach ($guru_list as $guru): ?>
                                    <option value="<?php echo $guru['ID']; ?>" 
                                        <?php echo ($_POST['pilihan_guru_1'] ?? '') == $guru['ID'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($guru['NAMA_LENGKAP']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Pilihan Guru 2 *</label>
                            <select name="pilihan_guru_2" required id="pilihan_guru_2">
                                <option value="">Pilih Guru BK</option>
                                <?php foreach ($guru_list as $guru): ?>
                                    <option value="<?php echo $guru['ID']; ?>" 
                                        <?php echo ($_POST['pilihan_guru_2'] ?? '') == $guru['ID'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($guru['NAMA_LENGKAP']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div id="selection-error" class="selection-error">
                        <i class='bx bx-error'></i> Pilihan guru 1 dan guru 2 tidak boleh sama!
                    </div>
                    
                    <?php if (!empty($guru_list)): ?>
                    <div class="form-group">
                        <label>Daftar Guru BK Tersedia:</label>
                        <div class="guru-list">
                            <?php foreach ($guru_list as $guru): ?>
                                <div class="guru-option">
                                    <strong><?php echo htmlspecialchars($guru['NAMA_LENGKAP']); ?></strong><br>
                                    <small>NIP: <?php echo htmlspecialchars($guru['NIP']); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php else: ?>
                        <div class="alert alert-error">
                            <i class='bx bx-error-circle'></i> Tidak ada guru BK yang tersedia saat ini. Silakan hubungi administrator.
                        </div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="submit-btn" id="submit-btn">
                    <i class='bx bx-send'></i>
                    Ajukan Konsultasi
                </button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('formKonsultasi').addEventListener('submit', function(e) {
            const guru1 = document.getElementById('pilihan_guru_1').value;
            const guru2 = document.getElementById('pilihan_guru_2').value;
            
            if (!guru1 || !guru2) {
                e.preventDefault();
                alert('Harus memilih kedua pilihan guru!');
                return false;
            }
            
            if (guru1 === guru2) {
                e.preventDefault();
                alert('Pilihan guru 1 dan guru 2 tidak boleh sama! Silakan pilih guru yang berbeda.');
                return false;
            }
            
            if (!confirm('Apakah Anda yakin ingin mengajukan konsultasi ini?')) {
                e.preventDefault();
                return false;
            }
        });

        function updateGuruOptions() {
            const guru1Select = document.getElementById('pilihan_guru_1');
            const guru2Select = document.getElementById('pilihan_guru_2');
            const selectedGuru1 = guru1Select.value;
            
            for (let i = 0; i < guru2Select.options.length; i++) {
                const option = guru2Select.options[i];
                option.disabled = false;
                option.style.color = '#2d3748';
            }
            
            if (selectedGuru1) {
                for (let i = 0; i < guru2Select.options.length; i++) {
                    const option = guru2Select.options[i];
                    if (option.value === selectedGuru1) {
                        option.disabled = true;
                        option.style.color = '#a0aec0';
                    }
                }
                
                if (guru2Select.value === selectedGuru1) {
                    guru2Select.value = '';
                }
            }
            
            validateGuruSelection();
        }

        document.getElementById('pilihan_guru_1').addEventListener('change', updateGuruOptions);
        document.getElementById('pilihan_guru_2').addEventListener('change', validateGuruSelection);

        function validateGuruSelection() {
            const guru1 = document.getElementById('pilihan_guru_1');
            const guru2 = document.getElementById('pilihan_guru_2');
            const errorDiv = document.getElementById('selection-error');
            const submitBtn = document.getElementById('submit-btn');
            const errorColor = '#e53e3e';
            const successColor = '#667eea';
            
            if (guru1.value && guru2.value && guru1.value === guru2.value) {
                guru1.style.borderColor = errorColor;
                guru1.style.boxShadow = '0 0 10px rgba(229, 62, 62, 0.3)';
                guru2.style.borderColor = errorColor;
                guru2.style.boxShadow = '0 0 10px rgba(229, 62, 62, 0.3)';
                errorDiv.style.display = 'block';
                submitBtn.disabled = true;
            } else {
                guru1.style.borderColor = successColor;
                guru1.style.boxShadow = '0 0 15px rgba(102, 126, 234, 0.2)';
                guru2.style.borderColor = successColor;
                guru2.style.boxShadow = '0 0 15px rgba(102, 126, 234, 0.2)';
                errorDiv.style.display = 'none';
                submitBtn.disabled = false;
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            updateGuruOptions();
            validateGuruSelection();
            
            const formSections = document.querySelectorAll('.form-section');
            formSections.forEach((section, index) => {
                section.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>
