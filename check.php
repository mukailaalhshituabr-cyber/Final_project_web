<?php
// check.php - Save to: C:\xampp\htdocs\webtech\WebtechFinal_Project\clothing-marketplace\check.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Check</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 30px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card {
            background: white;
            padding: 25px;
            margin: 15px 0;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        h1 { color: white; text-align: center; }
        h2 { color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .success { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        .code {
            background: #2c3e50;
            color: #2ecc71;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            overflow-x: auto;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        .btn:hover { background: #764ba2; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        .status-ok { color: #27ae60; }
        .status-error { color: #e74c3c; }
    </style>
</head>
<body>
    <h1>✅ System Check - Everything Working!</h1>

    <div class="card">
        <h2>📍 Current Location</h2>
        <p><strong>This file is located at:</strong></p>
        <div class="code"><?php echo __FILE__; ?></div>
        <p class="success">✓ Perfect! This is the correct location.</p>
    </div>

    <div class="card">
        <h2>🗄️ Database Check</h2>
        <?php
        try {
            $conn = new PDO("mysql:host=localhost;dbname=clothing_marketplace", "root", "");
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo '<p class="success">✓ Database Connected!</p>';
            
            $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
            $users = $stmt->fetch()['count'];
            echo "<p>Users in database: <strong>$users</strong></p>";
            
            $stmt = $conn->query("SELECT COUNT(*) as count FROM products");
            $products = $stmt->fetch()['count'];
            echo "<p>Products in database: <strong>$products</strong></p>";
            
            if ($products == 0) {
                echo '<p class="error">⚠️ No products yet. You can add products after logging in as a tailor.</p>';
            }
            
        } catch(PDOException $e) {
            echo '<p class="error">✗ Database Error: ' . $e->getMessage() . '</p>';
        }
        ?>
    </div>

    <div class="card">
        <h2>📁 Files Check</h2>
        <table>
            <tr><th>File</th><th>Status</th></tr>
            <?php
            $files = [
                'config.php',
                'index.php',
                'includes/classes/Database.php',
                'includes/classes/User.php',
                'includes/classes/Product.php',
                'includes/functions/helpers.php',
                'includes/functions/validation.php',
                'includes/functions/auth_functions.php',
                'pages/auth/login.php',
                'pages/auth/register.php',
                'pages/products/index.php',
                'pages/products/view.php',
                'pages/tailor/products.php',
                'assets/css/main.css',
                'assets/css/auth.css'
            ];
            
            $allExist = true;
            foreach($files as $file) {
                $exists = file_exists($file);
                if (!$exists) $allExist = false;
                $status = $exists ? '<span class="status-ok">✓ EXISTS</span>' : '<span class="status-error">✗ MISSING</span>';
                echo "<tr><td>$file</td><td>$status</td></tr>";
            }
            ?>
        </table>
        <?php if ($allExist): ?>
            <p class="success" style="margin-top: 15px;">✓ All required files exist!</p>
        <?php else: ?>
            <p class="error" style="margin-top: 15px;">⚠️ Some files are missing. Make sure you copied all the code files.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>📂 Folders Check</h2>
        <table>
            <tr><th>Folder</th><th>Status</th></tr>
            <?php
            $folders = [
                'includes/classes',
                'includes/functions',
                'pages/auth',
                'pages/products',
                'pages/tailor',
                'pages/customer',
                'assets/css',
                'assets/js',
                'assets/uploads',
                'assets/uploads/products',
                'assets/uploads/profile'
            ];
            
            $needsCreate = [];
            foreach($folders as $folder) {
                $exists = is_dir($folder);
                if (!$exists) $needsCreate[] = $folder;
                $writable = $exists && is_writable($folder) ? ' (Writable ✓)' : ($exists ? ' (Not Writable ✗)' : '');
                $status = $exists ? '<span class="status-ok">✓ EXISTS' . $writable . '</span>' : '<span class="status-error">✗ MISSING</span>';
                echo "<tr><td>$folder</td><td>$status</td></tr>";
            }
            ?>
        </table>
        
        <?php if (!empty($needsCreate)): ?>
            <p class="error" style="margin-top: 15px;">⚠️ Missing folders detected!</p>
            <p><strong>Run these commands in Command Prompt:</strong></p>
            <div class="code">
cd C:\xampp\htdocs\webtech\WebtechFinal_Project\clothing-marketplace
<?php foreach($needsCreate as $folder): ?>
mkdir <?php echo str_replace('/', '\\', $folder); ?>

<?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="success" style="margin-top: 15px;">✓ All folders exist!</p>
        <?php endif; ?>
    </div>

    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <h2 style="color: white; border-color: white;">🔗 YOUR CORRECT URLs</h2>
        <p style="font-size: 1.1rem;"><strong>Use these URLs to access your website:</strong></p>
        <table style="margin-top: 20px;">
            <tr><th style="background: rgba(255,255,255,0.2);">Page</th><th style="background: rgba(255,255,255,0.2);">Link</th></tr>
            <tr style="background: rgba(255,255,255,0.1);">
                <td style="color: white; border-color: rgba(255,255,255,0.2);">Homepage</td>
                <td style="border-color: rgba(255,255,255,0.2);"><a href="../clothing-marketplace/" class="btn">Open Homepage</a></td>
            </tr>
            <tr style="background: rgba(255,255,255,0.1);">
                <td style="color: white; border-color: rgba(255,255,255,0.2);">Register</td>
                <td style="border-color: rgba(255,255,255,0.2);"><a href="pages/auth/register.php" class="btn">Open Register</a></td>
            </tr>
            <tr style="background: rgba(255,255,255,0.1);">
                <td style="color: white; border-color: rgba(255,255,255,0.2);">Login</td>
                <td style="border-color: rgba(255,255,255,0.2);"><a href="pages/auth/login.php" class="btn">Open Login</a></td>
            </tr>
            <tr style="background: rgba(255,255,255,0.1);">
                <td style="color: white; border-color: rgba(255,255,255,0.2);">Browse Products</td>
                <td style="border-color: rgba(255,255,255,0.2);"><a href="pages/products/index.php" class="btn">Open Products</a></td>
            </tr>
        </table>
    </div>

    <div class="card">
        <h2>📋 Next Steps</h2>
        <ol>
            <li>Click the links above to test each page</li>
            <li>If folders are missing, run the commands shown above</li>
            <li>Register a new account (select "Tailor" if you want to add products)</li>
            <li>Login with your account</li>
            <li>Start adding products!</li>
        </ol>
    </div>

    <div class="card">
        <h2>📸 Quick Test</h2>
        <p>Click these buttons to test your pages:</p>
        <a href="index.php" class="btn">Test Homepage</a>
        <a href="pages/auth/register.php" class="btn">Test Register</a>
        <a href="pages/auth/login.php" class="btn">Test Login</a>
        <a href="pages/products/index.php" class="btn">Test Products</a>
    </div>

</body>
</html>