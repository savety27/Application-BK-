<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Guru_BK') {
    header("Location: ../index.php");
    exit();
}

$nama_guru = $_SESSION['nama_lengkap'] ?? 'Guru BK';
$user_id = $_SESSION['user_id'];
$nip_guru = '-';
$sekolah = "SMK Negeri 7 Batam";
$alamat_sekolah = "Jalan Pendidikan No. 123, Batam Center, Kota Batam";
$telepon_sekolah = "(0778) 456789";

$sql_guru = "SELECT NIP FROM guru_bk WHERE USER_ID = ?";
$stmt_guru = $koneksi->prepare($sql_guru);
if ($stmt_guru) {
    $stmt_guru->bind_param("i", $user_id);
    $stmt_guru->execute();
    $result_guru = $stmt_guru->get_result()->fetch_assoc();
    if ($result_guru && !empty($result_guru['NIP'])) {
        $nip_guru = $result_guru['NIP'];
    }
    $stmt_guru->close();
}

$bulan_romawi = [
    '1' => 'I', '2' => 'II', '3' => 'III', '4' => 'IV', '5' => 'V',
    '6' => 'VI', '7' => 'VII', '8' => 'VIII', '9' => 'IX', '10' => 'X',
    '11' => 'XI', '12' => 'XII'
];
$bulan_sekarang = date('n');
$bulan_romawi_sekarang = $bulan_romawi[$bulan_sekarang];

$data_default = [
    'nomor_surat' => '003/SMK7/BK/'.$bulan_romawi_sekarang.'/'.date('Y'),
    'tanggal_surat' => date('d-m-Y'),
    'nama_siswa' => '________________',
    'kelas' => 'XII',
    'jurusan' => 'Teknik Komputer dan Jaringan',
    'nama_orangtua' => '________________',
    'tanggal_pertemuan' => date('d-m-Y', strtotime('+3 days')),
    'jam_pertemuan' => '10:00',
    'tempat_pertemuan' => 'Ruang BK SMK Negeri 7 Batam',
    'alasan_pemanggilan' => 'Pembahasan Perkembangan Akademik dan Perilaku Siswa'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Pemanggilan Orang Tua - APK BK</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #1a365d 0%, #2d3748 50%, #4a5568 100%);
        color: #2d3748;
        min-height: 100vh;
        padding: 20px;
        position: relative;
        overflow-x: hidden;
    }
    
    body::before,
    body::after {
        content: "";
        position: fixed;
        width: 520px;
        height: 520px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(49, 130, 206, 0.18) 0%, rgba(49, 130, 206, 0) 70%);
        filter: blur(2px);
        z-index: 0;
        animation: floatOrb 12s ease-in-out infinite;
        pointer-events: none;
    }
    
    body::before {
        top: -120px;
        left: -140px;
    }
    
    body::after {
        bottom: -140px;
        right: -160px;
        animation-delay: 1.5s;
    }
    
    .dashboard {
        max-width: 1200px;
        margin: 0 auto;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 18px;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.18);
        overflow: hidden;
        animation: slideUp 0.5s ease-out;
        position: relative;
        z-index: 1;
        border: 1px solid rgba(49, 130, 206, 0.1);
        backdrop-filter: blur(15px);
    }
    
    .header-section {
        background: radial-gradient(circle at 15% 15%, rgba(255, 255, 255, 0.33) 0%, rgba(255,255,255,0) 35%),
                    linear-gradient(135deg, #1c64a7, #000000);
        color: white;
        padding: 36px 30px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .header-section::after {
        content: "";
        position: absolute;
        right: -60px;
        top: -80px;
        width: 240px;
        height: 240px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255,255,255,0.18) 0%, rgba(255,255,255,0) 70%);
        animation: bannerGlow 8s ease-in-out infinite;
        pointer-events: none;
    }
    
    .header-section h1 {
        font-size: 28px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        letter-spacing: 0.5px;
    }
    
    .header-section h2 {
        font-size: 15px;
        font-weight: normal;
        opacity: 0.95;
    }
    
    .form-section {
        padding: 30px;
        background: linear-gradient(180deg, #ffffff 0%, #f4f8ff 100%);
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 16px;
        margin-bottom: 25px;
    }
    
    .form-group {
        margin-bottom: 0;
        animation: slideUp 0.5s ease-out backwards;
    }
    
    .form-group:nth-child(1) { animation-delay: 0.1s; }
    .form-group:nth-child(2) { animation-delay: 0.15s; }
    .form-group:nth-child(3) { animation-delay: 0.2s; }
    .form-group:nth-child(4) { animation-delay: 0.25s; }
    .form-group:nth-child(5) { animation-delay: 0.3s; }
    
    .form-group label {
        display: block;
        margin-bottom: 6px;
        color: #2b6cb0;
        font-weight: 600;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 6px;
        letter-spacing: 0.2px;
    }
    
    .form-group label i {
        color: #3182ce;
        font-size: 16px;
    }
    
    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 11px 14px;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        font-family: inherit;
        transition: all 0.3s ease;
        background: #f8fbff;
        box-shadow: 0 2px 8px rgba(49, 130, 206, 0.04);
    }
    
    .form-group input:hover,
    .form-group textarea:hover {
        border-color: #90cdf4;
        background: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(49, 130, 206, 0.08);
    }
    
    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #3182ce;
        box-shadow: 0 0 0 4px rgba(49, 130, 206, 0.12);
        background: white;
    }
    
    .form-group input::placeholder,
    .form-group textarea::placeholder {
        color: #94a3b8;
        font-style: italic;
    }
    
    .form-group textarea {
        min-height: 90px;
        resize: vertical;
        line-height: 1.5;
    }
    
    .controls {
        display: flex;
        gap: 12px;
        justify-content: center;
        flex-wrap: wrap;
        padding: 18px;
        background: linear-gradient(90deg, #f0f9ff 0%, #f8fbff 100%);
        border-radius: 12px;
        margin-top: 25px;
        border: 1px solid #e2e8f0;
    }
    
    .btn {
        padding: 11px 22px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        text-decoration: none;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
    }
    
    .btn-preview {
        background: linear-gradient(135deg, #3182ce, #2b6cb0);
        color: white;
    }
    
    .btn-preview:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 22px rgba(49, 130, 206, 0.35);
    }
    
    .btn-reset {
        background: linear-gradient(135deg, #718096, #4a5568);
        color: white;
    }
    
    .btn-reset:hover {
        transform: translateY(-2px);
    }
    
    .btn-print {
        background: linear-gradient(135deg, #2b6cb0, #1a365d);
        color: white;
    }
    
    .btn-print:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 22px rgba(26, 54, 93, 0.35);
    }
    
    .btn-back {
        background: linear-gradient(135deg, #e53e3e, #c53030);
        color: white;
    }
    
    .btn-back:hover {
        transform: translateY(-2px);
    }
    
    .preview-info {
        background: linear-gradient(135deg, #e0f2fe 0%, #eef6ff 100%);
        padding: 14px;
        border-radius: 8px;
        border-left: 4px solid #3182ce;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13px;
        color: #2b6cb0;
        animation: fadeIn 0.6s ease-out;
    }
    
    .letter-container {
        display: none;
        max-width: 210mm;
        margin: 30px auto;
        background: white;
        padding: 13mm 18mm;
        box-shadow: 0 8px 30px rgba(49, 130, 206, 0.2);
        border: 2px solid #dbeafe;
        font-family: 'Times New Roman', serif;
        font-size: 10.8pt;
        line-height: 1.35;
        color: #000;
        position: relative;
        border-radius: 6px;
    }
    
    .letter-container::before,
    .letter-container::after {
        content: '';
        position: absolute;
        width: 40px;
        height: 40px;
        border: 3px solid #3182ce;
    }
    
    .letter-container::before {
        top: 8px;
        left: 8px;
        border-right: none;
        border-bottom: none;
        border-top-left-radius: 8px;
    }
    
    .letter-container::after {
        bottom: 8px;
        right: 8px;
        border-left: none;
        border-top: none;
        border-bottom-right-radius: 8px;
    }
    
    .letter-container.active {
        display: block;
        animation: slideInLetter 0.6s ease-out;
    }
    
    .letter-header {
        text-align: center;
        margin-bottom: 10px;
        padding-bottom: 8px;
        border-bottom: 3px double #000;
        position: relative;
        background: linear-gradient(180deg, #eef6ff 0%, #ffffff 100%);
        padding-top: 8px;
        animation: headerFade 0.8s ease-out 0.2s both;
    }
    
    .letter-header h1 {
        font-size: 13pt;
        font-weight: bold;
        margin: 2px 0;
        letter-spacing: 0.5px;
        color: #000;
    }
    
    .letter-header h2 {
        font-size: 12pt;
        font-weight: bold;
        margin: 2px 0;
        color: #000;
    }
    
    .letter-header p {
        font-size: 9pt;
        margin: 1px 0;
        line-height: 1.3;
        color: #000;
    }
    
    .letter-info {
        margin: 8px 0;
        animation: contentFadeIn 0.8s ease-out 0.4s both;
    }
    
    .letter-number {
        font-size: 11pt;
        margin-bottom: 2px;
    }
    
    .letter-date {
        font-size: 11pt;
        margin-bottom: 12px;
    }
    
    .letter-subject {
        font-size: 11pt;
        margin-bottom: 12px;
        font-weight: bold;
    }
    
    .underline {
        text-decoration: underline;
    }
    
    .letter-address {
        margin-bottom: 12px;
        font-size: 11pt;
        animation: contentFadeIn 0.8s ease-out 0.5s both;
    }
    
    .letter-address p {
        margin: 2px 0;
        line-height: 1.3;
    }
    
    .bold {
        font-weight: bold;
    }
    
    .letter-content {
        text-align: justify;
        margin-bottom: 12px;
        font-size: 10.8pt;
        animation: contentFadeIn 0.8s ease-out 0.6s both;
    }
    
    .letter-content p {
        margin-bottom: 8px;
        line-height: 1.4;
    }
    
    .indent {
        text-indent: 40px;
    }
    
    .meeting-info {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        padding: 7px 12px;
        margin: 8px 0;
        border-radius: 4px;
        border-left: 3px solid #3182ce;
        box-shadow: 0 2px 8px rgba(49, 130, 206, 0.1);
        animation: boxSlide 0.6s ease-out 0.7s both;
    }
    
    .meeting-info p {
        margin: 4px 0;
        line-height: 1.4;
        font-size: 11pt;
    }
    
    .place-date {
        text-align: left;
        margin: 10px 0 6px 0;
        font-size: 10.8pt;
        padding-left: 55%;
        animation: contentFadeIn 0.8s ease-out 0.8s both;
    }
    
    .letter-signature {
        margin-top: 10px;
        animation: contentFadeIn 0.8s ease-out 0.9s both;
    }
    
    .signature-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 30px;
    }
    
    .signature-box {
        flex: 1;
        text-align: center;
        max-width: 45%;
    }
    
    .signature-box p {
        margin: 2px 0;
        font-size: 11pt;
    }
    
    .signature-space {
        height: 50px;
    }
    
    .signature-name {
        font-weight: bold;
        text-decoration: underline;
        margin-top: 3px;
    }
    
    .signature-nip {
        font-size: 10pt;
    }
    
    .letter-footer {
        margin-top: 10px;
        padding-top: 5px;
        border-top: 1px solid #cbd5e1;
        font-size: 8pt;
        color: #64748b;
        text-align: center;
        animation: contentFadeIn 0.8s ease-out 1s both;
    }
    
    .letter-footer p {
        margin: 1px 0;
    }
    
    @media print {
        body {
            background: white !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        
        body::before,
        body::after {
            display: none !important;
        }
        
        .dashboard,
        .header-section,
        .form-section,
        .controls,
        .preview-info,
        .btn {
            display: none !important;
        }
        
        .letter-container {
            display: block !important;
            width: 190mm !important;
            max-width: 190mm !important;
            margin: 0 auto !important;
            padding: 10mm 12mm !important;
            box-shadow: none !important;
            page-break-after: auto;
            border: 2px solid #dbeafe !important;
            animation: none !important;
        }
        
        .letter-container::before,
        .letter-container::after {
            display: none !important;
        }
        
        .letter-container.active {
            display: block !important;
        }
        
        .letter-header {
            background: linear-gradient(180deg, #f0f9ff 0%, #ffffff 100%) !important;
            animation: none !important;
            border-bottom: 3px double #000 !important;
        }
        
        .meeting-info {
            background: #f0f9ff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            animation: none !important;
        }
        
        .letter-container,
        .letter-header,
        .letter-info,
        .letter-address,
        .letter-content,
        .meeting-info,
        .place-date,
        .letter-signature,
        .letter-footer {
            break-inside: avoid;
            page-break-inside: avoid;
        }
        
        .letter-info,
        .letter-address,
        .letter-content,
        .place-date,
        .letter-signature,
        .letter-footer {
            animation: none !important;
        }
        
        @page {
            margin: 8mm;
            size: A4 portrait;
        }
    }
    
    @media (max-width: 768px) {
        body {
            padding: 10px;
        }
        
        .header-section {
            padding: 20px;
        }
        
        .header-section h1 {
            font-size: 20px;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-section {
            padding: 20px;
        }
        
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .controls {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
            justify-content: center;
        }
        
        .letter-container {
            padding: 15mm 12mm;
            margin: 15px auto;
        }
        
        .signature-row {
            flex-direction: column;
            gap: 30px;
        }
        
        .signature-box {
            max-width: 100%;
        }
        
        .place-date {
            padding-left: 0;
            text-align: right;
        }
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes floatOrb {
        0%, 100% {
            transform: translateY(0) scale(1);
        }
        50% {
            transform: translateY(18px) scale(1.03);
        }
    }
    
    @keyframes bannerGlow {
        0%, 100% {
            transform: translateY(0) scale(1);
            opacity: 0.6;
        }
        50% {
            transform: translateY(10px) scale(1.05);
            opacity: 0.9;
        }
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }
    
    @keyframes slideInLetter {
        from {
            opacity: 0;
            transform: translateY(40px) scale(0.98);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    @keyframes headerFade {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes contentFadeIn {
        from {
            opacity: 0;
            transform: translateX(-15px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes boxSlide {
        from {
            opacity: 0;
            transform: translateY(15px);
            box-shadow: 0 0 0 rgba(49, 130, 206, 0);
        }
        to {
            opacity: 1;
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(49, 130, 206, 0.1);
        }
    }
    
    @keyframes pulse {
        0%, 100% {
            box-shadow: 0 0 0 0 rgba(49, 130, 206, 0.4);
        }
        50% {
            box-shadow: 0 0 0 8px rgba(49, 130, 206, 0);
        }
    }
    
    .btn:active {
        animation: pulse 0.5s ease-out;
    }
    
    .form-group input:focus,
    .form-group textarea:focus {
        animation: inputFocus 0.3s ease-out;
    }
    
    @keyframes inputFocus {
        from {
            border-color: #e2e8f0;
        }
        to {
            border-color: #3182ce;
        }
    }
</style>
</head>
<body>
    
    <div class="dashboard">
        <div class="header-section">
            <h1>
                <i class='bx bx-file-blank'></i>
                SURAT PEMANGGILAN ORANG TUA/WALI SISWA
            </h1>
            <h2><?php echo $sekolah; ?> - Sistem Bimbingan Konseling</h2>
        </div>
        
        <div class="form-section">
            <div class="preview-info">
                <i class='bx bx-info-circle'></i>
                Isi form di bawah ini, kemudian klik "Lihat Preview" untuk melihat hasil surat
            </div>
            
            <form id="suratForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label><i class='bx bx-hash'></i> Nomor Surat</label>
                        <input type="text" id="nomor_surat" value="<?php echo $data_default['nomor_surat']; ?>" placeholder="Contoh: 003/SMK7/BK/II/2026">
                    </div>
                    <div class="form-group">
                        <label><i class='bx bx-calendar'></i> Tanggal Surat</label>
                        <input type="text" id="tanggal_surat" value="<?php echo $data_default['tanggal_surat']; ?>" placeholder="DD-MM-YYYY">
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label><i class='bx bx-user'></i> Nama Siswa</label>
                        <input type="text" id="nama_siswa" value="<?php echo $data_default['nama_siswa']; ?>" required placeholder="Masukkan nama lengkap siswa">
                    </div>
                    <div class="form-group">
                        <label><i class='bx bx-building'></i> Kelas</label>
                        <input type="text" id="kelas" value="<?php echo $data_default['kelas']; ?>" required placeholder="Contoh: XII">
                    </div>
                    <div class="form-group">
                        <label><i class='bx bx-book'></i> Jurusan</label>
                        <input type="text" id="jurusan" value="<?php echo $data_default['jurusan']; ?>" required placeholder="Contoh: Teknik Komputer dan Jaringan">
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class='bx bx-user-voice'></i> Nama Orang Tua/Wali</label>
                    <input type="text" id="nama_orangtua" value="<?php echo $data_default['nama_orangtua']; ?>" required placeholder="Masukkan nama orang tua/wali siswa">
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label><i class='bx bx-calendar-event'></i> Tanggal Pertemuan</label>
                        <input type="text" id="tanggal_pertemuan" value="<?php echo $data_default['tanggal_pertemuan']; ?>" required placeholder="DD-MM-YYYY">
                    </div>
                    <div class="form-group">
                        <label><i class='bx bx-time'></i> Jam Pertemuan</label>
                        <input type="text" id="jam_pertemuan" value="<?php echo $data_default['jam_pertemuan']; ?>" required placeholder="Contoh: 10:00">
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class='bx bx-map'></i> Tempat Pertemuan</label>
                    <input type="text" id="tempat_pertemuan" value="<?php echo $data_default['tempat_pertemuan']; ?>" required placeholder="Contoh: Ruang BK SMK Negeri 7 Batam">
                </div>
                
                <div class="form-group">
                    <label><i class='bx bx-note'></i> Alasan Pemanggilan</label>
                    <textarea id="alasan_pemanggilan" rows="3" required placeholder="Jelaskan alasan pemanggilan orang tua/wali..."><?php echo $data_default['alasan_pemanggilan']; ?></textarea>
                </div>
            </form>
            
            <div class="controls">
                <button type="button" onclick="showPreview()" class="btn btn-preview">
                    <i class='bx bx-show'></i> Lihat Preview
                </button>
                <button type="button" onclick="resetForm()" class="btn btn-reset">
                    <i class='bx bx-reset'></i> Reset Form
                </button>
                <button type="button" onclick="printLetter()" class="btn btn-print">
                    <i class='bx bx-printer'></i> Cetak Surat
                </button>
                <a href="../dashboard_guru.php" class="btn btn-back">
                    <i class='bx bx-arrow-back'></i> Kembali
                </a>
            </div>
        </div>
    </div>
    
    
    <div class="letter-container" id="letterContainer">
        
        <div class="letter-header">
            <h1>PEMERINTAH KOTA BATAM</h1>
            <h1>DINAS PENDIDIKAN</h1>
            <h2><?php echo strtoupper($sekolah); ?></h2>
            <p><?php echo $alamat_sekolah; ?></p>
            <p>Telepon: <?php echo $telepon_sekolah; ?> | Email: smkn7batam@sch.id</p>
        </div>
        
        
        <div class="letter-info">
            <div class="letter-number" id="nomorSuratText">
                Nomor: <?php echo $data_default['nomor_surat']; ?>
            </div>
            <div class="letter-date" id="tanggalSuratText">
                Batam, <?php echo $data_default['tanggal_surat']; ?>
            </div>
            <div class="letter-subject">
                Hal: <span class="underline">Pemanggilan Orang Tua/Wali Siswa</span>
            </div>
        </div>
        
        
        <div class="letter-address">
            <p>Kepada Yth.</p>
            <p class="bold" id="namaOrangtuaText"><?php echo $data_default['nama_orangtua']; ?></p>
            <p>(Orang Tua/Wali dari)</p>
            <p class="bold" id="namaSiswaText"><?php echo $data_default['nama_siswa']; ?></p>
            <p>Kelas: <span id="kelasText"><?php echo $data_default['kelas']; ?></span> - Jurusan: <span id="jurusanText"><?php echo $data_default['jurusan']; ?></span></p>
            <p>di Tempat</p>
        </div>
        
        
        <div class="letter-content">
            <p class="indent">Dengan hormat,</p>
            
            <p class="indent">Sehubungan dengan <span class="bold" id="alasanPemanggilanText"><?php echo $data_default['alasan_pemanggilan']; ?></span>, maka melalui surat ini kami mengharapkan kehadiran Bapak/Ibu pada:</p>
            
            <div class="meeting-info">
                <p><span class="bold">Hari/Tanggal</span> : <span id="tanggalPertemuanText"><?php echo $data_default['tanggal_pertemuan']; ?></span></p>
                <p><span class="bold">Pukul</span> : <span id="jamPertemuanText"><?php echo $data_default['jam_pertemuan']; ?> WIB</span></p>
                <p><span class="bold">Tempat</span> : <span id="tempatPertemuanText"><?php echo $data_default['tempat_pertemuan']; ?></span></p>
            </div>
            
            <p class="indent">Demikian surat pemanggilan ini kami sampaikan. Atas perhatian dan kehadiran Bapak/Ibu, kami ucapkan terima kasih.</p>
        </div>
        
        
        <div class="place-date" id="tempatTanggalText">
            Batam, <?php echo $data_default['tanggal_surat']; ?>
        </div>
        
        <div class="letter-signature">
            <div class="signature-row">
                <div class="signature-box">
                    <p>Guru Bimbingan Konseling,</p>
                    <div class="signature-space"></div>
                    <p class="signature-name"><?php echo $nama_guru; ?></p>
                    <p class="signature-nip">NIP. <?php echo $nip_guru; ?></p>
                </div>
                
                <div class="signature-box">
                    <p>Kepala Sekolah,</p>
                    <div class="signature-space"></div>
                    <p class="signature-name">Nursyabani, M.Pd</p>
                    <p class="signature-nip">NIP.199205142023052008 </p>
                </div>
            </div>
        </div>
        
        
        <div class="letter-footer">
            <p>Surat ini dicetak dari Sistem Bimbingan Konseling <?php echo $sekolah; ?></p>
            <p id="printDate">Tanggal cetak: <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </div>

    <script>
        function formatDate(dateStr) {
            if (!dateStr) return '';
            
            const parts = dateStr.split('-');
            if (parts.length === 3) {
                const day = parts[0];
                const month = parts[1];
                const year = parts[2];
                
                const bulan = [
                    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                ];
                
                return `${day} ${bulan[parseInt(month) - 1]} ${year}`;
            }
            
            return dateStr;
        }
        
        function updatePreview() {
            const formData = {
                nomor_surat: document.getElementById('nomor_surat').value,
                tanggal_surat: document.getElementById('tanggal_surat').value,
                nama_siswa: document.getElementById('nama_siswa').value,
                kelas: document.getElementById('kelas').value,
                jurusan: document.getElementById('jurusan').value,
                nama_orangtua: document.getElementById('nama_orangtua').value,
                tanggal_pertemuan: document.getElementById('tanggal_pertemuan').value,
                jam_pertemuan: document.getElementById('jam_pertemuan').value,
                tempat_pertemuan: document.getElementById('tempat_pertemuan').value,
                alasan_pemanggilan: document.getElementById('alasan_pemanggilan').value
            };
            
            document.getElementById('nomorSuratText').innerHTML = `Nomor: ${formData.nomor_surat}`;
            
            const formattedDate = formatDate(formData.tanggal_surat);
            document.getElementById('tanggalSuratText').textContent = `Batam, ${formattedDate}`;
            document.getElementById('tempatTanggalText').innerHTML = `Batam, ${formattedDate}`;
            
            document.getElementById('namaSiswaText').textContent = formData.nama_siswa;
            document.getElementById('kelasText').textContent = formData.kelas;
            document.getElementById('jurusanText').textContent = formData.jurusan;
            document.getElementById('namaOrangtuaText').textContent = formData.nama_orangtua;
            document.getElementById('alasanPemanggilanText').textContent = formData.alasan_pemanggilan;
            
            const pertemuanDate = formatDate(formData.tanggal_pertemuan);
            document.getElementById('tanggalPertemuanText').textContent = pertemuanDate;
            
            document.getElementById('jamPertemuanText').textContent = formData.jam_pertemuan.includes('WIB') ? 
                formData.jam_pertemuan : `${formData.jam_pertemuan} WIB`;
            document.getElementById('tempatPertemuanText').textContent = formData.tempat_pertemuan;
        }
        
        function showPreview() {
            updatePreview();
            document.getElementById('letterContainer').classList.add('active');
            document.getElementById('letterContainer').scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
        }
        
        function resetForm() {
            if (confirm('Reset semua data ke nilai default?')) {
                const defaults = <?php echo json_encode($data_default); ?>;
                Object.keys(defaults).forEach(key => {
                    const element = document.getElementById(key);
                    if (element) element.value = defaults[key];
                });
                
                updatePreview();
                document.getElementById('letterContainer').classList.remove('active');
            }
        }
        
        function printLetter() {
            const now = new Date();
            const printDate = now.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('printDate').textContent = `Tanggal cetak: ${printDate}`;
            
            updatePreview();
            
            document.getElementById('letterContainer').classList.add('active');
            
            setTimeout(() => {
                window.print();
            }, 300);
        }
        
        document.querySelectorAll('#suratForm input, #suratForm textarea').forEach(element => {
            element.addEventListener('input', function() {
                if (document.getElementById('letterContainer').classList.contains('active')) {
                    updatePreview();
                }
            });
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            updatePreview();
        });
    </script>
</body>
</html>