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

$sql_belajar = "SELECT * FROM form_belajar WHERE SISWA_ID = ?";
$stmt_belajar = $koneksi->prepare($sql_belajar);
$stmt_belajar->bind_param("i", $siswa_id);
$stmt_belajar->execute();
$belajar = $stmt_belajar->get_result()->fetch_assoc();

$data_sudah_lengkap = false;
if ($belajar) {
    if (!empty($belajar['RATA_RATA_NILAI']) && !empty($belajar['RANKING_KELAS']) && 
        !empty($belajar['MATA_PELAJARAN_UNGGULAN']) && !empty($belajar['MATA_PELAJARAN_LEMAH']) &&
        !empty($belajar['WAKTU_BELAJAR_PERHARI']) && !empty($belajar['TEMPAT_BELAJAR_FAVORIT']) &&
        !empty($belajar['METODE_BELAJAR']) && !empty($belajar['TARGET_NILAI']) &&
        !empty($belajar['TARGET_RANKING']) && !empty($belajar['CITA_CITA_AKADEMIK'])) {
        $data_sudah_lengkap = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$data_sudah_lengkap) {
    $rata_rata_nilai = $_POST['rata_rata_nilai'];
    $ranking_kelas = $_POST['ranking_kelas'];
    $mata_pelajaran_unggulan = $_POST['mata_pelajaran_unggulan'];
    $mata_pelajaran_lemah = $_POST['mata_pelajaran_lemah'];
    $waktu_belajar_perhari = $_POST['waktu_belajar_perhari'];
    $tempat_belajar_favorit = $_POST['tempat_belajar_favorit'];
    $metode_belajar = $_POST['metode_belajar'];
    $kesulitan_belajar = $_POST['kesulitan_belajar'];
    $hambatan_belajar = $_POST['hambatan_belajar'];
    $target_nilai = $_POST['target_nilai'];
    $target_ranking = $_POST['target_ranking'];
    $cita_cita_akademik = $_POST['cita_cita_akademik'];
    
    error_log("Cita-cita Akademik: " . $cita_cita_akademik);
    
    if ($belajar) {
        $sql_update = "UPDATE form_belajar SET 
                      RATA_RATA_NILAI = ?, RANKING_KELAS = ?, MATA_PELAJARAN_UNGGULAN = ?, 
                      MATA_PELAJARAN_LEMAH = ?, WAKTU_BELAJAR_PERHARI = ?, TEMPAT_BELAJAR_FAVORIT = ?,
                      METODE_BELAJAR = ?, KESULITAN_BELAJAR = ?, HAMBATAN_BELAJAR = ?,
                      TARGET_NILAI = ?, TARGET_RANKING = ?, CITA_CITA_AKADEMIK = ?
                      WHERE SISWA_ID = ?";
        $stmt_update = $koneksi->prepare($sql_update);
        
        $stmt_update->bind_param("disssssssdisi", 
            $rata_rata_nilai,                   
            $ranking_kelas,                     
            $mata_pelajaran_unggulan,          
            $mata_pelajaran_lemah,               
            $waktu_belajar_perhari,              
            $tempat_belajar_favorit,            
            $metode_belajar,                    
            $kesulitan_belajar,                  
            $hambatan_belajar,                   
            $target_nilai,                       
            $target_ranking,                   
            $cita_cita_akademik,               
            $siswa_id                          
        );
        
        if ($stmt_update->execute()) {
            $success = "Data belajar berhasil diperbarui!";
            $stmt_belajar->execute();
            $belajar = $stmt_belajar->get_result()->fetch_assoc();
            $data_sudah_lengkap = true;
        } else {
            $error = "Gagal memperbarui data belajar! Error: " . $stmt_update->error;
        }
    } else {
        $sql_insert = "INSERT INTO form_belajar 
                      (SISWA_ID, RATA_RATA_NILAI, RANKING_KELAS, MATA_PELAJARAN_UNGGULAN,
                       MATA_PELAJARAN_LEMAH, WAKTU_BELAJAR_PERHARI, TEMPAT_BELAJAR_FAVORIT,
                       METODE_BELAJAR, KESULITAN_BELAJAR, HAMBATAN_BELAJAR,
                       TARGET_NILAI, TARGET_RANKING, CITA_CITA_AKADEMIK) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $koneksi->prepare($sql_insert);
        
        $stmt_insert->bind_param("idisssssssdis", 
            $siswa_id,                         
            $rata_rata_nilai,                   
            $ranking_kelas,                    
            $mata_pelajaran_unggulan,          
            $mata_pelajaran_lemah,              
            $waktu_belajar_perhari,           
            $tempat_belajar_favorit,          
            $metode_belajar,                   
            $kesulitan_belajar,                 
            $hambatan_belajar,                
            $target_nilai,                      
            $target_ranking,                    
            $cita_cita_akademik               
        );
        
        if ($stmt_insert->execute()) {
            $success = "Data belajar berhasil disimpan!";
            $stmt_belajar->execute();
            $belajar = $stmt_belajar->get_result()->fetch_assoc();
            $data_sudah_lengkap = true;
        } else {
            $error = "Gagal menyimpan data belajar! Error: " . $stmt_insert->error;
        }
    }
}
?> 

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Belajar Siswa - APK BK</title>
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
        
        .data-locked {
            text-align: center;
            padding: 40px;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 16px;
            margin: 20px;
            border: 2px dashed rgba(102, 126, 234, 0.3);
            animation: fadeIn 0.8s ease-out;
        }
        
        .data-locked h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .data-locked p {
            color: #718096;
            margin-bottom: 10px;
            line-height: 1.6;
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
        
        input[readonly], select[readonly], textarea[readonly],
        input:disabled, select:disabled, textarea:disabled {
            background: rgba(248, 246, 240, 0.8);
            color: #a0aec0;
            border-color: rgba(102, 126, 234, 0.1);
            cursor: not-allowed;
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
            }
            
            .section-title {
                font-size: 20px;
            }
            
            .student-info, .data-locked {
                padding: 30px 20px;
                margin: 15px;
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
        <h1><i class='bx bx-book'></i> Form Belajar Siswa</h1>
        <a href="dashboard_siswa.php" class="back-btn">
            <i class='bx bx-arrow-back'></i>
            Kembali ke Dashboard
        </a>
    </div>
    
    <div class="container">
        <div class="content">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class='bx bx-check-circle'></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
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
            
            <?php if ($data_sudah_lengkap): ?>
                <div class="data-locked">
                    <h3><i class='bx bx-lock-alt'></i> Data Belajar Sudah Lengkap</h3>
                    <p>Data belajar Anda tidak dapat diubah lagi untuk menjaga keaslian data.</p>
                    <p>Jika ada kesalahan data, silakan hubungi Konselor BK.</p>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="formBelajar" class="form-content">
                <div class="form-section">
                    <h3 class="section-title"><i class='bx bx-stats'></i> Prestasi Akademik</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Rata-rata Nilai *</label>
                            <input type="number" name="rata_rata_nilai" 
                                   value="<?php echo htmlspecialchars($belajar['RATA_RATA_NILAI'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : 'required'; ?> 
                                   min="0" max="100" step="0.01" placeholder="Contoh: 85.50">
                            <div class="info-text">Masukkan nilai rata-rata rapor</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Ranking Kelas *</label>
                            <input type="number" name="ranking_kelas" 
                                   value="<?php echo htmlspecialchars($belajar['RANKING_KELAS'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : 'required'; ?> 
                                   min="1" max="50" placeholder="Contoh: 5">
                            <div class="info-text">Peringkat Anda di kelas</div>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title"><i class='bx bx-target-lock'></i> Mata Pelajaran</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Mata Pelajaran Unggulan *</label>
                            <input type="text" name="mata_pelajaran_unggulan" 
                                   value="<?php echo htmlspecialchars($belajar['MATA_PELAJARAN_UNGGULAN'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : 'required'; ?> 
                                   placeholder="Contoh: Matematika, Pemrograman Web">
                            <div class="info-text">Mata pelajaran yang paling Anda kuasai</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Mata Pelajaran Lemah *</label>
                            <input type="text" name="mata_pelajaran_lemah" 
                                   value="<?php echo htmlspecialchars($belajar['MATA_PELAJARAN_LEMAH'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : 'required'; ?> 
                                   placeholder="Contoh: Bahasa Inggris, Fisika">
                            <div class="info-text">Mata pelajaran yang perlu ditingkatkan</div>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title"><i class='bx bx-time'></i> Kebiasaan Belajar</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Waktu Belajar per Hari *</label>
                            <select name="waktu_belajar_perhari" <?php echo $data_sudah_lengkap ? 'disabled' : 'required'; ?>>
                                <option value="">Pilih Waktu Belajar</option>
                                <option value="<1 jam" <?php echo ($belajar['WAKTU_BELAJAR_PERHARI'] ?? '') == '<1 jam' ? 'selected' : ''; ?>>Kurang dari 1 jam</option>
                                <option value="1-2 jam" <?php echo ($belajar['WAKTU_BELAJAR_PERHARI'] ?? '') == '1-2 jam' ? 'selected' : ''; ?>>1-2 jam</option>
                                <option value="2-3 jam" <?php echo ($belajar['WAKTU_BELAJAR_PERHARI'] ?? '') == '2-3 jam' ? 'selected' : ''; ?>>2-3 jam</option>
                                <option value=">3 jam" <?php echo ($belajar['WAKTU_BELAJAR_PERHARI'] ?? '') == '>3 jam' ? 'selected' : ''; ?>>Lebih dari 3 jam</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Tempat Belajar Favorit *</label>
                            <input type="text" name="tempat_belajar_favorit" 
                                   value="<?php echo htmlspecialchars($belajar['TEMPAT_BELAJAR_FAVORIT'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : 'required'; ?> 
                                   placeholder="Contoh: Kamar, Perpustakaan, Ruang Kelas">
                        </div>
                        
                        <div class="form-group">
                            <label>Metode Belajar *</label>
                            <input type="text" name="metode_belajar" 
                                   value="<?php echo htmlspecialchars($belajar['METODE_BELAJAR'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : 'required'; ?> 
                                   placeholder="Contoh: Mind Mapping, Diskusi Kelompok, Latihan Soal">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title"><i class='bx bx-error-circle'></i> Tantangan Belajar</h3>
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Kesulitan Belajar</label>
                            <textarea name="kesulitan_belajar" <?php echo $data_sudah_lengkap ? 'readonly' : ''; ?> 
                                      placeholder="Jelaskan kesulitan yang Anda hadapi dalam belajar (opsional)"><?php echo htmlspecialchars($belajar['KESULITAN_BELAJAR'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Hambatan Belajar</label>
                            <textarea name="hambatan_belajar" <?php echo $data_sudah_lengkap ? 'readonly' : ''; ?> 
                                      placeholder="Jelaskan hambatan yang mempengaruhi proses belajar Anda (opsional)"><?php echo htmlspecialchars($belajar['HAMBATAN_BELAJAR'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title"><i class='bx bx-trophy'></i> Target & Cita-cita</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Target Nilai *</label>
                            <input type="number" name="target_nilai" 
                                   value="<?php echo htmlspecialchars($belajar['TARGET_NILAI'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : 'required'; ?> 
                                   min="0" max="100" step="0.01" placeholder="Contoh: 90.00">
                        </div>
                        
                        <div class="form-group">
                            <label>Target Ranking *</label>
                            <input type="number" name="target_ranking" 
                                   value="<?php echo htmlspecialchars($belajar['TARGET_RANKING'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : 'required'; ?> 
                                   min="1" max="50" placeholder="Contoh: 3">
                        </div>
                        
                        <div class="form-group">
                            <label>Cita-cita Akademik *</label>
                            <input type="text" name="cita_cita_akademik" 
                                   value="<?php echo htmlspecialchars($belajar['CITA_CITA_AKADEMIK'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : 'required'; ?> 
                                   placeholder="Contoh: Kuliah di ITB, Lulus dengan IPK 3.5">
                        </div>
                    </div>
                </div>
                
                <?php if (!$data_sudah_lengkap): ?>
                    <button type="submit" class="submit-btn">
                        <i class='bx bx-save'></i>
                        Simpan Data Belajar
                    </button>
                    <div class="alert alert-info">
                        <i class='bx bx-info-circle'></i>
                        <strong>Perhatian:</strong> Data yang sudah disimpan tidak dapat diubah lagi. Pastikan semua data sudah benar sebelum menyimpan.
                    </div>
                <?php else: ?>
                    <button type="button" class="submit-btn" disabled>
                        <i class='bx bx-lock'></i>
                        Data Sudah Terkunci
                    </button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('formBelajar').addEventListener('submit', function(e) {
            <?php if ($data_sudah_lengkap): ?>
                e.preventDefault();
                alert('Data sudah lengkap dan tidak dapat diubah lagi. Silakan hubungi konselor BK jika ada kesalahan.');
            <?php endif; ?>
        });

        <?php if ($data_sudah_lengkap): ?>
            document.querySelectorAll('input, select, textarea').forEach(function(element) {
                if (element.tagName === 'SELECT') {
                    element.setAttribute('disabled', 'disabled');
                } else if (!element.hasAttribute('readonly')) {
                    element.setAttribute('readonly', 'readonly');
                }
            });
        <?php endif; ?>

        document.querySelector('input[name="rata_rata_nilai"]')?.addEventListener('change', function() {
            if (this.value < 0) this.value = 0;
            if (this.value > 100) this.value = 100;
        });

        document.querySelector('input[name="target_nilai"]')?.addEventListener('change', function() {
            if (this.value < 0) this.value = 0;
            if (this.value > 100) this.value = 100;
        });

        document.addEventListener('DOMContentLoaded', function() {
            const formSections = document.querySelectorAll('.form-section');
            formSections.forEach((section, index) => {
                section.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>