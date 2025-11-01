<?php
// Start session and check authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/header.php';

// Check if database connection is successful
if (!isset($pdo)) {
    die('ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່ຖານຂໍ້ມູນ');
}

try {
    // Test database connection
    $pdo->query('SELECT 1');
} catch (PDOException $e) {
    error_log('Database connection error: ' . $e->getMessage());
    die('ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່ຖານຂໍ້ມູນ');
}

// Handle delete request
if (isset($_POST['delete_vehicle']) && isset($_POST['vehicle_id'])) {
    $vehicleId = (int)$_POST['vehicle_id'];
    try {
        $stmt = $pdo->prepare('DELETE FROM vehicles WHERE id = ?');
        $stmt->execute([$vehicleId]);
        $_SESSION['success_message'] = 'ລົບຂໍ້ມູນລົດສຳເລັດແລ້ວ';
        header('Location: report.php?print=invoices');
        exit;
    } catch (PDOException $e) {
        error_log('Delete error: ' . $e->getMessage());
        $_SESSION['error_message'] = 'ເກີດຂໍ້ຜິດພາດໃນການລົບຂໍ້ມູນ';
    }
}

// Get statistics with error handling
try {
    $totalVehicles = (int)$pdo->query('SELECT COUNT(*) FROM vehicles')->fetchColumn();
    $totalRevenue = (float)$pdo->query('SELECT COALESCE(SUM(price),0) FROM vehicles')->fetchColumn();
    
    // By vehicle type
    $byTypeStmt = $pdo->query('
        SELECT t.id, t.code, t.name,
               COUNT(v.id) AS cnt,
               COALESCE(SUM(v.price),0) AS total
        FROM vehicle_types t
        LEFT JOIN vehicles v ON v.type_id = t.id
        GROUP BY t.id, t.code, t.name
        ORDER BY t.name
    ');
    $byType = $byTypeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent vehicles (last 10)
    $recentStmt = $pdo->query('
        SELECT v.id, v.ref_code, v.plate, v.owner_name, v.price, 
               COALESCE(t.code, "N/A") AS type_code, 
               COALESCE(t.name, "ບໍ່ລະບຸ") AS type_name, 
               v.created_at
        FROM vehicles v
        LEFT JOIN vehicle_types t ON v.type_id = t.id
        ORDER BY v.created_at DESC 
        LIMIT 10
    ');
    $recent = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log('Report query error: ' . $e->getMessage());
    die('ເກີດຂໍ້ຜິດພາດໃນການດຶງຂໍ້ມູນ');
}

// CSV Export Handler
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Set headers for CSV download
    $filename = 'parking_report_' . date('Y-m-d_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 300 days');
    
    // Add UTF-8 BOM for proper character encoding in Excel
    echo "\xEF\xBB\xBF";
    
    $out = fopen('php://output', 'w');
    
    // Header section
    fputcsv($out, ['ລາຍງານສະຫຼຸບ - Parking Management System']);
    fputcsv($out, ['ວັນທີ່ສ້າງລາຍງານ: ' . date('d/m/Y H:i:s')]);
    fputcsv($out, ['']);
    
    // Summary section
    fputcsv($out, ['ສະຫຼຸບລວມ']);
    fputcsv($out, ['ຈຳນວນລົດທັງໝົດ', $totalVehicles . ' ຄັນ']);
    fputcsv($out, ['ລາຍຮັບທັງໝົດ', number_format($totalRevenue, 2) . ' ກີບ']);
    fputcsv($out, ['']);
    
    // By type section
    fputcsv($out, ['ລາຍງານຕາມປະເພດລົດ']);
    fputcsv($out, ['ລະຫັດ', 'ຊື່ປະເພດ', 'ຈຳນວນ (ຄັນ)', 'ລາຍຮັບ (ກີບ)', 'ສ່ວນແບ່ງ (%)']);
    
    foreach ($byType as $r) {
        $percentage = $totalRevenue > 0 ? ($r['total'] / $totalRevenue * 100) : 0;
        fputcsv($out, [
            $r['code'],
            $r['name'],
            $r['cnt'],
            number_format($r['total'], 2),
            number_format($percentage, 2) . '%'
        ]);
    }
    
    fputcsv($out, ['ລວມ', '', $totalVehicles, number_format($totalRevenue, 2), '100%']);
    fputcsv($out, ['']);
    
    // All vehicles section
    fputcsv($out, ['ລາຍການລົດທັງໝົດ']);
    fputcsv($out, ['ເລກອ້າງອີງ', 'ເລກທະບຽນ', 'ປະເພດ', 'ຊື່ເຈົ້າຂອງ', 'ລາຄາ (ກີບ)', 'ວັນທີ່-ເວລາ']);
    
    $allVehicles = $pdo->query('
        SELECT v.ref_code, v.plate, 
               COALESCE(t.code, "N/A") AS type_code, 
               COALESCE(t.name, "ບໍ່ລະບຸ") AS type_name, 
               v.owner_name, v.price, v.created_at
        FROM vehicles v
        LEFT JOIN vehicle_types t ON v.type_id = t.id
        ORDER BY v.created_at DESC
    ');
    
    while ($row = $allVehicles->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [
            $row['ref_code'],
            $row['plate'],
            $row['type_code'] . ' - ' . $row['type_name'],
            $row['owner_name'],
            number_format($row['price'], 2),
            date('d/m/Y H:i:s', strtotime($row['created_at']))
        ]);
    }
    
    fclose($out);
    exit;
}

// Print Invoices Handler
if (isset($_GET['print']) && $_GET['print'] === 'invoices') {
    // Get all vehicles for print page
    $allVehiclesStmt = $pdo->query('
        SELECT v.id, v.ref_code, v.plate, v.owner_name, v.price, 
               COALESCE(t.code, "N/A") AS type_code, 
               COALESCE(t.name, "ບໍ່ລະບຸ") AS type_name, 
               v.created_at
        FROM vehicles v
        LEFT JOIN vehicle_types t ON v.type_id = t.id
        ORDER BY v.created_at DESC
    ');
    $allVehicles = $allVehiclesStmt->fetchAll(PDO::FETCH_ASSOC);
    ?><!doctype html>
    <html lang="lo">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>ໃບບິນຈອດລົດ - Printable</title>
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;600;700&display=swap" rel="stylesheet">
        <script>
            // Initialize theme before page loads to prevent flash
            (function() {
                const savedTheme = localStorage.getItem('theme');
                if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark-mode');
                }
            })();
        </script>
        <script>
            // Check for saved theme preference or system preference
            function initializeTheme() {
                const savedTheme = localStorage.getItem('theme');
                if (savedTheme) {
                    document.documentElement.classList.toggle('dark-mode', savedTheme === 'dark');
                } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark-mode');
                }
            }
            // Initialize theme before page loads to prevent flash
            initializeTheme();
        </script>
        <style>
        :root {
            /* Light Mode Colors */
            --bg-main: #f8f9fa;
            --bg-card: #ffffff;
            --bg-hover: #f3f4f6;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-color: #10b981;
            --danger-color: #ef4444;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            
            /* Dark Mode Colors */
            &.dark-mode {
                --bg-main: #1a1a2e;
                --bg-card: #262640;
                --bg-hover: #2f2f55;
                --text-primary: #e2e8f0;
                --text-secondary: #94a3b8;
                --border-color: #3f3f60;
                --shadow: 0 4px 6px rgba(0, 0, 0, 0.4);
                --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.5);
                color-scheme: dark;
            }
        }

        /* Dark Mode Colors */
        html.dark-mode {
            --bg-main: #0f172a;
            --bg-card: #1e293b;
            --bg-hover: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --border-color: #334155;
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            --success-color: #34d399;
            --danger-color: #f87171;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.4);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Noto Sans Lao', Arial, sans-serif;
            color: var(--text-primary); 
            line-height: 1.6;
            background: var(--bg-main);
            padding: 20px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
            background: var(--primary-gradient);
            color: white;
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }
        
        .page-header::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to right, 
                rgba(255,255,255,0.1) 0%,
                rgba(255,255,255,0) 100%);
            pointer-events: none;
        }
        
        .page-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            position: relative;
            z-index: 1;
        }
        
        .page-header p {
            font-size: 16px;
            opacity: 0.95;
            position: relative;
            z-index: 1;
        }

        /* Search Bar */
        .search-container {
            max-width: 1400px;
            margin: 0 auto 30px;
            background: var(--bg-card);
            padding: 20px;
            border-radius: 16px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .search-wrapper {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .search-input {
            flex: 1;
            padding: 14px 20px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 16px;
            font-family: 'Noto Sans Lao', sans-serif;
            transition: all 0.3s ease;
            background: var(--bg-main);
            color: var(--text-primary);
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-input::placeholder {
            color: var(--text-secondary);
        }

        .search-stats {
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 600;
            padding: 10px 0;
            transition: color 0.3s ease;
        }
        
        .btn-primary {
            color: white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 10px 18px;
            border-radius: 24px;
            box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
        }
        
        .invoice-date {
            font-size: 14px;
            color: #6b7280;
            font-weight: 600;
        }

        .invoice-actions {
            position: absolute;
            top: 28px;
            right: 28px;
            display: flex;
            gap: 8px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .invoice:hover .invoice-actions {
            opacity: 1;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 18px;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }

        .btn-delete:hover {
            background: #dc2626;
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }
        
        .info-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .info-item {
            background: var(--bg-card);
            padding: 16px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }

        html.dark-mode .info-item {
            background: var(--bg-hover);
        }
        
        .info-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .info-label {
            font-size: 11px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 700;
            margin-bottom: 8px;
            transition: color 0.3s ease;
        }
        
        .info-value {
            font-size: 16px;
            color: var(--text-primary);
            font-weight: 600;
            word-break: break-word;
            transition: color 0.3s ease;
        }
        
        .vehicle-type-badge {
            display: inline-block;
            padding: 6px 14px;
            background: var(--primary-gradient);
            color: white;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }
        
        .vehicle-type-badge:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .price-highlight {
           background: var(--success-color);
           color: white;
           padding: 4px 10px;
           border-radius: 6px;
           font-weight: 700;
           transition: all 0.3s ease;
        }

        html.dark-mode .price-highlight {
            box-shadow: 0 0 10px rgba(52, 211, 153, 0.2);
        }

        .invoice-footer {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 2px dashed #e5e7eb;
            text-align: center;
            color: #9ca3af;
            font-size: 13px;
            font-style: italic;
        }
        
        .print-controls {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: flex;
            gap: 12px;
            z-index: 1000;
        }
        
        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-family: 'Noto Sans Lao', sans-serif;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }
        
        .btn-back {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
        }
        
        .btn-print {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .empty-state-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        .empty-state-text {
            font-size: 20px;
            color: #6b7280;
            font-weight: 600;
        }

        /* Enhanced Search Styles */
        .search-container {
            position: relative;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .search-container:focus-within {
            transform: translateY(-2px);
        }

        .search-input {
            width: 100%;
            padding: 16px 20px 16px 50px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }

        .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            transition: all 0.3s ease;
        }

        .search-input:focus + .search-icon {
            color: #667eea;
        }

        .search-stats {
            margin-top: 12px;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 8px;
            font-size: 14px;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(4px);
        }

        .search-stats strong {
            color: #1f2937;
            background: #f3f4f6;
            padding: 2px 8px;
            border-radius: 4px;
        }

        mark {
            background: rgba(102, 126, 234, 0.2);
            color: inherit;
            padding: 0 2px;
            border-radius: 4px;
            animation: highlight 0.3s ease-in-out;
        }

        .hidden {
            display: none;
        }

        @keyframes highlight {
            from {
                background: rgba(102, 126, 234, 0.4);
            }
            to {
                background: rgba(102, 126, 234, 0.2);
            }
        }

        @keyframes countChange {
            0% {
                transform: scale(1.2);
                color: #667eea;
            }
            100% {
                transform: scale(1);
                color: #1f2937;
            }
        }

        /* Alert Messages */
        .alert {
            max-width: 1400px;
            margin: 0 auto 20px;
            padding: 16px 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            animation: slideDown 0.3s ease;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #ef4444;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            animation: fadeIn 0.3s ease;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 16px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: scaleIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .modal-icon {
            width: 50px;
            height: 50px;
            background: #fee2e2;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .modal-header h3 {
            font-size: 20px;
            color: #1f2937;
            margin: 0;
        }

        .modal-body {
            margin-bottom: 24px;
            color: #6b7280;
            line-height: 1.6;
        }

        .modal-body strong {
            color: #1f2937;
            display: block;
            margin-top: 12px;
            padding: 12px;
            background: #f9fafb;
            border-radius: 8px;
            border-left: 4px solid #ef4444;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .modal-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Noto Sans Lao', sans-serif;
        }

        .modal-btn-cancel {
            background: #f3f4f6;
            color: #6b7280;
        }

        .modal-btn-cancel:hover {
            background: #e5e7eb;
        }

        .modal-btn-confirm {
            background: #ef4444;
            color: white;
        }

        .modal-btn-confirm:hover {
            background: #dc2626;
        }
        
        @media print {
            body { 
                background: white; 
                padding: 0; 
            }
            
            .page-header {
                box-shadow: none;
                margin-bottom: 30px;
            }

            .search-container,
            .invoice-actions,
            .print-controls,
            .alert {
                display: none !important;
            }
            
            .invoice-grid {
                display: block;
            }
            
            .invoice { 
                margin-bottom: 30px;
                box-shadow: none;
                page-break-inside: avoid;
            }
            
            .invoice:hover {
                transform: none;
                box-shadow: none;
            }
        }
        
        @media (max-width: 768px) {
            .page-header {
                padding: 20px;
            }
            
            .page-header h1 {
                font-size: 24px;
            }
            
            .invoice-grid {
                grid-template-columns: 1fr;
            }
            
            .invoice {
                padding: 20px;
            }

            .invoice-actions {
                opacity: 1;
                position: static;
                margin-bottom: 16px;
                justify-content: flex-end;
            }
            
            .info-row {
                grid-template-columns: 1fr;
            }
            
            .print-controls {
                position: static;
                justify-content: center;
                margin: 30px 0;
                padding: 20px;
            }
            
            .btn {
                flex: 1;
            }
        }
        </style>
        <!-- Dark Mode Toggle Button Style -->
        <style>
            .theme-toggle {
                position: fixed;
                top: 20px;
                right: 20px;
                width: 50px;
                height: 50px;
                border-radius: 25px;
                background: var(--primary-gradient);
                border: none;
                color: white;
                font-size: 24px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: var(--shadow-lg);
                transition: all 0.3s ease;
                z-index: 1000;
            }

            .theme-toggle:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            }

            .theme-toggle:active {
                transform: translateY(0);
            }
        </style>
    </head>
    <body>
        <button id="themeToggle" class="theme-toggle" title="ສະຫຼັບໂໝດ">🌙</button>
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($_SESSION['success_message']) ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                ❌ <?= htmlspecialchars($_SESSION['error_message']) ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="page-header">
            <h1>🎫 ໃບບິນຈອດລົດ</h1>
            <p>ລະບົບຈັດການຈອດລົດ - Parking Management System</p>
        </div>

        <div class="search-container">
            <div class="search-wrapper">
                <input 
                    type="text" 
                    id="searchInput" 
                    class="search-input" 
                    placeholder="ຄົ້ນຫາດ້ວຍ: ເລກອ້າງອີງ, ທະບຽນລົດ, ຊື່ເຈົ້າຂອງ, ປະເພດລົດ..."
                    autocomplete="off"
                    autofocus
                >
                <span class="search-icon">🔍</span>
            </div>
            <div class="search-stats">
                <span>📊 ຜົນການຄົ້ນຫາ:</span>
                ສະແດງ <strong id="visibleCount"><?= count($allVehicles) ?></strong> 
                ຈາກ <strong><?= count($allVehicles) ?></strong> ລາຍການ
            </div>
        </div>
        
        <?php if (empty($allVehicles)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <div class="empty-state-text">ຍັງບໍ່ມີລາຍການລົດ</div>
            </div>
        <?php else: ?>
            <div class="invoice-grid" id="invoiceGrid">
                <?php $counter = 1; ?>
                <?php foreach ($allVehicles as $r): ?>
                <div class="invoice" 
                     data-ref="<?= htmlspecialchars($r['ref_code']) ?>"
                     data-plate="<?= htmlspecialchars($r['plate']) ?>"
                     data-owner="<?= htmlspecialchars($r['owner_name']) ?>"
                     data-type="<?= htmlspecialchars($r['type_code'] . ' - ' . $r['type_name']) ?>">
                    
                    <div class="invoice-actions">
                        <button 
                            class="btn-delete"
                            onclick="confirmDelete('<?= $r['id'] ?>', '<?= htmlspecialchars($r['ref_code']) ?>', '<?= htmlspecialchars($r['plate']) ?>')"
                            title="ລົບລາຍການນີ້"
                        >
                            🗑️
                        </button>
                    </div>

                    <div class="invoice-header">
                        <div class="invoice-number">
                            Invoice #<?= str_pad($counter++, 4, '0', STR_PAD_LEFT) ?>
                        </div>
                        <div class="invoice-date">
                            ⏰ <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-item">
                            <div class="info-label">📋 ເລກອ້າງອີງ</div>
                            <div class="info-value"><?= htmlspecialchars($r['ref_code']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">🚗 ທະບຽນລົດ</div>
                            <div class="info-value"><?= htmlspecialchars($r['plate']) ?></div>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-item">
                            <div class="info-label">🏷️ ປະເພດລົດ</div>
                            <div class="info-value">
                                <span class="vehicle-type-badge">
                                    <?= htmlspecialchars($r['type_code'] . ' - ' . $r['type_name']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">👤 ເຈົ້າຂອງ</div>
                            <div class="info-value"><?= htmlspecialchars($r['owner_name']) ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item" style="margin-top: 16px;">
                        <div class="info-label">💰 ລາຄາຈອດລົດ</div>
                        <div class="info-value">
                            <span class="price-highlight">
                                <?= number_format($r['price'], 0) ?> ກີບ
                            </span>
                        </div>
                    </div>
                    
                    <div class="invoice-footer">
                        ✨ ຂອບໃຈທີ່ໃຊ້ບໍລິການ ✨
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="print-controls">
            <a href="report.php" class="btn btn-back">⬅️ ກັບຄືນ</a>
            <button onclick="window.print()" class="btn btn-print">🖨️ ພິມໃບບິນ</button>
        </div>

        <!-- Delete Confirmation Modal -->
        <div id="deleteModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-icon">⚠️</div>
                    <h3>ຢືນຢັນການລົບ</h3>
                </div>
                <div class="modal-body">
                    <p>ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການລົບລາຍການນີ້?</p>
                    <strong id="deleteInfo"></strong>
                    <p style="margin-top: 12px; color: #ef4444;">⚠️ ການດຳເນີນການນີ້ບໍ່ສາມາດຍົກເລີກໄດ້!</p>
                </div>
                <div class="modal-actions">
                    <button class="modal-btn modal-btn-cancel" onclick="closeDeleteModal()">
                        ຍົກເລີກ
                    </button>
                    <form id="deleteForm" method="POST" style="display: inline;">
                        <input type="hidden" name="delete_vehicle" value="1">
                        <input type="hidden" name="vehicle_id" id="deleteVehicleId">
                        <button type="submit" class="modal-btn modal-btn-confirm">
                            🗑️ ລົບ
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <script>
        // Enhanced Real-time Search functionality
        const searchInput = document.getElementById('searchInput');
        const invoices = document.querySelectorAll('.invoice');
        const visibleCount = document.getElementById('visibleCount');
        const totalCount = invoices.length;
        let searchTimeout;

        function highlightMatch(text, searchTerm) {
            if (!searchTerm) return text;
            const regex = new RegExp(`(${searchTerm})`, 'gi');
            return text.replace(regex, '<mark>$1</mark>');
        }

        function performSearch() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            let visible = 0;

            // Reset all highlights first
            document.querySelectorAll('mark').forEach(mark => {
                const text = mark.textContent;
                mark.replaceWith(text);
            });

            invoices.forEach(invoice => {
                const refCode = invoice.dataset.ref.toLowerCase();
                const plate = invoice.dataset.plate.toLowerCase();
                const owner = invoice.dataset.owner.toLowerCase();
                const type = invoice.dataset.type.toLowerCase();

                const matches = refCode.includes(searchTerm) || 
                              plate.includes(searchTerm) || 
                              owner.includes(searchTerm) || 
                              type.includes(searchTerm);

                if (matches) {
                    invoice.classList.remove('hidden');
                    visible++;

                    // Highlight matching text if there's a search term
                    if (searchTerm) {
                        invoice.querySelectorAll('.info-value').forEach(element => {
                            const originalText = element.textContent;
                            if (originalText.toLowerCase().includes(searchTerm)) {
                                element.innerHTML = highlightMatch(originalText, searchTerm);
                            }
                        });
                    }
                } else {
                    invoice.classList.add('hidden');
                }
            });

            visibleCount.textContent = visible;
            
            // Animate count changes
            visibleCount.style.animation = 'none';
            visibleCount.offsetHeight; // Trigger reflow
            visibleCount.style.animation = 'countChange 0.3s ease-in-out';
        }

        // Debounced search for better performance
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(performSearch, 150); // 150ms delay
        });

        // Delete confirmation
        let deleteVehicleId = null;

        function confirmDelete(id, refCode, plate) {
            deleteVehicleId = id;
            document.getElementById('deleteVehicleId').value = id;
            document.getElementById('deleteInfo').textContent = 
                `ເລກອ້າງອີງ: ${refCode} | ທະບຽນ: ${plate}`;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            deleteVehicleId = null;
        }

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'all 0.3s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Include header after all redirects
require __DIR__ . '/../includes/header.php';
?>

<style>
:root {
    --gradient-1: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --gradient-2: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --gradient-3: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --gradient-4: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    --shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.1);
    --shadow-hover: 0 20px 40px -5px rgba(0, 0, 0, 0.2);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: var(--bg-main);
    min-height: 100vh;
    transition: background-color 0.3s ease;
}

.report-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
}

.report-header {
    background: var(--gradient-1);
    color: white;
    padding: 2.5rem;
    border-radius: 20px;
    margin-bottom: 2rem;
    box-shadow: var(--shadow);
    animation: slideDown 0.6s ease-out;
}

.report-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.report-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0;
    text-shadow: 0 2px 10px rgba(0,0,0,0.2);
}

.report-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.report-btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    cursor: pointer;
    font-family: 'Noto Sans Lao', sans-serif;
    font-size: 15px;
}

.report-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.btn-back {
    background: rgba(255,255,255,0.2);
    color: white;
    border: 2px solid rgba(255,255,255,0.3);
}

.btn-export {
    background: white;
    color: #10b981;
}

.btn-print {
    background: white;
    color: #667eea;
}

/* Summary Cards */
.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
    animation: fadeInUp 0.6s ease-out 0.2s both;
}

.summary-card {
    background: var(--bg-card);
    border-radius: 20px;
    padding: 2.5rem;
    box-shadow: var(--shadow);
    position: relative;
    overflow: hidden;
    transition: all 0.4s ease;
    border: 1px solid var(--border-color);
    color: var(--text-primary);
}

.summary-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
}

.summary-card.primary::before { background: var(--gradient-1); }
.summary-card.secondary::before { background: var(--gradient-2); }

.summary-card:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow-hover);
}

.summary-icon {
    width: 70px;
    height: 70px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    margin-bottom: 1.5rem;
    color: white;
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
}

.summary-card.primary .summary-icon { background: var(--gradient-1); }
.summary-card.secondary .summary-icon { background: var(--gradient-2); }

.summary-label {
    font-size: 0.9rem;
    color: var(--text-secondary);
    font-weight: 600;
    margin-bottom: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: color 0.3s ease;
}

.summary-value {
    font-size: 3rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
    margin-bottom: 0.5rem;
    transition: color 0.3s ease;
}

.summary-unit {
    font-size: 1rem;
    color: var(--text-secondary);
    font-weight: 500;
    transition: color 0.3s ease;
}

/* Analysis Section */
.analysis-section {
    background: var(--bg-card);
    color: var(--text-primary);
    border-radius: 20px;
    padding: 2.5rem;
    box-shadow: var(--shadow);
    margin-bottom: 2rem;
    animation: fadeInUp 0.6s ease-out 0.4s both;
    border: 1px solid var(--border-color);
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}

.section-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.type-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.type-card {
    border-radius: 16px;
    padding: 2rem;
    color: white;
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    transition: all 0.4s ease;
    position: relative;
    overflow: hidden;
}

.type-card::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    transition: all 0.6s ease;
}

.type-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 12px 30px rgba(0,0,0,0.25);
}

.type-card:hover::after {
    top: -100%;
    right: -100%;
}

.type-card.mot { background: var(--gradient-1); }
.type-card.bic { background: var(--gradient-2); }
.type-card.car { background: var(--gradient-3); }

.type-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    position: relative;
    z-index: 1;
}

.type-info h6 {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0.5rem 0 0 0;
}

.type-badge {
    background: rgba(255,255,255,0.25);
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    backdrop-filter: blur(10px);
}

.type-icon {
    font-size: 3rem;
    opacity: 0.9;
}

.type-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
    position: relative;
    z-index: 1;
}

.type-stat h4 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0.5rem 0 0 0;
}

.type-stat small {
    opacity: 0.85;
    font-size: 0.9rem;
}

.type-progress {
    position: relative;
    z-index: 1;
}

.progress-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
    font-size: 0.9rem;
}

.progress-bar-container {
    height: 8px;
    background: rgba(255,255,255,0.3);
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar-fill {
    height: 100%;
    background: white;
    border-radius: 10px;
    transition: width 1s ease;
    box-shadow: 0 0 10px rgba(255,255,255,0.5);
}

        /* Table Section */
        .table-section {
            background: var(--bg-card);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            animation: fadeInUp 0.6s ease-out 0.6s both;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .table-header {
            background: var(--gradient-1);
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
        }

        .modern-table thead {
            background: var(--bg-hover);
        }

        .modern-table thead th {
            padding: 1.25rem 1.5rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            border-bottom: 2px solid var(--border-color);
            white-space: nowrap;
            color: var(--text-secondary);
            transition: all 0.3s ease;
        }

        .modern-table tbody td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .modern-table tbody tr:hover {
            background: var(--bg-hover);
        }

        .modern-table tfoot td {
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            background: var(--bg-hover);
            border-top: 2px solid var(--border-color);
        }

        .badge-custom {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .badge-primary {
            background: var(--primary-gradient);
            color: white;
            box-shadow: var(--shadow);
        }

        .badge-secondary {
            background: var(--bg-hover);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }.table-header h3 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
}

.toggle-btn {
    background: rgba(255,255,255,0.2);
    border: 2px solid rgba(255,255,255,0.3);
    color: white;
    padding: 0.5rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: 'Noto Sans Lao', sans-serif;
}

.toggle-btn:hover {
    background: rgba(255,255,255,0.3);
    border-color: rgba(255,255,255,0.5);
}

.modern-table-container {
    overflow-x: auto;
    background: var(--bg-card);
    transition: background-color 0.3s ease;
}

.modern-table {
    width: 100%;
    border-collapse: collapse;
    color: var(--text-primary);
    transition: color 0.3s ease;
}

.modern-table thead {
    background: var(--bg-hover);
    transition: background-color 0.3s ease;
}

.modern-table thead th {
    padding: 1.25rem 1.5rem;
    text-align: left;
    font-weight: 700;
    color: var(--text-primary);
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    border-bottom: 2px solid var(--border-color);
    white-space: nowrap;
    transition: color 0.3s ease, border-color 0.3s ease;
}

.modern-table tbody td {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    color: var(--text-primary);
    transition: border-color 0.3s ease, color 0.3s ease;
}

.modern-table tbody tr {
    transition: background-color 0.3s ease;
}

.modern-table tbody tr:hover {
    background: var(--bg-hover);
}

.modern-table tfoot {
    background: var(--bg-hover);
    color: var(--text-primary);
    font-weight: 700;
    transition: background-color 0.3s ease, color 0.3s ease;
}

        .modern-table {
            width: 100%;
            border-collapse: collapse;
            color: var(--text-primary);
            background-color: var(--bg-card);
        }.modern-table thead {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.modern-table thead th {
    padding: 1.25rem 1.5rem;
    text-align: left;
    font-weight: 700;
    color: #1f2937;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    border-bottom: 2px solid #e5e7eb;
    white-space: nowrap;
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
}

.modern-table tfoot {
    background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
    color: white;
    font-weight: 700;
}

.modern-table tfoot td {
    padding: 1.25rem 1.5rem;
    border: none;
}

.badge-custom {
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-block;
    white-space: nowrap;
}

.badge-primary {
    background: var(--gradient-1);
    color: white;
    box-shadow: 0 2px 6px rgba(102, 126, 234, 0.3);
}

.badge-secondary {
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
    color: white;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #9ca3af;
}

.empty-state-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
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

/* Responsive Design */
@media (max-width: 1200px) {
    .type-cards-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    }
}

@media (max-width: 768px) {
    .report-container {
        padding: 1rem;
    }
    
    .report-header {
        padding: 2rem 1.5rem;
    }
    
    .report-header h1 {
        font-size: 1.75rem;
    }
    
    .report-header-content {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .report-actions {
        width: 100%;
    }
    
    .report-btn {
        flex: 1;
        justify-content: center;
    }
    
    .summary-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .summary-card {
        padding: 2rem;
    }
    
    .summary-value {
        font-size: 2.5rem;
    }
    
    .analysis-section {
        padding: 1.5rem;
    }
    
    .section-title {
        font-size: 1.5rem;
    }
    
    .type-cards-grid {
        grid-template-columns: 1fr;
    }
    
    .table-header {
        flex-direction: column;
        align-items: flex-start;
        padding: 1.25rem 1.5rem;
    }
    
    .table-header h3 {
        font-size: 1.25rem;
    }
    
    .toggle-btn {
        width: 100%;
    }
    
    .modern-table thead th,
    .modern-table tbody td,
    .modern-table tfoot td {
        padding: 1rem;
        font-size: 0.9rem;
    }
}

@media (max-width: 480px) {
    .report-header {
        padding: 1.5rem 1rem;
    }
    
    .report-header h1 {
        font-size: 1.5rem;
    }
    
    .summary-icon {
        width: 60px;
        height: 60px;
        font-size: 2rem;
    }
    
    .summary-value {
        font-size: 2rem;
    }
    
    .type-card {
        padding: 1.5rem;
    }
    
    .type-icon {
        font-size: 2.5rem;
    }
    
    .type-stat h4 {
        font-size: 1.5rem;
    }
    
    .section-title {
        font-size: 1.25rem;
    }
    
    .badge-custom {
        font-size: 0.75rem;
        padding: 0.3rem 0.8rem;
    }
}
</style>

<div class="report-container">
    <!-- Header -->
    <div class="report-header">
        <div class="report-header-content">
            <h1>📊 ລາຍງານລາຍຮັບລະບົບຈອດລົດ</h1>
            <div class="report-actions">
                <a class="report-btn btn-back" href="view_vehicle.php">⬅️ ກັບຄືນ</a>
                <a class="report-btn btn-export" href="?export=csv">📥 Export CSV</a>
                <a class="report-btn btn-print" href="?print=invoices" target="_blank">🖨️ ພິມໃບບິນ</a>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="summary-grid">
        <div class="summary-card primary">
            <div class="summary-icon">🚗</div>
            <div class="summary-label">ຈຳນວນລົດທັງໝົດ</div>
            <div class="summary-value counter-value" data-start="0" data-end="<?= $totalVehicles ?>" data-duration="2000">0</div>
            <div class="summary-unit">ຄັນ</div>
        </div>
        
        <div class="summary-card secondary">
            <div class="summary-icon">💰</div>
            <div class="summary-label">ລາຍຮັບທັງໝົດ</div>
            <div class="summary-value counter-value" data-start="0" data-end="<?= $totalRevenue ?>" data-duration="2000">0</div>
            <div class="summary-unit">ກີບ</div>
        </div>
    </div>

    <!-- Vehicle Type Analysis -->
    <div class="analysis-section">
        <h3 class="section-title">📈 ວິເຄາະຕາມປະເພດລົດ</h3>
        
        <div class="type-cards-grid">
            <?php 
            $typeClasses = [
                'MOT' => 'mot',
                'BIC' => 'bic',
                'CAR' => 'car',
            ];
            
            $typeIcons = [
                'MOT' => '🏍️',
                'BIC' => '🚲',
                'CAR' => '🚗',
            ];
            
            foreach ($byType as $r): 
                $typeClass = $typeClasses[$r['code']] ?? 'mot';
                $typeIcon = $typeIcons[$r['code']] ?? '🚙';
                $percentage = $totalRevenue > 0 ? ($r['total'] / $totalRevenue * 100) : 0;
            ?>
            <div class="type-card <?= $typeClass ?>">
                <div class="type-header">
                    <div class="type-info">
                        <span class="type-badge"><?= htmlspecialchars($r['code']) ?></span>
                        <h6><?= htmlspecialchars($r['name']) ?></h6>
                    </div>
                    <div class="type-icon"><?= $typeIcon ?></div>
                </div>
                
                <div class="type-stats">
                    <div class="type-stat">
                        <small>ຈຳນວນ</small>
                        <h4><?= number_format($r['cnt']) ?></h4>
                        <small>ຄັນ</small>
                    </div>
                    <div class="type-stat">
                        <small>ລາຍຮັບ</small>
                        <h4><?= number_format($r['total'], 0) ?></h4>
                        <small>ກີບ</small>
                    </div>
                </div>
                
                <div class="type-progress">
                    <div class="progress-info">
                        <span>ສ່ວນແບ່ງ</span>
                        <strong><?= number_format($percentage, 1) ?>%</strong>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-bar-fill" style="width: 0%" data-width="<?= $percentage ?>"></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Summary Table -->
        <div class="modern-table-container">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>ປະເພດລົດ</th>
                        <th style="text-align: center;">ຈຳນວນ</th>
                        <th style="text-align: right;">ລາຍຮັບ</th>
                        <th style="text-align: right;">ສ່ວນແບ່ງ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($byType as $r): 
                        $percentage = $totalRevenue > 0 ? ($r['total'] / $totalRevenue * 100) : 0;
                    ?>
                    <tr>
                        <td>
                            <span class="badge-custom badge-primary"><?= htmlspecialchars($r['code']) ?></span>
                            <strong style="margin-left: 0.5rem;"><?= htmlspecialchars($r['name']) ?></strong>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge-custom badge-secondary"><?= number_format($r['cnt']) ?> ຄັນ</span>
                        </td>
                        <td style="text-align: right;">
                            <strong style="color: #10b981;"><?= number_format($r['total'], 2) ?> ກີບ</strong>
                        </td>
                        <td style="text-align: right;">
                            <span style="color: #6b7280; font-weight: 600;"><?= number_format($percentage, 1) ?>%</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td>ລວມທັງໝົດ</td>
                        <td style="text-align: center;"><?= number_format($totalVehicles) ?> ຄັນ</td>
                        <td style="text-align: right;"><?= number_format($totalRevenue, 2) ?> ກີບ</td>
                        <td style="text-align: right;">100%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Recent Vehicles -->
    <div class="table-section">
        <div class="table-header">
            <h3>📋 ລາຍການລົດລ້າສຸດ (10 ລາຍການ)</h3>
            <button class="toggle-btn" id="toggleButton">ປິດ ▼</button>
        </div>
        <div id="recentVehiclesTable">
            <div class="modern-table-container">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>ເລກອ້າງອີງ</th>
                            <th>ເລກທະບຽນ</th>
                            <th>ປະເພດ</th>
                            <th>ຊື່ເຈົ້າຂອງ</th>
                            <th style="text-align: right;">ລາຄາ</th>
                            <th>ວັນທີ່-ເວລາ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent)): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">📭</div>
                                        <p>ຍັງບໍ່ມີຂໍ້ມູນລົດ</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent as $r): ?>
                            <tr>
                                <td>
                                    <span class="badge-custom badge-primary"><?= htmlspecialchars($r['ref_code']) ?></span>
                                </td>
                                <td><strong><?= htmlspecialchars($r['plate']) ?></strong></td>
                                <td>
                                    <span class="badge-custom badge-secondary">
                                        <?= htmlspecialchars($r['type_code'] . ' - ' . $r['type_name']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($r['owner_name']) ?></td>
                                <td style="text-align: right;">
                                    <strong style="color: #10b981; font-size: 1.05rem;">
                                        <?= number_format($r['price'], 0) ?> ກີບ
                                    </strong>
                                </td>
                                <td style="color: #6b7280; font-size: 0.9rem;">
                                    <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dark mode toggle functionality
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        // Set initial icon based on current theme
        if (document.documentElement.classList.contains('dark-mode')) {
            themeToggle.textContent = '☀️';
        }

        themeToggle.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark-mode');
            const isDark = document.documentElement.classList.contains('dark-mode');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            themeToggle.textContent = isDark ? '☀️' : '🌙';

            // Add animation to the toggle button
            themeToggle.style.transform = 'scale(1.2)';
            setTimeout(() => {
                themeToggle.style.transform = 'scale(1)';
            }, 200);
        });
    }

    // Handle system theme changes
    if (window.matchMedia) {
        const colorSchemeQuery = window.matchMedia('(prefers-color-scheme: dark)');
        colorSchemeQuery.addEventListener('change', (e) => {
            if (!localStorage.getItem('theme')) {
                document.documentElement.classList.toggle('dark-mode', e.matches);
                if (themeToggle) {
                    themeToggle.textContent = e.matches ? '☀️' : '🌙';
                }
            }
        });
    }

    // Toggle button for recent vehicles table
    const toggleButton = document.getElementById('toggleButton');
    const tableContent = document.getElementById('recentVehiclesTable');
    
    if (toggleButton && tableContent) {
        toggleButton.addEventListener('click', function() {
            if (tableContent.style.display === 'none') {
                tableContent.style.display = '';
                this.textContent = 'ປິດ ▼';
            } else {
                tableContent.style.display = 'none';
                this.textContent = 'ເປີດ ▶';
            }
        });
    }
    
    // Animate progress bars
    setTimeout(() => {
        const progressBars = document.querySelectorAll('.progress-bar-fill');
        progressBars.forEach(bar => {
            const targetWidth = bar.getAttribute('data-width');
            bar.style.width = targetWidth + '%';
        });
    }, 300);
    
    // Animate counter numbers
    function animateValue(element, start, end, duration) {
        const startTime = performance.now();
        const isDecimal = end % 1 !== 0;
        
        function updateValue(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Easing function for smooth animation
            const easeOutQuart = 1 - Math.pow(1 - progress, 4);
            const current = start + (end - start) * easeOutQuart;
            
            if (isDecimal) {
                element.textContent = current.toLocaleString('en-US', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                });
            } else {
                element.textContent = Math.floor(current).toLocaleString('en-US');
            }
            
            if (progress < 1) {
                requestAnimationFrame(updateValue);
            } else {
                element.textContent = end.toLocaleString('en-US', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                });
            }
        }
        
        requestAnimationFrame(updateValue);
    }
    
    const counterElements = document.querySelectorAll('.counter-value');
    counterElements.forEach(element => {
        const startValue = parseInt(element.getAttribute('data-start'));
        const endValue = parseInt(element.getAttribute('data-end'));
        const duration = parseInt(element.getAttribute('data-duration'));
        
        animateValue(element, startValue, endValue, duration);
    });
});
</script>
               