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

$jumlah_notif_konsultasi = 0;
$sql_guru_id = "SELECT ID FROM guru_bk WHERE USER_ID = ?";
$stmt_guru_id = $koneksi->prepare($sql_guru_id);
if ($stmt_guru_id) {
    $stmt_guru_id->bind_param("i", $user_id);
    $stmt_guru_id->execute();
    $guru_id_data = $stmt_guru_id->get_result()->fetch_assoc();
    $stmt_guru_id->close();

    if ($guru_id_data && isset($guru_id_data['ID'])) {
        $guru_id = (int) $guru_id_data['ID'];
        $sql_menunggu = "SELECT COUNT(*) as jumlah FROM konsultasi
                        WHERE STATUS = 'Menunggu'
                        AND (PILIHAN_GURU_1 = ? OR PILIHAN_GURU_2 = ?)";
        $stmt_menunggu = $koneksi->prepare($sql_menunggu);
        if ($stmt_menunggu) {
            $stmt_menunggu->bind_param("ii", $guru_id, $guru_id);
            $stmt_menunggu->execute();
            $result_menunggu = $stmt_menunggu->get_result()->fetch_assoc();
            $jumlah_notif_konsultasi = $result_menunggu['jumlah'] ?? 0;
            $stmt_menunggu->close();
        }
    }
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

    /* Layout Theme: match approve_konsultasi.php */
    :root { --bg-gradient: linear-gradient(135deg, #1e40af 0%, #2563eb 55%, #3b82f6 100%); --surface: rgba(255, 255, 255, 0.94); --surface-soft: rgba(255, 255, 255, 0.9); --surface-card: rgba(255, 255, 255, 0.95); --text-main: #2d3748; --text-muted: #718096; --accent: #2563eb; --border-soft: rgba(37, 99, 235, 0.18); --shadow-soft: 0 12px 28px rgba(16, 24, 40, 0.12); }
    body { background: var(--bg-gradient); color: var(--text-main); transition: background 0.35s ease, color 0.35s ease; padding: 0; }
    body.dark-mode { --bg-gradient: linear-gradient(135deg, #0f172a 0%, #111827 45%, #1f2937 100%); --surface: rgba(17, 24, 39, 0.92); --surface-soft: rgba(17, 24, 39, 0.88); --surface-card: rgba(17, 24, 39, 0.9); --text-main: #e5e7eb; --text-muted: #9ca3af; --accent: #60a5fa; --border-soft: rgba(96, 165, 250, 0.26); --shadow-soft: 0 12px 28px rgba(2, 6, 23, 0.5); }
    body.sidebar-open { overflow: hidden; }
    .header { margin-left: 270px; background: var(--surface); border-bottom: 3px solid var(--accent); box-shadow: var(--shadow-soft); overflow: visible; z-index: 1000; transition: margin-left 0.3s ease, background 0.3s ease; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; position: relative; }
    .brand-left { display: flex; align-items: center; gap: 14px; }
    .brand-left > a { text-decoration: none; color: inherit; }
    .header h1 { font-size: 28px; font-weight: 700; letter-spacing: 0.5px; background: linear-gradient(135deg, var(--accent), #1d4ed8); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; display: flex; align-items: center; gap: 10px; margin: 0; }
    .user-info { display: flex; align-items: center; gap: 12px; }
    .sidebar-toggle, .theme-toggle { border: 1px solid var(--border-soft); background: rgba(255, 255, 255, 0.4); color: var(--text-main); border-radius: 12px; height: 42px; padding: 0 14px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; transition: all 0.25s ease; font-weight: 600; text-decoration: none; }
    body.dark-mode .sidebar-toggle, body.dark-mode .theme-toggle { background: rgba(31, 41, 55, 0.85); }
    .sidebar-toggle:hover, .theme-toggle:hover { border-color: var(--accent); color: var(--accent); }
    .theme-toggle:hover { transform: translateY(-1px); }
    .sidebar-toggle { display: none; font-size: 22px; padding: 0 12px; min-width: 46px; min-height: 46px; touch-action: manipulation; position: relative; z-index: 1405; }
    .theme-toggle i { font-size: 18px; }
    .nav { position: fixed; left: 0; top: 0; bottom: 0; width: 270px; background: var(--surface-soft); border-right: 1px solid var(--border-soft); display: flex; flex-direction: column; gap: 10px; padding: 16px 18px 18px; overflow-y: auto; overflow-x: hidden; z-index: 1300; backdrop-filter: blur(18px); transition: transform 0.3s ease, background 0.3s ease; }
    .sidebar-top { margin-bottom: 14px; padding: 12px; border-radius: 14px; background: linear-gradient(135deg, rgba(37, 99, 235, 0.2), rgba(59, 130, 246, 0.18)); border: 1px solid var(--border-soft); }
    .sidebar-top h4 { font-size: 13px; font-weight: 700; letter-spacing: 0.4px; margin-bottom: 10px; color: var(--text-main); text-transform: uppercase; }
    .sidebar-icons { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
    .sidebar-icon { height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255, 255, 255, 0.35); background: rgba(255, 255, 255, 0.45); color: var(--text-main); font-size: 18px; }
    .nav a { color: var(--text-muted); width: 100%; border: 1px solid transparent; padding: 14px 22px; border-radius: 12px; font-weight: 600; font-size: 14px; transition: all 0.3s ease; white-space: nowrap; position: relative; overflow: hidden; display: flex; align-items: center; gap: 8px; justify-content: flex-start; text-decoration: none; }
    .nav a::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(37, 99, 235, 0.12), rgba(59, 130, 246, 0.12)); transform: scaleX(0); transform-origin: left; transition: transform 0.3s ease; z-index: -1; }
    .nav a:hover { color: var(--accent); border-color: var(--border-soft); transform: translateX(4px); }
    .nav a:hover::before { transform: scaleX(1); }
    .nav a.active, .nav a.tap-active { color: var(--accent); border-color: var(--border-soft); transform: translateX(4px) scale(0.98); background: linear-gradient(135deg, rgba(37, 99, 235, 0.14), rgba(59, 130, 246, 0.14)); }
    .nav-badge { position: relative; }
    .badge { position: absolute; top: 2px; right: 2px; background: linear-gradient(135deg, #ff6b6b, #ee5a52); color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 10px; font-weight: bold; display: flex; align-items: center; justify-content: center; animation: pulse 2s infinite; box-shadow: 0 2px 8px rgba(255, 107, 107, 0.4); border: 2px solid white; }
    .container { margin-left: 270px; max-width: none; transition: margin-left 0.3s ease; padding: 24px; }
    .dashboard { background: var(--surface-card); border-color: var(--border-soft); }
    .form-section { background: transparent; }
    .sidebar-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45); opacity: 0; visibility: hidden; transition: opacity 0.25s ease, visibility 0.25s ease; z-index: 1200; }
    .sidebar-overlay.show { opacity: 1; visibility: visible; }
    @media (max-width: 1024px) { .header { margin-left: 0; padding: 12px 14px; display: block; } .container { margin-left: 0; padding: 20px; } .brand-left { width: 100%; justify-content: space-between; margin-bottom: 10px; position: relative; z-index: 1405; } .sidebar-toggle { display: inline-flex; } .nav { transform: translateX(-105%); width: 280px; padding-top: 16px; box-shadow: 0 10px 30px rgba(2, 6, 23, 0.35); } .nav.open { transform: translateX(0); } .user-info { flex-direction: row; align-items: center; gap: 10px; flex-wrap: nowrap; justify-content: flex-end; width: 100%; } .theme-toggle span { display: none; } }
    @media (max-width: 768px) { .header { padding: 12px; } .brand-left { justify-content: center; margin-bottom: 8px; } .brand-left a { margin: 0 auto; text-align: center; } .sidebar-toggle { position: absolute; left: 0; top: 100%; transform: translateY(-50%); } .header h1 { font-size: 18px; margin: 0; text-align: center; } .user-info { gap: 8px; justify-content: center; flex-wrap: wrap; margin-top: 2px; } }
    @media (max-width: 480px) { .theme-toggle { height: 36px; padding: 0 9px; } .brand-left a h1 { font-size: 16px; text-align: center; } }
</style>
</head>
<body>
    <div class="header">
        <div class="brand-left">
            <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Buka menu">
                <i class='bx bx-menu'></i>
            </button>
            <a href="../halaman utama.php">
                <h1><i class='bx bx-file'></i> Dokumen Orang Tua</h1>
            </a>
        </div>
        <div class="user-info">
            <button class="theme-toggle" id="themeToggle" type="button" aria-label="Ganti mode tema">
                <i class='bx bx-moon'></i>
                <span>Mode</span>
            </button>
            <a href="../logout.php" class="theme-toggle" aria-label="Logout akun">
                <i class='bx bx-log-out'></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <div class="nav">
        <div class="sidebar-top">
            <h4>Menu Guru BK</h4>
            <div class="sidebar-icons">
                <span class="sidebar-icon"><i class='bx bx-home-heart'></i></span>
                <span class="sidebar-icon"><i class='bx bx-check-shield'></i></span>
                <span class="sidebar-icon"><i class='bx bx-calendar-star'></i></span>
                <span class="sidebar-icon"><i class='bx bx-clipboard'></i></span>
            </div>
        </div>
        <a href="../dashboard_guru.php">
            <i class='bx bx-home'></i>
            Dashboard
        </a>
        <a href="../approve_konsultasi.php" class="nav-badge">
            <i class='bx bx-check-shield'></i>
            Approve Konsultasi
            <?php if ($jumlah_notif_konsultasi > 0): ?>
                <span class="badge"><?php echo $jumlah_notif_konsultasi; ?></span>
            <?php endif; ?>
        </a>
        <a href="../sesi_konsultasi.php">
            <i class='bx bx-conversation'></i>
            Sesi Konsultasi
        </a>
        <a href="../kelola_jadwal.php">
            <i class='bx bx-calendar'></i>
            Kelola Jadwal
        </a>
        <a href="../review_form.php">
            <i class='bx bx-clipboard'></i>
            Review Form
        </a>
        <a href="pemanggilan_ortu.php" class="active">
            <i class='bx bx-file'></i>
            Dokumen
        </a>
        <a href="../profil.php">
            <i class='bx bx-face'></i>
            Profil
        </a>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="container">
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
            const sidebar = document.querySelector('.nav');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const themeToggle = document.getElementById('themeToggle');

            function closeSidebar() {
                if (sidebar && sidebarOverlay) {
                    sidebar.classList.remove('open');
                    sidebarOverlay.classList.remove('show');
                    document.body.classList.remove('sidebar-open');
                }
            }

            function toggleSidebar() {
                if (sidebar && sidebarOverlay) {
                    sidebar.classList.toggle('open');
                    sidebarOverlay.classList.toggle('show');
                    document.body.classList.toggle('sidebar-open');
                }
            }

            const savedTheme = localStorage.getItem('dashboard_theme');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-mode');
                if (themeToggle) {
                    themeToggle.innerHTML = "<i class='bx bx-sun'></i><span>Mode</span>";
                }
            }

            if (themeToggle) {
                themeToggle.addEventListener('click', function() {
                    const isDark = document.body.classList.toggle('dark-mode');
                    localStorage.setItem('dashboard_theme', isDark ? 'dark' : 'light');
                    this.innerHTML = isDark
                        ? "<i class='bx bx-sun'></i><span>Mode</span>"
                        : "<i class='bx bx-moon'></i><span>Mode</span>";
                });
            }

            if (sidebarToggle) {
                let lastSidebarToggle = 0;
                const handleSidebarToggle = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const now = Date.now();
                    if (now - lastSidebarToggle < 250) {
                        return;
                    }
                    lastSidebarToggle = now;
                    toggleSidebar();
                };

                sidebarToggle.addEventListener('click', handleSidebarToggle);
                sidebarToggle.addEventListener('touchend', handleSidebarToggle, { passive: false });
            }

            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', closeSidebar);
            }

            document.querySelectorAll('.nav a').forEach(link => {
                link.addEventListener('touchstart', function() {
                    this.classList.add('tap-active');
                }, { passive: true });

                link.addEventListener('touchend', function() {
                    setTimeout(() => {
                        this.classList.remove('tap-active');
                    }, 140);
                }, { passive: true });

                link.addEventListener('touchcancel', function() {
                    this.classList.remove('tap-active');
                }, { passive: true });

                link.addEventListener('click', function() {
                    if (window.innerWidth <= 1024) {
                        closeSidebar();
                    }
                });
            });

            window.addEventListener('resize', function() {
                if (window.innerWidth > 1024) {
                    closeSidebar();
                }
            });

            updatePreview();
        });
    </script>
</body>
</html>
