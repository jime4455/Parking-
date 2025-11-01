<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// stats
$totalVehicles = $pdo->query('SELECT COUNT(*) FROM vehicles')->fetchColumn();
$totalTypes = $pdo->query('SELECT COUNT(*) FROM vehicle_types')->fetchColumn();
$latest = $pdo->query('SELECT v.*, t.code AS type_code, t.name AS type_name FROM vehicles v JOIN vehicle_types t ON v.type_id=t.id ORDER BY created_at DESC LIMIT 5')->fetchAll();

// ນັບແຕ່ລະປະເພດລົດ
$carCount = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE type_id = 3")->fetchColumn(); // ລົດໃຫຍ່
$bikeCount = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE type_id = 1")->fetchColumn(); // ລົດຖີບ
$motorCount = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE type_id = 2")->fetchColumn(); // ລົດຈັກ
$todayCount = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE DATE(created_at) = CURDATE()")->fetchColumn();

// ລາຍຮັບມື້ນີ້
$todayRevenue = (float)$pdo->query("SELECT COALESCE(SUM(price),0) FROM vehicles WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(price),0) FROM vehicles")->fetchColumn();
?>

<style>
:root {
    --gradient-1: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --gradient-2: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --gradient-3: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --gradient-4: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    --gradient-5: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    --gradient-6: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
    --shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.1);
    --shadow-hover: 0 20px 40px -5px rgba(0, 0, 0, 0.2);
}

.dashboard-header {
    background: var(--gradient-1);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 20px;
    margin-bottom: 2rem;
    box-shadow: var(--shadow);
    animation: slideDown 0.6s ease-out;
}

.dashboard-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0;
    text-shadow: 0 2px 10px rgba(0,0,0,0.2);
}

.dashboard-header p {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: var(--shadow);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
    overflow: hidden;
    animation: fadeInUp 0.6s ease-out both;
}

.stat-card:nth-child(1) { animation-delay: 0.1s; }
.stat-card:nth-child(2) { animation-delay: 0.15s; }
.stat-card:nth-child(3) { animation-delay: 0.2s; }
.stat-card:nth-child(4) { animation-delay: 0.25s; }
.stat-card:nth-child(5) { animation-delay: 0.3s; }
.stat-card:nth-child(6) { animation-delay: 0.35s; }

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: var(--gradient);
}

.stat-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: var(--shadow-hover);
}

.stat-card.primary::before { background: var(--gradient-1); }
.stat-card.success::before { background: var(--gradient-4); }
.stat-card.warning::before { background: var(--gradient-5); }
.stat-card.info::before { background: var(--gradient-3); }
.stat-card.danger::before { background: var(--gradient-2); }
.stat-card.secondary::before { background: var(--gradient-6); }

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin-bottom: 1rem;
    background: var(--gradient);
    color: white;
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
}

.stat-card.primary .stat-icon { background: var(--gradient-1); }
.stat-card.success .stat-icon { background: var(--gradient-4); }
.stat-card.warning .stat-icon { background: var(--gradient-5); }
.stat-card.info .stat-icon { background: var(--gradient-3); }
.stat-card.danger .stat-icon { background: var(--gradient-2); }
.stat-card.secondary .stat-icon { background: var(--gradient-6); }

.stat-title {
    font-size: 0.95rem;
    color: #6b7280;
    font-weight: 600;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #1f2937;
    line-height: 1;
}

.stat-subtitle {
    font-size: 0.85rem;
    color: #9ca3af;
    margin-top: 0.5rem;
}

/* Quick Actions */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
    animation: fadeInUp 0.6s ease-out 0.4s both;
}

.action-btn {
    padding: 1.5rem;
    border: none;
    border-radius: 14px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    color: white;
    box-shadow: var(--shadow);
}

.action-btn:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-hover);
    color: white;
}

.action-btn.primary { background: var(--gradient-1); }
.action-btn.secondary { background: var(--gradient-6); }
.action-btn.success { background: var(--gradient-4); }

.action-icon {
    font-size: 1.5rem;
}

/* Latest Data Table */
.data-card {
    background: white;
    border-radius: 16px;
    box-shadow: var(--shadow);
    overflow: hidden;
    animation: fadeInUp 0.6s ease-out 0.5s both;
}

.data-card-header {
    background: var(--gradient-1);
    color: white;
    padding: 1.5rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.data-card-header h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
}

.toggle-btn {
    background: rgba(255, 255, 255, 0.2);
    border: 2px solid rgba(255, 255, 255, 0.3);
    color: white;
    padding: 0.5rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.toggle-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
    transform: scale(1.05);
}

.data-card-body {
    padding: 0;
    overflow-x: auto;
}

.modern-table {
    width: 100%;
    border-collapse: collapse;
}

.modern-table thead {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.modern-table thead th {
    padding: 1.25rem 1.5rem;
    text-align: left;
    font-weight: 700;
    color: #1f2937;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e5e7eb;
}

.modern-table tbody td {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #f3f4f6;
    color: #374151;
}

.modern-table tbody tr {
    transition: all 0.3s ease;
}

.modern-table tbody tr:hover {
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
    transform: scale(1.01);
}

.badge-ref {
    background: var(--gradient-1);
    color: white;
    padding: 0.35rem 0.85rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
}

.badge-type {
    background: var(--gradient-3);
    color: white;
    padding: 0.35rem 0.85rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #9ca3af;
}

.empty-state-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Animations */
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

/* Responsive */
@media (max-width: 768px) {
    .dashboard-header h1 {
        font-size: 2rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
    }
    
    .stat-value {
        font-size: 2rem;
    }
    
    .data-card-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .modern-table {
        font-size: 0.9rem;
    }
    
    .modern-table thead th,
    .modern-table tbody td {
        padding: 1rem;
    }
}

@media (max-width: 480px) {
    .dashboard-header {
        padding: 1.5rem 1rem;
    }
    
    .dashboard-header h1 {
        font-size: 1.5rem;
    }
    
    .stat-card {
        padding: 1.5rem;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }
}

/* Loading animation */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>

<div class="dashboard-header">
    <h1>🏠 ໜ້າຫຼັກລະບົບຈອດລົດ</h1>
    <p>ຍິນດີຕ້ອນຮັບ! ເບິ່ງຂໍ້ມູນສະຖິຕິ ແລະ ຈັດການລົດຂອງທ່ານ</p>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-icon">🚗</div>
        <div class="stat-title">ລົດທັງໝົດ</div>
        <div class="stat-value"><?= number_format($totalVehicles) ?></div>
        <div class="stat-subtitle">ຄັນ</div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-icon">💰</div>
        <div class="stat-title">ລາຍຮັບທັງໝົດ</div>
        <div class="stat-value"><?= number_format($totalRevenue, 0) ?></div>
        <div class="stat-subtitle">ກີບ</div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-icon">🚙</div>
        <div class="stat-title">ລົດໃຫຍ່</div>
        <div class="stat-value"><?= number_format($carCount) ?></div>
        <div class="stat-subtitle">ຄັນ</div>
    </div>
    
    <div class="stat-card info">
        <div class="stat-icon">🚲</div>
        <div class="stat-title">ລົດຖີບ</div>
        <div class="stat-value"><?= number_format($bikeCount) ?></div>
        <div class="stat-subtitle">ຄັນ</div>
    </div>
    
    <div class="stat-card danger">
        <div class="stat-icon">🏍️</div>
        <div class="stat-title">ລົດຈັກ</div>
        <div class="stat-value"><?= number_format($motorCount) ?></div>
        <div class="stat-subtitle">ຄັນ</div>
    </div>
    
    <div class="stat-card secondary">
        <div class="stat-icon">⚡</div>
        <div class="stat-title">ເພີ່ມມື້ນີ້</div>
        <div class="stat-value"><?= number_format($todayCount) ?></div>
        <div class="stat-subtitle">ຄັນ</div>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <a href="/Parking%20car/pages/add_vehicle.php" class="action-btn primary">
        <span class="action-icon">➕</span>
        ເພີ່ມລົດໃໝ່
    </a>
    <a href="/Parking%20car/pages/view_vehicle.php" class="action-btn secondary">
        <span class="action-icon">👁️</span>
        ເບິ່ງຂໍ້ມູນລົດ
    </a>
    <a href="/Parking%20car/pages/report.php" class="action-btn success">
        <span class="action-icon">📊</span>
        ລາຍງານ
    </a>
</div>

<!-- Latest Data Table -->
<div class="data-card">
    <div class="data-card-header">
        <h2>📋 ລາຍການລົດລ້າສຸດ</h2>
        <button id="toggleButton" class="toggle-btn">ປິດ</button>
    </div>
    <div class="data-card-body" id="latestData">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>ເລກອ້າງອີງ</th>
                    <th>ເລກລົດ</th>
                    <th>ປະເພດ</th>
                    <th>ເຈົ້າຂອງ</th>
                    <th>ເວລາ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($latest)): ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <div class="empty-state-icon">📭</div>
                                <p>ຍັງບໍ່ມີຂໍ້ມູນລົດ</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($latest as $r): ?>
                        <tr>
                            <td><span class="badge-ref"><?= htmlspecialchars($r['ref_code']) ?></span></td>
                            <td><strong><?= htmlspecialchars($r['plate']) ?></strong></td>
                            <td><span class="badge-type"><?= htmlspecialchars($r['type_code'] . ' - ' . $r['type_name']) ?></span></td>
                            <td><?= htmlspecialchars($r['owner_name']) ?></td>
                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleButton = document.getElementById('toggleButton');
    const latestData = document.getElementById('latestData');
    
    toggleButton.addEventListener('click', function() {
        if (latestData.style.display === 'none') {
            latestData.style.display = '';
            this.textContent = 'ປິດ';
        } else {
            latestData.style.display = 'none';
            this.textContent = 'ເປີດ';
        }
    });
    
    // Animate numbers on load
    const statValues = document.querySelectorAll('.stat-value');
    statValues.forEach(stat => {
        const finalValue = parseInt(stat.textContent.replace(/,/g, ''));
        if (isNaN(finalValue)) return;
        
        let currentValue = 0;
        const increment = finalValue / 50;
        const timer = setInterval(() => {
            currentValue += increment;
            if (currentValue >= finalValue) {
                stat.textContent = finalValue.toLocaleString();
                clearInterval(timer);
            } else {
                stat.textContent = Math.floor(currentValue).toLocaleString();
            }
        }, 20);
    });
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>