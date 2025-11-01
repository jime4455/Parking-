<?php
// Start session and check authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/header.php';

// Define uploads directory
$uploadsDir = __DIR__ . '/../assets/uploads';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
}

// Create QR code payment settings table if not exists
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS payment_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            qr_code_image VARCHAR(255),
            time_limit INT DEFAULT 30,
            payment_info TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
} catch (PDOException $e) {
    error_log('Error creating payment_settings table: ' . $e->getMessage());
}

// Handle QR code deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_qr_code'])) {
    try {
        // Get current QR code image filename
        $stmt = $pdo->query("SELECT qr_code_image FROM payment_settings LIMIT 1");
        $result = $stmt->fetch();
        
        if ($result && $result['qr_code_image']) {
            // Delete file from uploads directory
            $qrCodePath = $uploadsDir . '/' . $result['qr_code_image'];
            if (file_exists($qrCodePath)) {
                unlink($qrCodePath);
            }
            
            // Update database to remove QR code reference
            $pdo->exec("UPDATE payment_settings SET qr_code_image = NULL");
            
            $_SESSION['success_message'] = 'ລຶບ QR code ສຳເລັດແລ້ວ';
        }
    } catch (PDOException $e) {
        error_log('Error deleting QR code: ' . $e->getMessage());
        $_SESSION['error_message'] = 'ເກີດຂໍ້ຜິດພາດໃນການລຶບ QR code';
    }
    
    header('Location: setting.php');
    exit;
}

// Handle payment settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment_settings'])) {
    $timeLimit = (int)$_POST['time_limit'];
    $paymentInfo = trim($_POST['payment_info']);
    
    // Handle QR code image upload
    if (isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['qr_code'];
        $allowedTypes = ['image/png', 'image/jpeg', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        // Debug information
        error_log('File upload info: ' . print_r($file, true));
        
        if (!in_array($file['type'], $allowedTypes)) {
            $_SESSION['error_message'] = 'ກະລຸນາອັບໂຫຼດໄຟລ໌ຮູບພາບເທົ່ານັ້ນ (PNG, JPG, GIF)';
            error_log('Invalid file type: ' . $file['type']);
        } elseif ($file['size'] > $maxSize) {
            $_SESSION['error_message'] = 'ຂະໜາດໄຟລ໌ຕ້ອງບໍ່ເກີນ 5MB';
            error_log('File too large: ' . $file['size']);
        } else {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'qr_code_' . time() . '.' . $extension;
            $destination = $uploadsDir . '/' . $filename;
            
            // Ensure uploads directory exists and is writable
            if (!file_exists($uploadsDir)) {
                mkdir($uploadsDir, 0777, true);
                error_log('Created uploads directory: ' . $uploadsDir);
            }
            
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                error_log('File moved successfully to: ' . $destination);
                
                // Update or insert payment settings
                try {
                    // Check if record exists
                    $checkStmt = $pdo->query("SELECT id FROM payment_settings LIMIT 1");
                    $exists = $checkStmt->fetch();
                    
                    if ($exists) {
                        $stmt = $pdo->prepare("
                            UPDATE payment_settings 
                            SET qr_code_image = ?, time_limit = ?, payment_info = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$filename, $timeLimit, $paymentInfo, $exists['id']]);
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO payment_settings (qr_code_image, time_limit, payment_info)
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$filename, $timeLimit, $paymentInfo]);
                    }
                    
                    error_log('Database updated successfully with filename: ' . $filename);
                    $_SESSION['success_message'] = 'ອັບເດດການຕັ້ງຄ່າການບັນທຶກຄິວອາໂຄ້ດໃໝ່ແລ້ວ';
                } catch (PDOException $e) {
                    $_SESSION['error_message'] = 'ເກີດຂໍ້ຜິດພາດໃນການບັນທຶກການຕັ້ງຄ່າ';
                    error_log('Error updating payment settings: ' . $e->getMessage());
                    // If database update fails, remove the uploaded file
                    unlink($destination);
                }
            } else {
                $_SESSION['error_message'] = 'ເກີດຂໍ້ຜິດພາດໃນການບັນທຶກຮູບ QR code';
                error_log('Failed to move uploaded file to: ' . $destination);
            }
        }
    } else {
        // Update only time limit and payment info
        try {
            $stmt = $pdo->prepare("
                UPDATE payment_settings 
                SET time_limit = ?, payment_info = ?
                WHERE id = 1
            ");
            $stmt->execute([$timeLimit, $paymentInfo]);
            $_SESSION['success_message'] = 'ອັບເດດການຕັ້ງຄ່າການຊຳລະເງິນສຳເລັດແລ້ວ';
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'ເກີດຂໍ້ຜິດພາດໃນການບັນທຶກການຕັ້ງຄ່າ';
            error_log('Error updating payment settings: ' . $e->getMessage());
        }
    }
    
    header('Location: setting.php');
    exit;
}

// Get current payment settings
try {
    $stmt = $pdo->query("SELECT * FROM payment_settings LIMIT 1");
    $paymentSettings = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($paymentSettings) {
        error_log('Current payment settings: ' . print_r($paymentSettings, true));
    } else {
        error_log('No payment settings found in database');
    }
} catch (PDOException $e) {
    error_log('Error fetching payment settings: ' . $e->getMessage());
    $paymentSettings = null;
}

// Handle favicon upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['favicon'])) {
    $file = $_FILES['favicon'];
    $allowedTypes = ['image/x-icon', 'image/png', 'image/jpeg', 'image/gif'];
    $maxSize = 5024 * 5024; // 2MB -> 5MB

    if (!in_array($file['type'], $allowedTypes)) {
        $_SESSION['error_message'] = 'ກະລຸນາອັບໂຫຼດໄຟລ໌ຮູບພາບເທົ່ານັ້ນ (ICO, PNG, JPG, GIF)';
    } elseif ($file['size'] > $maxSize) {
        $_SESSION['error_message'] = 'ຂະໜາດໄຟລ໌ຕ້ອງບໍ່ເກີນ 5MB';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error_message'] = 'ເກີດຂໍ້ຜິດພາດໃນການອັບໂຫຼດໄຟລ໌';
    } else {
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'favicon.' . $extension;
        $destination = $uploadsDir . '/' . $filename;

        // Remove old favicon if exists
        $oldFavicons = glob($uploadsDir . '/favicon.*');
        foreach ($oldFavicons as $oldFavicon) {
            unlink($oldFavicon);
        }

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $_SESSION['success_message'] = 'ອັບເດຕໄອຄອນເວັບໄຊສຳເລັດແລ້ວ';
        } else {
            $_SESSION['error_message'] = 'ເກີດຂໍ້ຜິດພາດໃນການບັນທຶກໄຟລ໌';
        }
    }
    
    // Redirect to refresh the page
    header('Location: setting.php');
    exit;
}

// Get current favicon if exists
$currentFavicon = null;
$faviconFiles = glob($uploadsDir . '/favicon.*');
if (!empty($faviconFiles)) {
    $currentFavicon = basename($faviconFiles[0]);
}
?>

<style>
.settings-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.settings-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 2rem;
    border-radius: 1rem;
    color: white;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.settings-header h1 {
    margin: 0;
    font-size: 1.8rem;
}

.settings-card {
    background: white;
    border-radius: 1rem;
    padding: 2rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
}

.settings-card h2 {
    margin-top: 0;
    margin-bottom: 1.5rem;
    color: #374151;
    font-size: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #374151;
}

.form-hint {
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.preview-container {
    margin-top: 1rem;
    text-align: center;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 0.5rem;
}

.preview-container img {
    max-width: 64px;
    height: auto;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    border: none;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
}

.alert {
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #10b981;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #ef4444;
}

/* File input styling */
.file-input-container {
    position: relative;
    overflow: hidden;
    display: inline-block;
}

.file-input-container input[type="file"] {
    position: absolute;
    left: 0;
    top: 0;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    height: 100%;
}

.file-input-button {
    background: #f3f4f6;
    border: 2px dashed #d1d5db;
    padding: 1rem;
    border-radius: 0.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.file-input-button:hover {
    background: #e5e7eb;
    border-color: #9ca3af;
}

.file-input-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.current-favicon {
    margin-top: 1rem;
    padding: 1rem;
    background: #f3f4f6;
    border-radius: 0.5rem;
    text-align: center;
}

.current-favicon img {
    max-width: 32px;
    height: auto;
    margin-bottom: 0.5rem;
}

.current-qr-code {
    margin-top: 1rem;
    padding: 1rem;
    background: #f3f4f6;
    border-radius: 0.5rem;
    text-align: center;
}

.current-qr-code img {
    max-width: 200px;
    height: auto;
    margin-bottom: 0.5rem;
}

.form-input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

textarea.form-input {
    resize: vertical;
    min-height: 100px;
}

.qr-code-display {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.qr-code-display.active {
    opacity: 1;
    visibility: visible;
}

.qr-code-modal {
    background: white;
    padding: 2rem;
    border-radius: 1rem;
    max-width: 90%;
    width: 400px;
    text-align: center;
    position: relative;
    transform: scale(0.9);
    transition: all 0.3s ease;
}

.qr-code-display.active .qr-code-modal {
    transform: scale(1);
}

.qr-code-modal img {
    max-width: 100%;
    height: auto;
    margin-bottom: 1rem;
}

.qr-code-modal .timer {
    font-size: 1.5rem;
    font-weight: bold;
    color: #374151;
    margin-bottom: 1rem;
}

.qr-code-modal .close-btn {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6b7280;
    transition: color 0.3s ease;
}

.qr-code-modal .close-btn:hover {
    color: #374151;
}

.qr-code-modal .payment-info {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
    color: #6b7280;
    font-size: 0.9rem;
    text-align: left;
}

@media (max-width: 640px) {
    .settings-header {
        padding: 1.5rem;
    }

    .settings-header h1 {
        font-size: 1.5rem;
    }

    .settings-card {
        padding: 1.5rem;
    }
}
</style>

<div class="settings-container">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="settings-header">
        <h1>⚙️ ຕັ້ງຄ່າເວັບໄຊ</h1>
    </div>

    <div class="settings-card">
        <h2>💳 ຕັ້ງຄ່າການຊຳລະເງິນ QR Code</h2>
        
        <form action="setting.php" method="POST" enctype="multipart/form-data" id="paymentForm">
            <input type="hidden" name="update_payment_settings" value="1">
            
            <div class="form-group">
                <label class="form-label">QR Code ສຳລັບການຊຳລະເງິນ</label>
                
                <?php if ($paymentSettings && $paymentSettings['qr_code_image']): ?>
                <div class="current-qr-code">
                    <img src="../assets/uploads/<?= htmlspecialchars($paymentSettings['qr_code_image']) ?>" alt="Payment QR Code">
                    <div>QR Code ປັດຈຸບັນ</div>
                    <form method="POST" style="margin-top: 10px;">
                        <input type="hidden" name="delete_qr_code" value="1">
                        <button type="submit" class="btn-primary" style="background: #ef4444;" onclick="return confirm('ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການລຶບ QR code ນີ້?');">
                            🗑️ ລຶບ QR Code
                        </button>
                    </form>
                </div>
                <?php endif; ?>

                <div class="file-input-container">
                    <div class="file-input-button">
                        <div class="file-input-icon">📱</div>
                        <div>ກົດເພື່ອເລືອກຮູບ QR Code</div>
                        <input type="file" name="qr_code" accept=".png,.jpg,.jpeg,.gif">
                    </div>
                </div>
                
                <div class="form-hint">
                    ຮອງຮັບໄຟລ໌: PNG, JPG, GIF (ຂະໜາດສູງສຸດ 5MB)
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">ເວລາໝົດກຳນົດການຊຳລະເງິນ (ນາທີ)</label>
                <input type="number" name="time_limit" min="1" max="180" 
                       value="<?= $paymentSettings ? htmlspecialchars($paymentSettings['time_limit']) : 30 ?>" 
                       class="form-input" required>
                <div class="form-hint">
                    ກຳນົດເວລາສຳລັບການຊຳລະເງິນ (1-180 ນາທີ)
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">ຂໍ້ມູນການຊຳລະເງິນເພີ່ມເຕີມ</label>
                <textarea name="payment_info" class="form-input" rows="4"><?= $paymentSettings ? htmlspecialchars($paymentSettings['payment_info']) : '' ?></textarea>
                <div class="form-hint">
                    ເພີ່ມຂໍ້ມູນການຊຳລະເງິນ ເຊັ່ນ: ເລກບັນຊີ, ຊື່ບັນຊີ, ຂໍ້ແນະນຳອື່ນໆ
                </div>
            </div>

            <button type="submit" class="btn-primary">💾 ບັນທຶກການຕັ້ງຄ່າການຊຳລະເງິນ</button>
        </form>
    </div>

    <div class="settings-card">
        <h2>🎨 ການຕັ້ງຄ່າໜ້າຕາເວັບໄຊ</h2>
        
        <form action="setting.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label">ໄອຄອນເວັບໄຊ (Favicon)</label>
                
                <?php if ($currentFavicon): ?>
                <div class="current-favicon">
                    <img src="../assets/uploads/<?= htmlspecialchars($currentFavicon) ?>" alt="Current Favicon">
                    <div>ໄອຄອນປັດຈຸບັນ</div>
                </div>
                <?php endif; ?>

                <div class="file-input-container">
                    <div class="file-input-button">
                        <div class="file-input-icon">📁</div>
                        <div>ກົດເພື່ອເລືອກໄຟລ໌</div>
                        <input type="file" name="favicon" accept=".ico,.png,.jpg,.jpeg,.gif" required>
                    </div>
                </div>
                
                <div class="form-hint">
                    ຮອງຮັບໄຟລ໌: ICO, PNG, JPG, GIF (ຂະໜາດສູງສຸດ 5MB)
                </div>
            </div>

            <button type="submit" class="btn-primary">💾 ບັນທຶກການຕັ້ງຄ່າ</button>
        </form>
    </div>

    <button onclick="showQRCode()" class="btn-primary">
        📱 ສະແດງ QR Code ຊຳລະເງິນ
    </button>
</div>

<script>
// Custom styles for SweetAlert QR preview
const customSwalStyles = `
    <style>
        .swal2-popup {
            font-family: 'Phetsarath OT', sans-serif !important;
        }
        .swal2-title {
            font-family: 'Phetsarath OT', sans-serif !important;
            color: #1f2937;
        }
        .preview-timer {
            background: #f3f4f6;
            padding: 10px;
            border-radius: 8px;
            margin-top: 15px;
        }
        .qr-preview-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Phetsarath OT', sans-serif;
        }
        .qr-preview-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
        }
        .qr-preview-timer {
            font-size: 1.5rem;
            font-weight: bold;
            color: #1f2937;
            margin-top: 15px;
            padding: 15px;
            background: #f3f4f6;
            border-radius: 8px;
            display: none;
        }
    </style>
`;

// Preview uploaded favicon
document.querySelector('input[name="favicon"]').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const currentFavicon = document.querySelector('.current-favicon');
            if (currentFavicon) {
                currentFavicon.querySelector('img').src = e.target.result;
            } else {
                const previewDiv = document.createElement('div');
                previewDiv.className = 'current-favicon';
                previewDiv.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    <div>ຕົວຢ່າງໄອຄອນໃໝ່</div>
                `;
                document.querySelector('input[name="favicon"]').closest('.file-input-container').after(previewDiv);
            }
        }
        reader.readAsDataURL(file);
    }
});

// Preview uploaded QR code with popup
document.querySelector('input[name="qr_code"]').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // Update preview in form
            const currentQRCode = document.querySelector('.current-qr-code');
            const qrDisplayImage = document.querySelector('#qrCodeDisplay .qr-code-modal img');
            const previewSrc = e.target.result;

            // Update main preview
            if (currentQRCode) {
                currentQRCode.querySelector('img').src = previewSrc;
            } else {
                const previewDiv = document.createElement('div');
                previewDiv.className = 'current-qr-code';
                previewDiv.innerHTML = `
                    <img src="${previewSrc}" alt="QR Code Preview">
                    <div>ຕົວຢ່າງ QR Code ໃໝ່</div>
                `;
                document.querySelector('input[name="qr_code"]').closest('.form-group').insertBefore(
                    previewDiv, 
                    document.querySelector('.file-input-container')
                );
            }

            // Update QR display modal image if it exists
            if (qrDisplayImage) {
                qrDisplayImage.src = previewSrc;
            }

            // Show QR Code preview in SweetAlert2
            Swal.fire({
                title: 'ຕົວຢ່າງ QR Code',
                html: `
                    ${customSwalStyles}
                    <div style="margin-bottom: 20px;">
                        <img src="${previewSrc}" alt="QR Code Preview" style="max-width: 300px; width: 100%;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <button onclick="startQRPreviewTimer()" class="qr-preview-btn">
                            ⏱️ ທົດລອງນັບເວລາ
                        </button>
                    </div>
                    <div class="qr-preview-timer" id="previewTimer">
                        ເວລາທີ່ເຫຼືອ: <span>00:00</span>
                    </div>
                `,
                width: 400,
                showConfirmButton: true,
                confirmButtonText: 'ປິດ',
                showCancelButton: true,
                cancelButtonText: 'ລົບ QR Code',
                confirmButtonColor: '#667eea',
                cancelButtonColor: '#ef4444',
                allowOutsideClick: false
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    // Clear the file input and remove preview
                    e.target.value = '';
                    if (currentQRCode) {
                        currentQRCode.remove();
                    }
                }
                // Clear any running timer
                if (previewTimerInterval) {
                    clearInterval(previewTimerInterval);
                }
            });
        }
        reader.readAsDataURL(file);
    }
});

// Timer functionality for QR preview
function startQRPreviewTimer() {
    const timeLimit = parseInt(document.querySelector('input[name="time_limit"]').value) || 30;
    clearInterval(previewTimerInterval);
    
    const timerContainer = document.getElementById('previewTimer');
    const timerDisplay = timerContainer.querySelector('span');
    timerContainer.style.display = 'block';
    
    let timer = timeLimit * 60;

    function updatePreviewTimer() {
        const minutes = Math.floor(timer / 60);
        const seconds = timer % 60;
        timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        if (--timer < 0) {
            clearInterval(previewTimerInterval);
            Swal.close();
        }
    }

    updatePreviewTimer();
    previewTimerInterval = setInterval(updatePreviewTimer, 1000);
}
</script>

<!-- QR Code Display Modal -->
<div class="qr-code-display" id="qrCodeDisplay">
    <div class="qr-code-modal">
        <button class="close-btn" onclick="hideQRCode()">×</button>
        <h3>QR Code ສຳລັບການຊຳລະເງິນ</h3>
        <?php if ($paymentSettings && $paymentSettings['qr_code_image']): ?>
            <img src="../assets/uploads/<?= htmlspecialchars($paymentSettings['qr_code_image']) ?>" alt="Payment QR Code">
        <?php endif; ?>
        <div class="timer" id="paymentTimer">ເວລາທີ່ເຫຼືອ: <span>00:00</span></div>
        <?php if ($paymentSettings && $paymentSettings['payment_info']): ?>
            <div class="payment-info">
                <?= nl2br(htmlspecialchars($paymentSettings['payment_info'])) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
let timerInterval;

function showQRCode() {
    const display = document.getElementById('qrCodeDisplay');
    const timeLimit = <?= $paymentSettings ? $paymentSettings['time_limit'] : 30 ?>;
    display.classList.add('active');
    startTimer(timeLimit * 60); // Convert minutes to seconds
}

function hideQRCode() {
    const display = document.getElementById('qrCodeDisplay');
    display.classList.remove('active');
    clearInterval(timerInterval);
}

function startTimer(duration) {
    clearInterval(timerInterval);
    const timerDisplay = document.querySelector('#paymentTimer span');
    let timer = duration;

    function updateDisplay() {
        const minutes = Math.floor(timer / 60);
        const seconds = timer % 60;
        timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        if (--timer < 0) {
            clearInterval(timerInterval);
            hideQRCode();
        }
    }

    updateDisplay();
    timerInterval = setInterval(updateDisplay, 1000);
}

// Close QR code display when clicking outside the modal
document.getElementById('qrCodeDisplay').addEventListener('click', function(e) {
    if (e.target === this) {
        hideQRCode();
    }
});

// Use the existing customSwalStyles from above

let previewTimerInterval; // Global variable for preview timer

// Function to start timer in preview
// Function to start timer in preview - shares the same timer variable
function startQRPreviewTimer() {
    clearInterval(previewTimerInterval);
    
    const timerContainer = document.getElementById('previewTimer');
    const timerDisplay = timerContainer.querySelector('span');
    timerContainer.style.display = 'block';
    
    let timer = timeLimit * 60;

    function updatePreviewTimer() {
        const minutes = Math.floor(timer / 60);
        const seconds = timer % 60;
        timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        if (--timer < 0) {
            clearInterval(previewTimerInterval);
            Swal.close();
        }
    }

    updatePreviewTimer();
    previewTimerInterval = setInterval(updatePreviewTimer, 1000);
}
</script>
