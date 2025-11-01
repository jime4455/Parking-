<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/auth.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = bin2hex(random_bytes(16));
    $plate = trim($_POST['plate']);
    $owner = trim($_POST['owner_name']);
    $type_id = $_POST['type_id'];
    $phone = trim($_POST['phone']);
    $price = $_POST['price'] ?? 0;
    $note = trim($_POST['note'] ?? '');

    $plate_numbers_only = preg_replace('/[^0-9]/', '', $plate);
    $check = $pdo->prepare("SELECT id FROM vehicles WHERE REGEXP_REPLACE(plate, '[^0-9]', '') = ?");
    $check->execute([$plate_numbers_only]);
    
    if ($check->fetch()) {
        $error = 'duplicate';
    } else {
        // ✅ ref_code ບໍ່ຕ້ອງ insert, Trigger ຈັດການໃຫ້
        $stmt = $pdo->prepare("
            INSERT INTO vehicles (id, plate, owner_name, type_id, phone, price, note, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$id, $plate, $owner, $type_id, $phone, $price, $note]);
        $success = true;
    }
}


// Load vehicle types
$types = $pdo->query("SELECT id, code, name FROM vehicle_types ORDER BY name")->fetchAll();
?>

<style>
:root {
    --primary: #6366f1;
    --primary-dark: #4f46e5;
    --danger: #ef4444;
    --success: #10b981;
    --warning: #f59e0b;
    --bg-card: #ffffff;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

* {
    box-sizing: border-box;
}

.form-container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2.5rem;
    border-radius: 20px;
    margin-bottom: 2.5rem;
    box-shadow: var(--shadow-lg);
    animation: slideDown 0.6s ease-out;
    position: relative;
    overflow: hidden;
}

.page-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    animation: float 6s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(10deg); }
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    z-index: 1;
}

.header-title {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 800;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.header-subtitle {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.btn-back {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.btn-back:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateX(-5px);
    color: white;
}

.error-alert {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border-left: 5px solid var(--danger);
    padding: 1.5rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: var(--shadow);
    animation: shake 0.5s ease-out, slideDown 0.6s ease-out;
}

.error-icon {
    font-size: 2.5rem;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

.error-content strong {
    color: #991b1b;
    font-size: 1.1rem;
    display: block;
    margin-bottom: 0.25rem;
}

.error-content p {
    color: #dc2626;
    margin: 0;
}

.form-card {
    background: var(--bg-card);
    border-radius: 20px;
    box-shadow: var(--shadow-lg);
    padding: 2.5rem;
    margin-bottom: 2rem;
    animation: fadeInUp 0.6s ease-out both;
    border: 1px solid rgba(99, 102, 241, 0.1);
}

.form-card:nth-child(2) { animation-delay: 0.1s; }
.form-card:nth-child(3) { animation-delay: 0.2s; }
.form-card:nth-child(4) { animation-delay: 0.3s; }

.card-title {
    color: #667eea;
    font-weight: 700;
    font-size: 1.5rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    position: relative;
    padding-bottom: 1rem;
}

.card-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 60px;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
    border-radius: 2px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group {
    animation: slideIn 0.5s ease-out both;
}

.form-group:nth-child(1) { animation-delay: 0.1s; }
.form-group:nth-child(2) { animation-delay: 0.15s; }
.form-group:nth-child(3) { animation-delay: 0.2s; }
.form-group:nth-child(4) { animation-delay: 0.25s; }

.form-label {
    display: block;
    font-weight: 600;
    color: var(--text-primary);
    transition: color 0.3s ease;
    margin-bottom: 0.75rem;
    font-size: 0.95rem;
}

.required-mark {
    color: var(--danger);
    margin-left: 0.25rem;
}

.input-wrapper {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1.3rem;
    z-index: 1;
    transition: all 0.3s ease;
}

.form-input {
    width: 100%;
    padding: 1rem 1rem 1rem 3.5rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: #ffffff;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    transform: translateY(-2px);
}

.form-input:focus + .input-icon {
    color: var(--primary);
    transform: translateY(-50%) scale(1.1);
}

.form-input.invalid {
    border-color: var(--danger);
    animation: shake 0.5s ease-out;
}

.form-textarea {
    width: 100%;
    padding: 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    resize: vertical;
    min-height: 120px;
    font-family: inherit;
}

.form-textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.vehicle-types-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    margin-top: 1rem;
}

.vehicle-type-card {
    position: relative;
    border: 3px solid #e5e7eb;
    border-radius: 16px;
    padding: 2rem 1rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    background: white;
    overflow: hidden;
}

.vehicle-type-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    opacity: 0;
    transition: opacity 0.4s ease;
}

.vehicle-type-card:hover {
    transform: translateY(-8px) scale(1.03);
    box-shadow: 0 12px 24px rgba(99, 102, 241, 0.2);
    border-color: #667eea;
}

.vehicle-type-radio {
    position: absolute;
    opacity: 0;
}

.vehicle-type-radio:checked + .vehicle-type-content::before {
    opacity: 1;
}

.vehicle-type-radio:checked + .vehicle-type-content {
    color: white;
}

.vehicle-type-radio:checked ~ .check-badge {
    opacity: 1;
    transform: scale(1) rotate(0deg);
}

.vehicle-type-content {
    position: relative;
    z-index: 1;
    transition: all 0.3s ease;
}

.vehicle-type-content::before {
    content: '';
    position: absolute;
    inset: -2rem -1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    opacity: 0;
    transition: opacity 0.4s ease;
    border-radius: 16px;
    z-index: -1;
}

.vehicle-icon {
    font-size: 4rem;
    display: block;
    margin-bottom: 1rem;
    transition: transform 0.3s ease;
}

.vehicle-type-card:hover .vehicle-icon {
    transform: scale(1.1) rotate(5deg);
}

.vehicle-name {
    font-weight: 700;
    font-size: 1.3rem;
    margin-bottom: 0.25rem;
}

.vehicle-name-en {
    font-size: 0.85rem;
    opacity: 0.7;
}

.check-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: var(--success);
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    opacity: 0;
    transform: scale(0) rotate(-180deg);
    transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    z-index: 2;
}

.button-group {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 2rem;
    animation: slideIn 0.5s ease-out 0.4s both;
}

.btn {
    padding: 1rem 2.5rem;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
}

.btn-secondary {
    background: #f3f4f6;
    color: #6b7280;
}

.btn-secondary:hover {
    background: #e5e7eb;
    transform: translateY(-2px);
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
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

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
    20%, 40%, 60%, 80% { transform: translateX(5px); }
}

@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .header-title {
        font-size: 1.8rem;
    }
    
    .form-card {
        padding: 1.5rem;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .vehicle-types-grid {
        grid-template-columns: 1fr;
    }
    
    .button-group {
        flex-direction: column-reverse;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="form-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <div>
                <h1 class="header-title">🚗 ເພີ່ມຂໍ້ມູນລົດໃໝ່</h1>
                <p class="header-subtitle">ກະລຸນາປ້ອນຂໍ້ມູນລົດທີ່ຈອດໃໝ່ເຂົ້າລະບົບ</p>
            </div>
            <a href="/Parking car/pages/view_vehicle.php" class="btn-back">
                ← ກັບຄືນ
            </a>
        </div>
    </div>

    <?php if ($error === 'duplicate'): ?>
    <div class="error-alert">
        <span class="error-icon">⚠️</span>
        <div class="error-content">
            <strong>ທະບຽນຊ້ຳ!</strong>
            <p>ທະບຽນລົດນີ້ມີໃນລະບົບແລ້ວ. ກະລຸນາກວດສອບອີກຄັ້ງ</p>
        </div>
    </div>
    <?php endif; ?>

    <form method="post" id="vehicleForm">
        <!-- Vehicle Information Card -->
        <div class="form-card">
            <h5 class="card-title">
                <span>📋</span>
                ຂໍ້ມູນພື້ນຖານ
            </h5>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        ທະບຽນລົດ<span class="required-mark">*</span>
                    </label>
                    <div class="input-wrapper">
                        <input 
                            type="text" 
                            name="plate" 
                            class="form-input <?= $error === 'duplicate' ? 'invalid' : '' ?>" 
                            placeholder="ກ 1234"
                            required 
                            value="<?= htmlspecialchars($_POST['plate'] ?? '') ?>"
                            autofocus>
                        <span class="input-icon">🚗</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        ຊື່ເຈົ້າຂອງລົດ<span class="required-mark">*</span>
                    </label>
                    <div class="input-wrapper">
                        <input 
                            type="text" 
                            name="owner_name" 
                            class="form-input" 
                            placeholder="ປ້ອນຊື່..."
                            required 
                            value="<?= htmlspecialchars($_POST['owner_name'] ?? '') ?>">
                        <span class="input-icon">👤</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        ເບີໂທລະສັບ
                    </label>
                    <div class="input-wrapper">
                        <input 
                            type="tel" 
                            name="phone" 
                            class="form-input" 
                            placeholder="020 XXXX XXXX"
                            value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        <span class="input-icon">📞</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        ລາຄາຈອດລົດ (ກີບ)<span class="required-mark">*</span>
                    </label>
                    <div class="input-wrapper">
                        <input 
                            type="number" 
                            name="price" 
                            step="0.01" 
                            class="form-input" 
                            placeholder="0.00"
                            required
                            value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
                        <span class="input-icon">💰</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vehicle Type Selection Card -->
        <div class="form-card">
            <h5 class="card-title">
                <span>🏷️</span>
                ເລືອກປະເພດລົດ<span class="required-mark">*</span>
            </h5>
            
            <div class="vehicle-types-grid">
                <label class="vehicle-type-card">
                    <input type="radio" name="type_id" value="1" class="vehicle-type-radio" required <?= (($_POST['type_id'] ?? '') == 1) ? 'checked' : '' ?>>
                    <div class="vehicle-type-content">
                        <span class="vehicle-icon">🚲</span>
                        <div class="vehicle-name">ລົດຖີບ</div>
                        <div class="vehicle-name-en">Bicycle</div>
                    </div>
                    <span class="check-badge">✓</span>
                </label>

                <label class="vehicle-type-card">
                    <input type="radio" name="type_id" value="2" class="vehicle-type-radio" required <?= (($_POST['type_id'] ?? '') == 2) ? 'checked' : '' ?>>
                    <div class="vehicle-type-content">
                        <span class="vehicle-icon">🏍️</span>
                        <div class="vehicle-name">ລົດຈັກ</div>
                        <div class="vehicle-name-en">Motorcycle</div>
                    </div>
                    <span class="check-badge">✓</span>
                </label>

                <label class="vehicle-type-card">
                    <input type="radio" name="type_id" value="3" class="vehicle-type-radio" required <?= (($_POST['type_id'] ?? '') == 3) ? 'checked' : '' ?>>
                    <div class="vehicle-type-content">
                        <span class="vehicle-icon">🚗</span>
                        <div class="vehicle-name">ລົດໃຫຍ່</div>
                        <div class="vehicle-name-en">Car</div>
                    </div>
                    <span class="check-badge">✓</span>
                </label>
            </div>
        </div>

        <!-- Additional Notes Card -->
        <div class="form-card">
            <h5 class="card-title">
                <span>✏️</span>
                ລາຍລະອຽດເພີ່ມເຕີມ
            </h5>
            
            <div class="form-group">
                <label class="form-label">ໝາຍເຫດ</label>
                <textarea 
                    name="note" 
                    class="form-textarea" 
                    placeholder="ລາຍລະອຽດເພີ່ມເຕີມ (ຖ້າມີ)..."><?= htmlspecialchars($_POST['note'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="button-group">
            <a href="/Parking car/pages/view_vehicle.php" class="btn btn-secondary">
                <span>✕</span>
                ຍົກເລີກ
            </a>
            <button type="submit" class="btn btn-primary">
                <span>💾</span>
                ບັນທຶກຂໍ້ມູນ
            </button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const form = document.getElementById('vehicleForm');
    const phoneInput = document.querySelector('input[name="phone"]');
    
    // Auto-format phone number
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 3 && value.length <= 7) {
                value = value.slice(0, 3) + ' ' + value.slice(3);
            } else if (value.length > 7) {
                value = value.slice(0, 3) + ' ' + value.slice(3, 7) + ' ' + value.slice(7, 11);
            }
            e.target.value = value;
        });
    }

    // Form submission with confirmation
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const requiredInputs = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredInputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                input.classList.add('invalid');
                setTimeout(() => input.classList.remove('invalid'), 600);
            }
        });
        
        if (!isValid) {
            Swal.fire({
                icon: 'warning',
                title: '⚠️ ກະລຸນາຕື່ມຂໍ້ມູນໃຫ້ຄົບ!',
                text: 'ມີບາງຊ່ອງທີ່ຕ້ອງການຍັງບໍ່ໄດ້ຕື່ມ',
                confirmButtonText: 'ເຂົ້າໃຈແລ້ວ',
                confirmButtonColor: '#667eea'
            });
            return;
        }
        
        Swal.fire({
            title: '💾 ບັນທຶກຂໍ້ມູນ?',
            text: 'ທ່ານຕ້ອງການບັນທຶກຂໍ້ມູນລົດນີ້ບໍ່?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '✓ ບັນທຶກ',
            cancelButtonText: '✕ ຍົກເລີກ',
            confirmButtonColor: '#667eea',
            cancelButtonColor: '#6b7280',
            reverseButtons: true
        }).then(function(result){
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // Show success message after save
    <?php if ($success): ?>
    Swal.fire({
        icon: 'success',
        title: '✓ ສຳເລັດ',
        text: 'ບັນທຶກຂໍ້ມູນລົດສຳເລັດແລ້ວ',
        timer: 2000,
        showConfirmButton: false
    }).then(function() {
        window.location.href = '/Parking car/pages/view_vehicle.php';
    });
    <?php endif; ?>

    // Show error alert for duplicate
    <?php if ($error === 'duplicate'): ?>
    Swal.fire({
        icon: 'error',
        title: '⚠️ ທະບຽນຊ້ຳ!',
        text: 'ທະບຽນລົດນີ້ມີໃນລະບົບແລ້ວ. ກະລຸນາກວດສອບອີກຄັ້ງ',
        confirmButtonText: 'ເຂົ້າໃຈແລ້ວ',
        confirmButtonColor: '#ef4444'
    });
    <?php endif; ?>
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>