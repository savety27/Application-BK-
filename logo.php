<?php
header("refresh:10;url=halaman utama.php");

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loading - BK SKAJU</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #ffffff;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .logo-container {
            text-align: center;
            animation: fadeInScale 1.5s ease-out forwards,
                     gentleFloat 3s ease-in-out infinite;
        }

        .logo {
            max-width: 1000px;
            width: 80%;
            height: auto;
            opacity: 0;
            animation: logoAppear 1.2s ease-out 0.3s forwards;
        }

        .loading-text {
            margin-top: 30px;
            color: #333;
            font-size: 30px;
            font-weight: 500;
            opacity: 0;
            animation: textAppear 1s ease-out 1.5s forwards;
        }

        .redirect-text {
            margin-top: 20px;
            color: #666;
            font-size: 25px;
            opacity: 0;
            animation: textAppear 1s ease-out 2s forwards;
        }

        @keyframes fadeInScale {
            0% {
                transform: scale(0.8);
                opacity: 0;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes logoAppear {
            0% {
                transform: translateY(30px);
                opacity: 0;
            }
            100% {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes textAppear {
            0% {
                transform: translateY(10px);
                opacity: 0;
            }
            100% {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes gentleFloat {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        @keyframes fadeOut {
            0% {
                opacity: 1;
                transform: scale(1);
            }
            100% {
                opacity: 0;
                transform: scale(0.9);
            }
        }

        .fade-out {
            animation: fadeOut 1s ease-in-out forwards;
        }
    </style>
</head>
<body>
    <div class="logo-container" id="logoContainer">
        <img src="uploads/BK.png" alt="Logo BK SKAJU" class="logo">
        
        <div class="loading-text">
            Memuat Aplikasi BK
        </div>
        
        <div class="redirect-text">
            Mengarahkan ke halaman utama dalam <span id="countdown">10</span> detik...
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const logoContainer = document.getElementById('logoContainer');
            const countdownElement = document.getElementById('countdown');
            
            let seconds = 10;
            const countdownInterval = setInterval(() => {
                seconds--;
                countdownElement.textContent = seconds;
                
                if (seconds <= 0) {
                    clearInterval(countdownInterval);
                }
            }, 1000);
            
            setTimeout(() => {
                logoContainer.classList.add('fade-out');
            }, 9500);
            
            document.body.addEventListener('click', function() {
                clearInterval(countdownInterval);
                logoContainer.classList.add('fade-out');
                setTimeout(() => {
                    window.location.href = 'halaman utama.php';
                }, 800);
            });
            
            document.addEventListener('keydown', function() {
                clearInterval(countdownInterval);
                logoContainer.classList.add('fade-out');
                setTimeout(() => {
                    window.location.href = 'halaman utama.php';
                }, 800);
            });
        });
    </script>
</body>
</html>