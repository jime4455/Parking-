<?php
// start session before any output
if (session_status() === PHP_SESSION_NONE) session_start();
?>

<?php
require __DIR__ . '/../includes/db.php';
// header may output HTML — session already started
require __DIR__ . '/../includes/header.php';
// if already logged in, redirect to home

$error = '';
// show message (e.g. from logout)
$infoMsg = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM login WHERE username = ? LIMIT 1');
    $stmt->execute([$u]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($p, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = $user['username'];
        header('Location: /Parking%20car/index.php');
        exit;
    } else {
        $error = 'ຊື່ຜູ້ໃຊ້ ຫຼື ລະຫັດຜ່ານບໍ່ຖືກ';
    }
}
?>
<!doctype html>
<html lang="lo">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ເຂົ້າສູ່ລະບົບ - Parking Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
    body {
          font-family: 'Noto Sans Lao', sans-serif;
          background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('/Parking%20car/assets/images/bg1.jpg') no-repeat center center;
          background-size: cover;
          background-attachment: fixed;
          min-height: 100vh;

         /* ໃຫ້ຢູ່ເຄິງກາງແທ້ໆ */
          display: flex;
          align-items: center;   /* ແນວຕັ້ງ */
          justify-content: center; /* ແນວນອນ */
          padding: 1; /* ຕັດຄ່າ padding ອອກເພາະມັນດັນຂຶ້ນເທິງ */
        }

/* ສຳລັບໂທລະສັບ - ໃຊ້ scroll ແທນ fixed */
    @media (max-width: 768px) {
    body {
        background-attachment: scroll;
        min-height: 100vh;
        height: auto;
        padding: 60px 20px 40px;
        }
}
        
    .login-container {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        padding: 35px 35px;
        width: 100%;
        max-width: 390px;
        animation: slideUp 0.5s ease-out;

    /* ປ້ອງກັນບໍ່ໃຫ້ຊິດຂຶ້ນເທິງ */
        margin: 0 auto;
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
        
        .login-header {
            text-align: center;
            margin-bottom: 36px;
        }
        
        .login-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .login-icon svg {
            width: 40px;
            height: 40px;
            fill: white;
        }
        
        h2 {
            color: #1a202c;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .subtitle {
            color: #718096;
            font-size: 14px;
            font-weight: 400;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        label {
            display: block;
            color: #4a5568;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            width: 20px;
            height: 20px;
            pointer-events: none;
        }
        
        input.form-control {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            font-family: 'Noto Sans Lao', sans-serif;
            transition: all 0.3s ease;
            background: #f7fafc;
        }
        
        input.form-control:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        input.form-control::placeholder {
            color: #cbd5e0;
        }
        
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            font-family: 'Noto Sans Lao', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .forgot-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .forgot-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .forgot-link a:hover {
            color: #764ba2;
        }
        
        /* Tablet */
        @media (max-width: 768px) {
            .login-container {
                padding: 40px 32px;
                border-radius: 20px;
            }
            
            .login-icon {
                width: 70px;
                height: 70px;
            }
            
            .login-icon svg {
                width: 35px;
                height: 35px;
            }
            
            h2 {
                font-size: 24px;
            }
        }
        
        /* Mobile */
        @media (max-width: 480px) {
            body {
                padding: 40px 16px;
            }
            
            .login-container {
                padding: 32px 24px;
                border-radius: 16px;
                max-width: 100%;
            }
            
            .login-icon {
                width: 60px;
                height: 60px;
                border-radius: 16px;
                margin-bottom: 16px;
            }
            
            .login-icon svg {
                width: 30px;
                height: 30px;
            }
            
            h2 {
                font-size: 22px;
            }
            
            .subtitle {
                font-size: 13px;
            }
            
            .login-header {
                margin-bottom: 28px;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            label {
                font-size: 13px;
            }
            
            input.form-control {
                padding: 12px 16px 12px 44px;
                font-size: 14px;
            }
            
            .btn-login {
                padding: 14px;
                font-size: 15px;
            }
            
            .forgot-link {
                margin-top: 16px;
            }
            
            .forgot-link a {
                font-size: 13px;
            }
        }
        
        /* Small Mobile */
        @media (max-width: 360px) {
            .login-container {
                padding: 24px 20px;
            }
            
            h2 {
                font-size: 20px;
            }
            
            .login-icon {
                width: 56px;
                height: 56px;
            }
            
            input.form-control {
                padding: 11px 14px 11px 42px;
                font-size: 13px;
            }
            
            .btn-login {
                padding: 12px;
                font-size: 14px;
            }
        }
        
        /* Landscape Mobile */
        @media (max-height: 500px) and (orientation: landscape) {
            body {
                padding: 30px 10px;
                align-items: flex-start;
            }
            
            .login-container {
                padding: 20px 32px;
                margin: auto;
            }
            
            .login-icon {
                width: 50px;
                height: 50px;
                margin-bottom: 12px;
            }
            
            .login-icon svg {
                width: 25px;
                height: 25px;
            }
            
            .login-header {
                margin-bottom: 20px;
            }
            
            h2 {
                font-size: 20px;
                margin-bottom: 6px;
            }
            
            .subtitle {
                font-size: 12px;
            }
            
            .form-group {
                margin-bottom: 16px;
            }
            
            input.form-control,
            .btn-login {
                padding: 10px 14px;
                font-size: 13px;
            }
            
            input.form-control {
                padding-left: 42px;
            }
            
            .forgot-link {
                margin-top: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                </svg>
            </div>
            <h2>ເຂົ້າສູ່ລະບົບ</h2>
            <p class="subtitle">ລະບົບຈັດການຝາກລົດ</p>
        </div>

        <form method="post" novalidate>
            <div class="form-group">
                <label for="username">ຊື່ຜູ້ໃຊ້</label>
                <div class="input-wrapper">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-control" 
                           placeholder="ປ້ອນຊື່ຜູ້ໃຊ້" 
                           required 
                           autofocus>
                </div>
            </div>

            <div class="form-group">
                <label for="password">ລະຫັດຜ່ານ</label>
                <div class="input-wrapper">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                    </svg>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           placeholder="ປ້ອນລະຫັດຜ່ານ" 
                           required>
                </div>
            </div>

            <button type="submit" class="btn-login">ເຂົ້າສູ່ລະບົບ</button>
        </form>
        
        <div class="forgot-link">
            <a href="reset_password.php">🔐 ລືມລະຫັດຜ່ານ?</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        (function(){
            var error = <?= json_encode($error) ?>;
            var info = <?= json_encode($infoMsg) ?>;
            
            if (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'ຜິດພາດ',
                    text: error,
                    confirmButtonColor: '#667eea',
                    customClass: {
                        popup: 'border-radius-24'
                    }
                });
                document.getElementById('plainError').style.display = 'none';
            } else if (info === 'loggedout') {
                Swal.fire({
                    icon: 'success',
                    title: 'ອອກຈາກລະບົບສຳເລັດ',
                    text: 'ທ່ານໄດ້ອອກຈາກລະບົບແລ້ວ',
                    timer: 2000,
                    showConfirmButton: false,
                    customClass: {
                        popup: 'border-radius-24'
                    }
                });
            } else if (info === 'password_reset_success') {
                Swal.fire({
                    icon: 'success',
                    title: 'ປ່ຽນລະຫັດຜ່ານສຳເລັດ',
                    text: 'ລະຫັດຜ່ານຂອງທ່ານໄດ້ຖືກປ່ຽນແລ້ວ ກະລຸນາ login ໃໝ່',
                    confirmButtonText: 'ເຂົ້າສູ່ລະບົບ',
                    confirmButtonColor: '#667eea',
                    customClass: {
                        popup: 'border-radius-24'
                    }
                });
            }
        })();
    </script>
</body>
</html>