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

$sql_kepribadian = "SELECT * FROM form_kepribadian WHERE SISWA_ID = ?";
$stmt_kepribadian = $koneksi->prepare($sql_kepribadian);
$stmt_kepribadian->bind_param("i", $siswa_id);
$stmt_kepribadian->execute();
$kepribadian = $stmt_kepribadian->get_result()->fetch_assoc();

$data_sudah_lengkap = false;
if ($kepribadian) {
    if (!empty($kepribadian['NAMA_AYAH']) && !empty($kepribadian['PEKERJAAN_AYAH']) && 
        !empty($kepribadian['PENDIDIKAN_AYAH']) && !empty($kepribadian['NAMA_IBU']) &&
        !empty($kepribadian['PEKERJAAN_IBU']) && !empty($kepribadian['PENDIDIKAN_IBU']) &&
        !empty($kepribadian['STATUS_RUMAH']) && !empty($kepribadian['STATUS_KELUARGA']) &&
        !empty($kepribadian['JUMLAH_ANGGOTA_KELUARGA']) && !empty($kepribadian['ANAK_KE']) &&
        !empty($kepribadian['HUBUNGAN_DENGAN_ORTU'])) {
        $data_sudah_lengkap = true;
    }
}

$status_rumah_asal = $kepribadian['STATUS_RUMAH'] ?? '';
$status_rumah_lainnya_value = '';
$status_rumah_display = '';

if ($status_rumah_asal) {
    $dropdown_status = ['Milik Sendiri', 'Kontrak', 'Kost', 'Lainnya'];
    if (in_array($status_rumah_asal, $dropdown_status)) {
        $status_rumah_display = $status_rumah_asal;
        $status_rumah_lainnya_value = '';
    } else {
        $status_rumah_display = 'Lainnya';
        $status_rumah_lainnya_value = $status_rumah_asal;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$data_sudah_lengkap) {
    $nama_ayah = $_POST['nama_ayah'];
    $pekerjaan_ayah = $_POST['pekerjaan_ayah'];
    $pendidikan_ayah = $_POST['pendidikan_ayah'];
    $penghasilan_ayah = $_POST['penghasilan_ayah'];
    $nama_ibu = $_POST['nama_ibu'];
    $pekerjaan_ibu = $_POST['pekerjaan_ibu'];
    $pendidikan_ibu = $_POST['pendidikan_ibu'];
    $penghasilan_ibu = $_POST['penghasilan_ibu'];
    $status_rumah = $_POST['status_rumah'];
    $status_rumah_lainnya = $_POST['status_rumah_lainnya'] ?? '';
    $kendaraan = $_POST['kendaraan'];
    $status_keluarga = $_POST['status_keluarga'];
    $jumlah_anggota_keluarga = $_POST['jumlah_anggota_keluarga'];
    $anak_ke = $_POST['anak_ke'];
    $hubungan_dengan_ortu = $_POST['hubungan_dengan_ortu'];
    $masalah_keluarga = $_POST['masalah_keluarga'];
    
    if ($status_rumah === 'Lainnya' && !empty($status_rumah_lainnya)) {
        $status_rumah_final = $status_rumah_lainnya;
    } else {
        $status_rumah_final = $status_rumah;
    }
    
    if ($kepribadian) {
        $sql_update = "UPDATE form_kepribadian SET 
                      NAMA_AYAH = ?, PEKERJAAN_AYAH = ?, PENDIDIKAN_AYAH = ?, PENGHASILAN_AYAH = ?,
                      NAMA_IBU = ?, PEKERJAAN_IBU = ?, PENDIDIKAN_IBU = ?, PENGHASILAN_IBU = ?,
                      STATUS_RUMAH = ?, KENDARAAN = ?, STATUS_KELUARGA = ?, JUMLAH_ANGGOTA_KELUARGA = ?,
                      ANAK_KE = ?, HUBUNGAN_DENGAN_ORTU = ?, MASALAH_KELUARGA = ?
                      WHERE SISWA_ID = ?";
        $stmt_update = $koneksi->prepare($sql_update);
        $stmt_update->bind_param("ssssssssssssissi", 
            $nama_ayah, $pekerjaan_ayah, $pendidikan_ayah, $penghasilan_ayah,
            $nama_ibu, $pekerjaan_ibu, $pendidikan_ibu, $penghasilan_ibu,
            $status_rumah_final, $kendaraan, $status_keluarga, $jumlah_anggota_keluarga,
            $anak_ke, $hubungan_dengan_ortu, $masalah_keluarga, $siswa_id
        );
        
        if ($stmt_update->execute()) {
            $success = "Data kepribadian berhasil diperbarui!";
            $stmt_kepribadian->execute();
            $kepribadian = $stmt_kepribadian->get_result()->fetch_assoc();
            $data_sudah_lengkap = true;
            
            if ($status_rumah === 'Lainnya') {
                $status_rumah_display = 'Lainnya';
                $status_rumah_lainnya_value = $status_rumah_lainnya;
            } else {
                $status_rumah_display = $status_rumah;
                $status_rumah_lainnya_value = '';
            }
        } else {
            $error = "Gagal memperbarui data kepribadian!";
        }
    } else {
        $sql_insert = "INSERT INTO form_kepribadian 
                      (SISWA_ID, NAMA_AYAH, PEKERJAAN_AYAH, PENDIDIKAN_AYAH, PENGHASILAN_AYAH,
                       NAMA_IBU, PEKERJAAN_IBU, PENDIDIKAN_IBU, PENGHASILAN_IBU,
                       STATUS_RUMAH, KENDARAAN, STATUS_KELUARGA, JUMLAH_ANGGOTA_KELUARGA,
                       ANAK_KE, HUBUNGAN_DENGAN_ORTU, MASALAH_KELUARGA) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $koneksi->prepare($sql_insert);
        $stmt_insert->bind_param("isssssssssssiiss", 
            $siswa_id, $nama_ayah, $pekerjaan_ayah, $pendidikan_ayah, $penghasilan_ayah,
            $nama_ibu, $pekerjaan_ibu, $pendidikan_ibu, $penghasilan_ibu,
            $status_rumah_final, $kendaraan, $status_keluarga, $jumlah_anggota_keluarga,
            $anak_ke, $hubungan_dengan_ortu, $masalah_keluarga
        );
        
        if ($stmt_insert->execute()) {
            $success = "Data kepribadian berhasil disimpan!";
            $stmt_kepribadian->execute();
            $kepribadian = $stmt_kepribadian->get_result()->fetch_assoc();
            $data_sudah_lengkap = true;
            
            if ($status_rumah === 'Lainnya') {
                $status_rumah_display = 'Lainnya';
                $status_rumah_lainnya_value = $status_rumah_lainnya;
            } else {
                $status_rumah_display = $status_rumah;
                $status_rumah_lainnya_value = '';
            }
        } else {
            $error = "Gagal menyimpan data kepribadian!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Kepribadian Siswa - APK BK</title>
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
        
        .lainnya-input {
            margin-top: 15px;
            display: none;
            animation: fadeIn 0.3s ease;
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
        <h1><i class='bx bx-brain'></i> Form Kepribadian Siswa</h1>
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
                <p><strong>NIS:</strong> <?php echo htmlspecialchars($siswa['NIS'] ?? '-'); ?></p>
                <p><strong>Kelas:</strong> <?php echo htmlspecialchars($siswa['KELAS'] ?? '-'); ?></p>
                <p><strong>Jurusan:</strong> <?php echo htmlspecialchars($siswa['JURUSAN'] ?? '-'); ?></p>
            </div>
            
            <?php if ($data_sudah_lengkap): ?>
                <div class="data-locked">
                    <h3><i class='bx bx-lock-alt'></i> Data Kepribadian Sudah Lengkap</h3>
                    <p>Data kepribadian Anda tidak dapat diubah lagi untuk menjaga keaslian data.</p>
                    <p>Jika ada kesalahan data, silakan hubungi Konselor BK.</p>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="formKepribadian" class="form-content">
                <div class="form-section">
                    <h3 class="section-title"><i class='bx bx-group'></i> Data Keluarga</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Status Keluarga *</label>
                            <select name="status_keluarga" <?php echo $data_sudah_lengkap ? 'disabled' : 'required'; ?>>
                                <option value="">Pilih Status Keluarga</option>
                                <option value="Lengkap" <?php echo ($kepribadian['STATUS_KELUARGA'] ?? '') == 'Lengkap' ? 'selected' : ''; ?>>Lengkap</option>
                                <option value="Orang Tua Bercerai" <?php echo ($kepribadian['STATUS_KELUARGA'] ?? '') == 'Orang Tua Bercerai' ? 'selected' : ''; ?>>Orang Tua Bercerai</option>
                                <option value="Yatim" <?php echo ($kepribadian['STATUS_KELUARGA'] ?? '') == 'Yatim' ? 'selected' : ''; ?>>Yatim</option>
                                <option value="Piatu" <?php echo ($kepribadian['STATUS_KELUARGA'] ?? '') == 'Piatu' ? 'selected' : ''; ?>>Piatu</option>
                                <option value="Yatim Piatu" <?php echo ($kepribadian['STATUS_KELUARGA'] ?? '') == 'Yatim Piatu' ? 'selected' : ''; ?>>Yatim Piatu</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Jumlah Anggota Keluarga *</label>
                            <input type="number" name="jumlah_anggota_keluarga" 
                                   value="<?php echo htmlspecialchars($kepribadian['JUMLAH_ANGGOTA_KELUARGA'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : 'required'; ?> 
                                   min="1" max="20" placeholder="Contoh: 4">
                        </div>
                        
                        <div class="form-group">
                            <label>Anak Ke- *</label>
                            <input type="number" name="anak_ke" 
                                   value="<?php echo htmlspecialchars($kepribadian['ANAK_KE'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : 'required'; ?> 
                                   min="1" max="20" placeholder="Contoh: 2">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title"><i class='bx bx-male'></i> Data Ayah</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nama Ayah *</label>
                            <input type="text" name="nama_ayah" 
                                   value="<?php echo htmlspecialchars($kepribadian['NAMA_AYAH'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : 'required'; ?> 
                                   placeholder="Masukkan nama lengkap ayah">
                        </div>
                        
                        <div class="form-group">
                            <label>Pekerjaan Ayah *</label>
                            <input type="text" name="pekerjaan_ayah" 
                                   value="<?php echo htmlspecialchars($kepribadian['PEKERJAAN_AYAH'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : 'required'; ?> 
                                   placeholder="Contoh: PNS, Wiraswasta, Buruh">
                        </div>
                        
                        <div class="form-group">
                            <label>Pendidikan Ayah *</label>
                            <select name="pendidikan_ayah" <?php echo $data_sudah_lengkap ? 'disabled' : 'required'; ?>>
                                <option value="">Pilih Pendidikan</option>
                                <option value="SD" <?php echo ($kepribadian['PENDIDIKAN_AYAH'] ?? '') == 'SD' ? 'selected' : ''; ?>>SD</option>
                                <option value="SMP" <?php echo ($kepribadian['PENDIDIKAN_AYAH'] ?? '') == 'SMP' ? 'selected' : ''; ?>>SMP</option>
                                <option value="SMA" <?php echo ($kepribadian['PENDIDIKAN_AYAH'] ?? '') == 'SMA' ? 'selected' : ''; ?>>SMA</option>
                                <option value="D1/D2/D3" <?php echo ($kepribadian['PENDIDIKAN_AYAH'] ?? '') == 'D1/D2/D3' ? 'selected' : ''; ?>>D1/D2/D3</option>
                                <option value="S1" <?php echo ($kepribadian['PENDIDIKAN_AYAH'] ?? '') == 'S1' ? 'selected' : ''; ?>>S1</option>
                                <option value="S2" <?php echo ($kepribadian['PENDIDIKAN_AYAH'] ?? '') == 'S2' ? 'selected' : ''; ?>>S2</option>
                                <option value="S3" <?php echo ($kepribadian['PENDIDIKAN_AYAH'] ?? '') == 'S3' ? 'selected' : ''; ?>>S3</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Penghasilan Ayah (Rp)</label>
                            <input type="number" name="penghasilan_ayah" 
                                   value="<?php echo htmlspecialchars($kepribadian['PENGHASILAN_AYAH'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : ''; ?> 
                                   min="0" step="100000" placeholder="Contoh: 5000000">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title"><i class='bx bx-female'></i> Data Ibu</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nama Ibu *</label>
                            <input type="text" name="nama_ibu" 
                                   value="<?php echo htmlspecialchars($kepribadian['NAMA_IBU'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : 'required'; ?> 
                                   placeholder="Masukkan nama lengkap ibu">
                        </div>
                        
                        <div class="form-group">
                            <label>Pekerjaan Ibu *</label>
                            <input type="text" name="pekerjaan_ibu" 
                                   value="<?php echo htmlspecialchars($kepribadian['PEKERJAAN_IBU'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : 'required'; ?> 
                                   placeholder="Contoh: Ibu Rumah Tangga, PNS, Wiraswasta">
                        </div>
                        
                        <div class="form-group">
                            <label>Pendidikan Ibu *</label>
                            <select name="pendidikan_ibu" <?php echo $data_sudah_lengkap ? 'disabled' : 'required'; ?>>
                                <option value="">Pilih Pendidikan</option>
                                <option value="SD" <?php echo ($kepribadian['PENDIDIKAN_IBU'] ?? '') == 'SD' ? 'selected' : ''; ?>>SD</option>
                                <option value="SMP" <?php echo ($kepribadian['PENDIDIKAN_IBU'] ?? '') == 'SMP' ? 'selected' : ''; ?>>SMP</option>
                                <option value="SMA" <?php echo ($kepribadian['PENDIDIKAN_IBU'] ?? '') == 'SMA' ? 'selected' : ''; ?>>SMA</option>
                                <option value="D1/D2/D3" <?php echo ($kepribadian['PENDIDIKAN_IBU'] ?? '') == 'D1/D2/D3' ? 'selected' : ''; ?>>D1/D2/D3</option>
                                <option value="S1" <?php echo ($kepribadian['PENDIDIKAN_IBU'] ?? '') == 'S1' ? 'selected' : ''; ?>>S1</option>
                                <option value="S2" <?php echo ($kepribadian['PENDIDIKAN_IBU'] ?? '') == 'S2' ? 'selected' : ''; ?>>S2</option>
                                <option value="S3" <?php echo ($kepribadian['PENDIDIKAN_IBU'] ?? '') == 'S3' ? 'selected' : ''; ?>>S3</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Penghasilan Ibu (Rp)</label>
                            <input type="number" name="penghasilan_ibu" 
                                   value="<?php echo htmlspecialchars($kepribadian['PENGHASILAN_IBU'] ?? ''); ?>" 
                                   <?php echo $data_sudah_lengkap ? 'readonly' : ''; ?> 
                                   min="0" step="100000" placeholder="Contoh: 3000000">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title"><i class='bx bx-home'></i> Kondisi Tempat Tinggal</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Status Rumah *</label>
                            <select name="status_rumah" id="status_rumah" <?php echo $data_sudah_lengkap ? 'disabled' : 'required'; ?>>
                                <option value="">Pilih Status Rumah</option>
                                <option value="Milik Sendiri" <?php echo ($status_rumah_display == 'Milik Sendiri') ? 'selected' : ''; ?>>Milik Sendiri</option>
                                <option value="Kontrak" <?php echo ($status_rumah_display == 'Kontrak') ? 'selected' : ''; ?>>Kontrak</option>
                                <option value="Kost" <?php echo ($status_rumah_display == 'Kost') ? 'selected' : ''; ?>>Kost</option>
                                <option value="Lainnya" <?php echo ($status_rumah_display == 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                            </select>
                            
                            <div class="lainnya-input" id="status_rumah_lainnya_container">
                                <label>Status Rumah Lainnya *</label>
                                <input type="text" name="status_rumah_lainnya" id="status_rumah_lainnya"
                                       value="<?php echo htmlspecialchars($status_rumah_lainnya_value); ?>"
                                       <?php echo $data_sudah_lengkap ? 'readonly' : ''; ?>
                                       placeholder="Tulis status rumah lainnya">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Kendaraan yang Dimiliki</label>
                            <textarea name="kendaraan" <?php echo $data_sudah_lengkap ? 'readonly' : ''; ?> 
                                      placeholder="Contoh: Motor 2, Mobil 1, Sepeda 1"><?php echo htmlspecialchars($kepribadian['KENDARAAN'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title"><i class='bx bx-heart'></i> Hubungan Keluarga</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Hubungan dengan Orang Tua *</label>
                            <select name="hubungan_dengan_ortu" <?php echo $data_sudah_lengkap ? 'disabled' : 'required'; ?>>
                                <option value="">Pilih Tingkat Hubungan</option>
                                <option value="Sangat Baik" <?php echo ($kepribadian['HUBUNGAN_DENGAN_ORTU'] ?? '') == 'Sangat Baik' ? 'selected' : ''; ?>>Sangat Baik</option>
                                <option value="Baik" <?php echo ($kepribadian['HUBUNGAN_DENGAN_ORTU'] ?? '') == 'Baik' ? 'selected' : ''; ?>>Baik</option>
                                <option value="Cukup" <?php echo ($kepribadian['HUBUNGAN_DENGAN_ORTU'] ?? '') == 'Cukup' ? 'selected' : ''; ?>>Cukup</option>
                                <option value="Kurang" <?php echo ($kepribadian['HUBUNGAN_DENGAN_ORTU'] ?? '') == 'Kurang' ? 'selected' : ''; ?>>Kurang</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Masalah Keluarga (Jika Ada)</label>
                            <textarea name="masalah_keluarga" <?php echo $data_sudah_lengkap ? 'readonly' : ''; ?> 
                                      placeholder="Ceritakan masalah keluarga yang mungkin mempengaruhi kondisi Anda (opsional)"><?php echo htmlspecialchars($kepribadian['MASALAH_KELUARGA'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <?php if (!$data_sudah_lengkap): ?>
                    <button type="submit" class="submit-btn">
                        <i class='bx bx-save'></i>
                        Simpan Data Kepribadian
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
        document.getElementById('formKepribadian').addEventListener('submit', function(e) {
            <?php if ($data_sudah_lengkap): ?>
                e.preventDefault();
                alert('Data sudah lengkap dan tidak dapat diubah lagi. Silakan hubungi konselor BK jika ada kesalahan.');
            <?php endif; ?>
            
            const statusRumahSelect = document.getElementById('status_rumah');
            const statusRumahLainnyaInput = document.getElementById('status_rumah_lainnya');
            
            if (statusRumahSelect.value === 'Lainnya' && statusRumahLainnyaInput.value.trim() === '') {
                e.preventDefault();
                alert('Silakan isi status rumah lainnya.');
                statusRumahLainnyaInput.focus();
            }
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

        function toggleStatusRumahLainnya() {
            const statusRumahSelect = document.getElementById('status_rumah');
            const statusRumahLainnyaContainer = document.getElementById('status_rumah_lainnya_container');
            const statusRumahLainnyaInput = document.getElementById('status_rumah_lainnya');
            
            if (statusRumahSelect.value === 'Lainnya') {
                statusRumahLainnyaContainer.style.display = 'block';
                statusRumahLainnyaInput.required = true;
            } else {
                statusRumahLainnyaContainer.style.display = 'none';
                statusRumahLainnyaInput.required = false;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const statusRumahSelect = document.getElementById('status_rumah');
            
            <?php if ($status_rumah_display == 'Lainnya'): ?>
                document.getElementById('status_rumah_lainnya_container').style.display = 'block';
                document.getElementById('status_rumah_lainnya').required = true;
            <?php endif; ?>
            
            statusRumahSelect.addEventListener('change', toggleStatusRumahLainnya);
            
            const formSections = document.querySelectorAll('.form-section');
            formSections.forEach((section, index) => {
                section.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>