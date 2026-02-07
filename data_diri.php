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

$sql_siswa = "SELECT s.*, u.NAMA_LENGKAP, u.EMAIL, u.NO_TELEPON 
              FROM siswa s 
              JOIN users u ON s.USER_ID = u.ID 
              WHERE s.USER_ID = ?";
$stmt_siswa = $koneksi->prepare($sql_siswa);
$stmt_siswa->bind_param("i", $user_id);
$stmt_siswa->execute();
$siswa = $stmt_siswa->get_result()->fetch_assoc();

$data_sudah_lengkap = false;
if ($siswa) {
    if (!empty($siswa['NIS']) && !empty($siswa['NISN']) && !empty($siswa['JENIS_KELAMIN']) && 
        !empty($siswa['TEMPAT_LAHIR']) && !empty($siswa['TANGGAL_LAHIR']) && 
        !empty($siswa['ALAMAT']) && !empty($siswa['AGAMA']) && 
        !empty($siswa['KELAS']) && !empty($siswa['JURUSAN']) && !empty($siswa['ANGKATAN'])) {
        $data_sudah_lengkap = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$data_sudah_lengkap) {
    $nis = $_POST['nis'];
    $nisn = $_POST['nisn'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $tempat_lahir = $_POST['tempat_lahir'];
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $alamat = $_POST['alamat'];
    $agama = $_POST['agama'];
    $kelas = $_POST['kelas'];
    $jurusan = $_POST['jurusan'];
    $angkatan = $_POST['angkatan'];
    
    if (!preg_match('/^[0-9]{10}$/', $nisn)) {
        $error = "NISN harus terdiri dari 10 digit angka!";
    } else {
        if ($siswa) {
            $sql_update = "UPDATE siswa SET 
                          NIS = ?, NISN = ?, JENIS_KELAMIN = ?, TEMPAT_LAHIR = ?, 
                          TANGGAL_LAHIR = ?, ALAMAT = ?, AGAMA = ?, KELAS = ?, 
                          JURUSAN = ?, ANGKATAN = ? 
                          WHERE USER_ID = ?";
            $stmt_update = $koneksi->prepare($sql_update);
            $stmt_update->bind_param("ssssssssssi", $nis, $nisn, $jenis_kelamin, $tempat_lahir, 
                                   $tanggal_lahir, $alamat, $agama, $kelas, $jurusan, $angkatan, $user_id);
            
            if ($stmt_update->execute()) {
                $success = "Data diri berhasil diperbarui!";
                $stmt_siswa->execute();
                $siswa = $stmt_siswa->get_result()->fetch_assoc();
                $data_sudah_lengkap = true;
            } else {
                $error = "Gagal memperbarui data diri!";
            }
        } else {
            $sql_insert = "INSERT INTO siswa (USER_ID, NIS, NISN, JENIS_KELAMIN, TEMPAT_LAHIR, 
                           TANGGAL_LAHIR, ALAMAT, AGAMA, KELAS, JURUSAN, ANGKATAN) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert = $koneksi->prepare($sql_insert);
            $stmt_insert->bind_param("issssssssss", $user_id, $nis, $nisn, $jenis_kelamin, $tempat_lahir, 
                                   $tanggal_lahir, $alamat, $agama, $kelas, $jurusan, $angkatan);
            
            if ($stmt_insert->execute()) {
                $success = "Data diri berhasil disimpan!";
                $stmt_siswa->execute();
                $siswa = $stmt_siswa->get_result()->fetch_assoc();
                $data_sudah_lengkap = true;
            } else {
                $error = "Gagal menyimpan data diri!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Diri Siswa - APK BK</title>
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
            background: rgba(255, 255, 255, 0.8);
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
    
        input[type="date"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }
        
        input[type="date"]::-webkit-calendar-picker-indicator:hover {
            opacity: 1;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.2);
            background: rgba(255, 255, 255, 0.95);
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
            background: rgba(247, 250, 252, 0.6);
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
            
            .data-locked {
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
          

            .content {
                background: rgba(255, 253, 245, 0.95); 
                border-radius: 20px;
                border: 1px solid rgba(102, 126, 234, 0.1);
                box-shadow: 0 15px 35px rgba(102, 126, 234, 0.1);
                backdrop-filter: blur(15px);
                overflow: hidden;
                animation: fadeIn 0.8s ease-out;
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

            .form-section:hover {
                background: rgba(255, 251, 240, 0.95); 
                transform: translateY(-3px);
                box-shadow: 0 12px 30px rgba(102, 126, 234, 0.12);
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

            input:focus, select:focus, textarea:focus {
                outline: none;
                border-color: #667eea;
                box-shadow: 0 0 20px rgba(102, 126, 234, 0.2);
                background: rgba(255, 253, 245, 0.95); 
                transform: translateY(-2px);
            }

            input[readonly], select[readonly], textarea[readonly],
            input:disabled, select:disabled, textarea:disabled {
                background: rgba(248, 246, 240, 0.8); 
                color: #a0aec0;
                border-color: rgba(102, 126, 234, 0.1);
                cursor: not-allowed;
            }

            .data-locked {
                text-align: center;
                padding: 40px;
                background: rgba(255, 251, 240, 0.9);
                border-radius: 16px;
                margin: 20px;
                border: 2px dashed rgba(102, 126, 234, 0.3);
                animation: fadeIn 0.8s ease-out;
            }

            .alert {
                padding: 20px;
                border-radius: 12px;
                margin: 20px;
                font-weight: 500;
                border: 2px solid transparent;
                animation: slideUp 0.6s ease-out;
                background: rgba(255, 253, 245, 0.9); 
            }
        }
    </style>
</head>
<body>
    
    <div class="floating"></div>
    <div class="floating"></div>
    <div class="floating"></div>
    
    <div class="header">
        <h1><i class='bx bx-user'></i> Data Diri Siswa</h1>
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
            
            <?php if ($data_sudah_lengkap): ?>
                <div class="data-locked">
                    <h3><i class='bx bx-lock-alt'></i> Data Diri Sudah Lengkap</h3>
                    <p>Data diri Anda tidak dapat diubah lagi untuk menjaga keaslian data.</p>
                    <p>Jika ada kesalahan data, silakan hubungi Konselor BK.</p>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="formDataDiri" class="form-content">
                <div class="form-section">
                    <h3 class="section-title"><i class='bx bx-id-card'></i> Informasi Pribadi</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" value="<?php echo htmlspecialchars($siswa['NAMA_LENGKAP'] ?? $_SESSION['nama_lengkap']); ?>" readonly>
                            <div class="info-text">Nama tidak dapat diubah</div>
                        </div>
                        
                        <div class="form-group">
                            <label>NIS *</label>
                            <input type="text" name="nis" value="<?php echo htmlspecialchars($siswa['NIS'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : 'required'; ?> 
                                   placeholder="Masukkan NIS" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            <div class="info-text">NIS (boleh angka saja)</div>
                        </div>
                        
                        <div class="form-group">
                            <label>NISN *</label>
                            <input type="text" name="nisn" id="nisnInput" value="<?php echo htmlspecialchars($siswa['NISN'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : 'required'; ?> 
                                   placeholder="Masukkan 10 digit NISN" 
                                   maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            <div class="info-text">NISN harus 10 digit angka</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Jenis Kelamin *</label>
                            <select name="jenis_kelamin" <?php echo $data_sudah_lengkap ? 'disabled' : 'required'; ?>>
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="L" <?php echo ($siswa['JENIS_KELAMIN'] ?? '') == 'L' ? 'selected' : ''; ?>>Laki-laki</option>
                                <option value="P" <?php echo ($siswa['JENIS_KELAMIN'] ?? '') == 'P' ? 'selected' : ''; ?>>Perempuan</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title"><i class='bx bx-cake'></i> Data Kelahiran</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" value="<?php echo htmlspecialchars($siswa['TEMPAT_LAHIR'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : ''; ?> 
                                   placeholder="Masukkan tempat lahir">
                        </div>
                        
                        <div class="form-group">
                            <label>Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" value="<?php echo htmlspecialchars($siswa['TANGGAL_LAHIR'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : ''; ?>>
                        </div>
                        
                        <div class="form-group">
                            <label>Agama</label>
                            <select name="agama" <?php echo $data_sudah_lengkap ? 'disabled' : ''; ?>>
                                <option value="">Pilih Agama</option>
                                <option value="Islam" <?php echo ($siswa['AGAMA'] ?? '') == 'Islam' ? 'selected' : ''; ?>>Islam</option>
                                <option value="Kristen" <?php echo ($siswa['AGAMA'] ?? '') == 'Kristen' ? 'selected' : ''; ?>>Kristen</option>
                                <option value="Katolik" <?php echo ($siswa['AGAMA'] ?? '') == 'Katolik' ? 'selected' : ''; ?>>Katolik</option>
                                <option value="Hindu" <?php echo ($siswa['AGAMA'] ?? '') == 'Hindu' ? 'selected' : ''; ?>>Hindu</option>
                                <option value="Buddha" <?php echo ($siswa['AGAMA'] ?? '') == 'Buddha' ? 'selected' : ''; ?>>Buddha</option>
                                <option value="Konghucu" <?php echo ($siswa['AGAMA'] ?? '') == 'Konghucu' ? 'selected' : ''; ?>>Konghucu</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title"><i class='bx bx-home'></i> Alamat</h3>
                    <div class="form-group">
                        <label>Alamat Lengkap</label>
                        <textarea name="alamat" <?php echo $data_sudah_lengkap ? 'readonly' : ''; ?> 
                                  placeholder="Masukkan alamat lengkap"><?php echo htmlspecialchars($siswa['ALAMAT'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="form-section">
    <h3 class="section-title"><i class='bx bx-graduation'></i> Informasi Akademik</h3>
    <div class="form-grid">
        <div class="form-group">
            <label>Jurusan *</label>
            <select name="jurusan" id="jurusanSelect" <?php echo $data_sudah_lengkap ? 'disabled' : 'required'; ?>>
                <option value="">Pilih Jurusan</option>
                <option value="Rekayasa Perangkat Lunak" <?php echo ($siswa['JURUSAN'] ?? '') == 'Rekayasa Perangkat Lunak' ? 'selected' : ''; ?>>Rekayasa Perangkat Lunak</option>
                <option value="Teknik Instalasi Tenaga Listrik" <?php echo ($siswa['JURUSAN'] ?? '') == 'Teknik Instalasi Tenaga Listrik' ? 'selected' : ''; ?>>Teknik Instalasi Tenaga Listrik</option>
                <option value="Teknik Komputer dan Jaringan" <?php echo ($siswa['JURUSAN'] ?? '') == 'Teknik Komputer dan Jaringan' ? 'selected' : ''; ?>>Teknik Komputer dan Jaringan</option>
                <option value="Teknik Jaringan Akses Telekomunikasi" <?php echo ($siswa['JURUSAN'] ?? '') == 'Teknik Jaringan Akses Telekomunikasi' ? 'selected' : ''; ?>>Teknik Jaringan Akses Telekomunikasi</option>
                <option value="Desain Komunikasi Visual" <?php echo ($siswa['JURUSAN'] ?? '') == 'Desain Komunikasi Visual' ? 'selected' : ''; ?>>Desain Komunikasi Visual</option>
                <option value="Manajemen Perkantoran" <?php echo ($siswa['JURUSAN'] ?? '') == 'Manajemen Perkantoran' ? 'selected' : ''; ?>>Manajemen Perkantoran</option>
                <option value="Bisnis Retail" <?php echo ($siswa['JURUSAN'] ?? '') == 'Bisnis Retail' ? 'selected' : ''; ?>>Bisnis Retail</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Kelas *</label>
            <select name="kelas" id="kelasSelect" <?php echo $data_sudah_lengkap ? 'disabled' : 'required'; ?>>
                <option value="">Pilih Jurusan terlebih dahulu</option>
                <?php if ($siswa && isset($siswa['KELAS'])): ?>
                    <option value="<?php echo htmlspecialchars($siswa['KELAS']); ?>" selected><?php echo htmlspecialchars($siswa['KELAS']); ?></option>
                <?php endif; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Angkatan</label>
            <input type="number" name="angkatan" min="2000" max="2025" 
                   value="<?php echo htmlspecialchars($siswa['ANGKATAN'] ?? ''); ?>" 
                   <?php echo $data_sudah_lengkap ? 'readonly' : ''; ?> 
                   placeholder="Contoh: 2023">
        </div>
    </div>
</div>
                
                <?php if (!$data_sudah_lengkap): ?>
                    <button type="submit" class="submit-btn">
                        <i class='bx bx-save'></i>
                        Simpan Data Diri
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
const kelasByJurusan = {
    'Rekayasa Perangkat Lunak': [
        'X RPL 1', 'X RPL 2', 'X RPL 3',
        'XI RPL 1', 'XI RPL 2', 'XI RPL 3', 'XI RPL 4',
        'XII RPL 1', 'XII RPL 2', 'XII RPL 3'
    ],
    'Teknik Instalasi Tenaga Listrik': [
        'X TITL 1', 'X TITL 2', 'X TITL 3', 'X TITL 4',
        'XI TITL 1', 'XI TITL 2', 'XI TITL 3', 'XI TITL 4',
        'XII TITL 1', 'XII TITL 2', 'XII TITL 3'
    ],
    'Teknik Komputer dan Jaringan': [
        'X TKJ AXIOO', 'X TKJ 1', 'X TKJ 2', 'X TKJ 3', 'X TKJ 4', 'X TKJ 5', 'X TKJ 6',
        'XI TKJ AXIOO', 'XI TKJ 1', 'XI TKJ 2', 'XI TKJ 3', 'XI TKJ 4',
        'XII TKJ AXIOO', 'XII TKJ 1', 'XII TKJ 2', 'XII TKJ 3'
    ],
    'Teknik Jaringan Akses Telekomunikasi': [
        'XI TJAT 1', 'XI TJAT 2', 'XI TJAT 3',
        'XII TJAT 1', 'XII TJAT 2', 'XII TJAT 3'
    ],
    'Desain Komunikasi Visual': [
        'X DKV 1', 'X DKV 2', 'X DKV 3',
        'XI DKV 1', 'XI DKV 2', 'XI DKV 3', 'XI DKV 4',
        'XII DKV 1', 'XII DKV 2', 'XII DKV 3'
    ],
    'Manajemen Perkantoran': [
        'X MP 1', 'X MP 2', 'X MP 3'
    ],
    'Bisnis Retail': [
        'X BR 1', 'X BR 2'
    ]
};

function updateKelasDropdown() {
    const jurusanSelect = document.getElementById('jurusanSelect');
    const kelasSelect = document.getElementById('kelasSelect');
    const selectedJurusan = jurusanSelect.value;
    
    kelasSelect.innerHTML = '<option value="">Pilih Kelas</option>';
    
    if (selectedJurusan && kelasByJurusan[selectedJurusan]) {
        kelasByJurusan[selectedJurusan].forEach(kelas => {
            const option = document.createElement('option');
            option.value = kelas;
            option.textContent = kelas;
            kelasSelect.appendChild(option);
        });
        
        const previousKelas = '<?php echo htmlspecialchars($siswa['KELAS'] ?? ''); ?>';
        if (previousKelas && kelasByJurusan[selectedJurusan].includes(previousKelas)) {
            kelasSelect.value = previousKelas;
        }
    } else {
        kelasSelect.innerHTML = '<option value="">Pilih Jurusan terlebih dahulu</option>';
    }
}

document.getElementById('jurusanSelect').addEventListener('change', updateKelasDropdown);

document.addEventListener('DOMContentLoaded', function() {
    const currentJurusan = '<?php echo htmlspecialchars($siswa['JURUSAN'] ?? ''); ?>';
    if (currentJurusan) {
        updateKelasDropdown();
    }
    
    <?php if ($data_sudah_lengkap): ?>
        document.querySelectorAll('input, select, textarea').forEach(function(element) {
            if (element.tagName === 'SELECT') {
                element.setAttribute('disabled', 'disabled');
            } else if (!element.hasAttribute('readonly')) {
                element.setAttribute('readonly', 'readonly');
            }
        });
    <?php endif; ?>
    
    document.getElementById('formDataDiri').addEventListener('submit', function(e) {
        <?php if ($data_sudah_lengkap): ?>
            e.preventDefault();
            alert('Data sudah lengkap dan tidak dapat diubah lagi. Silakan hubungi administrator jika ada kesalahan.');
            return false;
        <?php endif; ?>
        
        const nisnInput = document.getElementById('nisnInput');
        const nisnValue = nisnInput.value;
        
        if (nisnValue.length !== 10 || !/^\d+$/.test(nisnValue)) {
            e.preventDefault();
            alert('NISN harus terdiri dari tepat 10 digit angka!');
            nisnInput.focus();
            return false;
        }
    });
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