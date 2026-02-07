<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: index.php");
    exit();
}

$success = '';
$error = '';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10; 

$sql_pending = "SELECT COUNT(*) as total FROM password_reset_requests WHERE STATUS = 'pending'";
$result_pending = $koneksi->query($sql_pending);
$pending_count = $result_pending ? $result_pending->fetch_assoc()['total'] : 0;

if (isset($_GET['approve'])) {
    $request_id = $_GET['approve'];
    approveResetRequest($request_id, $koneksi);
} elseif (isset($_GET['reject'])) {
    $request_id = $_GET['reject'];
    rejectResetRequest($request_id, $koneksi);
}

function approveResetRequest($request_id, $koneksi) {
    global $success, $error;
    
    $otp = rand(100000, 999999);
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    $sql_approve = "UPDATE password_reset_requests SET STATUS = 'approved', OTP_CODE = ?, OTP_EXPIRES = ?, UPDATED_AT = NOW() WHERE ID = ?";
    $stmt_approve = $koneksi->prepare($sql_approve);
    $stmt_approve->bind_param("ssi", $otp, $expires_at, $request_id);
    
    if ($stmt_approve->execute()) {
        $request_info = getRequestInfo($request_id, $koneksi);
        if ($request_info && sendOTPToStudent($request_info, $otp)) {
            $success = "✅ Request disetujui! OTP telah dikirim ke WhatsApp siswa.";
        } else {
            $error = "⚠️ Request disetujui tapi gagal kirim OTP. OTP: $otp";
        }
    } else {
        $error = "❌ Gagal menyetujui request!";
    }
}

function rejectResetRequest($request_id, $koneksi) {
    global $success, $error;
    
    $catatan = $_POST['catatan'] ?? 'Tidak ada catatan';
    
    $sql_reject = "UPDATE password_reset_requests SET STATUS = 'rejected', CATATAN_ADMIN = ?, UPDATED_AT = NOW() WHERE ID = ?";
    $stmt_reject = $koneksi->prepare($sql_reject);
    $stmt_reject->bind_param("si", $catatan, $request_id);
    
    if ($stmt_reject->execute()) {
        $success = "Request ditolak!";
    } else {
        $error = "Gagal menolak request!";
    }
}

function getRequestInfo($request_id, $koneksi) {
    $sql = "SELECT r.*, u.NAMA_LENGKAP, u.USERNAME, u.NO_TELEPON 
            FROM password_reset_requests r 
            JOIN users u ON r.USER_ID = u.ID 
            WHERE r.ID = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function sendOTPToStudent($student_info, $otp) {
    $no_telepon = $student_info['NO_TELEPON'];
    $nama = $student_info['NAMA_LENGKAP'];
    
    if (empty($no_telepon)) {
        return false;
    }
    
    $no_telepon = preg_replace('/[^0-9]/', '', $no_telepon);
    $no_telepon = ltrim($no_telepon, '0');
    if (substr($no_telepon, 0, 2) != '62') {
        $no_telepon = '62' . $no_telepon;
    }
    
   $pesan = "🔐 *KODE OTP RESET PASSWORD APK BK*

Halo $nama! 

Berikut adalah kode OTP untuk reset password Anda:

📱 *KODE OTP: $otp*

⏰ *Masa Berlaku:* 1 jam dari pesan ini dikirim

⚠️ *PERINGATAN KEAMANAN:*
• Jangan berikan kode ini kepada siapapun
• Kode ini bersifat rahasia dan pribadi
• Tim admin tidak akan meminta kode OTP Anda

🔒 *Tips Keamanan:*
• Gunakan password yang kuat
• Jangan bagikan password kepada siapapun
• Ganti password secara berkala

Terima kasih,
*Tim IT APK BK* 📚";
    
    $pesan_encoded = urlencode($pesan);
    $_SESSION['wa_otp_link'] = "https://wa.me/$no_telepon?text=$pesan_encoded";
    
    return true;
}

try {
    $sql_base = "SELECT r.*, u.NAMA_LENGKAP, u.USERNAME, u.NO_TELEPON, s.NIS, s.KELAS 
                 FROM password_reset_requests r 
                 JOIN users u ON r.USER_ID = u.ID 
                 LEFT JOIN siswa s ON u.ID = s.USER_ID";
    
    $count_sql = "SELECT COUNT(*) as total 
                  FROM password_reset_requests r 
                  JOIN users u ON r.USER_ID = u.ID 
                  LEFT JOIN siswa s ON u.ID = s.USER_ID";
    
    if (!empty($search)) {
        $search_like = "%" . $search . "%";
        $sql_base .= " WHERE (u.NAMA_LENGKAP LIKE ? OR u.USERNAME LIKE ? OR u.NO_TELEPON LIKE ? OR r.ALASAN LIKE ? OR s.KELAS LIKE ? OR s.NIS LIKE ?)";
        $count_sql .= " WHERE (u.NAMA_LENGKAP LIKE ? OR u.USERNAME LIKE ? OR u.NO_TELEPON LIKE ? OR r.ALASAN LIKE ? OR s.KELAS LIKE ? OR s.NIS LIKE ?)";
    }
    
    $sql_base .= " ORDER BY r.CREATED_AT DESC";
    
    if (!empty($search)) {
        $stmt_count = $koneksi->prepare($count_sql);
        $stmt_count->bind_param("ssssss", $search_like, $search_like, $search_like, $search_like, $search_like, $search_like);
    } else {
        $stmt_count = $koneksi->prepare($count_sql);
    }
    
    if (!empty($search)) {
        $stmt_count->execute();
    } else {
        $stmt_count->execute();
    }
    $count_result = $stmt_count->get_result();
    $total_records = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_records / $per_page);
    
    if ($page < 1) $page = 1;
    if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
    
    $offset = ($page - 1) * $per_page;
    
    $sql_requests = $sql_base . " LIMIT ? OFFSET ?";
    
    $stmt_requests = $koneksi->prepare($sql_requests);
    
    if (!empty($search)) {
        $stmt_requests->bind_param("ssssssii", $search_like, $search_like, $search_like, $search_like, $search_like, $search_like, $per_page, $offset);
    } else {
        $stmt_requests->bind_param("ii", $per_page, $offset);
    }
    
    $stmt_requests->execute();
    $requests = $stmt_requests->get_result();
    
    if (!$requests) {
        throw new Exception("Error query: " . $koneksi->error);
    }
    
} catch (Exception $e) {
    $error = "Terjadi kesalahan: " . $e->getMessage();
    $sql_requests = "SELECT r.*, u.NAMA_LENGKAP, u.USERNAME, u.NO_TELEPON, s.NIS, s.KELAS 
                     FROM password_reset_requests r 
                     JOIN users u ON r.USER_ID = u.ID 
                     LEFT JOIN siswa s ON u.ID = s.USER_ID 
                     ORDER BY r.CREATED_AT DESC";
    $requests = $koneksi->query($sql_requests);
    $total_records = $requests->num_rows;
    $total_pages = 1;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Request Reset - APK BK</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Poppins', sans-serif; 
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); 
            min-height: 100vh; 
            color: #f8fafc; 
        }
        
        .header { 
            background: rgba(15, 23, 42, 0.95);
            color: #f8fafc; 
            padding: 21px 40px !important; 
            display: flex; 
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #8b5cf6;
            box-shadow: 0 8px 32px rgba(139, 92, 246, 0.2);
            backdrop-filter: blur(20px);
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 700;
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
            font-size: 13px;
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
        
        .nav a span {
            background: #ef4444;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            margin-left: 5px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .container { 
            padding: 40px; 
            max-width: 1200px; 
            margin: 0 auto; 
        }
        
        .alert { 
            padding: 15px 20px; 
            border-radius: 12px; 
            margin-bottom: 25px; 
            font-weight: 600; 
            border: 2px solid; 
        }
        
        .alert-success { 
            background: rgba(34, 197, 94, 0.15); 
            border-color: #22c55e; 
            color: #22c55e; 
        }
        
        .alert-error { 
            background: rgba(239, 68, 68, 0.15); 
            border-color: #ef4444; 
            color: #ef4444; 
        }
        
        .card { 
            background: rgba(15, 23, 42, 0.8); 
            padding: 30px; 
            border-radius: 20px; 
            margin-bottom: 30px; 
            border: 1px solid rgba(139, 92, 246, 0.2); 
        }
        
        .card h2 { 
            color: #8b5cf6; 
            margin-bottom: 20px; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }
        
        .search-container {
            background: rgba(15, 23, 42, 0.8);
            padding: 25px 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            border: 1px solid rgba(139, 92, 246, 0.2);
            box-shadow: 0 10px 30px rgba(139, 92, 246, 0.15);
            backdrop-filter: blur(15px);
        }
        
        .search-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .search-header h3 {
            font-size: 20px;
            color: #f8fafc;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .search-form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .search-input-group {
            flex: 1;
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 16px 20px 16px 50px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(139, 92, 246, 0.3);
            border-radius: 12px;
            color: #f8fafc;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }
        
        .search-input::placeholder {
            color: #94a3b8;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #8b5cf6;
            box-shadow: 0 0 20px rgba(139, 92, 246, 0.3);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 20px;
        }
        
        .search-buttons {
            display: flex;
            gap: 10px;
            align-self: end;
        }
        
        .btn-search {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.4s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
            white-space: nowrap;
        }
        
        .btn-search::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-search:hover::before {
            left: 100%;
        }
        
        .btn-search:hover {
            background: linear-gradient(135deg, #7c3aed, #8b5cf6);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(139, 92, 246, 0.3);
        }
        
        .btn-reset-search {
            background: linear-gradient(135deg, #64748b, #475569);
            color: white;
            padding: 14px 20px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.4s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-reset-search::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-reset-search:hover::before {
            left: 100%;
        }
        
        .btn-reset-search:hover {
            background: linear-gradient(135deg, #475569, #64748b);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(100, 116, 139, 0.3);
        }
        
        .search-summary {
            margin-top: 15px;
            padding: 12px 20px;
            background: rgba(139, 92, 246, 0.1);
            border-radius: 12px;
            font-size: 14px;
            color: #cbd5e1;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .search-summary i {
            color: #8b5cf6;
            font-size: 18px;
        }
        
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            background: rgba(139, 92, 246, 0.05);
            border-top: 2px solid rgba(139, 92, 246, 0.1);
        }
        
        .pagination-info {
            color: #94a3b8;
            font-size: 14px;
        }
        
        .pagination {
            display: flex;
            gap: 8px;
        }
        
        .pagination a, .pagination span {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .pagination a {
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
            border: 2px solid transparent;
        }
        
        .pagination a:hover {
            background: rgba(139, 92, 246, 0.2);
            border-color: #8b5cf6;
            transform: translateY(-2px);
        }
        
        .pagination .active {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
        }
        
        .pagination .disabled {
            background: rgba(100, 116, 139, 0.1);
            color: #64748b;
            cursor: not-allowed;
        }
        
        .request-item { 
            background: rgba(30, 41, 59, 0.6); 
            padding: 20px; 
            border-radius: 12px; 
            margin-bottom: 15px; 
            border-left: 4px solid #8b5cf6; 
        }
        
        .request-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start; 
            margin-bottom: 10px; 
            flex-wrap: wrap; 
            gap: 10px; 
        }
        
        .student-info { 
            flex: 1; 
            min-width: 250px; 
        }
        
        .student-name { 
            font-weight: 700; 
            color: #8b5cf6; 
        }
        
        .student-details { 
            color: #94a3b8; 
            font-size: 14px; 
        }
        
        .request-date { 
            color: #94a3b8; 
            font-size: 14px;
            white-space: nowrap; 
        }
        
        .request-alasan { 
            color: #cbd5e1; 
            margin: 15px 0; 
        }
        
        .action-buttons { 
            display: flex; 
            gap: 10px; 
        }
        
        .btn { 
            padding: 10px 16px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: 600; 
            display: flex; 
            align-items: center; 
            gap: 6px; 
            text-decoration: none; 
        }
        
        .btn-approve { 
            background: linear-gradient(135deg, #10b981, #059669); 
            color: white; 
        }
        
        .btn-reject { 
            background: linear-gradient(135deg, #ef4444, #dc2626); 
            color: white; 
        }
        
        .btn-whatsapp { 
            background: linear-gradient(135deg, #25D366, #128C7E); 
            color: white; 
        }
        
        .status-badge { 
            padding: 6px 12px; 
            border-radius: 20px; 
            font-size: 12px; 
            font-weight: 700; 
            white-space: nowrap; 
        }
        
        .status-pending { 
            background: #f59e0b; 
            color: white; 
        }
        
        .status-approved { 
            background: #10b981; 
            color: white; 
        }
        
        .status-rejected { 
            background: #ef4444; 
            color: white; 
        }
        
        .reject-form { 
            display: none; 
            margin-top: 15px; 
        }
        
        .reject-form textarea { 
            width: 100%; 
            padding: 10px; 
            border-radius: 8px; 
            background: rgba(30, 41, 59, 0.8); 
            border: 1px solid #ef4444; 
            color: white; 
        }
        
        .contact-info { 
            font-size: 13px; 
            color: #cbd5e1; 
            margin-top: 5px; 
        }
        
        .contact-info i { 
            color: #8b5cf6; 
            margin-right: 5px; 
        }
        
        .otp-expired {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            padding: 8px 12px;
            border-radius: 6px;
            margin-top: 10px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 40px;
            background: rgba(15, 23, 42, 0.8);
            border-radius: 20px;
            border: 3px dashed rgba(139, 92, 246, 0.3);
            backdrop-filter: blur(15px);
            margin: 20px;
        }
        
        .empty-state h3 {
            color: #8b5cf6;
            font-size: 28px;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .empty-state p {
            color: #94a3b8;
            font-size: 16px;
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
            
            .search-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-input-group,
            .search-buttons {
                width: 100%;
            }
            
            .pagination-container {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .request-header {
                flex-direction: column;
            }
            
            .action-buttons {
                flex-direction: column;
                width: 100%;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>⚡ APK BK - Request Reset Password</h1>
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
        <a href="admin_manage_reset_requests.php" class="active">
            <i class='bx bx-message-square-dots'></i>
            Request Reset Password
            <?php if ($pending_count > 0): ?>
                <span><?php echo $pending_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="admin_reports.php">
            <i class='bx bx-bar-chart'></i>
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
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class='bx bx-check-circle'></i> <?php echo $success; ?>
                
                <?php if (isset($_SESSION['wa_otp_link'])): ?>
                <div style="margin-top: 10px;">
                    <a href="<?php echo $_SESSION['wa_otp_link']; ?>" target="_blank" class="btn btn-whatsapp">
                        <i class='bx bxl-whatsapp'></i> Buka WhatsApp & Kirim OTP
                    </a>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class='bx bx-error-circle'></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="search-container">
            <div class="search-header">
                <h3><i class='bx bx-search'></i> Cari Request Reset Password</h3>
            </div>
            
            <form method="GET" action="" class="search-form">
                <div class="form-group search-input-group">
                    <i class='bx bx-search search-icon'></i>
                    <input type="text" 
                           name="search" 
                           class="search-input" 
                           placeholder="Cari berdasarkan nama siswa, username, no. telepon, alasan, kelas, atau NIS..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="search-buttons">
                    <button type="submit" class="btn-search">
                        <i class='bx bx-search-alt'></i>
                        Cari
                    </button>
                    <a href="admin_manage_reset_requests.php" class="btn-reset-search">
                        <i class='bx bx-reset'></i>
                        Reset
                    </a>
                </div>
            </form>
            
            <?php if (!empty($search)): ?>
            <div class="search-summary">
                <i class='bx bx-info-circle'></i>
                Hasil pencarian untuk: <strong><?php echo htmlspecialchars($search); ?></strong>
                | Ditemukan: <strong><?php echo $total_records; ?> request</strong>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2><i class='bx bx-message-square-dots'></i> Request Reset Password</h2>
            
            <?php if ($requests->num_rows > 0): ?>
                <?php while($request = $requests->fetch_assoc()): 
                    $is_otp_expired = false;
                    if ($request['STATUS'] == 'approved' && $request['OTP_EXPIRES']) {
                        $current_time = time();
                        $expiry_time = strtotime($request['OTP_EXPIRES']);
                        if ($current_time > $expiry_time && empty($request['USED_AT'])) {
                            $is_otp_expired = true;
                        }
                    }
                ?>
                <div class="request-item">
                    <div class="request-header">
                        <div class="student-info">
                            <div class="student-name"><?php echo htmlspecialchars($request['NAMA_LENGKAP']); ?></div>
                            <div class="student-details">
                                <?php echo htmlspecialchars($request['USERNAME']); ?>
                                <?php if ($request['NIS']): ?> | NIS: <?php echo htmlspecialchars($request['NIS']); ?><?php endif; ?>
                                <?php if ($request['KELAS']): ?> | Kelas: <?php echo htmlspecialchars($request['KELAS']); ?><?php endif; ?>
                            </div>
                            <?php if (!empty($request['NO_TELEPON'])): ?>
                                <div class="contact-info">
                                    <i class='bx bx-phone'></i> <?php echo htmlspecialchars($request['NO_TELEPON']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="request-date">
                            <?php echo date('d/m/Y H:i', strtotime($request['CREATED_AT'])); ?>
                        </div>
                        <span class="status-badge status-<?php echo $request['STATUS']; ?>">
                            <?php echo strtoupper($request['STATUS']); ?>
                        </span>
                    </div>
                    
                    <div class="request-alasan">
                        <strong>Alasan:</strong> <?php echo htmlspecialchars($request['ALASAN']); ?>
                    </div>
                    
                    <?php if ($request['STATUS'] == 'pending'): ?>
                    <div class="action-buttons">
                        <a href="?<?php echo !empty($search) ? 'search=' . urlencode($search) . '&' : ''; ?>page=<?php echo $page; ?>&approve=<?php echo $request['ID']; ?>" class="btn btn-approve">
                            <i class='bx bx-check'></i> Setujui & Kirim OTP
                        </a>
                        <button onclick="toggleRejectForm(<?php echo $request['ID']; ?>)" class="btn btn-reject">
                            <i class='bx bx-x'></i> Tolak
                        </button>
                    </div>
                    
                    <div id="reject-form-<?php echo $request['ID']; ?>" class="reject-form">
                        <form method="POST" action="?<?php echo !empty($search) ? 'search=' . urlencode($search) . '&' : ''; ?>page=<?php echo $page; ?>&reject=<?php echo $request['ID']; ?>">
                            <textarea name="catatan" rows="3" placeholder="Berikan alasan penolakan..."></textarea>
                            <button type="submit" class="btn btn-reject" style="margin-top: 10px;">
                                <i class='bx bx-send'></i> Kirim Penolakan
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($request['STATUS'] == 'approved' && $request['OTP_CODE']): ?>
                        <div style="color: #10b981; margin-top: 10px;">
                            <i class='bx bx-check'></i> OTP dikirim: <?php echo $request['OTP_CODE']; ?>
                            (exp: <?php echo date('H:i', strtotime($request['OTP_EXPIRES'])); ?>)
                            
                            <?php if ($is_otp_expired): ?>
                                <div class="otp-expired">
                                    <i class='bx bx-time-five'></i> OTP sudah expired
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($request['STATUS'] == 'rejected' && $request['CATATAN_ADMIN']): ?>
                        <div style="color: #ef4444; margin-top: 10px;">
                            <strong>Alasan ditolak:</strong> <?php echo htmlspecialchars($request['CATATAN_ADMIN']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
                
                <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <div class="pagination-info">
                        Menampilkan <?php echo min(($page - 1) * $per_page + 1, $total_records); ?> - <?php echo min($page * $per_page, $total_records); ?> dari <?php echo $total_records; ?> request
                    </div>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo !empty($search) ? 'search=' . urlencode($search) . '&' : ''; ?>page=1" title="Halaman Pertama">
                                <i class='bx bx-chevrons-left'></i>
                            </a>
                            <a href="?<?php echo !empty($search) ? 'search=' . urlencode($search) . '&' : ''; ?>page=<?php echo $page - 1; ?>" title="Sebelumnya">
                                <i class='bx bx-chevron-left'></i>
                            </a>
                        <?php else: ?>
                            <span class="disabled"><i class='bx bx-chevrons-left'></i></span>
                            <span class="disabled"><i class='bx bx-chevron-left'></i></span>
                        <?php endif; ?>
                        
                        <?php 
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1) {
                            echo '<a href="?' . (!empty($search) ? 'search=' . urlencode($search) . '&' : '') . 'page=1">1</a>';
                            if ($start_page > 2) echo '<span class="disabled">...</span>';
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++): 
                        ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo !empty($search) ? 'search=' . urlencode($search) . '&' : ''; ?>page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) echo '<span class="disabled">...</span>';
                            echo '<a href="?' . (!empty($search) ? 'search=' . urlencode($search) . '&' : '') . 'page=' . $total_pages . '">' . $total_pages . '</a>';
                        }
                        ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo !empty($search) ? 'search=' . urlencode($search) . '&' : ''; ?>page=<?php echo $page + 1; ?>" title="Berikutnya">
                                <i class='bx bx-chevron-right'></i>
                            </a>
                            <a href="?<?php echo !empty($search) ? 'search=' . urlencode($search) . '&' : ''; ?>page=<?php echo $total_pages; ?>" title="Halaman Terakhir">
                                <i class='bx bx-chevrons-right'></i>
                            </a>
                        <?php else: ?>
                            <span class="disabled"><i class='bx bx-chevron-right'></i></span>
                            <span class="disabled"><i class='bx bx-chevrons-right'></i></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <?php if (!empty($search)): ?>
                        <h3><i class='bx bx-search-alt'></i> Tidak Ditemukan</h3>
                        <p>Tidak ada request reset password yang sesuai dengan kriteria pencarian "<?php echo htmlspecialchars($search); ?>"</p>
                        <div style="margin-top: 20px;">
                            <a href="admin_manage_reset_requests.php" class="btn-search" style="display: inline-flex; text-decoration: none;">
                                <i class='bx bx-reset'></i>
                                Tampilkan Semua Request
                            </a>
                        </div>
                    <?php else: ?>
                        <h3><i class='bx bx-message-square-x'></i> Tidak Ada Request</h3>
                        <p>Tidak ada request reset password saat ini</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleRejectForm(requestId) {
            const form = document.getElementById('reject-form-' + requestId);
            form.style.display = form.style.display === 'block' ? 'none' : 'block';
        }
        
        <?php unset($_SESSION['wa_otp_link']); ?>
    </script>
</body>
</html>