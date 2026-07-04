<?php
/**
 * Database Connection File
 * Connects to the Aiven MySQL database using PDO,
 * and auto-creates any missing tables/columns this project needs.
 */

// Define the absolute path to the .env file in the project root

//termu mobile path
//$envPath = '/storage/emulated/0/Download/raja-ram-and-sons-musical-instruments/.env';

//pc path
define('ROOT_PATH', dirname(__DIR__));
$envPath = ROOT_PATH . '/.env';

// Priority 1: local .env file (used only for local/PC/mobile dev, NOT on Vercel).
// Priority 2: real environment variables (this is what Vercel Project Settings ->
//             Environment Variables populates in production).
if (file_exists($envPath)) {
    $env = parse_ini_file($envPath);
    if ($env === false) {
        die("ERROR: Found a .env file but failed to parse it. Please check the file format (KEY=VALUE per line, no quotes needed).");
    }
    $host = $env['DB_HOST'] ?? null;
    $port = $env['DB_PORT'] ?? null;
    $db   = $env['DB_NAME'] ?? null;
    $user = $env['DB_USER'] ?? null;
    $pass = $env['DB_PASS'] ?? null;
} else {
    $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: null;
    $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: null;
    $db   = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: null;
    $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: null;
    $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: null;
}

// If any required value is still missing, fail with a clear, actionable message
// instead of a vague PDO connection error.
$missing = [];
foreach (['DB_HOST' => $host, 'DB_PORT' => $port, 'DB_NAME' => $db, 'DB_USER' => $user, 'DB_PASS' => $pass] as $key => $val) {
    if ($val === null || $val === '') {
        $missing[] = $key;
    }
}
if (!empty($missing)) {
    die("ERROR: Missing database configuration: " . implode(', ', $missing) .
        ". On Vercel, set these in Project Settings -> Environment Variables. Locally, create a .env file in the project root.");
}

try {
    // Construct the DSN (Data Source Name) for MySQL
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    // Create the PDO connection instance
    $pdo = new PDO($dsn, $user, $pass, $options);

} catch (PDOException $e) {
    die("Connection Failed: " . $e->getMessage());
}

// ==========================================================
// AUTO SCHEMA CREATION
// Har request pe check karta hai ki saari tables/columns
// exist karti hain ya nahi. Agar nahi, to create/add kar deta hai.
// Isse "table doesn't exist" / "column doesn't exist" errors
// dobara dobara fix nahi karne padenge.
// ==========================================================

function ensureSchema(PDO $pdo) {

    // ---- 1. Saari tables ka structure define karo ----
    // Agar table exist nahi karti, IF NOT EXISTS se create ho jayegi.
    $tables = [

        "admins" => "
            CREATE TABLE IF NOT EXISTS admins (
                admin_id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                username VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(50) NOT NULL DEFAULT 'Staff',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",

        "users" => "
            CREATE TABLE IF NOT EXISTS users (
                u_id INT AUTO_INCREMENT PRIMARY KEY,
                u_name VARCHAR(150) NOT NULL,
                email_id VARCHAR(150) NOT NULL UNIQUE,
                mobile_number VARCHAR(20) DEFAULT NULL,
                password VARCHAR(255) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",

        "categories" => "
            CREATE TABLE IF NOT EXISTS categories (
                c_id INT AUTO_INCREMENT PRIMARY KEY,
                category_name VARCHAR(150) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",

        "products" => "
            CREATE TABLE IF NOT EXISTS products (
                pid INT AUTO_INCREMENT PRIMARY KEY,
                catid INT DEFAULT NULL,
                product_name VARCHAR(200) NOT NULL,
                description TEXT,
                image VARCHAR(255) DEFAULT NULL,
                price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                gst_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                stock_quantity INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_products_category FOREIGN KEY (catid) REFERENCES categories(c_id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",

        "carts" => "
            CREATE TABLE IF NOT EXISTS carts (
                cart_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_carts_user FOREIGN KEY (user_id) REFERENCES users(u_id) ON DELETE CASCADE,
                CONSTRAINT fk_carts_product FOREIGN KEY (product_id) REFERENCES products(pid) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",

        "wishlist" => "
            CREATE TABLE IF NOT EXISTS wishlist (
                w_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_wishlist_user FOREIGN KEY (user_id) REFERENCES users(u_id) ON DELETE CASCADE,
                CONSTRAINT fk_wishlist_product FOREIGN KEY (product_id) REFERENCES products(pid) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",

        "orders" => "
            CREATE TABLE IF NOT EXISTS orders (
                oid INT AUTO_INCREMENT PRIMARY KEY,
                uid INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL DEFAULT 1,
                total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                shipping_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                payment_method VARCHAR(50) NOT NULL DEFAULT 'COD',
                status VARCHAR(50) NOT NULL DEFAULT 'Pending',
                tracking_no VARCHAR(100) DEFAULT NULL,
                courier_website VARCHAR(255) DEFAULT NULL,
                admin_notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_orders_user FOREIGN KEY (uid) REFERENCES users(u_id) ON DELETE CASCADE,
                CONSTRAINT fk_orders_product FOREIGN KEY (product_id) REFERENCES products(pid) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",

        "order_tracking" => "
            CREATE TABLE IF NOT EXISTS order_tracking (
                track_id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                status VARCHAR(50) NOT NULL,
                message TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_tracking_order FOREIGN KEY (order_id) REFERENCES orders(oid) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",

        "order_shipping_addresses" => "
            CREATE TABLE IF NOT EXISTS order_shipping_addresses (
                o_ship_id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                user_id INT NOT NULL,
                full_address TEXT NOT NULL,
                city_name VARCHAR(100) DEFAULT NULL,
                zip_code VARCHAR(20) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_shipaddr_order FOREIGN KEY (order_id) REFERENCES orders(oid) ON DELETE CASCADE,
                CONSTRAINT fk_shipaddr_user FOREIGN KEY (user_id) REFERENCES users(u_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",

        "product_reviews" => "
            CREATE TABLE IF NOT EXISTS product_reviews (
                review_id INT AUTO_INCREMENT PRIMARY KEY,
                pid INT NOT NULL,
                user_id INT NOT NULL,
                rating INT NOT NULL DEFAULT 5,
                review_text TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_review_product FOREIGN KEY (pid) REFERENCES products(pid) ON DELETE CASCADE,
                CONSTRAINT fk_review_user FOREIGN KEY (user_id) REFERENCES users(u_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",

        "offline_sales" => "
            CREATE TABLE IF NOT EXISTS offline_sales (
                offline_sales_id INT AUTO_INCREMENT PRIMARY KEY,
                buyer_name VARCHAR(150) DEFAULT NULL,
                mobile_number VARCHAR(20) DEFAULT NULL,
                product_name VARCHAR(200) NOT NULL,
                quantity INT NOT NULL DEFAULT 1,
                price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                sum_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                field_name VARCHAR(100) DEFAULT NULL,
                billed_by VARCHAR(150) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
    ];

    // Tables ko sahi order me banao (jinke foreign keys hain wo baad me)
    $order = [
        "categories", "users", "admins", "products",
        "carts", "wishlist", "orders", "order_tracking",
        "order_shipping_addresses", "product_reviews", "offline_sales"
    ];

    foreach ($order as $tableName) {
        $pdo->exec($tables[$tableName]);
    }

    // ---- 2. Agar table pehle se exist karti thi par koi column missing hai, wo add karo ----
    // [table => [column => "column definition SQL"]]
    $expectedColumns = [
        "admins" => [
            "admin_id" => "INT AUTO_INCREMENT PRIMARY KEY",
            "name" => "VARCHAR(150) NOT NULL",
            "username" => "VARCHAR(100) NOT NULL",
            "password" => "VARCHAR(255) NOT NULL",
            "role" => "VARCHAR(50) NOT NULL DEFAULT 'Staff'",
            "created_at" => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        ],
        "users" => [
            "u_id" => "INT AUTO_INCREMENT PRIMARY KEY",
            "u_name" => "VARCHAR(150) NOT NULL",
            "email_id" => "VARCHAR(150) NOT NULL",
            "mobile_number" => "VARCHAR(20) DEFAULT NULL",
            "password" => "VARCHAR(255) NOT NULL",
            "status" => "VARCHAR(20) NOT NULL DEFAULT 'active'",
            "created_at" => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        ],
        "categories" => [
            "c_id" => "INT AUTO_INCREMENT PRIMARY KEY",
            "category_name" => "VARCHAR(150) NOT NULL",
        ],
        "products" => [
            "pid" => "INT AUTO_INCREMENT PRIMARY KEY",
            "catid" => "INT DEFAULT NULL",
            "product_name" => "VARCHAR(200) NOT NULL",
            "description" => "TEXT",
            "image" => "VARCHAR(255) DEFAULT NULL",
            "price" => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
            "gst_rate" => "DECIMAL(5,2) NOT NULL DEFAULT 0.00",
            "stock_quantity" => "INT NOT NULL DEFAULT 0",
            "created_at" => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        ],
        "carts" => [
            "cart_id" => "INT AUTO_INCREMENT PRIMARY KEY",
            "user_id" => "INT NOT NULL",
            "product_id" => "INT NOT NULL",
            "quantity" => "INT NOT NULL DEFAULT 1",
            "created_at" => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        ],
        "wishlist" => [
            "w_id" => "INT AUTO_INCREMENT PRIMARY KEY",
            "user_id" => "INT NOT NULL",
            "product_id" => "INT NOT NULL",
            "created_at" => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        ],
        "orders" => [
            "oid" => "INT AUTO_INCREMENT PRIMARY KEY",
            "uid" => "INT NOT NULL",
            "product_id" => "INT NOT NULL",
            "quantity" => "INT NOT NULL DEFAULT 1",
            "total_price" => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
            "shipping_amount" => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
            "payment_method" => "VARCHAR(50) NOT NULL DEFAULT 'COD'",
            "status" => "VARCHAR(50) NOT NULL DEFAULT 'Pending'",
            "tracking_no" => "VARCHAR(100) DEFAULT NULL",
            "courier_website" => "VARCHAR(255) DEFAULT NULL",
            "admin_notes" => "TEXT",
            "created_at" => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        ],
        "order_tracking" => [
            "track_id" => "INT AUTO_INCREMENT PRIMARY KEY",
            "order_id" => "INT NOT NULL",
            "status" => "VARCHAR(50) NOT NULL",
            "message" => "TEXT",
            "created_at" => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        ],
        "order_shipping_addresses" => [
            "o_ship_id" => "INT AUTO_INCREMENT PRIMARY KEY",
            "order_id" => "INT NOT NULL",
            "user_id" => "INT NOT NULL",
            "full_address" => "TEXT NOT NULL",
            "city_name" => "VARCHAR(100) DEFAULT NULL",
            "zip_code" => "VARCHAR(20) DEFAULT NULL",
            "created_at" => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        ],
        "product_reviews" => [
            "review_id" => "INT AUTO_INCREMENT PRIMARY KEY",
            "pid" => "INT NOT NULL",
            "user_id" => "INT NOT NULL",
            "rating" => "INT NOT NULL DEFAULT 5",
            "review_text" => "TEXT",
            "created_at" => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        ],
        "offline_sales" => [
            "offline_sales_id" => "INT AUTO_INCREMENT PRIMARY KEY",
            "buyer_name" => "VARCHAR(150) DEFAULT NULL",
            "mobile_number" => "VARCHAR(20) DEFAULT NULL",
            "product_name" => "VARCHAR(200) NOT NULL",
            "quantity" => "INT NOT NULL DEFAULT 1",
            "price" => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
            "sum_total" => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
            "field_name" => "VARCHAR(100) DEFAULT NULL",
            "billed_by" => "VARCHAR(150) DEFAULT NULL",
            "created_at" => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        ],
    ];

    foreach ($expectedColumns as $tableName => $columns) {
        // Is table me abhi konse columns hain wo nikalo
        $stmt = $pdo->query("SHOW COLUMNS FROM `$tableName`");
        $existingCols = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($columns as $colName => $colDef) {
            if (!in_array($colName, $existingCols)) {
                // PRIMARY KEY / AUTO_INCREMENT wale column already CREATE TABLE se ban jate hain,
                // ALTER TABLE me unhe dobara add karne ki koshish na karo
                if (stripos($colDef, 'AUTO_INCREMENT') !== false) {
                    continue;
                }
                try {
                    $pdo->exec("ALTER TABLE `$tableName` ADD COLUMN `$colName` $colDef");
                } catch (PDOException $e) {
                    // Agar add karte waqt bhi koi issue aaye (rare case), to silently skip karo
                    // taaki page crash na ho - error sirf log ho jaye.
                    error_log("Could not add column $colName to $tableName: " . $e->getMessage());
                }
            }
        }
    }
}

try {
    ensureSchema($pdo);
} catch (PDOException $e) {
    die("Schema Setup Failed: " . $e->getMessage());
}

try {
    $catCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ($catCount == 0) {
        $pdo->exec("INSERT INTO categories (c_id, category_name) VALUES
            (1, 'Classical Strings'),
            (2, 'Western & Keys'),
            (3, 'Rhythm & Beats')
            ON DUPLICATE KEY UPDATE category_name = VALUES(category_name)");
    }
} catch (PDOException $e) {
    error_log("Default category seed failed: " . $e->getMessage());
}
?>
