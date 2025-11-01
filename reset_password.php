<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/header.php';


$errors = [];
$success = false;
$step = $_GET['step'] ?? 'request'; // request, verify, reset

// ຈັດການການ reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 'request') {
        // ຂັ້ນຕອນທີ 1: ກວດສອບຊື່ຜູ້ໃຊ້
        $username = trim($_POST['username'] ?? '');
        
        if (empty($username)) {
            $errors[] = 'ກະລຸນາປ້ອນຊື່ຜູ້ໃຊ້';
        } else {
            // ກວດສອບວ່າຊື່ຜູ້ໃຊ້ມີຢູ່ບໍ່
            $stmt = $pdo->prepare('SELECT id FROM login WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user) {
                // ສ້າງ reset token
                $reset_token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // ບັນທຶກ token ໃນຖານຂໍ້ມູນ
                $update_stmt = $pdo->prepare('UPDATE login SET reset_token = ?, reset_token_expires = ?, reset_token_used = 0 WHERE username = ?');
                $update_stmt->execute([$reset_token, $expires_at, $username]);
                
                // ບັນທຶກການ reset ໃນ log
                $log_stmt = $pdo->prepare('INSERT INTO password_reset_log (username, reset_token, ip_address, user_agent) VALUES (?, ?, ?, ?)');
                $log_stmt->execute([$username, $reset_token, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);
                
                $success = true;
                $_SESSION['reset_username'] = $username;
                $_SESSION['reset_token'] = $reset_token;
            } else {
                $errors[] = 'ຊື່ຜູ້ໃຊ້ບໍ່ພົບໃນລະບົບ';
            }
        }
    } elseif ($step === 'reset') {
        // ຂັ້ນຕອນທີ 3: ປ່ຽນລະຫັດຜ່ານໃໝ່
        $username = $_SESSION['reset_username'] ?? '';
        $token = $_SESSION['reset_token'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($username) || empty($token) || empty($new_password) || empty($confirm_password)) {
            $errors[] = 'ກະລຸນາປ້ອນຂໍ້ມູນໃຫ້ຄົບ';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'ລະຫັດຜ່ານທີ່ຢືນຢັນບໍ່ກົງກັນ';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'ລະຫັດຜ່ານຕ້ອງມີຢ່າງໜ້ອຍ 6 ຕົວອັກສອນ';
        } else {
            // ກວດສອບ token ອີກຄັ້ງ
            $stmt = $pdo->prepare('SELECT reset_token, reset_token_expires, reset_token_used FROM login WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if (!$user || $user['reset_token'] !== $token || $user['reset_token_used'] == 1) {
                $errors[] = 'Token ບໍ່ຖືກຕ້ອງ ຫຼື ໃຊ້ແລ້ວ';
            } elseif (strtotime($user['reset_token_expires']) < time()) {
                $errors[] = 'Token ໝົດອາຍຸແລ້ວ';
            } else {
                // ອັບເດດລະຫັດຜ່ານໃໝ່
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $pdo->prepare('UPDATE login SET password = ?, reset_token = NULL, reset_token_expires = NULL, reset_token_used = 1 WHERE username = ?');
                $update_stmt->execute([$hashed_password, $username]);
                
                // ອັບເດດ log
                $log_stmt = $pdo->prepare('UPDATE password_reset_log SET completed_at = NOW(), status = "completed" WHERE username = ? AND status = "pending"');
                $log_stmt->execute([$username]);
                
                // ລຶບ session
                unset($_SESSION['reset_username']);
                unset($_SESSION['reset_token']);
                
                header('Location: login.php?msg=password_reset_success');
                exit;
            }
        }
    }
}

// ກວດສອບ reset token ຖ້າຢູ່ໃນຂັ້ນຕອນ verify
if ($step === 'verify') {
    $token = $_GET['token'] ?? '';
    $username = $_GET['username'] ?? '';
    
    if (empty($token) || empty($username)) {
        $errors[] = 'ລິ້ງ reset ບໍ່ຖືກຕ້ອງ';
        $step = 'request';
    } else {
        // ກວດສອບ token
        $stmt = $pdo->prepare('SELECT reset_token, reset_token_expires, reset_token_used FROM login WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user || $user['reset_token'] !== $token || $user['reset_token_used'] == 1) {
            $errors[] = 'ລິ້ງ reset ບໍ່ຖືກຕ້ອງ ຫຼື ໃຊ້ແລ້ວ';
            $step = 'request';
        } elseif (strtotime($user['reset_token_expires']) < time()) {
            $errors[] = 'ລິ້ງ reset ໝົດອາຍຸແລ້ວ';
            $step = 'request';
        } else {
            // ບັນທຶກໃນ session ເພື່ອຂັ້ນຕອນຕໍ່ໄປ
            $_SESSION['reset_username'] = $username;
            $_SESSION['reset_token'] = $token;
        }
    }
}
?>

<style>
:root {
    --primary: #6366f1;
    --primary-dark: #4f46e5;
    --primary-light: #818cf8;
    --danger: #ef4444;
    --danger-dark: #dc2626;
    --success: #10b981;
    --warning: #f59e0b;
    --bg-main: #ffffff;
    --bg-card: #ffffff;
    --bg-page: #f3f4f6;
    --text-primary: #111827;
    --text-secondary: #6b7280;
    --border-color: #e5e7eb;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

html.dark-mode {
    --bg-main: #0f172a;
    --bg-card: #1e293b;
    --bg-page: #0f172a;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --border-color: #334155;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
    --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.4), 0 10px 10px -5px rgba(0, 0, 0, 0.3);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    color: var(--text-primary);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    font-family: 'Phetsarath OT', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

     body {
            font-family: 'Noto Sans Lao', sans-serif;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), 
                        url('/Parking%20car/assets/images/bg1.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            margin: 0;
            overflow: hidden;
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
        }

.container {
    max-width: 450px;
    width: 100%;
}

.reset-card {
    background: var(--bg-card);
    padding: 2rem;
    border-radius: 16px;
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--border-color);
    animation: slideUp 0.6s ease-out;
}

.reset-header {
    text-align: center;
    margin-bottom: 2rem;
}

.reset-header .icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    display: block;
}

.reset-header h1 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.reset-header p {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
    font-size: 0.9rem;
}

.form-input {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 2px solid var(--border-color);
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: var(--bg-card);
    color: var(--text-primary);
    font-family: inherit;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    transform: translateY(-2px);
}

.btn {
    width: 100%;
    padding: 0.875rem;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-family: inherit;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
}

.btn-secondary {
    background: var(--bg-card);
    color: var(--text-secondary);
    border: 2px solid var(--border-color);
}

.btn-secondary:hover {
    background: var(--bg-page);
    border-color: var(--primary);
    color: var(--primary);
    transform: translateY(-2px);
}

.alert {
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    border: 1px solid;
}

.alert-error {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border-color: var(--danger);
    color: #991b1b;
}

html.dark-mode .alert-error {
    background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 100%);
    color: #fecaca;
}

.alert-success {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    border-color: var(--success);
    color: #065f46;
}

html.dark-mode .alert-success {
    background: linear-gradient(135deg, #064e3b 0%, #065f46 100%);
    color: #a7f3d0;
}

.step-indicator {
    display: flex;
    justify-content: center;
    margin-bottom: 2rem;
    gap: 1rem;
}

.step {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.9rem;
    border: 2px solid var(--border-color);
    background: var(--bg-card);
    color: var(--text-secondary);
}

.step.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.step.completed {
    background: var(--success);
    color: white;
    border-color: var(--success);
}

.step-line {
    width: 40px;
    height: 2px;
    background: var(--border-color);
    margin-top: 19px;
}

.step-line.completed {
    background: var(--success);
}

.back-link {
    text-align: center;
    margin-top: 1.5rem;
}

.back-link a {
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.3s ease;
}

.back-link a:hover {
    color: var(--primary);
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

.password-strength {
    margin-top: 0.5rem;
    font-size: 0.8rem;
}

.strength-weak { color: var(--danger); }
.strength-medium { color: var(--warning); }
.strength-strong { color: var(--success); }

@media (max-width: 480px) {
    .reset-card {
        padding: 1.5rem;
        margin: 0.5rem;
    }
    
    .reset-header .icon {
        font-size: 2.5rem;
    }
    
    .reset-header h1 {
        font-size: 1.25rem;
    }
}
</style>

<div class="container">
    <div class="reset-card">
        <?php if ($step === 'request'): ?>
            <!-- ຂັ້ນຕອນທີ 1: ປ້ອນຊື່ຜູ້ໃຊ້ -->
            <div class="reset-header">
                <span class="icon">🔐</span>
                <h1>ລືມລະຫັດຜ່ານ</h1>
                <p>ປ້ອນຊື່ຜູ້ໃຊ້ຂອງທ່ານເພື່ອສ້າງລິ້ງ reset</p>
            </div>

            <div class="step-indicator">
                <div class="step active">1</div>
                <div class="step-line"></div>
                <div class="step">2</div>
                <div class="step-line"></div>
                <div class="step">3</div>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <div>⚠️ <?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✅ ສ້າງລິ້ງ reset ສຳເລັດແລ້ວ!<br>
                    ກະລຸນາກົດລິ້ງຂ້າງລຸ່ມເພື່ອຢືນຢັນການ reset ລະຫັດຜ່ານ
                </div>
                <a href="reset_password.php?step=verify&username=<?= urlencode($_SESSION['reset_username']) ?>&token=<?= urlencode($_SESSION['reset_token']) ?>" class="btn btn-primary">
                    🔗 ກົດເພື່ອຢືນຢັນ
                </a>
            <?php else: ?>
                <form method="post">
                    <div class="form-group">
                        <label class="form-label">👤 ຊື່ຜູ້ໃຊ້</label>
                        <input type="text" name="username" class="form-input" required 
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" 
                               placeholder="ປ້ອນຊື່ຜູ້ໃຊ້ຂອງທ່ານ">
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <span>🚀</span>
                        <span>ສ້າງລິ້ງ Reset</span>
                    </button>
                </form>
            <?php endif; ?>

        <?php elseif ($step === 'verify'): ?>
            <!-- ຂັ້ນຕອນທີ 2: ຢືນຢັນ -->
            <div class="reset-header">
                <span class="icon">✅</span>
                <h1>ຢືນຢັນການ Reset</h1>
                <p>ກະລຸນາຢືນຢັນວ່າທ່ານຕ້ອງການ reset ລະຫັດຜ່ານ</p>
            </div>

            <div class="step-indicator">
                <div class="step completed">1</div>
                <div class="step-line completed"></div>
                <div class="step active">2</div>
                <div class="step-line"></div>
                <div class="step">3</div>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <div>⚠️ <?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    ✅ ການຢືນຢັນສຳເລັດແລ້ວ!<br>
                    ຊື່ຜູ້ໃຊ້: <strong><?= htmlspecialchars($_SESSION['reset_username']) ?></strong>
                </div>

                <a href="reset_password.php?step=reset" class="btn btn-primary">
                    <span>🔑</span>
                    <span>ປ້ອນລະຫັດຜ່ານໃໝ່</span>
                </a>
            <?php endif; ?>

        <?php elseif ($step === 'reset'): ?>
            <!-- ຂັ້ນຕອນທີ 3: ປ້ອນລະຫັດຜ່ານໃໝ່ -->
            <div class="reset-header">
                <span class="icon">🔑</span>
                <h1>ລະຫັດຜ່ານໃໝ່</h1>
                <p>ປ້ອນລະຫັດຜ່ານໃໝ່ຂອງທ່ານ</p>
            </div>

            <div class="step-indicator">
                <div class="step completed">1</div>
                <div class="step-line completed"></div>
                <div class="step completed">2</div>
                <div class="step-line completed"></div>
                <div class="step active">3</div>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <div>⚠️ <?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label class="form-label">👤 ຊື່ຜູ້ໃຊ້</label>
                    <input type="text" class="form-input" value="<?= htmlspecialchars($_SESSION['reset_username'] ?? '') ?>" disabled>
                </div>

                <div class="form-group">
                    <label class="form-label">🔒 ລະຫັດຜ່ານໃໝ່</label>
                    <input type="password" name="new_password" class="form-input" required 
                           placeholder="ປ້ອນລະຫັດຜ່ານໃໝ່" minlength="6">
                    <div class="password-strength" id="passwordStrength"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">🔒 ຢືນຢັນລະຫັດຜ່ານ</label>
                    <input type="password" name="confirm_password" class="form-input" required 
                           placeholder="ປ້ອນລະຫັດຜ່ານໃໝ່ອີກຄັ້ງ">
                </div>

                <button type="submit" class="btn btn-primary">
                    <span>💾</span>
                    <span>ບັນທຶກລະຫັດຜ່ານໃໝ່</span>
                </button>
            </form>
        <?php endif; ?>

        <div class="back-link">
            <a href="login.php">← ກັບໄປໜ້າ Login</a>
        </div>
    </div>
</div>

<script>
// ການກວດສອບຄວາມເຂັ້ມຂອງລະຫັດຜ່ານ
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.querySelector('input[name="new_password"]');
    const strengthIndicator = document.getElementById('passwordStrength');
    
    if (passwordInput && strengthIndicator) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = checkPasswordStrength(password);
            
            strengthIndicator.textContent = strength.text;
            strengthIndicator.className = 'password-strength ' + strength.class;
        });
    }
    
    // ການຢືນຢັນລະຫັດຜ່ານ
    const confirmPasswordInput = document.querySelector('input[name="confirm_password"]');
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            const password = passwordInput.value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.style.borderColor = '#ef4444';
            } else {
                this.style.borderColor = '#10b981';
            }
        });
    }
});

function checkPasswordStrength(password) {
    let score = 0;
    let feedback = [];
    
    if (password.length >= 6) score++;
    else feedback.push('ຢ່າງໜ້ອຍ 6 ຕົວອັກສອນ');
    
    if (password.length >= 8) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;
    
    if (score < 2) {
        return { text: 'ອ່ອນ', class: 'strength-weak' };
    } else if (score < 4) {
        return { text: 'ກາງ', class: 'strength-medium' };
    } else {
        return { text: 'ເຂັ້ມ', class: 'strength-strong' };
    }
}
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
