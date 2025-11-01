<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/auth.php';

$errors = [];
$id = $_GET['id'] ?? null;
if (!$id) {
    echo '<div class="alert alert-danger">Missing id</div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

// load types for select
$types = $pdo->query('SELECT * FROM vehicle_types ORDER BY name')->fetchAll();

// handle POST (update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? $id;
    $plate = trim($_POST['plate'] ?? '');
    $type_id = (int)($_POST['type_id'] ?? 0);
    $owner_name = trim($_POST['owner_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $note = trim($_POST['note'] ?? '');

    if ($plate === '' || $type_id <= 0 || $owner_name === '') {
        $errors[] = 'ກະລຸນາປ້ອນຂໍ້ມູນທີ່ຈໍາເປັນ';
    }

    // verify type exists
    $tstmt = $pdo->prepare('SELECT id FROM vehicle_types WHERE id = ?');
    $tstmt->execute([$type_id]);
    if (! $tstmt->fetch()) {
        $errors[] = 'Selected vehicle type does not exist';
    }

    if (empty($errors)) {
        try {
            $u = $pdo->prepare('UPDATE vehicles SET plate = ?, type_id = ?, owner_name = ?, phone = ?, price = ?, note = ? WHERE id = ?');
            $u->execute([$plate, $type_id, $owner_name, $phone, $price, $note, $id]);
            header('Location: view_vehicle.php?msg=updated');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Update error: ' . $e->getMessage();
        }
    }
}

// load current vehicle data (for GET or to refill after validation error)
$vstmt = $pdo->prepare('SELECT * FROM vehicles WHERE id = ?');
$vstmt->execute([$id]);
$vehicle = $vstmt->fetch();
if (! $vehicle) {
    echo '<div class="alert alert-danger">Vehicle not found</div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

// if POST failed validation, override $vehicle values with submitted ones so form keeps user input
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($errors)) {
    $vehicle['plate'] = htmlspecialchars($plate);
    $vehicle['type_id'] = $type_id;
    $vehicle['owner_name'] = htmlspecialchars($owner_name);
    $vehicle['phone'] = htmlspecialchars($phone);
    $vehicle['price'] = $price;
    $vehicle['note'] = htmlspecialchars($note);
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
    --bg-main: #ffffff;
    --bg-card: #ffffff;
    --bg-page: #f3f4f6;
    --bg-hover: #f9fafb;
    --bg-disabled: #f3f4f6;
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
    --bg-hover: #334155;
    --bg-disabled: #1e293b;
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
    background-color: var(--bg-page);
    font-family: 'Phetsarath OT', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    transition: background-color 0.3s, color 0.3s;
}

.container {
    max-width: 900px;
    margin: 0 auto;
    padding: 1rem;
}

.theme-toggle {
    position: fixed;
    top: 1rem;
    right: 1rem;
    z-index: 1000;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(99, 102, 241, 0.2);
    color: white;
}

.theme-toggle:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(99, 102, 241, 0.3);
}

.theme-toggle:active {
    transform: translateY(0);
}

.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: clamp(1rem, 5vw, 2rem);
    border-radius: 16px;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-lg);
    animation: slideDown 0.5s ease-out;
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.page-header h1 {
    margin: 0;
    font-size: clamp(1.25rem, 5vw, 2rem);
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-container {
    background: var(--bg-card);
    padding: clamp(1.5rem, 5vw, 2.5rem);
    border-radius: 16px;
    box-shadow: var(--shadow-lg);
    animation: fadeInUp 0.6s ease-out;
    border: 1px solid var(--border-color);
}

.form-group {
    margin-bottom: 1.5rem;
    animation: slideIn 0.5s ease-out both;
}

.form-group:nth-child(1) { animation-delay: 0.1s; }
.form-group:nth-child(2) { animation-delay: 0.15s; }
.form-group:nth-child(3) { animation-delay: 0.2s; }
.form-group:nth-child(4) { animation-delay: 0.25s; }
.form-group:nth-child(5) { animation-delay: 0.3s; }
.form-group:nth-child(6) { animation-delay: 0.35s; }

.form-label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
    font-size: clamp(0.875rem, 2vw, 0.95rem);
    transition: color 0.3s ease;
}

.form-label::after {
    content: ' *';
    color: var(--danger);
}

.form-label.optional::after {
    content: '';
}

.form-input,
.form-select,
.form-textarea {
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

.form-input::placeholder,
.form-select::placeholder,
.form-textarea::placeholder {
    color: var(--text-secondary);
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    transform: translateY(-2px);
}

.form-input:disabled {
    background: var(--bg-disabled);
    cursor: not-allowed;
    color: var(--text-secondary);
}

.form-textarea {
    resize: vertical;
    min-height: 100px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

@media (max-width: 640px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}

.disabled-input-wrapper {
    position: relative;
}

.disabled-badge {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.button-group {
    display: flex;
    flex-direction: row;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 2px solid var(--border-color);
    animation: slideIn 0.5s ease-out 0.4s both;
    flex-wrap: wrap;
}

@media (max-width: 640px) {
    .button-group {
        flex-direction: column;
        gap: 0.75rem;
    }
}

.btn {
    padding: 0.875rem 2.5rem;
    border: none;
    border-radius: 10px;
    font-size: clamp(0.875rem, 2vw, 1rem);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-family: inherit;
    white-space: nowrap;
}

@media (max-width: 640px) {
    .btn {
        width: 100%;
        padding: 1rem;
    }
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

.btn-primary:active {
    transform: translateY(-1px);
}

.btn-secondary {
    background: var(--bg-card);
    color: var(--text-secondary);
    border: 2px solid var(--border-color);
}

.btn-secondary:hover {
    background: var(--bg-hover);
    border-color: var(--primary);
    color: var(--primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

html.dark-mode .btn-secondary:hover {
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
}

.alert-error {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border-left: 4px solid var(--danger);
    padding: clamp(1rem, 4vw, 1.5rem);
    border-radius: 12px;
    margin-bottom: 2rem;
    animation: shake 0.5s ease-out, fadeIn 0.5s ease-out;
    border: 1px solid var(--border-color);
}

html.dark-mode .alert-error {
    background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 100%);
    border-left-color: #fca5a5;
}

.alert-error-item {
    color: #991b1b;
    font-weight: 500;
    padding: 0.5rem 0;
    font-size: clamp(0.875rem, 2vw, 1rem);
}

html.dark-mode .alert-error-item {
    color: #fecaca;
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

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
    20%, 40%, 60%, 80% { transform: translateX(5px); }
}

.icon {
    font-size: clamp(1rem, 3vw, 1.2rem);
}

/* Responsive */
@media (max-width: 768px) {
    .container {
        padding: 0.75rem;
    }

    .page-header {
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
    }

    .page-header h1 {
        font-size: 1.5rem;
    }

    .form-container {
        padding: 1.25rem;
    }

    .form-group {
        margin-bottom: 1.25rem;
    }

    .theme-toggle {
        width: 45px;
        height: 45px;
        font-size: 1.25rem;
        top: 0.75rem;
        right: 0.75rem;
    }
}

@media (max-width: 480px) {
    .container {
        padding: 0.5rem;
    }

    .page-header {
        padding: 1rem 0.75rem;
        margin-bottom: 1rem;
    }

    .page-header h1 {
        font-size: 1.15rem;
    }

    .form-container {
        padding: 1rem;
        border-radius: 12px;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-input,
    .form-select,
    .form-textarea {
        padding: 0.75rem 0.875rem;
        font-size: 16px;
    }

    .form-label {
        font-size: 0.875rem;
        margin-bottom: 0.4rem;
    }

    .button-group {
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        gap: 0.5rem;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        font-size: 0.875rem;
    }

    .disabled-badge {
        font-size: 0.65rem;
        padding: 0.2rem 0.5rem;
        right: 0.75rem;
    }

    .theme-toggle {
        width: 40px;
        height: 40px;
        font-size: 1rem;
        top: 0.5rem;
        right: 0.5rem;
    }
}
</style>

<button class="theme-toggle" id="themeToggle" title="ສະຫຼັບໂໝົດ">🌙</button>

<div class="container">
    <div class="page-header">
        <span class="icon">✏️</span>
        <h1>ແກ້ໄຂຂໍ້ມູນລົດ</h1>
    </div>

    <?php if ($errors): ?>
      <div class="alert-error">
        <?php foreach ($errors as $e): ?>
          <div class="alert-error-item">⚠️ <?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="form-container">
      <form method="post">
        <input type="hidden" name="id" value="<?= htmlspecialchars($vehicle['id']) ?>">
        
        <div class="form-group">
          <label class="form-label optional">ເລກອ້າງອີງ</label>
          <div class="disabled-input-wrapper">
            <input class="form-input" value="<?= htmlspecialchars($vehicle['ref_code']) ?>" disabled>
            <span class="disabled-badge">ລັອກ</span>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">🚗 ທະບຽນລົດ</label>
          <input name="plate" class="form-input" required value="<?= htmlspecialchars($vehicle['plate']) ?>" placeholder="ປ້ອນເລກທະບຽນລົດ">
        </div>

        <div class="form-group">
          <label class="form-label">🏷️ ປະເພດລົດ</label>
          <select name="type_id" class="form-select" required>
            <option value="">-- ເລືອກປະເພດ --</option>
            <?php foreach ($types as $t): ?>
              <option value="<?= $t['id'] ?>" <?= $t['id'] == $vehicle['type_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($t['code'].' - '.$t['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">👤 ຊື່ເຈົ້າຂອງລົດ</label>
          <input name="owner_name" class="form-input" required value="<?= htmlspecialchars($vehicle['owner_name']) ?>" placeholder="ປ້ອນຊື່ເຈົ້າຂອງລົດ">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label optional">📞 ເບີໂທ</label>
            <input name="phone" class="form-input" value="<?= htmlspecialchars($vehicle['phone']) ?>" placeholder="020 XXXX XXXX">
          </div>
          <div class="form-group">
            <label class="form-label optional">💰 ລາຄາ</label>
            <input name="price" type="number" step="0.01" class="form-input" value="<?= htmlspecialchars($vehicle['price']) ?>" placeholder="0.00">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label optional">📝 ລາຍລະອຽດ</label>
          <textarea name="note" class="form-textarea" rows="4" placeholder="ເພີ່ມລາຍລະອຽດເພີ່ມເຕີມ (ຖ້າມີ)"><?= htmlspecialchars($vehicle['note']) ?></textarea>
        </div>

        <div class="button-group">
          <button type="submit" class="btn btn-primary">
            <span class="icon">💾</span>
            <span>ບັນທຶກການແກ້ໄຂ</span>
          </button>
          <a class="btn btn-secondary" href="view_vehicle.php">
            <span class="icon">✕</span>
            <span>ຍົກເລີກ</span>
          </a>
        </div>
      </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Theme Toggle
function initThemeToggle() {
    const toggle = document.getElementById('themeToggle');
    const html = document.documentElement;
    const savedTheme = localStorage.getItem('theme') || 'light';
    
    if (savedTheme === 'dark') {
        html.classList.add('dark-mode');
        toggle.textContent = '☀️';
    }
    
    toggle.addEventListener('click', () => {
        html.classList.toggle('dark-mode');
        const isDark = html.classList.contains('dark-mode');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        toggle.textContent = isDark ? '☀️' : '🌙';
    });

    if (window.matchMedia && !localStorage.getItem('theme')) {
        if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            html.classList.add('dark-mode');
            toggle.textContent = '☀️';
        }
    }
}

document.addEventListener('DOMContentLoaded', function(){
    initThemeToggle();

    const form = document.querySelector('form');
    
    // Add input animation effects
    const inputs = document.querySelectorAll('.form-input, .form-select, .form-textarea');
    inputs.forEach(function(input){
        input.addEventListener('focus', function(){
            this.parentElement.style.transform = 'scale(1.01)';
        });
        
        input.addEventListener('blur', function(){
            this.parentElement.style.transform = 'scale(1)';
        });
    });

    // Form submission confirmation
    form.addEventListener('submit', function(e){
        e.preventDefault();
        
        // Validate required fields
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
                confirmButtonColor: '#6366f1'
            });
            return;
        }
        
        Swal.fire({
            title: '💾 ບັນທຶກການແກ້ໄຂ?',
            text: 'ທ່ານຕ້ອງການບັນທຶກການປ່ຽນແປງນີ້ບໍ່?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '✓ ບັນທຶກ',
            cancelButtonText: '✕ ຍົກເລີກ',
            confirmButtonColor: '#6366f1',
            cancelButtonColor: '#6b7280',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'ກຳລັງບັນທຶກ...',
                    html: 'ກະລຸນາລໍຖ້າສັກຄູ່',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                setTimeout(() => {
                    form.submit();
                }, 100);
            }
        });
    });

    // Apply theme to SweetAlert2
    const applyThemeToSwal = () => {
        const isDark = document.documentElement.classList.contains('dark-mode');
        if (isDark) {
            document.documentElement.style.colorScheme = 'dark';
        } else {
            document.documentElement.style.colorScheme = 'light';
        }
    };

    applyThemeToSwal();
    const toggle = document.getElementById('themeToggle');
    toggle.addEventListener('click', applyThemeToSwal);
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>