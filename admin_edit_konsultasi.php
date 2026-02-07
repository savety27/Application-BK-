<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: index.php");
    exit();
}

$success = '';
$error = '';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $sql = "SELECT k.*, u.NAMA_LENGKAP as nama_siswa, s.KELAS, s.JURUSAN, 
                   u_guru.NAMA_LENGKAP as nama_guru, g.ID as guru_id
            FROM konsultasi k
            JOIN siswa s ON k.SISWA_ID = s.ID
            JOIN users u ON s.USER_ID = u.ID
            LEFT JOIN guru_bk g ON k.GURU_BK_ID = g.ID
            LEFT JOIN users u_guru ON g.USER_ID = u_guru.ID
            WHERE k.ID = ?";
    
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $konsultasi = $result->fetch_assoc();
    
    if (!$konsultasi) {
        header("Location: admin_laporan_konsultasi.php?error=Data konsultasi tidak ditemukan");
        exit();
    }
} else {
    header("Location: admin_laporan_konsultasi.php?error=ID tidak valid");
    exit();
}

$sql_guru = "SELECT g.ID, u.NAMA_LENGKAP 
             FROM guru_bk g 
             JOIN users u ON g.USER_ID = u.ID 
             ORDER BY u.NAMA_LENGKAP";
$guru_list = $koneksi->query($sql_guru);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $guru_id = $_POST['guru_id'];
    $status = $_POST['status'];
    $prioritas = $_POST['prioritas'];
    $topik = $_POST['topik'];
    $deskripsi = $_POST['deskripsi'];

    
    $sql_update = "UPDATE konsultasi 
                   SET GURU_BK_ID = ?, STATUS = ?, PRIORITAS = ?, 
                       TOPIK_KONSULTASI = ?, DESKRIPSI_MASALAH = ?
                   WHERE ID = ?";
    
    $stmt = $koneksi->prepare($sql_update);
    $stmt->bind_param("issssi", $guru_id, $status, $prioritas, $topik, $deskripsi, $id);
    
    if ($stmt->execute()) {
        $success = "Data konsultasi berhasil diperbarui";
        $sql = "SELECT k.*, u.NAMA_LENGKAP as nama_siswa, s.KELAS, s.JURUSAN, 
                       u_guru.NAMA_LENGKAP as nama_guru, g.ID as guru_id
                FROM konsultasi k
                JOIN siswa s ON k.SISWA_ID = s.ID
                JOIN users u ON s.USER_ID = u.ID
                LEFT JOIN guru_bk g ON k.GURU_BK_ID = g.ID
                LEFT JOIN users u_guru ON g.USER_ID = u_guru.ID
                WHERE k.ID = ?";
        
        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $konsultasi = $result->fetch_assoc();
    } else {
        $error = "Gagal memperbarui data konsultasi";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Konsultasi - APK BK</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Poppins', 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            min-height: 100vh;
            color: #f8fafc;
            overflow-x: hidden;
            position: relative;
        }
        
        .floating {
            position: fixed;
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
            z-index: 0;
            filter: blur(1px);
            opacity: 0.3;
        }
        
        .floating:nth-child(1) {
            width: 120px;
            height: 120px;
            top: 10%;
            left: 5%;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.4) 0%, rgba(139, 92, 246, 0.1) 70%);
            animation-delay: 0s;
            box-shadow: 0 0 30px rgba(139, 92, 246, 0.3);
        }
        
        .floating:nth-child(2) {
            width: 160px;
            height: 160px;
            top: 60%;
            right: 8%;
            background: radial-gradient(circle, rgba(14, 165, 233, 0.4) 0%, rgba(14, 165, 233, 0.1) 70%);
            animation-delay: 2s;
            box-shadow: 0 0 40px rgba(14, 165, 233, 0.3);
        }
        
        .header { 
            background: rgba(15, 23, 42, 0.95);
            color: #f8fafc; 
            padding: 20px 40px; 
            display: flex; 
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #8b5cf6;
            box-shadow: 0 8px 32px rgba(139, 92, 246, 0.2);
            backdrop-filter: blur(20px);
            position: relative;
            overflow: hidden;
            z-index: 10;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 0.5px;
            background: linear-gradient(135deg, #8b5cf6, #06b6d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .logout-btn {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .logout-btn:hover {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 25px rgba(239, 68, 68, 0.4);
        }
        
         .nav { 
            background: rgba(15, 23, 42, 0.9);
            padding: 18px 40px;
            display: flex;
            gap: 25px;
            border-bottom: 1px solid rgba(139, 92, 246, 0.2);
            backdrop-filter: blur(15px);
            overflow-x: auto;
            scrollbar-width: none;
            z-index: 10;
        }
        
        .nav::-webkit-scrollbar {
            display: none;
        }
        
        .nav a { 
            color: #94a3b8; 
            text-decoration: none; 
            padding: 14px 22px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            white-space: nowrap;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav a::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(6, 182, 212, 0.1));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
            z-index: -1;
        }
        
        .nav a:hover { 
            color: #8b5cf6;
            border-color: #8b5cf6;
            transform: translateY(-2px);
        }
        
        .nav a:hover::before {
            transform: scaleX(1);
        }

        .nav a.active { 
            background: linear-gradient(135deg, rgba(138, 92, 246, 0.09));
            color: #8b5cf6;
            border-color: #8b5cf6;
            box-shadow: 0 4px 15px rgba(138, 92, 246, 0.2);
        }

        .nav a.active::before {
            transform: scaleX(1);
        }
        
        .container { 
            padding: 40px; 
            max-width: 1000px; 
            margin: 0 auto;
            position: relative;
            z-index: 5;
        }
        
        .page-header {
            background: rgba(15, 23, 42, 0.8);
            padding: 35px 40px;
            border-radius: 20px;
            margin-bottom: 35px;
            border: 1px solid rgba(139, 92, 246, 0.2);
            box-shadow: 0 15px 35px rgba(139, 92, 246, 0.15);
            backdrop-filter: blur(15px);
        }
        
        .page-header h2 {
            font-size: 32px;
            margin-bottom: 12px;
            color: #f8fafc;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 600;
            border: 2px solid transparent;
            backdrop-filter: blur(15px);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success {
            background: rgba(72, 187, 120, 0.15);
            border-color: rgba(72, 187, 120, 0.3);
            color: #38a169;
        }
        
        .alert-error {
            background: rgba(245, 101, 101, 0.15);
            border-color: rgba(245, 101, 101, 0.3);
            color: #e53e3e;
        }
        
        .form-container {
            background: rgba(15, 23, 42, 0.8);
            padding: 40px;
            border-radius: 20px;
            border: 1px solid rgba(139, 92, 246, 0.2);
            box-shadow: 0 15px 35px rgba(139, 92, 246, 0.15);
            backdrop-filter: blur(15px);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #cbd5e1;
            font-weight: 600;
            font-size: 14px;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px 16px;
            background: rgba(15, 23, 42, 0.9);
            border: 2px solid rgba(139, 92, 246, 0.3);
            border-radius: 10px;
            color: #f8fafc;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #8b5cf6;
            box-shadow: 0 0 20px rgba(139, 92, 246, 0.3);
            background: rgba(15, 23, 42, 0.95);
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #8b5cf6, #06b6d4);
            color: white;
            box-shadow: 0 6px 20px rgba(139, 92, 246, 0.3);
        }
        
        .btn-secondary {
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
            border: 2px solid rgba(139, 92, 246, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-primary:hover {
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4);
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .info-box {
            background: rgba(139, 92, 246, 0.1);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid rgba(139, 92, 246, 0.3);
            margin-bottom: 25px;
        }
        
        .info-box h4 {
            color: #8b5cf6;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(139, 92, 246, 0.1);
        }
        
        .info-label {
            color: #cbd5e1;
            font-weight: 600;
        }
        
        .info-value {
            color: #f8fafc;
            font-weight: 500;
        }
    </style>
</head>
<body>
    
    <div class="floating"></div>
    <div class="floating"></div>
    
    <div class="header">
        <h1>📊 APK BK - Edit Konsultasi</h1>
        <div class="user-info">
            <span>Halo, <strong><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></strong> 👑</span>
            <a href="logout.php" class="logout-btn">
                <i class='bx bx-log-out'></i>
                Logout
            </a>
        </div>
    </div>
    
    <div class="nav">
        <a href="admin_dashboard.php">
            <i class='bx bx-home'></i>
            Dashboard
        </a>
        <a href="admin_manage_guru.php">
            <i class='bx bx-user-plus'></i>
            Kelola Guru BK
        </a>
        <a href="admin_manage_siswa.php">
            <i class='bx bx-group'></i>
            Kelola Siswa
        </a>
        <a href="admin_manage_reset_requests.php">
            <i class='bx bx-message-square-dots'></i>
            Request Reset Password
        </a>
         <a href="admin_reports.php">
            <i class='bx bx-bar-chart'></i>
            Laporan
        </a>
        <a href="admin_laporan_konsultasi.php" class="active">
            <i class='bx bx-bar-chart'></i>
            Laporan Konsultasi
        </a>    
        <a href="admin_settings.php">
            <i class='bx bx-cog'></i>
            Pengaturan
        </a>
    </div>
    
    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class='bx bx-check-circle'></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class='bx bx-error-circle'></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="page-header">
            <h2><i class='bx bx-edit'></i> Edit Data Konsultasi</h2>
            <p>Ubah data konsultasi siswa</p>
        </div>
        
        <div class="form-container">
            <div class="info-box">
                <h4>Informasi Siswa</h4>
                <div class="info-item">
                    <span class="info-label">Nama Siswa:</span>
                    <span class="info-value"><?php echo htmlspecialchars($konsultasi['nama_siswa']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Kelas:</span>
                    <span class="info-value"><?php echo htmlspecialchars($konsultasi['KELAS']); ?> - <?php echo htmlspecialchars($konsultasi['JURUSAN']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Kode Konsultasi:</span>
                    <span class="info-value"><?php echo htmlspecialchars($konsultasi['KODE_KONSULTASI']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tanggal Pengajuan:</span>
                    <span class="info-value"><?php echo date('d/m/Y', strtotime($konsultasi['TANGGAL_PENGAJUAN'])); ?></span>
                </div>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Topik Konsultasi</label>
                    <input type="text" name="topik" value="<?php echo htmlspecialchars($konsultasi['TOPIK_KONSULTASI']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Deskripsi Masalah</label>
                    <textarea name="deskripsi" required><?php echo htmlspecialchars($konsultasi['DESKRIPSI_MASALAH']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Guru BK</label>
                    <select name="guru_id">
                        <option value="">-- Pilih Guru BK --</option>
                        <?php while($guru = $guru_list->fetch_assoc()): ?>
                            <option value="<?php echo $guru['ID']; ?>" 
                                <?php echo ($konsultasi['guru_id'] == $guru['ID']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($guru['NAMA_LENGKAP']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="Menunggu" <?php echo $konsultasi['STATUS'] == 'Menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                        <option value="Disetujui" <?php echo $konsultasi['STATUS'] == 'Disetujui' ? 'selected' : ''; ?>>Disetujui</option>
                        <option value="Ditolak" <?php echo $konsultasi['STATUS'] == 'Ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                        <option value="Selesai" <?php echo $konsultasi['STATUS'] == 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Prioritas</label>
                    <select name="prioritas" required>
                        <option value="Rendah" <?php echo $konsultasi['PRIORITAS'] == 'Rendah' ? 'selected' : ''; ?>>Rendah</option>
                        <option value="Sedang" <?php echo $konsultasi['PRIORITAS'] == 'Sedang' ? 'selected' : ''; ?>>Sedang</option>
                        <option value="Tinggi" <?php echo $konsultasi['PRIORITAS'] == 'Tinggi' ? 'selected' : ''; ?>>Tinggi</option>
                        <option value="Darurat" <?php echo $konsultasi['PRIORITAS'] == 'Darurat' ? 'selected' : ''; ?>>Darurat</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-save'></i> Simpan Perubahan
                    </button>
                    <a href="admin_laporan_konsultasi.php" class="btn btn-secondary">
                        <i class='bx bx-arrow-back'></i> Kembali
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>