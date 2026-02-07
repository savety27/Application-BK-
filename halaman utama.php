<?php
session_start();
$logged_in = isset($_SESSION['user_id']);
$user_role = $_SESSION['role'] ?? '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APK BK - Bimbingan Konseling SMKN 7 Batam</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #0c2461 0%, #1e3799 25%, #4a69bd 50%, #6a89cc 100%);
        color: #ffffff;
        min-height: 100vh;
        position: relative;
        overflow-x: hidden;
    }

    .bg-elements {
        position: fixed;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 0;
    }

    .circle {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        animation: float 6s ease-in-out infinite;
        box-shadow: 0 0 40px rgba(255, 255, 255, 0.1);
    }

    .circle:nth-child(1) {
        width: 300px;
        height: 300px;
        top: 10%;
        right: 5%;
        animation-delay: 0s;
    }

    .circle:nth-child(2) {
        width: 200px;
        height: 200px;
        top: 60%;
        left: 8%;
        background: rgba(255, 255, 255, 0.08);
        animation-delay: 2s;
    }

    .circle:nth-child(3) {
        width: 150px;
        height: 150px;
        bottom: 15%;
        right: 15%;
        background: rgba(255, 255, 255, 0.06);
        animation-delay: 4s;
    }

    @keyframes float {
        0%, 100% {
            transform: translateY(0) rotate(0deg);
        }
        50% {
            transform: translateY(-20px) rotate(180deg);
        }
    }

    .header {
        background: rgba(12, 36, 97, 0.95);
        backdrop-filter: blur(10px);
        padding: 20px 50px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        position: relative;
        z-index: 100;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .logo-container {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .logo-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #1e3799, #4a69bd);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
        box-shadow: 0 4px 15px rgba(30, 55, 153, 0.5);
    }

    .logo-text h1 {
        font-size: 28px;
        font-weight: 700;
        background: linear-gradient(135deg, #ffffff, #bbdefb);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .logo-text p {
        font-size: 14px;
        color: #bbdefb;
        font-weight: 500;
    }

    .nav-buttons {
        display: flex;
        gap: 15px;
    }

    .btn {
        padding: 12px 28px;
        border-radius: 25px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        border: 2px solid transparent;
        position: relative;
        overflow: hidden;
    }

    .btn-login {
        background: linear-gradient(135deg, #1e3799 0%, #4a69bd 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(30, 55, 153, 0.4);
    }

    .btn-login:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(30, 55, 153, 0.6);
    }

    .btn-dashboard {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border-color: #4a69bd;
        backdrop-filter: blur(10px);
    }

    .btn-dashboard:hover {
        background: linear-gradient(135deg, #1e3799 0%, #4a69bd 100%);
        color: white;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(30, 55, 153, 0.4);
    }

    .hero {
        padding: 100px 50px;
        text-align: center;
        position: relative;
        z-index: 10;
        max-width: 1200px;
        margin: 0 auto;
        margin-top: 110px;
    }

    .hero h2 {
        font-size: 48px;
        font-weight: 700;
        margin-bottom: 20px;
        background: linear-gradient(135deg, #ffffff, #bbdefb);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        line-height: 1.2;
        text-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .hero p {
        font-size: 20px;
        color: #e3f2fd;
        margin-bottom: 40px;
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
        line-height: 1.6;
    }

    .highlight {
        background: linear-gradient(135deg, rgba(30, 55, 153, 0.4), rgba(74, 105, 189, 0.4));
        padding: 4px 12px;
        border-radius: 20px;
        font-weight: 600;
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .gallery-section {
        padding: 80px 50px;
        position: relative;
        z-index: 10;
        max-width: 1400px;
        margin: 0 auto;
    }

    .gallery-container {
        position: relative;
        overflow: hidden;
        padding: 30px 0;
    }

    .gallery-slider {
        display: flex;
        gap: 30px;
        transition: transform 0.5s ease;
        padding: 15px 10px;
    }

    .gallery-item {
        flex: 0 0 350px;
        height: 250px;
        border-radius: 20px;
        overflow: hidden;
        position: relative;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
        border: 3px solid transparent;
    }

    .gallery-item:hover {
        transform: translateY(-15px) scale(1.08);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
        border-color: #4a69bd;
        z-index: 2;
    }

    .gallery-item::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(45deg, rgba(74, 105, 189, 0.1), transparent);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .gallery-item:hover::after {
        opacity: 1;
    }

    .gallery-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.7s ease;
    }

    .gallery-item:hover img {
        transform: scale(1.15);
    }

    .gallery-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(transparent, rgba(12, 36, 97, 0.95));
        padding: 25px;
        transform: translateY(100%);
        transition: transform 0.3s ease;
        z-index: 1;
    }

    .gallery-item:hover .gallery-overlay {
        transform: translateY(0);
    }

    .gallery-overlay h4 {
        font-size: 18px;
        font-weight: 600;
        color: white;
        margin-bottom: 8px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
    }

    .gallery-overlay p {
        font-size: 14px;
        color: #bbdefb;
        line-height: 1.4;
    }

    .gallery-nav {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-top: 40px;
    }

    .gallery-nav-btn {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        border: 2px solid #4a69bd;
        color: white;
        font-size: 20px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
    }

    .gallery-nav-btn:hover {
        background: #4a69bd;
        transform: translateY(-3px) scale(1.1);
        box-shadow: 0 8px 20px rgba(74, 105, 189, 0.5);
    }

    .gallery-dots {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 25px;
    }

    .gallery-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .gallery-dot.active {
        background: #4a69bd;
        transform: scale(1.3);
        box-shadow: 0 0 10px rgba(74, 105, 189, 0.8);
    }

    .features {
        padding: 80px 50px;
        background: rgba(12, 36, 97, 0.9);
        backdrop-filter: blur(10px);
        border-radius: 30px;
        margin: 0 50px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        position: relative;
        z-index: 10;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .section-title {
        text-align: center;
        margin-bottom: 50px;
        margin-top: 85px;
    }

    .section-title h3 {
        font-size: 36px;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 15px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    .section-title p {
        color: #bbdefb;
        font-size: 18px;
        max-width: 600px;
        margin: 0 auto;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 30px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .feature-card {
        background: rgba(255, 255, 255, 0.1);
        padding: 40px 30px;
        border-radius: 20px;
        text-align: center;
        transition: all 0.3s ease;
        border: 1px solid rgba(255, 255, 255, 0.1);
        position: relative;
        overflow: hidden;
        backdrop-filter: blur(10px);
    }

    .feature-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 5px;
        background: linear-gradient(90deg, #1e3799, #4a69bd);
    }

    .feature-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
        border-color: #4a69bd;
        background: rgba(255, 255, 255, 0.15);
    }

    .feature-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, rgba(30, 55, 153, 0.3), rgba(74, 105, 189, 0.3));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 25px;
        font-size: 32px;
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.2);
    }

    .feature-card h4 {
        font-size: 22px;
        color: #ffffff;
        margin-bottom: 15px;
        font-weight: 600;
    }

    .feature-card p {
        color: #e3f2fd;
        line-height: 1.6;
    }

    .how-it-works {
        padding: 80px 50px;
        position: relative;
        z-index: 10;
        max-width: 1200px;
        margin: 0 auto;
    }

    .steps {
        display: flex;
        justify-content: center;
        gap: 40px;
        margin-top: 50px;
        flex-wrap: wrap;
    }

    .step {
        flex: 1;
        min-width: 250px;
        text-align: center;
        position: relative;
    }

    .step-number {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #1e3799, #4a69bd);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        font-weight: 700;
        margin: 0 auto 20px;
        box-shadow: 0 8px 20px rgba(30, 55, 153, 0.5);
        border: 2px solid rgba(255, 255, 255, 0.2);
    }

    .step h4 {
        font-size: 20px;
        color: #ffffff;
        margin-bottom: 15px;
    }

    .step p {
        color: #e3f2fd;
        line-height: 1.6;
    }

    .testimonial {
        padding: 80px 50px;
        background: linear-gradient(135deg, rgba(30, 55, 153, 0.2), rgba(74, 105, 189, 0.1));
        border-radius: 30px;
        margin: 50px;
        position: relative;
        z-index: 10;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .testimonial-content {
        max-width: 1200px;
        margin: 0 auto;
    }

    .testimonial-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 30px;
        margin-top: 40px;
    }

    .testimonial-card {
        background: rgba(255, 255, 255, 0.1);
        padding: 35px 30px;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.1);
        position: relative;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
    }

    .testimonial-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        border-color: #4a69bd;
        background: rgba(255, 255, 255, 0.15);
    }

    .quote {
        font-size: 16px;
        color: #ffffff;
        font-style: italic;
        margin-bottom: 25px;
        line-height: 1.6;
    }

    .rating {
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .star {
        color: #FFD700;
        font-size: 20px;
        filter: drop-shadow(0 0 3px rgba(255, 215, 0, 0.5));
    }

    .author-info {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.2);
    }

    .author {
        font-weight: 600;
        color: #ffffff;
        font-size: 16px;
    }

    .author-detail {
        color: #bbdefb;
        font-size: 14px;
        font-weight: 500;
    }

    .cta {
        padding: 80px 50px;
        text-align: center;
        position: relative;
        z-index: 10;
    }

    .cta-content {
        max-width: 600px;
        margin: 0 auto;
    }

    .cta h3 {
        font-size: 36px;
        color: #ffffff;
        margin-bottom: 20px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    .cta p {
        font-size: 18px;
        color: #e3f2fd;
        margin-bottom: 40px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #1e3799 0%, #4a69bd 100%);
        color: white;
        padding: 18px 40px;
        font-size: 18px;
        border-radius: 30px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 8px 25px rgba(30, 55, 153, 0.5);
        transition: all 0.3s ease;
        border: 2px solid rgba(255, 255, 255, 0.2);
        position: relative;
        overflow: hidden;
    }

    .btn-primary:hover {
        transform: translateY(-5px) scale(1.05);
        box-shadow: 0 12px 30px rgba(30, 55, 153, 0.6);
        background: linear-gradient(135deg, #273ca1 0%, #5a7ad1 100%);
    }

    .btn-primary::after {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.5s;
    }

    .btn-primary:hover::after {
        left: 100%;
    }

    .footer {
        background: linear-gradient(135deg, #0c2461, #1e3799);
        color: white;
        padding: 60px 50px 30px;
        position: relative;
        z-index: 100;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .footer-content {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 40px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .footer-section h4 {
        font-size: 20px;
        margin-bottom: 20px;
        color: #bbdefb;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    }

    .footer-section p {
        color: #e3f2fd;
        line-height: 1.6;
        margin-bottom: 15px;
    }

    .contact-info {
        list-style: none;
    }

    .contact-info li {
        color: #e3f2fd;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .service-hours p {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        padding-bottom: 5px;
        border-bottom: 1px dashed rgba(255, 255, 255, 0.2);
    }

    .service-hours .day {
        font-weight: 600;
        color: #bbdefb;
    }

    .service-hours .time {
        color: #e3f2fd;
    }

    .social-links {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }

    .social-links a {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        text-decoration: none;
        transition: all 0.3s ease;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .social-links a:hover {
        background: #4a69bd;
        transform: translateY(-3px);
        box-shadow: 0 4px 10px rgba(74, 105, 189, 0.4);
    }

    .footer-bottom {
        text-align: center;
        padding-top: 30px;
        margin-top: 40px;
        border-top: 1px solid rgba(255, 255, 255, 0.2);
        color: #bbdefb;
        font-size: 14px;
    }

    @media (max-width: 768px) {
        .header {
            padding: 15px 20px;
            flex-direction: column;
            gap: 20px;
        }
        
        .hero {
            padding: 60px 20px;
        }
        
        .hero h2 {
            font-size: 36px;
        }
        
        .gallery-section {
            padding: 40px 20px;
        }
        
        .gallery-item {
            flex: 0 0 300px;
            height: 220px;
        }
        
        .gallery-overlay h4 {
            font-size: 16px;
        }
        
        .gallery-overlay p {
            font-size: 12px;
        }
        
        .features, .testimonial {
            margin: 20px;
            padding: 40px 20px;
        }
        
        .how-it-works {
            padding: 40px 20px;
        }
        
        .steps {
            flex-direction: column;
            align-items: center;
        }
        
        .testimonial-grid {
            grid-template-columns: 1fr;
        }
        
        .footer {
            padding: 40px 20px;
        }
        
        .circle {
            display: none;
        }
    }

    @media (max-width: 480px) {
        .hero h2 {
            font-size: 28px;
        }
        
        .hero p {
            font-size: 16px;
        }
        
        .section-title h3 {
            font-size: 28px;
        }
        
        .gallery-item {
            flex: 0 0 250px;
            height: 180px;
        }
        
        .gallery-overlay {
            padding: 15px;
        }
        
        .gallery-overlay h4 {
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .gallery-overlay p {
            font-size: 11px;
        }
        
        .feature-card {
            padding: 30px 20px;
        }
        
        .testimonial-card {
            padding: 25px 20px;
        }
        
        .btn {
            padding: 10px 20px;
            font-size: 14px;
        }
        
        .service-hours p {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }
    }
</style>
</head>
<body>
    <div class="bg-elements">
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
    </div>

    <header class="header">
        <div class="logo-container">
            <div class="logo-icon">
                <i class='bx bx-conversation'></i>
            </div>
            <div class="logo-text">
                <h1>APK BK SMKN 7 Batam</h1>
                <p>Bimbingan & Konseling Profesional</p>
            </div>
        </div>
        
        <div class="nav-buttons">
            <?php if ($logged_in): ?>
                <?php
                $dashboard_url = '';
                if ($user_role == 'Siswa') {
                    $dashboard_url = 'dashboard_siswa.php';
                } elseif ($user_role == 'Guru_BK') {
                    $dashboard_url = 'dashboard_guru.php';
                } elseif ($user_role == 'Admin') {
                    $dashboard_url = 'admin_dashboard.php';
                } else {
                    $dashboard_url = 'dashboard_siswa.php';
                }
                ?>
                <a href="<?php echo $dashboard_url; ?>" class="btn btn-dashboard">
                    <i class='bx bx-dashboard'></i>
                    Dashboard
                </a>
            <?php else: ?>
                <a href="index.php" class="btn btn-login">
                    <i class='bx bx-log-in'></i>
                    Masuk
                </a>
            <?php endif; ?>
        </div>
    </header>

    <section class="hero">
        <h2>Selamat Datang di Layanan Bimbingan Konseling SMKN 7 Batam</h2>
        <p>
            Platform digital untuk mendukung perkembangan pribadi, sosial, belajar, dan karir siswa. 
            <span class="highlight">Konsultasi lebih mudah, privat, dan efektif !</span>
        </p>
    </section>

    <section class="gallery-section">
        <div class="section-title">
            <h3>Galeri Dokumentasi BK</h3>
            <p>Momen-momen kegiatan Bimbingan Konseling SMKN 7 Batam</p>
        </div>
        
        <div class="gallery-container">
            <div class="gallery-slider" id="gallerySlider">
                <?php

                $photos = [
                    ['dokumentasi bk/foto 1.jpg', 'Sesi Konseling Individu', 'Konsultasi pribadi dengan guru BK'],
                    ['dokumentasi bk/foto 2.jpeg', 'Workshop Karir', 'Pembimbingan pemilihan jurusan kuliah'],
                    ['dokumentasi bk/foto 3.jpeg', 'Sosialisasi BK', 'Pengenalan layanan BK kepada siswa baru'],
                    ['dokumentasi bk/foto 4.jpg', 'Kegiatan Kelompok', 'Diskusi kelompok tentang masalah belajar'],
                    ['dokumentasi bk/foto 5.jpeg', 'Parent Meeting', 'Pertemuan dengan orang tua siswa'],
                    ['dokumentasi bk/foto 6.jfif', 'Pelatihan Soft Skills', 'Pengembangan keterampilan sosial siswa'],
                    ['dokumentasi bk/foto 7.jfif', 'Evaluasi Program BK', 'Review dan perbaikan program bimbingan']
                ];
                
                foreach ($photos as $index => $photo):
                ?>
                <div class="gallery-item">
                    <img src="<?php echo $photo[0]; ?>" alt="<?php echo $photo[1]; ?>" 
                         onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1503676260728-1c00da094a0b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=300&q=80';">
                    <div class="gallery-overlay">
                        <h4><?php echo $photo[1]; ?></h4>
                        <p><?php echo $photo[2]; ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="gallery-nav">
                <button class="gallery-nav-btn" id="prevBtn">
                    <i class='bx bx-chevron-left'></i>
                </button>
                <button class="gallery-nav-btn" id="nextBtn">
                    <i class='bx bx-chevron-right'></i>
                </button>
            </div>
            
            <div class="gallery-dots" id="galleryDots">
                <?php for($i = 0; $i < count($photos); $i++): ?>
                    <span class="gallery-dot <?php echo $i === 0 ? 'active' : ''; ?>" data-index="<?php echo $i; ?>"></span>
                <?php endfor; ?>
            </div>
        </div>
    </section>

    <section class="features">
        <div class="section-title">
            <h3>Layanan yang Tersedia</h3>
            <p>Kami menyediakan berbagai layanan bimbingan dan konseling sesuai kebutuhan siswa</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class='bx bx-user-circle'></i>
                </div>
                <h4>Bimbingan Kepribadian</h4>
                <p>Mengembangkan potensi diri, meningkatkan kepercayaan diri, dan mengatasi masalah pribadi</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class='bx bx-book-reader'></i>
                </div>
                <h4>Bimbingan Belajar</h4>
                <p>Strategi belajar efektif, manajemen waktu, dan mengatasi kesulitan belajar di sekolah</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class='bx bx-briefcase'></i>
                </div>
                <h4>Bimbingan Karir</h4>
                <p>Panduan merencanakan masa depan karir, pemilihan jurusan, dan pengembangan potensi diri</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class='bx bx-group'></i>
                </div>
                <h4>Bimbingan Sosial</h4>
                <p>Membangun hubungan baik dengan teman, keluarga, dan lingkungan sekitar</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class='bx bx-message-square-dots'></i>
                </div>
                <h4>Konsultasi Privat</h4>
                <p>Sesi curhat dan konsultasi pribadi dengan guru BK untuk berbagai masalah</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class='bx bx-calendar'></i>
                </div>
                <h4>Jadwal Fleksibel</h4>
                <p>Atur jadwal konsultasi Anda dengan sistem booking online yang mudah</p>
            </div>
        </div>
    </section>

    <section class="how-it-works">
        <div class="section-title">
            <h3>Cara Menggunakan Layanan</h3>
            <p>Ikuti langkah-langkah berikut untuk memulai konsultasi</p>
        </div>
        
        <div class="steps">
            <div class="step">
                <div class="step-number">1</div>
                <h4>Daftar / Login</h4>
                <p>Daftar akun baru jika belum punya, atau login menggunakan akun yang sudah terdaftar</p>
            </div>
            
            <div class="step">
                <div class="step-number">2</div>
                <h4>Isi Form Assessment</h4>
                <p>Lengkapi form karir, kepribadian, dan assessment lainnya untuk data yang akurat</p>
            </div>
            
            <div class="step">
                <div class="step-number">3</div>
                <h4>Ajukan Konsultasi</h4>
                <p>Pilih waktu konsultasi yang tersedia dan ajukan permintaan konsultasi</p>
            </div>
            
            <div class="step">
                <div class="step-number">4</div>
                <h4>Mulai Konsultasi</h4>
                <p>Datang sesuai jadwal atau lakukan konsultasi sesuai yang telah disetujui</p>
            </div>
        </div>
    </section>

    <section class="testimonial">
        <div class="section-title">
            <h3>Apa Kata Mereka</h3>
            <p>Pengalaman siswa yang telah menggunakan layanan BK kami</p>
        </div>
        
        <div class="testimonial-content">
            <div class="testimonial-grid">
                <div class="testimonial-card">
                    <div class="rating">
                        <span class="star">⭐</span>
                        <span class="star">⭐</span>
                        <span class="star">⭐</span>
                        <span class="star">⭐</span>
                        <span class="star">⭐</span>
                        <span style="margin-left: 10px; color: #ffffff; font-weight: 600;">5.0</span>
                    </div>
                    <p class="quote">
                        "Dengan APK BK, saya bisa berkonsultasi dengan nyaman tentang masalah belajar saya. 
                        Guru BK sangat membantu memberikan solusi yang tepat dan memotivasi saya untuk lebih baik."
                    </p>
                    <div class="author-info">
                        <div>
                            <div class="author">Andi Pratama</div>
                            <div class="author-detail">Siswa Kelas XII TKL</div>
                        </div>
                        <div style="font-size: 12px; color: #bbdefb;">6 Januari 2025</div>
                    </div>
                </div>
                
                <div class="testimonial-card">
                    <div class="rating">
                        <span class="star">⭐</span>
                        <span class="star">⭐</span>
                        <span class="star">⭐</span>
                        <span class="star" style="color: rgba(255, 255, 255, 0.3);">⭐</span>
                        <span style="margin-left: 10px; color: #ffffff; font-weight: 600;">4.0</span>
                    </div>
                    <p class="quote">
                        "Bimbingan karir membantu saya menentukan jurusan kuliah. 
                        Tes minat bakat yang diberikan sangat akurat sesuai dengan kemampuan saya."
                    </p>
                    <div class="author-info">
                        <div>
                            <div class="author">Siti Rahma</div>
                            <div class="author-detail">Siswa Kelas XI TKJ</div>
                        </div>
                        <div style="font-size: 12px; color: #bbdefb;">14 April 2025</div>
                    </div>
                </div>
                
                <div class="testimonial-card">
                    <div class="rating">
                        <span class="star">⭐</span>
                        <span class="star">⭐</span>
                        <span class="star">⭐</span>
                        <span class="star">⭐</span>
                        <span class="star">⭐</span>
                        <span style="margin-left: 10px; color: #ffffff; font-weight: 600;">5.0</span>
                    </div>
                    <p class="quote">
                        "Sesi konseling membantu saya mengatasi masalah sosial dengan teman sekelas. 
                        Sekarang hubungan saya dengan teman-teman menjadi lebih baik."
                    </p>
                    <div class="author-info">
                        <div>
                            <div class="author">Budi Santoso</div>
                            <div class="author-detail">Siswa Kelas XI DKV</div>
                        </div>
                        <div style="font-size: 12px; color: #bbdefb;">19 Desember 2025</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta">
        <div class="cta-content">
            <h3>Siap untuk Berkembang Bersama Kami ?</h3>
            <p>Jangan biarkan masalah menghambat potensi terbaik Anda. Mari bicara dan temukan solusinya bersama guru BK profesional kami.</p>
            
            <?php if ($logged_in): ?>
                <?php
                $dashboard_url = '';
                if ($user_role == 'Siswa') {
                    $dashboard_url = 'dashboard_siswa.php';
                } elseif ($user_role == 'Guru_BK') {
                    $dashboard_url = 'dashboard_guru.php';
                } elseif ($user_role == 'Admin') {
                    $dashboard_url = 'admin_dashboard.php';
                } else {
                    $dashboard_url = 'dashboard_siswa.php';
                }
                ?>
                <a href="<?php echo $dashboard_url; ?>" class="btn-primary">
                    <i class='bx bx-rocket'></i>
                    Lanjut ke Dashboard
                </a>
            <?php else: ?>
                <a href="index.php" class="btn-primary">
                    <i class='bx bx-log-in'></i>
                    Mulai Sekarang
                </a>
            <?php endif; ?>
        </div>
    </section>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>Tentang APK BK</h4>
                <p>Aplikasi Bimbingan Konseling SMKN 7 Batam merupakan platform digital untuk memudahkan siswa mendapatkan layanan bimbingan dan konseling secara profesional.</p>
                <div class="social-links">
                    <a href="https://smknegeri7batam.sch.id/"><i class='bx bx-globe'></i></a>
                    <a href="https://www.instagram.com/smkn7batam_official?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw=="><i class='bx bxl-instagram'></i></a>
                    <a href="https://web.facebook.com/groups/smknegeri7batamkota/?locale=id_ID&_rdc=1&_rdr#"><i class='bx bxl-facebook'></i></a>
                    <a href="https://www.youtube.com/@smkn7batam_official"><i class='bx bxl-youtube'></i></a>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>Kontak Kami</h4>
                <ul class="contact-info">
                    <li>
                        <i class='bx bx-map'></i>
                        <span>Perumahan Sekawan, Kelurahan Belian, Kecamatan Batam Kota, Kota Batam.</span>
                    </li>
                    <li>
                        <i class='bx bx-phone'></i>
                        <span> (0778) 4805790</span>
                    </li>
                    <li>
                        <i class='bx bx-envelope'></i>
                        <span>bk@smkn7batam.sch.id</span>
                    </li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Jam Layanan</h4>
                <div class="service-hours">
                    <p>
                        <span class="day">Senin</span>
                        <span class="time">08:00 - 15:00</span>
                    </p>
                    <p>
                        <span class="day">Selasa</span>
                        <span class="time">08:00 - 15:00</span>
                    </p>
                    <p>
                        <span class="day">Rabu</span>
                        <span class="time">08:00 - 15:00</span>
                    </p>
                    <p>
                        <span class="day">Kamis</span>
                        <span class="time">08:00 - 15:00</span>
                    </p>
                    <p>
                        <span class="day">Jumat</span>
                        <span class="time">08:00 - 15:00</span>
                    </p>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>Tim BK SMKN 7 Batam</h4>
                <p>Helmidah, S.Pd</p>
                <p>Putri, S.Pd</p>
                <p>Siti Hariani, S.Pd</p>
                <p>Sylviana Dessy, S.Pd, M.Pd</p>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2026 APK BK SMKN 7 Batam. Semua Hak Dilindungi.</p>
            <p>Dikembangkan oleh Tim IT SMKN 7 Batam | Sistem Bimbingan & Konseling Digital</p>
        </div>
    </footer>

    <script>
        const gallerySlider = document.getElementById('gallerySlider');
        const galleryDots = document.getElementById('galleryDots');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const galleryItems = document.querySelectorAll('.gallery-item');
        const totalItems = galleryItems.length;
        
        let currentIndex = 0;
        const itemsPerView = window.innerWidth < 768 ? 1 : 3;
        const itemWidth = galleryItems[0].offsetWidth + 25; 
        
        function updateGallery() {
            const translateX = -currentIndex * itemWidth;
            gallerySlider.style.transform = `translateX(${translateX}px)`;
            
            document.querySelectorAll('.gallery-dot').forEach((dot, index) => {
                dot.classList.toggle('active', index === currentIndex);
            });
            
            prevBtn.disabled = currentIndex === 0;
            nextBtn.disabled = currentIndex >= totalItems - itemsPerView;
        }
        
        prevBtn.addEventListener('click', () => {
            if (currentIndex > 0) {
                currentIndex--;
                updateGallery();
            }
        });
        
        nextBtn.addEventListener('click', () => {
            if (currentIndex < totalItems - itemsPerView) {
                currentIndex++;
                updateGallery();
            }
        });
        
        document.querySelectorAll('.gallery-dot').forEach(dot => {
            dot.addEventListener('click', () => {
                currentIndex = parseInt(dot.dataset.index);
                updateGallery();
            });
        });
        
        let autoSlideInterval = setInterval(() => {
            if (currentIndex < totalItems - itemsPerView) {
                currentIndex++;
            } else {
                currentIndex = 0;
            }
            updateGallery();
        }, 5000);
        
        gallerySlider.addEventListener('mouseenter', () => {
            clearInterval(autoSlideInterval);
        });
        
        gallerySlider.addEventListener('mouseleave', () => {
            autoSlideInterval = setInterval(() => {
                if (currentIndex < totalItems - itemsPerView) {
                    currentIndex++;
                } else {
                    currentIndex = 0;
                }
                updateGallery();
            }, 5000);
        });
        
        function handleResize() {
            const newItemsPerView = window.innerWidth < 768 ? 1 : 3;
            if (newItemsPerView !== itemsPerView) {
                currentIndex = 0;
                updateGallery();
            }
        }
        
        window.addEventListener('resize', handleResize);
        
        updateGallery();
        
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        const animateElements = document.querySelectorAll('.feature-card, .step, .testimonial-card, .cta-content, .gallery-item');
        animateElements.forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
        
        document.querySelectorAll('.feature-card, .testimonial-card, .gallery-item').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                if (!this.classList.contains('gallery-item')) {
                    this.style.transform = 'translateY(0) scale(1)';
                }
            });
        });
        
        document.querySelectorAll('.rating').forEach(rating => {
            const stars = rating.querySelectorAll('.star');
            stars.forEach((star, index) => {
                star.style.animationDelay = `${index * 0.1}s`;
                star.style.animation = 'starPulse 2s infinite';
            });
        });
        
        const style = document.createElement('style');
        style.textContent = `
            @keyframes starPulse {
                0%, 100% { 
                    transform: scale(1); 
                    opacity: 1; 
                    filter: drop-shadow(0 0 3px rgba(255, 215, 0, 0.5));
                }
                50% { 
                    transform: scale(1.2); 
                    opacity: 0.8; 
                    filter: drop-shadow(0 0 8px rgba(255, 215, 0, 0.8));
                }
            }
            
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>