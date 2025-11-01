<?php
/**
 * ສະຄິບຕັ້ງຄ່າ Reset Password
 */

require __DIR__ . '/../includes/db.php';

echo "🔧 ກຳລັງຕັ້ງຄ່າລະບົບ Reset Password...\n\n";

try {
    // ເພີ່ມຟິວໃນຕາຕະລາງ login ສຳລັບ reset token
    echo "📝 ເພີ່ມຟິວ reset token ໃນຕາຕະລາງ login...\n";
    
    try {
        $pdo->exec("ALTER TABLE login ADD COLUMN reset_token VARCHAR(255) NULL");
        echo "   ✅ ເພີ່ມ reset_token ແລ້ວ\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "   ℹ️ reset_token ມີຢູ່ແລ້ວ\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE login ADD COLUMN reset_token_expires TIMESTAMP NULL");
        echo "   ✅ ເພີ່ມ reset_token_expires ແລ້ວ\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "   ℹ️ reset_token_expires ມີຢູ່ແລ້ວ\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE login ADD COLUMN reset_token_used TINYINT(1) DEFAULT 0");
        echo "   ✅ ເພີ່ມ reset_token_used ແລ້ວ\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "   ℹ️ reset_token_used ມີຢູ່ແລ້ວ\n";
        } else {
            throw $e;
        }
    }
    
    // ສ້າງ index ສຳລັບ reset_token
    try {
        $pdo->exec("CREATE INDEX idx_reset_token ON login(reset_token)");
        echo "   ✅ ສ້າງ index idx_reset_token ແລ້ວ\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "   ℹ️ index idx_reset_token ມີຢູ່ແລ້ວ\n";
        } else {
            throw $e;
        }
    }
    
    // ສ້າງຕາຕະລາງບັນທຶກການ reset password
    echo "\n📋 ສ້າງຕາຕະລາງ password_reset_log...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS password_reset_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            reset_token VARCHAR(255) NOT NULL,
            requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            status ENUM('pending', 'completed', 'expired', 'cancelled') DEFAULT 'pending',
            INDEX idx_username (username),
            INDEX idx_reset_token (reset_token),
            INDEX idx_status (status),
            INDEX idx_requested_at (requested_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✅ ສ້າງຕາຕະລາງ password_reset_log ແລ້ວ\n";
    
    echo "\n🎉 ຕັ້ງຄ່າລະບົບ Reset Password ສຳເລັດແລ້ວ!\n";
    echo "📌 ຟີເຈີໃໝ່ທີ່ມີ:\n";
    echo "   - ການສ້າງ reset token\n";
    echo "   - ການກວດສອບ token expiration\n";
    echo "   - ການບັນທຶກ reset log\n";
    echo "   - ການປົກປ້ອງຄວາມປອດໄພ\n";
    
} catch (Exception $e) {
    echo "❌ ຜິດພາດ: " . $e->getMessage() . "\n";
}
?>
