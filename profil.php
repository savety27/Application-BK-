<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$nama_lengkap = $_SESSION['nama_lengkap'];

$success = '';
$error = '';
$user_data = null;

$sql_user = "SELECT * FROM users WHERE ID = ?";
$stmt_user = $koneksi->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user_data = $result_user->fetch_assoc();

$additional_data = null;
if ($role == 'Siswa') {
    $sql_siswa = "SELECT * FROM siswa WHERE USER_ID = ?";
    $stmt_siswa = $koneksi->prepare($sql_siswa);
    $stmt_siswa->bind_param("i", $user_id);
    $stmt_siswa->execute();
    $result_siswa = $stmt_siswa->get_result();
    $additional_data = $result_siswa->fetch_assoc();
} elseif ($role == 'Guru_BK') {
    $sql_guru = "SELECT * FROM guru_bk WHERE USER_ID = ?";
    $stmt_guru = $koneksi->prepare($sql_guru);
    $stmt_guru->bind_param("i", $user_id);
    $stmt_guru->execute();
    $result_guru = $stmt_guru->get_result();
    $additional_data = $result_guru->fetch_assoc();
}

$default_photo = 'https://ui-avatars.com/api/?name=' . urlencode($nama_lengkap) . '&background=' . ($role == 'Admin' ? '8b5cf6' : ($role == 'Guru_BK' ? '3182ce' : '667eea')) . '&color=fff&size=150';

$has_photo = false;
$profile_photo = $default_photo;

$profile_files = glob('uploads/profile_' . $user_id . '.*');
if (!empty($profile_files)) {
    foreach ($profile_files as $file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $profile_photo = $file;
            $has_photo = true;
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $nama_lengkap_new = trim($_POST['nama_lengkap']);
        $email = trim($_POST['email']);
        $no_telepon = trim($_POST['no_telepon']);
        
        $username = trim($_POST['username']);
        $username_changed = ($username !== $user_data['USERNAME']);
        
        if ($username_changed) {
            $sql_check = "SELECT ID FROM users WHERE USERNAME = ? AND ID != ?";
            $stmt_check = $koneksi->prepare($sql_check);
            $stmt_check->bind_param("si", $username, $user_id);
            $stmt_check->execute();
            
            if ($stmt_check->get_result()->num_rows > 0) {
                $error = "Username sudah digunakan!";
            } else {
                $update_username = true;
            }
            $stmt_check->close();
        }
        
        if (!$error) {
            $sql_update = "UPDATE users SET 
                          NAMA_LENGKAP = ?, 
                          EMAIL = ?, 
                          NO_TELEPON = ?";
            
            if ($username_changed && isset($update_username)) {
                $sql_update .= ", USERNAME = ?";
            }
            
            $sql_update .= " WHERE ID = ?";
            
            $stmt_update = $koneksi->prepare($sql_update);
            
            if ($username_changed && isset($update_username)) {
                $stmt_update->bind_param("ssssi", $nama_lengkap_new, $email, $no_telepon, $username, $user_id);
            } else {
                $stmt_update->bind_param("sssi", $nama_lengkap_new, $email, $no_telepon, $user_id);
            }
            
            if ($stmt_update->execute()) {
                $_SESSION['nama_lengkap'] = $nama_lengkap_new;
                
                if ($username_changed && isset($update_username)) {
                    $_SESSION['username'] = $username;
                }
                
                $success = "Profile berhasil diperbarui!";
                
                $stmt_user->execute();
                $result_user = $stmt_user->get_result();
                $user_data = $result_user->fetch_assoc();
                $nama_lengkap = $nama_lengkap_new;
            } else {
                $error = "Gagal memperbarui profile!";
            }
        }
    }
    
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
        $file_type = $_FILES['profile_photo']['type'];
        $file_size = $_FILES['profile_photo']['size'];
        $file_tmp = $_FILES['profile_photo']['tmp_name'];
        $file_name = $_FILES['profile_photo']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (!in_array($file_type, $allowed_types)) {
            $error = "Format file tidak didukung! Hanya JPG, JPEG, dan PNG yang diperbolehkan.";
        }
        elseif ($file_size > 5 * 1024 * 1024) {
            $error = "Ukuran file terlalu besar! Maksimal 5MB.";
        }
        elseif (!in_array($file_ext, ['jpg', 'jpeg', 'png'])) {
            $error = "Ekstensi file tidak valid! Gunakan .jpg, .jpeg, atau .png";
        }
        
        if (!$error) {
            if (!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            $new_filename = 'profile_' . $user_id . '.' . $file_ext;
            
            $old_files = glob('uploads/profile_' . $user_id . '.*');
            foreach ($old_files as $old_file) {
                if (is_file($old_file)) {
                    unlink($old_file);
                }
            }
            
            if (move_uploaded_file($file_tmp, 'uploads/' . $new_filename)) {
                $success = "Foto profil berhasil diunggah!";
                $profile_photo = 'uploads/' . $new_filename;
                $has_photo = true;
                
                echo "<script>window.location.href = window.location.href;</script>";
                exit();
            } else {
                $error = "Gagal mengunggah foto profil!";
            }
        }
    } elseif (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] != 4) {
        switch ($_FILES['profile_photo']['error']) {
            case 1:
            case 2:
                $error = "Ukuran file terlalu besar!";
                break;
            case 3:
                $error = "File hanya terunggah sebagian!";
                break;
            case 6:
                $error = "Folder temporary tidak ditemukan!";
                break;
            case 7:
                $error = "Gagal menulis file ke disk!";
                break;
            case 8:
                $error = "Upload dihentikan oleh ekstensi PHP!";
                break;
            default:
                $error = "Terjadi kesalahan saat mengunggah file!";
        }
    }
    
    if (isset($_POST['delete_photo'])) {
        $old_files = glob('uploads/profile_' . $user_id . '.*');
        $deleted = false;
        
        foreach ($old_files as $old_file) {
            if (is_file($old_file) && unlink($old_file)) {
                $deleted = true;
            }
        }
        
        if ($deleted) {
            $success = "Foto profil berhasil dihapus!";
            $has_photo = false;
            $profile_photo = $default_photo;
        } else {
            $error = "Tidak ada foto profil yang dapat dihapus!";
        }
    }
}

$color_scheme = '';
$gradient_bg = '';
$primary_color = '';
$secondary_color = '';

switch ($role) {
    case 'Admin':
        $color_scheme = 'admin';
        $gradient_bg = 'linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%)';
        $primary_color = '#8b5cf6';
        $secondary_color = '#06b6d4';
        break;
    case 'Guru_BK':
        $color_scheme = 'guru';
        $gradient_bg = 'linear-gradient(135deg, #1a365d 0%, #2d3748 50%, #4a5568 100%)';
        $primary_color = '#3182ce';
        $secondary_color = '#2b6cb0';
        break;
    case 'Siswa':
        $color_scheme = 'siswa';
        $gradient_bg = 'linear-gradient(135deg, #434190 0%, #553c9a 100%)';
        $primary_color = '#667eea';
        $secondary_color = '#764ba2';
        break;
}

if ($role == 'Admin') {
    $dashboard_link = "admin_dashboard.php";
} elseif ($role == 'Guru_BK') {
    $dashboard_link = "dashboard_guru.php";
} else {
    $dashboard_link = "dashboard_siswa.php";
}

$page_title = "Profile " . ($role == 'Guru_BK' ? 'Guru BK' : $role);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - APK BK</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Poppins', 'Segoe UI', sans-serif; 
            background: <?php echo $gradient_bg; ?>;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }
        
        <?php if ($role == 'Admin'): ?>
        body { 
            color: #f8fafc;
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
        
        .header { 
            background: rgba(15, 23, 42, 0.95);
            color: #f8fafc; 
            padding: 20px 40px; 
            display: flex; 
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid <?php echo $primary_color; ?>;
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
            display: flex;
            align-items: center;
            gap: 12px;
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
        
        .back-btn {
            background: linear-gradient(135deg, #8b5cf6, #06b6d4);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(139, 92, 246, 0.3);
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
            background: linear-gradient(135deg, #06b6d4, #8b5cf6);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 25px rgba(139, 92, 246, 0.4);
        }
        
        .container { 
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            position: relative;
            z-index: 5;
        }
        
        .profile-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 30px;
        }
        
        .sidebar {
            background: rgba(15, 23, 42, 0.8);
            padding: 30px;
            border-radius: 20px;
            border: 1px solid rgba(139, 92, 246, 0.2);
            box-shadow: 0 15px 35px rgba(139, 92, 246, 0.15);
            backdrop-filter: blur(15px);
            text-align: center;
            animation: fadeIn 0.8s ease-out;
        }
        
        .profile-pic-container {
            position: relative;
            width: 200px;
            height: 200px;
            margin: 0 auto 25px;
        }
        
        .profile-pic {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid <?php echo $primary_color; ?>;
            box-shadow: 0 10px 30px rgba(139, 92, 246, 0.4);
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, <?php echo $primary_color; ?>, <?php echo $secondary_color; ?>);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 60px;
        }
        
        .profile-pic img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-pic::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: translateX(-100%);
            animation: shine 3s infinite;
        }
        
        @keyframes shine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .profile-info h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #f8fafc;
        }
        
        .profile-info .role-badge {
            display: inline-block;
            padding: 6px 15px;
            background: linear-gradient(135deg, <?php echo $primary_color; ?>, <?php echo $secondary_color; ?>);
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .profile-info p {
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .photo-actions {
            margin-top: 25px;
        }
        
        .upload-btn, .delete-btn {
            background: linear-gradient(135deg, <?php echo $primary_color; ?>, <?php echo $secondary_color; ?>);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            margin: 5px 0;
            width: 100%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .upload-btn:hover {
            background: linear-gradient(135deg, <?php echo $secondary_color; ?>, <?php echo $primary_color; ?>);
            transform: translateY(-3px);
        }
        
        .delete-btn {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        
        .delete-btn:hover {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            transform: translateY(-3px);
        }
        
        .main-content {
            background: rgba(15, 23, 42, 0.8);
            padding: 40px;
            border-radius: 20px;
            border: 1px solid rgba(139, 92, 246, 0.2);
            box-shadow: 0 15px 35px rgba(139, 92, 246, 0.15);
            backdrop-filter: blur(15px);
            animation: fadeIn 0.8s ease-out 0.2s both;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 500;
            border: 2px solid transparent;
            animation: slideUp 0.5s ease-out;
            display: flex;
            align-items: center;
            gap: 12px;
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
        
        .section-title {
            font-size: 24px;
            margin-bottom: 25px;
            color: #f8fafc;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(139, 92, 246, 0.2);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #cbd5e1;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        input {
            width: 100%;
            padding: 15px 20px;
            background: rgba(30, 41, 59, 0.8);
            border: 2px solid rgba(139, 92, 246, 0.2);
            border-radius: 12px;
            color: #f8fafc;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }
        
        input:focus {
            outline: none;
            border-color: <?php echo $primary_color; ?>;
            box-shadow: 0 0 20px rgba(139, 92, 246, 0.2);
            background: rgba(30, 41, 59, 0.9);
        }
        
        ::placeholder {
            color: #64748b;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, <?php echo $primary_color; ?>, <?php echo $secondary_color; ?>);
            color: white;
            padding: 18px 32px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 700;
            width: 100%;
            margin-top: 15px;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
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
        
        .submit-btn:hover {
            background: linear-gradient(135deg, <?php echo $secondary_color; ?>, <?php echo $primary_color; ?>);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 12px 35px rgba(139, 92, 246, 0.4);
        }
        
        .info-text {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 5px;
            font-style: italic;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 2px solid rgba(139, 92, 246, 0.2);
        }
        
        .tab {
            padding: 12px 24px;
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            position: relative;
        }
        
        .tab.active {
            color: <?php echo $primary_color; ?>;
        }
        
        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, <?php echo $primary_color; ?>, <?php echo $secondary_color; ?>);
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.5s ease-out;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .additional-info {
            background: rgba(139, 92, 246, 0.1);
            padding: 20px;
            border-radius: 12px;
            margin-top: 30px;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            margin-bottom: 15px;
        }
        
        .info-label {
            color: #94a3b8;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #f8fafc;
            font-size: 16px;
            font-weight: 600;
        }
        
        .photo-upload-form {
            background: rgba(139, 92, 246, 0.1);
            padding: 25px;
            border-radius: 15px;
            margin-top: 20px;
            border: 2px dashed rgba(139, 92, 246, 0.3);
        }
        
        .file-input-wrapper {
            position: relative;
            margin-bottom: 15px;
        }
        
        .file-input {
            width: 100%;
            padding: 15px;
            background: rgba(30, 41, 59, 0.8);
            border: 2px dashed rgba(139, 92, 246, 0.5);
            border-radius: 12px;
            color: #f8fafc;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .file-input:hover {
            border-color: <?php echo $primary_color; ?>;
            background: rgba(30, 41, 59, 0.9);
        }
        
        .photo-instructions {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 10px;
            text-align: center;
        }
        
        <?php else: ?>
        body { 
            color: #2d3748;
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
        
        .header { 
            background: rgba(255, 255, 255, 0.95);
            color: #2d3748; 
            padding: 20px 40px; 
            display: flex; 
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid <?php echo $primary_color; ?>;
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
            background: linear-gradient(90deg, transparent, rgba(<?php echo $primary_color == '#667eea' ? '102, 126, 234' : '49, 130, 206'; ?>, 0.05), transparent);
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
            background: linear-gradient(135deg, <?php echo $primary_color; ?>, <?php echo $secondary_color; ?>);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-info span {
            font-weight: 600;
            color: #4a5568;
        }
        
        .back-btn {
            background: linear-gradient(135deg, <?php echo $primary_color; ?>, <?php echo $secondary_color; ?>);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(<?php echo $primary_color == '#667eea' ? '102, 126, 234' : '49, 130, 206'; ?>, 0.3);
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
            background: linear-gradient(135deg, <?php echo $secondary_color; ?>, <?php echo $primary_color; ?>);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 25px rgba(<?php echo $primary_color == '#667eea' ? '102, 126, 234' : '49, 130, 206'; ?>, 0.4);
        }
        
        .container { 
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            position: relative;
            z-index: 5;
        }
        
        .profile-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 30px;
        }
        
        .sidebar {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 20px;
            border: 1px solid rgba(<?php echo $primary_color == '#667eea' ? '102, 126, 234' : '49, 130, 206'; ?>, 0.1);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.1);
            backdrop-filter: blur(15px);
            text-align: center;
            animation: fadeIn 0.8s ease-out;
        }
        
        .profile-pic-container {
            position: relative;
            width: 200px;
            height: 200px;
            margin: 0 auto 25px;
        }
        
        .profile-pic {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid <?php echo $primary_color; ?>;
            box-shadow: 0 10px 30px rgba(<?php echo $primary_color == '#667eea' ? '102, 126, 234' : '49, 130, 206'; ?>, 0.4);
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, <?php echo $primary_color; ?>, <?php echo $secondary_color; ?>);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 60px;
        }
        
        .profile-pic img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-pic::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: translateX(-100%);
            animation: shine 3s infinite;
        }
        
        @keyframes shine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .profile-info h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #2d3748;
        }
        
        .profile-info .role-badge {
            display: inline-block;
            padding: 6px 15px;
            background: linear-gradient(135deg, <?php echo $primary_color; ?>, <?php echo $secondary_color; ?>);
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .profile-info p {
            color: #718096;
            font-size: 14px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .photo-actions {
            margin-top: 25px;
        }
        
        .upload-btn, .delete-btn {
            background: linear-gradient(135deg, <?php echo $primary_color; ?>, <?php echo $secondary_color; ?>);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            margin: 5px 0;
            width: 100%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .upload-btn:hover {
            background: linear-gradient(135deg, <?php echo $secondary_color; ?>, <?php echo $primary_color; ?>);
            transform: translateY(-3px);
        }
        
        .delete-btn {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        
        .delete-btn:hover {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            transform: translateY(-3px);
        }
        
        .main-content {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            border: 1px solid rgba(<?php echo $primary_color == '#667eea' ? '102, 126, 234' : '49, 130, 206'; ?>, 0.1);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.1);
            backdrop-filter: blur(15px);
            animation: fadeIn 0.8s ease-out 0.2s both;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 500;
            border: 2px solid transparent;
            animation: slideUp 0.5s ease-out;
            display: flex;
            align-items: center;
            gap: 12px;
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
        
        .section-title {
            font-size: 24px;
            margin-bottom: 25px;
            color: #2d3748;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(<?php echo $primary_color == '#667eea' ? '102, 126, 234' : '49, 130, 206'; ?>, 0.2);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        input {
            width: 100%;
            padding: 15px 20px;
            background: rgba(255, 251, 240, 0.9);
            border: 2px solid rgba(<?php echo $primary_color == '#667eea' ? '102, 126, 234' : '49, 130, 206'; ?>, 0.2);
            border-radius: 12px;
            color: #2d3748;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }
        
        input:focus {
            outline: none;
            border-color: <?php echo $primary_color; ?>;
            box-shadow: 0 0 20px rgba(<?php echo $primary_color == '#667eea' ? '102, 126, 234' : '49, 130, 206'; ?>, 0.2);
            background: rgba(255, 251, 240, 0.95);
        }
        
        ::placeholder {
            color: #a0aec0;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, <?php echo $primary_color; ?>, <?php echo $secondary_color; ?>);
            color: white;
            padding: 18px 32px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 700;
            width: 100%;
            margin-top: 15px;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(<?php echo $primary_color == '#667eea' ? '102, 126, 234' : '49, 130, 206'; ?>, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
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
        
        .submit-btn:hover {
            background: linear-gradient(135deg, <?php echo $secondary_color; ?>, <?php echo $primary_color; ?>);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 12px 35px rgba(<?php echo $primary_color == '#667eea' ? '102, 126, 234' : '49, 130, 206'; ?>, 0.4);
        }
        
        .info-text {
            font-size: 12px;
            color: #718096;
            margin-top: 5px;
            font-style: italic;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 2px solid rgba(<?php echo $primary_color == '#667eea' ? '102, 126, 234' : '49, 130, 206'; ?>, 0.2);
        }
        
        .tab {
            padding: 12px 24px;
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            position: relative;
        }
        
        .tab.active {
            color: <?php echo $primary_color; ?>;
        }
        
        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, <?php echo $primary_color; ?>, <?php echo $secondary_color; ?>);
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.5s ease-out;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .additional-info {
            background: rgba(<?php echo $primary_color == '#667eea' ? '102, 126, 234' : '49, 130, 206'; ?>, 0.05);
            padding: 20px;
            border-radius: 12px;
            margin-top: 30px;
            border: 1px solid rgba(<?php echo $primary_color == '#667eea' ? '102, 126, 234' : '49, 130, 206'; ?>, 0.15);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            margin-bottom: 15px;
        }
        
        .info-label {
            color: #718096;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #2d3748;
            font-size: 16px;
            font-weight: 600;
        }
        
        .photo-upload-form {
            background: rgba(<?php echo $primary_color == '#667eea' ? '102, 126, 234' : '49, 130, 206'; ?>, 0.05);
            padding: 25px;
            border-radius: 15px;
            margin-top: 20px;
            border: 2px dashed rgba(<?php echo $primary_color == '#667eea' ? '102, 126, 234' : '49, 130, 206'; ?>, 0.3);
        }
        
        .file-input-wrapper {
            position: relative;
            margin-bottom: 15px;
        }
        
        .file-input {
            width: 100%;
            padding: 15px;
            background: rgba(255, 251, 240, 0.9);
            border: 2px dashed rgba(<?php echo $primary_color == '#667eea' ? '102, 126, 234' : '49, 130, 206'; ?>, 0.5);
            border-radius: 12px;
            color: #2d3748;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .file-input:hover {
            border-color: <?php echo $primary_color; ?>;
            background: rgba(255, 251, 240, 0.95);
        }
        
        .photo-instructions {
            font-size: 12px;
            color: #718096;
            margin-top: 10px;
            text-align: center;
        }
        
        <?php endif; ?>
        
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
        
        @media (max-width: 992px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                text-align: center;
            }
            
            .profile-pic-container {
                width: 150px;
                height: 150px;
            }
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .container {
                padding: 20px;
            }
            
            .sidebar {
                padding: 25px;
            }
            
            .main-content {
                padding: 25px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .floating {
                display: none;
            }
        }
        
        @media (max-width: 480px) {
            .header h1 {
                font-size: 22px;
            }
            
            .profile-pic-container {
                width: 120px;
                height: 120px;
            }
            
            .section-title {
                font-size: 20px;
            }
            
            .tabs {
                flex-direction: column;
            }
            
            .tab {
                text-align: left;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    
    <div class="floating"></div>
    <div class="floating"></div>
    <div class="floating"></div>
    
    <div class="header">
        <h1><i class='bx bx-user'></i> <?php echo $page_title; ?></h1>
        <div class="user-info">
            <?php if ($role == 'Admin'): ?>
                <span>Halo, <strong><?php echo htmlspecialchars($nama_lengkap); ?></strong> 👑</span>
            <?php endif; ?>
            <a href="<?php echo $dashboard_link; ?>" class="back-btn">
                <i class='bx bx-arrow-back'></i>
                Kembali ke Dashboard
            </a>
        </div>
    </div>
    
    <div class="container">
        <div class="profile-container">
            <div class="sidebar">
                <div class="profile-pic-container">
                    <div class="profile-pic">
                        <?php if ($has_photo): ?>
                            <img src="<?php echo $profile_photo . '?t=' . time(); ?>" alt="Profile Photo" 
                                 onerror="this.onerror=null; this.style.display='none'; this.parentNode.innerHTML='<i class=\'bx bx-user\'></i>'">
                        <?php else: ?>
                            <i class='bx bx-user'></i>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($user_data['NAMA_LENGKAP']); ?></h3>
                    <span class="role-badge">
                        <?php echo $role == 'Guru_BK' ? 'Guru BK' : $role; ?>
                    </span>
                    <p><i class='bx bx-user'></i> <?php echo htmlspecialchars($user_data['USERNAME']); ?></p>
                    <p><i class='bx bx-envelope'></i> <?php echo htmlspecialchars($user_data['EMAIL'] ?? 'Belum diatur'); ?></p>
                    <p><i class='bx bx-phone'></i> <?php echo htmlspecialchars($user_data['NO_TELEPON'] ?? 'Belum diatur'); ?></p>
                    <p><i class='bx bx-calendar'></i> Bergabung: <?php echo date('d/m/Y', strtotime($user_data['CREATED_AT'])); ?></p>
                    
                    <div class="photo-actions">
                        <form method="POST" action="" enctype="multipart/form-data" id="photoForm">
                            <div class="file-input-wrapper">
                                <input type="file" name="profile_photo" id="profile_photo" 
                                       accept="image/jpeg,image/jpg,image/png" 
                                       class="file-input" onchange="validateFile(this)">
                            </div>
                            <div class="photo-instructions">
                                <i class='bx bx-info-circle'></i> Format: JPG, JPEG, PNG | Maks: 5MB
                            </div>
                        </form>
                        
                        <?php if ($has_photo): ?>
                        <form method="POST" action="" onsubmit="return confirm('Yakin ingin menghapus foto profil?')">
                            <input type="hidden" name="delete_photo" value="1">
                            <button type="submit" class="delete-btn">
                                <i class='bx bx-trash'></i>
                                Hapus Foto Profil
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="main-content">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class='bx bx-check-circle'></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class='bx bx-error-circle'></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="tabs">
                    <button class="tab active" onclick="showTab('profile')">
                        <i class='bx bx-edit'></i>
                        Edit Profile
                    </button>
                    <button class="tab" onclick="showTab('info')">
                        <i class='bx bx-info-circle'></i>
                        Informasi Akun
                    </button>
                </div>
                
                <div id="profile-tab" class="tab-content active">
                    <h3 class="section-title">
                        <i class='bx bx-edit'></i>
                        Edit Data Pribadi
                    </h3>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Username *</label>
                                <input type="text" name="username" value="<?php echo htmlspecialchars($user_data['USERNAME']); ?>" required>
                                <div class="info-text">Username untuk login ke sistem</div>
                            </div>
                            
                            <div class="form-group">
                                <label>Nama Lengkap *</label>
                                <input type="text" name="nama_lengkap" value="<?php echo htmlspecialchars($user_data['NAMA_LENGKAP']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['EMAIL'] ?? ''); ?>" placeholder="example@email.com">
                                <div class="info-text">Email untuk notifikasi</div>
                            </div>
                            
                            <div class="form-group">
                                <label>No. Telepon</label>
                                <input type="tel" name="no_telepon" value="<?php echo htmlspecialchars($user_data['NO_TELEPON'] ?? ''); ?>" placeholder="0812-3456-7890">
                                <div class="info-text">Nomor WhatsApp/Telepon</div>
                            </div>
                        </div>
                        
                        <button type="submit" class="submit-btn">
                            <i class='bx bx-save'></i>
                            Simpan Perubahan
                        </button>
                    </form>
                </div>
                
                <div id="info-tab" class="tab-content">
                    <h3 class="section-title">
                        <i class='bx bx-info-circle'></i>
                        Informasi Akun
                    </h3>
                    
                    <div class="additional-info">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">ID Akun</div>
                                <div class="info-value">#<?php echo $user_data['ID']; ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Role</div>
                                <div class="info-value"><?php echo $role == 'Guru_BK' ? 'Guru BK' : $role; ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Status</div>
                                <div class="info-value">
                                    <span style="color: <?php echo $user_data['STATUS'] == 'Aktif' ? '#38a169' : '#e53e3e'; ?>;">
                                        <?php echo $user_data['STATUS'] == 'Aktif' ? 'Aktif ✅' : 'Tidak Aktif ❌'; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Bergabung Pada</div>
                                <div class="info-value"><?php echo date('d F Y', strtotime($user_data['CREATED_AT'])); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Terakhir Update</div>
                                <div class="info-value"><?php echo date('d F Y H:i', strtotime($user_data['UPDATED_AT'])); ?></div>
                            </div>
                        </div>
                        
                        <?php if ($additional_data): ?>
                            <h4 style="margin-top: 25px; margin-bottom: 15px; <?php echo $role == 'Admin' ? 'color: #f8fafc;' : 'color: #4a5568;'; ?> font-size: 18px;">
                                Informasi <?php echo $role == 'Siswa' ? 'Siswa' : ($role == 'Guru_BK' ? 'Guru BK' : 'Admin'); ?>
                            </h4>
                            <div class="info-grid">
                                <?php if ($role == 'Siswa'): ?>
                                    <div class="info-item">
                                        <div class="info-label">Kelas</div>
                                        <div class="info-value"><?php echo $additional_data['KELAS'] ?? '-'; ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Jurusan</div>
                                        <div class="info-value"><?php echo $additional_data['JURUSAN'] ?? '-'; ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">NIS</div>
                                        <div class="info-value"><?php echo $additional_data['NIS'] ?? '-'; ?></div>
                                    </div>
                                <?php elseif ($role == 'Guru_BK'): ?>
                                    <div class="info-item">
                                        <div class="info-label">NIP</div>
                                        <div class="info-value"><?php echo $additional_data['NIP'] ?? '-'; ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Pengalaman</div>
                                        <div class="info-value"><?php echo $additional_data['PENGALAMAN_MENGAJAR'] ?? '-'; ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label"> </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.getElementById(tabName + '-tab').classList.add('active');
            event.currentTarget.classList.add('active');
        }
        
        function validateFile(input) {
            const file = input.files[0];
            if (!file) return;
            
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!validTypes.includes(file.type)) {
                alert('Format file tidak didukung! Hanya JPG, JPEG, dan PNG yang diperbolehkan.');
                input.value = '';
                return false;
            }
            
            if (file.size > 5 * 1024 * 1024) {
                alert('Ukuran file terlalu besar! Maksimal 5MB.');
                input.value = '';
                return false;
            }
            
            const fileName = file.name;
            const fileExt = fileName.split('.').pop().toLowerCase();
            const validExt = ['jpg', 'jpeg', 'png'];
            if (!validExt.includes(fileExt)) {
                alert('Ekstensi file tidak valid! Gunakan .jpg, .jpeg, atau .png');
                input.value = '';
                return false;
            }
            
            document.getElementById('photoForm').submit();
            return true;
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('profile_photo');
            if (fileInput) {
                fileInput.addEventListener('change', function(e) {
                    const fileName = e.target.files[0]?.name || 'Pilih file';
                    this.parentNode.querySelector('.file-input').value = fileName;
                });
            }
        });
    </script>
</body>
</html>