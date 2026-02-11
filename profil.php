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
        } elseif ($file_size > 5 * 1024 * 1024) {
            $error = "Ukuran file terlalu besar! Maksimal 5MB.";
        } elseif (!in_array($file_ext, ['jpg', 'jpeg', 'png'])) {
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
    <title>Profile - APK BK</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #1e40af 0%, #2563eb 55%, #3b82f6 100%);
            --surface: rgba(255, 255, 255, 0.94);
            --surface-soft: rgba(255, 255, 255, 0.9);
            --surface-card: rgba(255, 255, 255, 0.95);
            --text-main: #2d3748;
            --text-muted: #718096;
            --accent: #2563eb;
            --border-soft: rgba(37, 99, 235, 0.18);
            --shadow-soft: 0 12px 28px rgba(16, 24, 40, 0.12);
        }

        body {
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            background: var(--bg-gradient);
            color: var(--text-main);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
            transition: background 0.35s ease, color 0.35s ease;
        }

        /* Override for Admin/Guru specific themes if desired */
        <?php if ($role == 'Admin'): ?>
            :root {
                --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
                --accent: #8b5cf6;
            }

        <?php elseif ($role == 'Guru_BK'): ?>
            :root {
                --bg-gradient: linear-gradient(135deg, #1a365d 0%, #2d3748 50%, #4a5568 100%);
                --accent: #3182ce;
            }

        <?php endif; ?>

        body.dark-mode {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #111827 45%, #1f2937 100%);
            --surface: rgba(17, 24, 39, 0.92);
            --surface-soft: rgba(17, 24, 39, 0.88);
            --surface-card: rgba(17, 24, 39, 0.9);
            --text-main: #e5e7eb;
            --text-muted: #9ca3af;
            --accent: #60a5fa;
            --border-soft: rgba(96, 165, 250, 0.26);
            --shadow-soft: 0 12px 28px rgba(2, 6, 23, 0.5);
        }

        * {
            box-sizing: border-box;
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
            background: radial-gradient(circle, rgba(255, 255, 255, 0.8) 0%, rgba(255, 255, 255, 0.3) 70%);
            animation-delay: 0s;
            box-shadow: 0 0 40px rgba(255, 255, 255, 0.4);
        }

        .floating:nth-child(2) {
            width: 180px;
            height: 180px;
            top: 65%;
            right: 7%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.7) 0%, rgba(255, 255, 255, 0.2) 70%);
            animation-delay: 1.5s;
            box-shadow: 0 0 50px rgba(255, 255, 255, 0.3);
        }

        .floating:nth-child(3) {
            width: 200px;
            height: 200px;
            bottom: 15%;
            left: 35%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.6) 0%, rgba(255, 255, 255, 0.25) 70%);
            animation-delay: 3s;
            box-shadow: 0 0 45px rgba(255, 255, 255, 0.35);
        }

        @keyframes float {

            0%,
            100% {
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

        /* Sidebar & Header */
        body.sidebar-open {
            overflow: hidden;
        }

        .sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.25s ease, visibility 0.25s ease;
            z-index: 1200;
        }

        .sidebar-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .header {
            margin-left: 270px;
            background: var(--surface);
            border-bottom: 2px solid var(--accent);
            box-shadow: var(--shadow-soft);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1000;
            transition: margin-left 0.3s ease, background 0.3s ease;
        }

        .brand-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-left a {
            text-decoration: none;
            color: inherit;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent), #1d4ed8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .theme-toggle,
        .sidebar-toggle {
            border: 1px solid var(--border-soft);
            background: rgba(255, 255, 255, 0.4);
            color: var(--text-main);
            border-radius: 12px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.25s ease;
            font-weight: 600;
        }

        .theme-toggle {
            padding: 0 14px;
            gap: 8px;
        }

        .sidebar-toggle {
            display: none;
            padding: 0 12px;
            font-size: 22px;
            width: 46px;
        }

        body.dark-mode .theme-toggle,
        body.dark-mode .sidebar-toggle {
            background: rgba(31, 41, 55, 0.85);
        }

        .theme-toggle:hover,
        .sidebar-toggle:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .nav {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 270px;
            background: var(--surface-soft);
            border-right: 1px solid var(--border-soft);
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 16px 18px 18px;
            overflow-y: auto;
            z-index: 1300;
            backdrop-filter: blur(18px);
            transition: transform 0.3s ease, background 0.3s ease;
        }

        .sidebar-top {
            margin-bottom: 14px;
            padding: 12px;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.2), rgba(59, 130, 246, 0.18));
            border: 1px solid var(--border-soft);
        }

        .sidebar-top h4 {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.4px;
            margin-bottom: 10px;
            color: var(--text-main);
            text-transform: uppercase;
        }

        .sidebar-icons {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }

        .sidebar-icon {
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.35);
            background: rgba(255, 255, 255, 0.45);
            color: var(--text-main);
            font-size: 18px;
        }

        body.dark-mode .sidebar-icon {
            background: rgba(31, 41, 55, 0.72);
            border-color: rgba(129, 140, 248, 0.35);
        }

        .nav a {
            color: var(--text-muted);
            text-decoration: none;
            width: 100%;
            padding: 14px 22px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .nav a:hover,
        .nav a.active {
            color: var(--accent);
            border-color: var(--border-soft);
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.12), rgba(59, 130, 246, 0.12));
            transform: translateX(4px);
        }

        .nav a::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.12), rgba(59, 130, 246, 0.12));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
            z-index: -1;
        }

        .nav a:hover::before {
            transform: scaleX(1);
        }

        .nav a:active,
        .nav a.tap-active {
            color: var(--accent);
            border-color: var(--border-soft);
            transform: translateX(4px) scale(0.98);
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.14), rgba(59, 130, 246, 0.14));
        }

        /* Container & Layout */
        .container {
            margin-left: 270px;
            max-width: none;
            padding: 40px;
            transition: margin-left 0.3s ease;
            position: relative;
            z-index: 5;
        }

        .profile-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 30px;
        }

        /* Content Cards */
        .content-card {
            background: var(--surface-card);
            border-radius: 20px;
            border: 1px solid var(--border-soft);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.1);
            backdrop-filter: blur(15px);
            padding: 30px;
            animation: fadeIn 0.8s ease-out;
        }

        body.dark-mode .content-card {
            background: rgba(17, 24, 39, 0.9);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        /* Mobile / Responsive Styles - MATCHING data_diri.php EXACTLY */
        @media (max-width: 1024px) {
            .header {
                margin-left: 0;
                padding: 12px 14px;
                display: block;
            }

            .container {
                margin-left: 0;
                padding: 20px;
            }

            .brand-left {
                width: 100%;
                justify-content: space-between;
                margin-bottom: 10px;
                position: relative;
                z-index: 1405;
            }

            .sidebar-toggle {
                display: inline-flex;
            }

            .nav {
                transform: translateX(-105%);
                width: 280px;
                padding-top: 16px;
                box-shadow: 0 10px 30px rgba(2, 6, 23, 0.35);
            }

            .nav.open {
                transform: translateX(0);
            }

            .user-info {
                flex-direction: row;
                align-items: center;
                gap: 10px;
                flex-wrap: nowrap;
                justify-content: flex-end;
                width: 100%;
            }

            .theme-toggle span {
                display: none;
            }

            .profile-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 12px;
            }

            .brand-left {
                justify-content: center;
                margin-bottom: 8px;
            }

            .brand-left a {
                margin: 0 auto;
                text-align: center;
            }

            .sidebar-toggle {
                position: absolute;
                left: 0;
                top: 100%;
                transform: translateY(-50%);
            }

            .header h1 {
                font-size: 18px;
                margin: 0;
                text-align: center;
            }

            .user-info {
                gap: 8px;
                justify-content: center;
                flex-wrap: wrap;
                margin-top: 2px;
            }
        }

        @media (max-width: 480px) {
            .theme-toggle {
                height: 36px;
                padding: 0 9px;
            }

            .brand-left a h1 {
                font-size: 16px;
                text-align: center;
            }
        }

        /* Profile Specific Styles */
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
            border: 4px solid var(--accent);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            background: linear-gradient(135deg, var(--accent), #1d4ed8);
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

        .profile-info {
            text-align: center;
        }

        .profile-info h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: var(--text-main);
        }

        .profile-info .role-badge {
            display: inline-block;
            padding: 6px 15px;
            background: linear-gradient(135deg, var(--accent), #1d4ed8);
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        .profile-info p {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .upload-btn,
        .delete-btn,
        .submit-btn {
            background: linear-gradient(135deg, var(--accent), #1d4ed8);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            width: 100%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }

        .submit-btn {
            padding: 16px 24px;
            font-size: 16px;
            margin-top: 20px;
        }

        .delete-btn {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .upload-btn:hover,
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-main);
            font-weight: 600;
            font-size: 14px;
        }

        input,
        select {
            width: 100%;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.8);
            border: 2px solid var(--border-soft);
            border-radius: 12px;
            color: var(--text-main);
            font-family: inherit;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        body.dark-mode input,
        body.dark-mode select {
            background: rgba(30, 41, 59, 0.8);
            color: #e5e7eb;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 2px solid var(--border-soft);
        }

        .tab {
            padding: 12px 24px;
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-weight: 600;
            position: relative;
            transition: all 0.3s ease;
        }

        .tab.active {
            color: var(--accent);
        }

        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--accent);
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.5s ease-out;
        }

        .tab-content.active {
            display: block;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: rgba(72, 187, 120, 0.15);
            color: #059669;
        }

        .alert-error {
            background: rgba(245, 101, 101, 0.15);
            color: #dc2626;
        }

        .section-title {
            font-size: 20px;
            color: var(--text-main);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .additional-info {
            background: rgba(37, 99, 235, 0.05);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-soft);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .info-label {
            font-size: 12px;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .info-value {
            font-weight: 600;
            color: var(--text-main);
        }

        .file-input {
            width: 100%;
            padding: 10px;
            border: 2px dashed var(--border-soft);
            border-radius: 12px;
            cursor: pointer;
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
    </style>
</head>

<body>

    <div class="floating"></div>
    <div class="floating"></div>
    <div class="floating"></div>

    <!-- Header -->
    <div class="header">
        <div class="brand-left">
            <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Buka menu">
                <i class='bx bx-menu'></i>
            </button>
            <h1><i class='bx bx-user-circle'></i> <?php echo $page_title; ?></h1>
        </div>
        <div class="user-info">
            <button class="theme-toggle" id="themeToggle" type="button" aria-label="Ganti mode tema">
                <i class='bx bx-moon'></i>
                <span>Mode</span>
            </button>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="nav">
        <div class="sidebar-top">
            <h4>Menu <?php echo $role; ?></h4>
            <div class="sidebar-icons">
                <span class="sidebar-icon"><i class='bx bx-home-heart'></i></span>
                <span class="sidebar-icon"><i class='bx bx-book-open'></i></span>
                <span class="sidebar-icon"><i class='bx bx-brain'></i></span>
                <span class="sidebar-icon"><i class='bx bx-calendar-star'></i></span>
            </div>
        </div>

        <a href="<?php echo $dashboard_link; ?>">
            <i class='bx bx-home'></i>
            Dashboard
        </a>

        <?php if ($role == 'Siswa'): ?>
            <a href="data_diri.php">
                <i class='bx bx-user'></i>
                Data Diri
            </a>
            <a href="form_kepribadian.php">
                <i class='bx bx-brain'></i>
                Form Kepribadian
            </a>
            <a href="form_belajar.php">
                <i class='bx bx-book'></i>
                Form Belajar
            </a>
            <a href="form_karir.php">
                <i class='bx bx-briefcase'></i>
                Form Karir
            </a>
            <a href="form_sosial.php">
                <i class='bx bx-group'></i>
                Form Sosial
            </a>
            <a href="jadwal_guru.php">
                <i class='bx bx-calendar'></i>
                Jadwal Guru
            </a>
        <?php endif; ?>

        <a href="profil.php" class="active">
            <i class='bx bx-face'></i>
            Profil
        </a>

        <a href="logout.php" style="margin-top: auto; color: #ef4444;">
            <i class='bx bx-log-out'></i>
            Logout
        </a>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Main Container -->
    <div class="container">
        <div class="profile-container">

            <!-- Left Column: Profile Card -->
            <div class="content-card sidebar-card">
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
                    <p><i class='bx bx-envelope'></i>
                        <?php echo htmlspecialchars($user_data['EMAIL'] ?? 'Belum diatur'); ?></p>
                    <p><i class='bx bx-phone'></i>
                        <?php echo htmlspecialchars($user_data['NO_TELEPON'] ?? 'Belum diatur'); ?></p>
                    <p><i class='bx bx-calendar'></i> Join:
                        <?php echo date('d/m/Y', strtotime($user_data['CREATED_AT'])); ?></p>

                    <div class="photo-actions">
                        <form method="POST" action="" enctype="multipart/form-data" id="photoForm">
                            <div class="file-input-wrapper">
                                <input type="file" name="profile_photo" id="profile_photo"
                                    accept="image/jpeg,image/jpg,image/png" class="file-input"
                                    onchange="validateFile(this)" style="display: none;">
                                <button type="button" class="upload-btn"
                                    onclick="document.getElementById('profile_photo').click()">
                                    <i class='bx bx-camera'></i> Ganti Foto
                                </button>
                            </div>
                        </form>

                        <?php if ($has_photo): ?>
                            <form method="POST" action="" onsubmit="return confirm('Yakin ingin menghapus foto profil?')">
                                <input type="hidden" name="delete_photo" value="1">
                                <button type="submit" class="delete-btn">
                                    <i class='bx bx-trash'></i> Hapus Foto
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Main Content -->
            <div class="content-card main-content">
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

                <div class="tabs">
                    <button class="tab active" onclick="showTab('profile')">
                        <i class='bx bx-edit'></i> Edit Profile
                    </button>
                    <button class="tab" onclick="showTab('info')">
                        <i class='bx bx-info-circle'></i> Informasi Akun
                    </button>
                </div>

                <div id="profile-tab" class="tab-content active">
                    <h3 class="section-title"><i class='bx bx-user-pin'></i> Edit Data Pribadi</h3>

                    <form method="POST" action="">
                        <input type="hidden" name="update_profile" value="1">

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Username *</label>
                                <input type="text" name="username"
                                    value="<?php echo htmlspecialchars($user_data['USERNAME']); ?>" required>
                                <div style="font-size: 12px; color: var(--text-muted); margin-top: 5px;">Username untuk
                                    login</div>
                            </div>

                            <div class="form-group">
                                <label>Nama Lengkap *</label>
                                <input type="text" name="nama_lengkap"
                                    value="<?php echo htmlspecialchars($user_data['NAMA_LENGKAP']); ?>" required>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email"
                                    value="<?php echo htmlspecialchars($user_data['EMAIL'] ?? ''); ?>"
                                    placeholder="example@email.com">
                            </div>

                            <div class="form-group">
                                <label>No. Telepon</label>
                                <input type="tel" name="no_telepon"
                                    value="<?php echo htmlspecialchars($user_data['NO_TELEPON'] ?? ''); ?>"
                                    placeholder="0812-3456-7890">
                            </div>
                        </div>

                        <button type="submit" class="submit-btn">
                            <i class='bx bx-save'></i> Simpan Perubahan
                        </button>
                    </form>
                </div>

                <div id="info-tab" class="tab-content">
                    <h3 class="section-title"><i class='bx bx-detail'></i> Detail Informasi</h3>

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
                                    <span
                                        style="color: <?php echo $user_data['STATUS'] == 'Aktif' ? '#059669' : '#dc2626'; ?>;">
                                        <?php echo $user_data['STATUS'] == 'Aktif' ? 'Aktif' : 'Tidak Aktif'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Terakhir Update</div>
                                <div class="info-value">
                                    <?php echo date('d F Y', strtotime($user_data['UPDATED_AT'])); ?></div>
                            </div>
                        </div>

                        <?php if ($additional_data): ?>
                            <hr style="margin: 20px 0; border: 0; border-top: 1px solid var(--border-soft);">
                            <h4 style="margin-bottom: 15px; font-size: 16px; color: var(--text-main);">
                                Data <?php echo $role == 'Siswa' ? 'Siswa' : ($role == 'Guru_BK' ? 'Guru BK' : 'Admin'); ?>
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
                                        <div class="info-value"><?php echo $additional_data['PENGALAMAN_MENGAJAR'] ?? '-'; ?>
                                        </div>
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
        document.addEventListener('DOMContentLoaded', function () {
            // Sidebar & Theme Logic - Matched to data_diri.php
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
                themeToggle.addEventListener('click', function () {
                    const isDark = document.body.classList.toggle('dark-mode');
                    localStorage.setItem('dashboard_theme', isDark ? 'dark' : 'light');
                    this.innerHTML = isDark
                        ? "<i class='bx bx-sun'></i><span>Mode</span>"
                        : "<i class='bx bx-moon'></i><span>Mode</span>";
                });
            }

            if (sidebarToggle) {
                // Throttle click to prevent double firing issues
                let lastSidebarToggle = 0;
                const handleSidebarToggle = function (e) {
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

            // Mobile Touch Improvements for Links
            document.querySelectorAll('.nav a').forEach(link => {
                link.addEventListener('touchstart', function () {
                    this.classList.add('tap-active');
                }, { passive: true });

                link.addEventListener('touchend', function () {
                    setTimeout(() => {
                        this.classList.remove('tap-active');
                    }, 140);
                }, { passive: true });

                link.addEventListener('touchcancel', function () {
                    this.classList.remove('tap-active');
                }, { passive: true });

                link.addEventListener('click', function () {
                    if (window.innerWidth <= 1024) {
                        closeSidebar();
                    }
                });
            });

            window.addEventListener('resize', function () {
                if (window.innerWidth > 1024) {
                    closeSidebar();
                }
            });
        });

        // Profile Page Specific Logic
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));

            const content = document.getElementById(tabName + '-tab');
            if (content) content.classList.add('active');

            // Find the button that triggered this
            const button = event.currentTarget;
            if (button) button.classList.add('active');
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

            document.getElementById('photoForm').submit();
            return true;
        }
    </script>
</body>

</html>