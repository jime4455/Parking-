<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/auth.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$q = trim($_GET['q'] ?? '');
$params = [];
$sql = 'SELECT v.*, t.code AS type_code, t.name AS type_name FROM vehicles v JOIN vehicle_types t ON v.type_id=t.id';
if ($q !== '') {
    $sql .= ' WHERE v.ref_code LIKE ? OR v.plate LIKE ? OR v.owner_name LIKE ?';
    $like = "%$q%";
    $params = [$like, $like, $like];
}
$sql .= ' ORDER BY v.created_at DESC LIMIT 200';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// delete handling with confirmation
if (isset($_POST['delete_id'])) {
    $delId = $_POST['delete_id'];
    $d = $pdo->prepare('DELETE FROM vehicles WHERE id = ?');
    $d->execute([$delId]);
    echo json_encode(['success' => true]);
    exit;
}
?>

<style>
:root {
    --primary: #6366f1;
    --primary-dark: #4f46e5;
    --primary-light: #818cf8;
    --danger: #ef4444;
    --danger-dark: #dc2626;
    --danger-light: #f87171;
    --success: #10b981;
    --bg-main: #ffffff;
    --bg-card: #ffffff;
    --bg-hover: #f9fafb;
    --bg-thead: #667eea;
    --text-primary: #1f2937;
    --text-secondary: #6b7280;
    --border-color: #e5e7eb;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

html.dark-mode {
    --bg-main: #0f172a;
    --bg-card: #1e293b;
    --bg-hover: #334155;
    --bg-thead: #1e293b;
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
    background-color: var(--bg-main);
    color: var(--text-primary);
    transition: background-color 0.3s, color 0.3s;
    font-family: 'Phetsarath OT', sans-serif;
}

.container {
    max-width: 1400px;
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
    padding: 2rem 1.5rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-lg);
    animation: slideDown 0.5s ease-out;
}

.page-header h1 {
    margin: 0;
    font-size: clamp(1.5rem, 5vw, 2rem);
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.search-container {
    background: var(--bg-card);
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: var(--shadow);
    margin-bottom: 2rem;
    animation: fadeIn 0.6s ease-out 0.2s both;
    border: 1px solid var(--border-color);
}

.search-wrapper {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
    position: relative;
}

.search-input {
    flex: 1;
    min-width: 200px;
    padding: 0.75rem 1rem;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-size: 1rem;
    background: var(--bg-main);
    color: var(--text-primary);
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.search-input::placeholder {
    color: var(--text-secondary);
}

.btn-search {
    padding: 0.75rem 2rem;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(99, 102, 241, 0.2);
    white-space: nowrap;
    font-family: 'Phetsarath OT', sans-serif;
}

.btn-search:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(99, 102, 241, 0.3);
}

.btn-search:active {
    transform: translateY(0);
}

.btn-clear {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--text-secondary);
    font-size: 1rem;
    cursor: pointer;
    padding: 5px;
    display: none;
}

.btn-clear:hover {
    color: var(--danger);
}

    .table-container {
        background: var(--bg-card);
        border-radius: 12px;
        box-shadow: var(--shadow);
        overflow: hidden;
        animation: fadeIn 0.6s ease-out 0.4s both;
        border: 1px solid var(--border-color);
        overflow-x: auto;
    }

    .table-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 1rem 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
    }

    .table-header h2 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
    }

    .toggle-btn {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.3);
        padding: 0.5rem 1.5rem;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
        font-family: 'Phetsarath OT', sans-serif;
    }

    .toggle-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-2px);
    }

    .toggle-btn.closed {
        background: rgba(255, 255, 255, 0.15);
    }

    .toggle-btn.closed:hover {
        background: rgba(255, 255, 255, 0.25);
    }

    #tableWrapper {
        transition: all 0.3s ease;
        opacity: 1;
        transform: translateY(0);
    }

    #tableWrapper.hidden {
        opacity: 0;
        transform: translateY(-10px);
    }.modern-table {
    width: 100%;
    border-collapse: collapse;
}

.modern-table thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    position: sticky;
    top: 0;
    z-index: 10;
}

.modern-table thead th {
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    white-space: nowrap;
}

.modern-table tbody tr {
    border-bottom: 1px solid var(--border-color);
    transition: all 0.3s ease;
    animation: slideUp 0.5s ease-out both;
}

.modern-table tbody tr:nth-child(1) { animation-delay: 0.1s; }
.modern-table tbody tr:nth-child(2) { animation-delay: 0.15s; }
.modern-table tbody tr:nth-child(3) { animation-delay: 0.2s; }
.modern-table tbody tr:nth-child(4) { animation-delay: 0.25s; }
.modern-table tbody tr:nth-child(5) { animation-delay: 0.3s; }

.modern-table tbody tr:hover {
    background: var(--bg-hover);
    transform: scale(1.01);
}

.modern-table tbody td {
    padding: 1rem;
    font-size: 0.9rem;
    color: var(--text-primary);
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    white-space: nowrap;
}

.btn-action {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
    margin-bottom: 0.25rem;
    margin-right: 0.25rem;
    white-space: nowrap;
    font-family: 'Phetsarath OT', sans-serif;
}

.btn-edit {
    background: var(--primary);
    color: white;
}

.btn-edit:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(99, 102, 241, 0.3);
}

.btn-delete {
    background: var(--danger);
    color: white;
}

.btn-delete:hover {
    background: var(--danger-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(239, 68, 68, 0.3);
}

.price-tag {
    color: var(--success);
    font-weight: 700;
    font-size: 1rem;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--text-secondary);
}

.empty-state-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
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

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Custom SweetAlert2 styling */
html.dark-mode .swal2-popup {
    background: var(--bg-card) !important;
    color: var(--text-primary) !important;
}

html.dark-mode .swal2-title {
    color: var(--text-primary) !important;
}

html.dark-mode .swal2-html-container {
    color: var(--text-primary) !important;
}

.swal2-popup {
    border-radius: 16px;
    box-shadow: var(--shadow-lg);
    font-family: 'Phetsarath OT', sans-serif;
    transition: background-color 0.3s, color 0.3s;
}

.swal2-title {
    font-family: 'Phetsarath OT', sans-serif;
    font-size: 1.5rem;
}

.swal2-html-container {
    font-family: 'Phetsarath OT', sans-serif;
}

.swal2-confirm {
    background: var(--danger) !important;
    border-radius: 8px;
    padding: 10px 30px;
    font-family: 'Phetsarath OT', sans-serif;
}

.swal2-cancel {
    border-radius: 8px;
    padding: 10px 30px;
    font-family: 'Phetsarath OT', sans-serif;
}

.swal2-icon.swal2-warning {
    border-color: #f59e0b;
    color: #f59e0b;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .container {
        padding: 0.75rem;
    }

    .page-header {
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .page-header h1 {
        font-size: 1.5rem;
    }

    .search-container {
        padding: 1rem;
    }

    .modern-table thead th {
        padding: 0.75rem 0.5rem;
        font-size: 0.7rem;
    }

    .modern-table tbody td {
        padding: 0.75rem 0.5rem;
        font-size: 0.85rem;
    }

    .btn-action {
        padding: 0.4rem 0.75rem;
        font-size: 0.8rem;
    }
}

@media (max-width: 768px) {
    .search-wrapper {
        gap: 0.5rem;
    }

    .search-input {
        min-width: 150px;
    }

    .btn-search {
        padding: 0.75rem 1.5rem;
    }

    .theme-toggle {
        width: 45px;
        height: 45px;
        font-size: 1.25rem;
        top: 0.75rem;
        right: 0.75rem;
    }

    .modern-table {
        font-size: 0.85rem;
    }

    .modern-table thead th {
        padding: 0.6rem 0.3rem;
        font-size: 0.65rem;
    }

    .modern-table tbody td {
        padding: 0.6rem 0.3rem;
        font-size: 0.8rem;
    }

    .badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.5rem;
    }

    .btn-action {
        padding: 0.35rem 0.6rem;
        font-size: 0.75rem;
        margin-right: 0.2rem;
        margin-bottom: 0.2rem;
    }

    .price-tag {
        font-size: 0.9rem;
    }

    .page-header h1 {
        font-size: 1.25rem;
    }
}

@media (max-width: 480px) {
    .container {
        padding: 0.5rem;
    }

    .page-header {
        padding: 1rem 0.75rem;
        margin-bottom: 1rem;
        border-radius: 12px;
    }

    .page-header h1 {
        font-size: 1rem;
    }

    .search-container {
        padding: 0.75rem;
        margin-bottom: 1rem;
    }

    .search-wrapper {
        flex-direction: column;
        gap: 0.5rem;
    }

    .search-input {
        width: 100%;
        min-width: unset;
    }

    .btn-search {
        width: 100%;
    }

    .theme-toggle {
        width: 40px;
        height: 40px;
        font-size: 1.1rem;
        top: 0.5rem;
        right: 0.5rem;
    }

    .table-container {
        border-radius: 8px;
    }

    .modern-table {
        font-size: 0.75rem;
    }

    .modern-table thead th {
        padding: 0.5rem 0.25rem;
        font-size: 0.6rem;
    }

    .modern-table tbody td {
        padding: 0.5rem 0.25rem;
        font-size: 0.7rem;
    }

    .badge {
        font-size: 0.65rem;
        padding: 0.15rem 0.4rem;
    }

    .btn-action {
        padding: 0.3rem 0.5rem;
        font-size: 0.7rem;
        display: block;
        width: 100%;
        margin-bottom: 0.25rem;
        margin-right: 0;
    }

    .price-tag {
        font-size: 0.85rem;
    }
    
    /* Toggle button for entire table */
    #toggleButton {
        display: block;
        margin: 0 0 10px 0;
        padding: 8px 16px;
        background-color: #4a6cf7;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
        transition: all 0.3s ease;
        font-family: 'Phetsarath OT', sans-serif;
    }
    
    #toggleButton:hover {
        background-color: #3a5bd9;
        transform: translateY(-2px);
    }
    
    #toggleButton.closed {
        background-color: #e74c3c;
    }
    
    #toggleButton.closed:hover {
        background-color: #c0392b;
    }
    
    /* Detail row styles */
    .detail-row {
        background-color: rgba(74, 108, 247, 0.05);
    }
    
    .detail-content {
        padding: 0;
    }
    
    .detail-card {
        padding: 16px;
        border-radius: 8px;
        margin: 8px;
        background-color: var(--card-bg);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .detail-card h3 {
        margin-top: 0;
        margin-bottom: 16px;
        color: var(--text-color);
        font-size: 1.1rem;
    }
    
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 16px;
    }
    
    .detail-item {
        display: flex;
        flex-direction: column;
    }
    
    .detail-label {
        font-weight: bold;
        color: var(--text-secondary);
        margin-bottom: 4px;
    }
    
    .detail-value {
        color: var(--text-color);
    }
}
</style>

<button class="theme-toggle" id="themeToggle" title="ສະຫຼັບໂໝົດ">🌙</button>

<div class="container">
    <div class="page-header">
        <h1>🚗 ຂໍ້ມູນລົດ</h1>
    </div>

    <div class="search-container">
        <div class="search-wrapper">
            <input id="searchInput" name="q" value="<?= htmlspecialchars($q) ?>" class="search-input" placeholder="🔍 ຄົ້ນຫາ ໄອດີ/ເລກລົດ/ຊື່...">
            <button id="clearSearch" class="btn-clear" type="button">✖</button>
        </div>
    </div>

    <div class="table-container">
        <div class="table-header">
            <h2>📋 ຕາຕະລາງຂໍ້ມູນລົດ</h2>
            <button id="toggleButton" class="toggle-btn" title="ເປີດ/ປິດ ຕາຕະລາງ">
                <span class="toggle-text">ປິດ</span>
            </button>
        </div>
        <div id="tableWrapper">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>ເລກອ້າງອີງ</th>
                    <th>ເລກລົດ</th>
                    <th>ປະເພດລົດ</th>
                    <th>ຊື່ເຈົ້າຂອງລົດ</th>
                    <th>ເບີໂທ</th>
                    <th>ວັນທີ - ເວລາ</th>
                    <th>ລາຄາ</th>
                    <th>ການແກ້ໄຂ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rows) > 0): ?>
                    <?php foreach ($rows as $r): ?>
                    <tr class="vehicle-row" data-id="<?= htmlspecialchars($r['id']) ?>">
                        <td>
                            <button class="toggle-btn" data-id="<?= htmlspecialchars($r['id']) ?>">+</button>
                            <strong><?= htmlspecialchars($r['ref_code']) ?></strong>
                        </td>
                        <td><?= htmlspecialchars($r['plate']) ?></td>
                        <td><span class="badge"><?= htmlspecialchars($r['type_code'].' '.$r['type_name']) ?></span></td>
                        <td><?= htmlspecialchars($r['owner_name']) ?></td>
                        <td><?= htmlspecialchars($r['phone']) ?></td>
                        <td><?= htmlspecialchars($r['created_at']) ?></td>
                        <td><span class="price-tag"><?= htmlspecialchars(number_format($r['price'],2)) ?> ₭</span></td>
                        <td>
                            <a class="btn-action btn-edit" href="/Parking car/pages/edit_vehicle.php?id=<?= urlencode($r['id']) ?>">✏️ ແກ້ໄຂ</a>
                            <button class="btn-action btn-delete delete-btn" 
                                    data-id="<?= htmlspecialchars($r['id']) ?>"
                                    data-ref="<?= htmlspecialchars($r['ref_code']) ?>"
                                    data-plate="<?= htmlspecialchars($r['plate']) ?>">
                                🗑️ ລົບ
                            </button>
                        </td>
                    </tr>
                    <tr class="detail-row" id="detail-<?= htmlspecialchars($r['id']) ?>" style="display: none;">
                        <td colspan="8" class="detail-content">
                            <div class="detail-card">
                                <h3>ລາຍລະອຽດເພີ່ມເຕີມ</h3>
                                <div class="detail-grid">
                                    <div class="detail-item">
                                        <span class="detail-label">ໝາຍເຫດ:</span>
                                        <span class="detail-value"><?= !empty($r['note']) ? htmlspecialchars($r['note']) : 'ບໍ່ມີໝາຍເຫດ' ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">ວັນທີສ້າງ:</span>
                                        <span class="detail-value"><?= htmlspecialchars($r['created_at']) ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">ID:</span>
                                        <span class="detail-value"><?= htmlspecialchars($r['id']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            <div class="empty-state-icon">📭</div>
                            <p>ບໍ່ມີຂໍ້ມູນລົດ</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div> <!-- Close tableWrapper div -->
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

    // System preference
    if (window.matchMedia && !localStorage.getItem('theme')) {
        if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            html.classList.add('dark-mode');
            toggle.textContent = '☀️';
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initThemeToggle();

    // Real-time search functionality
    const searchInput = document.getElementById('searchInput');
    const clearSearch = document.getElementById('clearSearch');
    const tableBody = document.querySelector('.modern-table tbody');
    let searchTimeout;

    // Show clear button when search has text
    searchInput.addEventListener('input', function() {
        if (this.value.length > 0) {
            clearSearch.style.display = 'block';
        } else {
            clearSearch.style.display = 'none';
        }
        
        // Debounce search to avoid too many requests
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            performSearch(this.value);
        }, 300);
    });

    // Clear search when button is clicked
    clearSearch.addEventListener('click', function() {
        searchInput.value = '';
        clearSearch.style.display = 'none';
        performSearch('');
    });

    // Function to perform AJAX search
    function performSearch(query) {
        fetch(`search_vehicle_ajax.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                updateTable(data);
            })
            .catch(error => console.error('Error:', error));
    }

    // Function to update table with search results
    function updateTable(vehicles) {
        tableBody.innerHTML = '';
        
        if (vehicles.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <p>ບໍ່ມີຂໍ້ມູນລົດ</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        vehicles.forEach(vehicle => {
            // Create main row
            const mainRow = document.createElement('tr');
            mainRow.className = 'vehicle-row';
            mainRow.setAttribute('data-id', vehicle.id);
            
            mainRow.innerHTML = `
                <td>
                    <button class="toggle-btn" data-id="${vehicle.id}">+</button>
                    <strong>${vehicle.ref_code}</strong>
                </td>
                <td>${vehicle.plate}</td>
                <td><span class="badge">${vehicle.type_code} ${vehicle.type_name}</span></td>
                <td>${vehicle.owner_name}</td>
                <td>${vehicle.phone}</td>
                <td>${vehicle.created_at}</td>
                <td><span class="price-tag">${parseFloat(vehicle.price).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} ₭</span></td>
                <td>
                    <a class="btn-action btn-edit" href="/Parking car/pages/edit_vehicle.php?id=${vehicle.id}">✏️ ແກ້ໄຂ</a>
                    <button class="btn-action btn-delete delete-btn" 
                            data-id="${vehicle.id}"
                            data-ref="${vehicle.ref_code}"
                            data-plate="${vehicle.plate}">
                        🗑️ ລົບ
                    </button>
                </td>
            `;
            tableBody.appendChild(mainRow);
            
            // Create detail row
            const detailRow = document.createElement('tr');
            detailRow.className = 'detail-row';
            detailRow.id = `detail-${vehicle.id}`;
            detailRow.style.display = 'none';
            
            detailRow.innerHTML = `
                <td colspan="8" class="detail-content">
                    <div class="detail-card">
                        <h3>ລາຍລະອຽດເພີ່ມເຕີມ</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">ໝາຍເຫດ:</span>
                                <span class="detail-value">${vehicle.note ? vehicle.note : 'ບໍ່ມີໝາຍເຫດ'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">ວັນທີສ້າງ:</span>
                                <span class="detail-value">${vehicle.created_at}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">ID:</span>
                                <span class="detail-value">${vehicle.id}</span>
                            </div>
                        </div>
                    </div>
                </td>
            `;
            tableBody.appendChild(detailRow);
        });
        
        // Reattach event listeners for toggle buttons
        attachToggleListeners();
        // Reattach event listeners for delete buttons
        attachDeleteListeners();
    }

    // Function to attach toggle listeners to newly created elements
    function attachToggleListeners() {
        document.querySelectorAll('.toggle-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const detailRow = document.getElementById('detail-' + id);
                
                if (detailRow.style.display === 'none' || !detailRow.style.display) {
                    detailRow.style.display = 'table-row';
                    this.classList.add('active');
                    this.textContent = '-';
                } else {
                    detailRow.style.display = 'none';
                    this.classList.remove('active');
                    this.textContent = '+';
                }
            });
        });
    }

    // Function to attach delete listeners to newly created elements
    function attachDeleteListeners() {
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const vehicleId = this.getAttribute('data-id');
                const refCode = this.getAttribute('data-ref');
                const plate = this.getAttribute('data-plate');
                
                Swal.fire({
                    title: 'ຢືນຢັນການລົບ',
                    html: `ທ່ານຕ້ອງການລົບຂໍ້ມູນລົດ <strong>${refCode}</strong> (${plate}) ແທ້ບໍ່?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'ລົບ',
                    cancelButtonText: 'ຍົກເລີກ',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading state
                        Swal.fire({
                            title: 'ກຳລັງລົບຂໍ້ມູນ...',
                            html: 'ກະລຸນາລໍຖ້າ...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        
                        // Send delete request
                        const formData = new FormData();
                        formData.append('delete_id', vehicleId);
                        
                        fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    title: 'ລົບຂໍ້ມູນສຳເລັດ',
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    // Refresh search results
                                    performSearch(searchInput.value);
                                });
                            } else {
                                Swal.fire({
                                    title: 'ເກີດຂໍ້ຜິດພາດ',
                                    text: 'ບໍ່ສາມາດລົບຂໍ້ມູນໄດ້',
                                    icon: 'error'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                title: 'ເກີດຂໍ້ຜິດພາດ',
                                text: 'ບໍ່ສາມາດລົບຂໍ້ມູນໄດ້',
                                icon: 'error'
                            });
                        });
                    }
                });
            });
        });
    }

    // Enhanced Table toggle functionality
    const toggleButton = document.getElementById('toggleButton');
    const tableWrapper = document.getElementById('tableWrapper');
    const toggleText = toggleButton.querySelector('.toggle-text');
    
    // Set initial state from localStorage if exists
    const isTableOpen = localStorage.getItem('tableState') !== 'closed';
    if (!isTableOpen) {
        tableWrapper.style.display = 'none';
        toggleButton.classList.add('closed');
        toggleText.textContent = 'ເປີດ';
    }

    toggleButton.addEventListener('click', function() {
        const isHidden = tableWrapper.style.display === 'none';
        
        // Animate the transition
        if (isHidden) {
            tableWrapper.style.display = 'block';
            setTimeout(() => {
                tableWrapper.style.opacity = '1';
                tableWrapper.style.transform = 'translateY(0)';
            }, 10);
            toggleText.textContent = 'ປິດ';
            toggleButton.classList.remove('closed');
            localStorage.setItem('tableState', 'open');
        } else {
            tableWrapper.style.opacity = '0';
            tableWrapper.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                tableWrapper.style.display = 'none';
            }, 300);
            toggleText.textContent = 'ເປີດ';
            toggleButton.classList.add('closed');
            localStorage.setItem('tableState', 'closed');
        }
    });

    // Toggle row details functionality
    document.querySelectorAll('.toggle-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const detailRow = document.getElementById('detail-' + id);
            
            // Toggle the detail row visibility
            if (detailRow.style.display === 'none' || !detailRow.style.display) {
                detailRow.style.display = 'table-row';
                this.classList.add('active');
                this.textContent = '-';
            } else {
                detailRow.style.display = 'none';
                this.classList.remove('active');
                this.textContent = '+';
            }
        });
    });

    // Delete confirmation
    const deleteButtons = document.querySelectorAll('.delete-btn');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const vehicleId = this.getAttribute('data-id');
            const refCode = this.getAttribute('data-ref');
            const plate = this.getAttribute('data-plate');
            
            Swal.fire({
                title: '⚠️ ຢືນຢັນການລົບ',
                html: `ທ່ານຕ້ອງການລົບຂໍ້ມູນລົດນີ້ບໍ່?<br><br>` +
                      `<strong>ເລກອ້າງອີງ:</strong> ${refCode}<br>` +
                      `<strong>ເລກລົດ:</strong> ${plate}<br><br>` +
                      `<span style="color: #ef4444;">⚠️ ການດຳເນີນການນີ້ບໍ່ສາມາດຍົກເລີກໄດ້!</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '🗑️ ລົບຂໍ້ມູນ',
                cancelButtonText: '❌ ຍົກເລີກ',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                reverseButtons: true,
                focusCancel: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'ກຳລັງລົບ...',
                        html: 'ກະລຸນາລໍຖ້າ',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    const formData = new FormData();
                    formData.append('delete_id', vehicleId);
                    
                    fetch(window.location.href = '/Parking car/pages/view_vehicle.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '✅ ສຳເລັດ',
                                text: 'ລົບຂໍ້ມູນລົດສຳເລັດແລ້ວ',
                                timer: 2500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        }
                    })
                }
            });
        });
    });
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>