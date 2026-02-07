<?php
session_start();
include 'koneksi.php';

$sql_pending = "SELECT COUNT(*) as total FROM password_reset_requests WHERE STATUS = 'pending'";
$result_pending = $koneksi->query($sql_pending);
$pending_count = $result_pending ? $result_pending->fetch_assoc()['total'] : 0;

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: index.php");
    exit();
}

if (isset($_POST['export_excel'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="laporan_bk_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    $sql_stats = "SELECT 
        (SELECT COUNT(*) FROM users WHERE ROLE = 'Siswa' AND STATUS = 'Aktif') as total_siswa,
        (SELECT COUNT(*) FROM users WHERE ROLE = 'Guru_BK' AND STATUS = 'Aktif') as total_guru,
        (SELECT COUNT(*) FROM konsultasi) as total_konsultasi,
        (SELECT COUNT(*) FROM konsultasi WHERE STATUS = 'Menunggu') as menunggu,
        (SELECT COUNT(*) FROM konsultasi WHERE STATUS = 'Disetujui') as disetujui,
        (SELECT COUNT(*) FROM konsultasi WHERE STATUS = 'Selesai') as selesai,
        (SELECT COUNT(*) FROM konsultasi WHERE STATUS = 'Ditolak') as ditolak";
    
    $result_stats = $koneksi->query($sql_stats);
    $stats = $result_stats->fetch_assoc();
    
    $sql_top_issues = "SELECT TOPIK_KONSULTASI, COUNT(*) as jumlah 
                       FROM konsultasi 
                       GROUP BY TOPIK_KONSULTASI 
                       ORDER BY jumlah DESC 
                       LIMIT 5";
    $top_issues = $koneksi->query($sql_top_issues);
    
    $sql_guru_stats = "SELECT u.NAMA_LENGKAP, 
                              COUNT(k.ID) as total_konsultasi,
                              SUM(CASE WHEN k.STATUS = 'Selesai' THEN 1 ELSE 0 END) as selesai
                       FROM guru_bk g
                       JOIN users u ON g.USER_ID = u.ID
                       LEFT JOIN konsultasi k ON g.ID = k.GURU_BK_ID
                       WHERE u.STATUS = 'Aktif'
                       GROUP BY g.ID, u.NAMA_LENGKAP
                       ORDER BY total_konsultasi DESC";
    $guru_stats = $koneksi->query($sql_guru_stats);
    
    echo '<table border="1" style="width:100%;">';
    
    echo '<tr>';
    echo '<th colspan="8" style="background-color:#4CAF50;color:white;font-size:18px;padding:15px;text-align:center;">LAPORAN SISTEM BIMBINGAN KONSELING</th>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<td colspan="8" style="text-align:center;padding:10px;font-weight:bold;">Tanggal: ' . date('d/m/Y H:i:s') . '</td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<td colspan="8" style="padding:10px;"></td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<th colspan="8" style="background-color:#2196F3;color:white;padding:12px;text-align:center;">STATISTIK SISTEM</th>';
    echo '</tr>';
    
    echo '<tr style="background-color:#E3F2FD;">';
    echo '<th style="padding:10px;text-align:center;">Total Siswa</th>';
    echo '<th style="padding:10px;text-align:center;">Total Guru BK</th>';
    echo '<th style="padding:10px;text-align:center;">Total Konsultasi</th>';
    echo '<th style="padding:10px;text-align:center;">Menunggu</th>';
    echo '<th style="padding:10px;text-align:center;">Disetujui</th>';
    echo '<th style="padding:10px;text-align:center;">Selesai</th>';
    echo '<th style="padding:10px;text-align:center;">Ditolak</th>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<td style="padding:10px;text-align:center;font-weight:bold;">' . $stats['total_siswa'] . '</td>';
    echo '<td style="padding:10px;text-align:center;font-weight:bold;">' . $stats['total_guru'] . '</td>';
    echo '<td style="padding:10px;text-align:center;font-weight:bold;">' . $stats['total_konsultasi'] . '</td>';
    echo '<td style="padding:10px;text-align:center;font-weight:bold;">' . $stats['menunggu'] . '</td>';
    echo '<td style="padding:10px;text-align:center;font-weight:bold;">' . $stats['disetujui'] . '</td>';
    echo '<td style="padding:10px;text-align:center;font-weight:bold;">' . $stats['selesai'] . '</td>';
    echo '<td style="padding:10px;text-align:center;font-weight:bold;">' . $stats['ditolak'] . '</td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<td colspan="8" style="padding:10px;"></td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<th colspan="8" style="background-color:#2196F3;color:white;padding:12px;text-align:center;">TOP 5 MASALAH KONSULTASI</th>';
    echo '</tr>';
    
    if ($top_issues->num_rows > 0) {
        echo '<tr style="background-color:#E3F2FD;">';
        echo '<th style="padding:10px;text-align:center;">No</th>';
        echo '<th style="padding:10px;text-align:center;">Topik Konsultasi</th>';
        echo '<th style="padding:10px;text-align:center;">Jumlah Kasus</th>';
        echo '<th style="padding:10px;text-align:center;">Persentase</th>';
        echo '<td colspan="4" style="padding:10px;text-align:center;"></td>';
        echo '</tr>';
        
        $counter = 1;
        while($issue = $top_issues->fetch_assoc()) {
            $percentage = $stats['total_konsultasi'] > 0 ? 
                round(($issue['jumlah'] / $stats['total_konsultasi']) * 100, 2) : 0;
            
            echo '<tr>';
            echo '<td style="padding:10px;text-align:center;">' . $counter . '</td>';
            echo '<td style="padding:10px;">' . $issue['TOPIK_KONSULTASI'] . '</td>';
            echo '<td style="padding:10px;text-align:center;font-weight:bold;">' . $issue['jumlah'] . '</td>';
            echo '<td style="padding:10px;text-align:center;">' . $percentage . '%</td>';
            echo '<td colspan="4" style="padding:10px;"></td>';
            echo '</tr>';
            $counter++;
        }
    } else {
        echo '<tr>';
        echo '<td colspan="8" style="padding:10px;text-align:center;">Belum ada data konsultasi</td>';
        echo '</tr>';
    }
    
    echo '<tr>';
    echo '<td colspan="8" style="padding:10px;"></td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<th colspan="8" style="background-color:#2196F3;color:white;padding:12px;text-align:center;">KINERJA GURU BK</th>';
    echo '</tr>';
    
    if ($guru_stats->num_rows > 0) {
        echo '<tr style="background-color:#E3F2FD;">';
        echo '<th style="padding:10px;text-align:center;">Nama Guru</th>';
        echo '<th style="padding:10px;text-align:center;">Total Konsultasi</th>';
        echo '<th style="padding:10px;text-align:center;">Selesai</th>';
        echo '<th style="padding:10px;text-align:center;">Success Rate</th>';
        echo '<th style="padding:10px;text-align:center;">Status</th>';
        echo '<td colspan="3" style="padding:10px;text-align:center;"></td>';
        echo '</tr>';
        
        while($guru = $guru_stats->fetch_assoc()) {
            $success_rate = $guru['total_konsultasi'] > 0 ? 
                round(($guru['selesai'] / $guru['total_konsultasi']) * 100) : 0;
            
            $status = '';
            if ($success_rate >= 80) {
                $status = 'Sangat Baik';
            } elseif ($success_rate >= 60) {
                $status = 'Baik';
            } elseif ($success_rate >= 40) {
                $status = 'Cukup';
            } else {
                $status = 'Perlu Perbaikan';
            }
            
            echo '<tr>';
            echo '<td style="padding:10px;font-weight:bold;">' . $guru['NAMA_LENGKAP'] . '</td>';
            echo '<td style="padding:10px;text-align:center;">' . $guru['total_konsultasi'] . '</td>';
            echo '<td style="padding:10px;text-align:center;">' . $guru['selesai'] . '</td>';
            echo '<td style="padding:10px;text-align:center;font-weight:bold;">' . $success_rate . '%</td>';
            echo '<td style="padding:10px;text-align:center;">' . $status . '</td>';
            echo '<td colspan="3" style="padding:10px;"></td>';
            echo '</tr>';
        }
    } else {
        echo '<tr>';
        echo '<td colspan="8" style="padding:10px;text-align:center;">Belum ada data kinerja guru</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    exit();
}

$sql_stats = "SELECT 
    (SELECT COUNT(*) FROM users WHERE ROLE = 'Siswa' AND STATUS = 'Aktif') as total_siswa,
    (SELECT COUNT(*) FROM users WHERE ROLE = 'Guru_BK' AND STATUS = 'Aktif') as total_guru,
    (SELECT COUNT(*) FROM konsultasi) as total_konsultasi,
    (SELECT COUNT(*) FROM konsultasi WHERE STATUS = 'Menunggu') as menunggu,
    (SELECT COUNT(*) FROM konsultasi WHERE STATUS = 'Disetujui') as disetujui,
    (SELECT COUNT(*) FROM konsultasi WHERE STATUS = 'Selesai') as selesai,
    (SELECT COUNT(*) FROM konsultasi WHERE STATUS = 'Ditolak') as ditolak";

$result_stats = $koneksi->query($sql_stats);
$stats = $result_stats->fetch_assoc();

$sql_top_issues = "SELECT TOPIK_KONSULTASI, COUNT(*) as jumlah 
                   FROM konsultasi 
                   GROUP BY TOPIK_KONSULTASI 
                   ORDER BY jumlah DESC 
                   LIMIT 5";
$top_issues = $koneksi->query($sql_top_issues);

$sql_guru_stats = "SELECT u.NAMA_LENGKAP, 
                          COUNT(k.ID) as total_konsultasi,
                          SUM(CASE WHEN k.STATUS = 'Selesai' THEN 1 ELSE 0 END) as selesai
                   FROM guru_bk g
                   JOIN users u ON g.USER_ID = u.ID
                   LEFT JOIN konsultasi k ON g.ID = k.GURU_BK_ID
                   WHERE u.STATUS = 'Aktif'
                   GROUP BY g.ID, u.NAMA_LENGKAP
                   ORDER BY total_konsultasi DESC";
$guru_stats = $koneksi->query($sql_guru_stats);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - APK BK</title>
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
        
        .floating:nth-child(3) {
            width: 200px;
            height: 200px;
            bottom: 10%;
            left: 40%;
            background: radial-gradient(circle, rgba(236, 72, 153, 0.4) 0%, rgba(236, 72, 153, 0.1) 70%);
            animation-delay: 4s;
            box-shadow: 0 0 50px rgba(236, 72, 153, 0.3);
        }
        
        @keyframes float {
            0%, 100% { 
                transform: translateY(0px) translateX(0px) rotate(0deg) scale(1);
                opacity: 0.2;
            }
            25% { 
                transform: translateY(-20px) translateX(10px) rotate(90deg) scale(1.03);
                opacity: 0.4;
            }
            50% { 
                transform: translateY(-10px) translateX(-5px) rotate(180deg) scale(1.06);
                opacity: 0.3;
            }
            75% { 
                transform: translateY(-15px) translateX(3px) rotate(270deg) scale(1.03);
                opacity: 0.5;
            }
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
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(139, 92, 246, 0.05), transparent);
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
        
        .user-info span {
            font-weight: 600;
            color: #cbd5e1;
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
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .logout-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }
        
        .logout-btn:hover::before {
            left: 100%;
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
            background: rgba(139, 92, 246, 0.2);
            color: #8b5cf6;
            border-color: #8b5cf6;
        }

        .nav a .badge {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            margin-left: 8px;
            animation: pulse 2s infinite;
            min-width: 20px;
            display: inline-block;
            text-align: center;
            box-shadow: 0 3px 10px rgba(239, 68, 68, 0.3);
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .container { 
            padding: 40px; 
            max-width: 1400px; 
            margin: 0 auto;
            position: relative;
            z-index: 5;
        }
        
        .page-header {
            background: rgba(15, 23, 42, 0.8);
            padding: 35px 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            border: 1px solid rgba(139, 92, 246, 0.2);
            box-shadow: 0 15px 35px rgba(139, 92, 246, 0.15);
            backdrop-filter: blur(15px);
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.8s ease-out;
        }
        
        .page-header h2 {
            font-size: 32px;
            margin-bottom: 10px;
            color: #f8fafc;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .page-header p {
            color: #94a3b8;
            font-size: 16px;
            font-weight: 500;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card { 
            background: rgba(15, 23, 42, 0.8);
            padding: 30px 25px;
            border-radius: 20px;
            text-align: center;
            border: 1px solid rgba(139, 92, 246, 0.2);
            box-shadow: 0 15px 35px rgba(139, 92, 246, 0.15);
            backdrop-filter: blur(15px);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #8b5cf6, #06b6d4);
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 45px rgba(139, 92, 246, 0.25);
            border-color: #8b5cf6;
        }
        
        .stat-card:hover::before {
            transform: scaleX(1);
        }
        
        .stat-card h3 { 
            font-size: 3em; 
            margin-bottom: 15px;
            font-weight: 800;
            background: linear-gradient(135deg, #8b5cf6, #06b6d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-card p { 
            color: #94a3b8;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
        }
        
        .report-section {
            background: rgba(15, 23, 42, 0.8);
            padding: 35px;
            border-radius: 20px;
            border: 1px solid rgba(139, 92, 246, 0.2);
            box-shadow: 0 15px 35px rgba(139, 92, 246, 0.15);
            backdrop-filter: blur(15px);
            margin-bottom: 30px;
            animation: fadeIn 0.8s ease-out;
        }
        
        .report-section h3 {
            font-size: 24px;
            margin-bottom: 25px;
            color: #8b5cf6;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid rgba(139, 92, 246, 0.2);
            padding-bottom: 15px;
        }
        
        .issue-list, .guru-list {
            display: grid;
            gap: 18px;
        }
        
        .issue-item, .guru-item {
            background: rgba(139, 92, 246, 0.1);
            padding: 20px;
            border-radius: 16px;
            border-left: 4px solid #8b5cf6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }
        
        .issue-item:hover, .guru-item:hover {
            background: rgba(139, 92, 246, 0.15);
            transform: translateX(8px);
        }
        
        .issue-topic {
            font-weight: 600;
            color: #f8fafc;
            font-size: 16px;
        }
        
        .issue-count {
            background: linear-gradient(135deg, #8b5cf6, #06b6d4);
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            color: white;
            font-size: 14px;
        }
        
        .guru-name {
            font-weight: 700;
            color: #8b5cf6;
            font-size: 16px;
        }
        
        .guru-stats {
            display: flex;
            gap: 20px;
        }
        
        .stat {
            text-align: center;
            padding: 10px 15px;
            background: rgba(15, 23, 42, 0.6);
            border-radius: 12px;
            border: 1px solid rgba(139, 92, 246, 0.2);
            min-width: 80px;
        }
        
        .stat-number {
            font-size: 20px;
            font-weight: 800;
            color: #f8fafc;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        
        .success-rate {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .export-buttons {
            display: flex;
            gap: 20px;
            margin-top: 40px;
            justify-content: center;
        }
        
        .btn {
            padding: 16px 32px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            font-size: 16px;
            position: relative;
            overflow: hidden;
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
        
        .btn-excel {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
        }
        
        .btn-print {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 25px rgba(139, 92, 246, 0.4);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 40px;
            background: rgba(15, 23, 42, 0.8);
            border-radius: 20px;
            border: 3px dashed rgba(139, 92, 246, 0.3);
            backdrop-filter: blur(15px);
        }
        
        .empty-state h4 {
            color: #8b5cf6;
            font-size: 20px;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .empty-state p {
            color: #94a3b8;
            font-size: 14px;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
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
            
            .nav {
                padding: 12px 20px;
                flex-wrap: wrap;
                justify-content: center;
                gap: 12px;
            }
            
            .container {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .export-buttons {
                flex-direction: column;
            }
            
            .guru-stats {
                flex-direction: column;
                gap: 10px;
            }
            
            .stat {
                min-width: auto;
            }
            
            .floating {
                display: none;
            }
            
            .issue-item, .guru-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .guru-stats {
                width: 100%;
                justify-content: space-between;
            }
        }
        
        @media (max-width: 480px) {
            .page-header {
                padding: 25px 20px;
            }
            
            .page-header h2 {
                font-size: 24px;
            }
            
            .report-section {
                padding: 25px;
            }
            
            .stat-card {
                padding: 25px 20px;
            }
            
            .stat-card h3 {
                font-size: 2.5em;
            }
        }
    </style>
</head>
<body>
    
    <div class="floating"></div>
    <div class="floating"></div>
    <div class="floating"></div>
    
    <div class="header">
        <h1>⚡ APK BK - Laporan & Analytics</h1>
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
        <?php if ($pending_count > 0): ?>
            <span class="badge"><?php echo $pending_count; ?></span>
        <?php endif; ?>
        </a>
        <a href="admin_reports.php" class="active">
            <i class='bx bx-group'></i>
            Laporan
        </a>
         <a href="admin_laporan_konsultasi.php">
            <i class='bx bx-bar-chart'></i>
            Laporan Konsultasi
        </a>
        <a href="admin_settings.php">
            <i class='bx bx-cog'></i>
            Pengaturan
        </a>
        <a href="profil.php">
            <i class='bx bx-face'></i>
            Profil 
        </a>
    </div>
    
    <div class="container">
        <div class="page-header">
            <h2><i class='bx bx-bar-chart'></i> Laporan & Analytics Sistem</h2>
            <p>Statistik dan analisis kinerja sistem Bimbingan Konseling</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $stats['total_siswa']; ?></h3>
                <p>Total Siswa</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['total_guru']; ?></h3>
                <p>Guru BK</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['total_konsultasi']; ?></h3>
                <p>Total Konsultasi</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['menunggu']; ?></h3>
                <p>Menunggu</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['disetujui']; ?></h3>
                <p>Disetujui</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['selesai']; ?></h3>
                <p>Selesai</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['ditolak']; ?></h3>
                <p>Ditolak</p>
            </div>
        </div>
        
        <div class="report-section">
            <h3><i class='bx bx-trending-up'></i> Top 5 Masalah Konsultasi</h3>
            <div class="issue-list">
                <?php if ($top_issues->num_rows > 0): ?>
                    <?php while($issue = $top_issues->fetch_assoc()): ?>
                    <div class="issue-item">
                        <div class="issue-topic"><?php echo htmlspecialchars($issue['TOPIK_KONSULTASI']); ?></div>
                        <div class="issue-count"><?php echo $issue['jumlah']; ?> kasus</div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <h4><i class='bx bx-info-circle'></i> Belum Ada Data</h4>
                        <p>Belum ada data konsultasi yang tercatat</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="report-section">
            <h3><i class='bx bx-user-voice'></i> Kinerja Guru BK</h3>
            <div class="guru-list">
                <?php if ($guru_stats->num_rows > 0): ?>
                    <?php while($guru = $guru_stats->fetch_assoc()): ?>
                    <div class="guru-item">
                        <div class="guru-name"><?php echo htmlspecialchars($guru['NAMA_LENGKAP']); ?></div>
                        <div class="guru-stats">
                            <div class="stat">
                                <div class="stat-number"><?php echo $guru['total_konsultasi']; ?></div>
                                <div class="stat-label">Total</div>
                            </div>
                            <div class="stat">
                                <div class="stat-number"><?php echo $guru['selesai']; ?></div>
                                <div class="stat-label">Selesai</div>
                            </div>
                            <div class="stat <?php echo $guru['total_konsultasi'] > 0 ? 'success-rate' : ''; ?>">
                                <div class="stat-number">
                                    <?php echo $guru['total_konsultasi'] > 0 ? 
                                        round(($guru['selesai'] / $guru['total_konsultasi']) * 100) : 0; ?>%
                                </div>
                                <div class="stat-label">Success Rate</div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <h4><i class='bx bx-user-x'></i> Belum Ada Data</h4>
                        <p>Belum ada data kinerja guru yang tercatat</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="export-buttons">
            <form method="post" style="display: inline;">
                <button type="submit" name="export_excel" class="btn btn-excel">
                    <i class='bx bx-spreadsheet'></i> Export Excel
                </button>
            </form>
            <button class="btn btn-print" onclick="window.print()">
                <i class='bx bx-printer'></i> Print Laporan
            </button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
        });
    </script>
</body>
</html>