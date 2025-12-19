<?php
// check_permissions.php
echo "<h2>Server Permission Diagnostic Tool</h2>";

// Test directories
$test_dirs = [
    'assets/images/avatars/' => 'Avatar Upload Directory',
    'assets/images/products/' => 'Product Image Directory',
    dirname(__FILE__) => 'Project Root Directory',
    sys_get_temp_dir() => 'System Temp Directory'
];

foreach ($test_dirs as $path => $description) {
    echo "<h3>$description</h3>";
    echo "Path: " . realpath($path) ?: $path . "<br>";
    echo "Exists: " . (file_exists($path) ? "✅ YES" : "❌ NO") . "<br>";
    
    if (file_exists($path)) {
        echo "Is Directory: " . (is_dir($path) ? "✅ YES" : "❌ NO") . "<br>";
        $perms = fileperms($path);
        echo "Permissions (octal): " . substr(sprintf('%o', $perms), -4) . "<br>";
        echo "Permissions (human): ";
        echo (($perms & 0x0100) ? 'r' : '-');
        echo (($perms & 0x0080) ? 'w' : '-');
        echo (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x') : (($perms & 0x0800) ? 'S' : '-'));
        echo (($perms & 0x0020) ? 'r' : '-');
        echo (($perms & 0x0010) ? 'w' : '-');
        echo (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x') : (($perms & 0x0400) ? 'S' : '-'));
        echo (($perms & 0x0004) ? 'r' : '-');
        echo (($perms & 0x0002) ? 'w' : '-');
        echo (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x') : (($perms & 0x0200) ? 'T' : '-'));
        echo "<br>";
        echo "Writable: " . (is_writable($path) ? "✅ YES" : "❌ NO") . "<br>";
        echo "Readable: " . (is_readable($path) ? "✅ YES" : "❌ NO") . "<br>";
        
        // Try to create a test file
        $test_file = $path . '/test_permissions_' . time() . '.txt';
        if (is_writable($path)) {
            if (file_put_contents($test_file, 'test')) {
                echo "Can write files: ✅ YES<br>";
                unlink($test_file);
            } else {
                echo "Can write files: ❌ NO (file_put_contents failed)<br>";
            }
        }
    }
    echo "<hr>";
}

// Check PHP user
echo "<h3>PHP User Information</h3>";
echo "PHP User (exec): " . @exec('whoami') . "<br>";
echo "PHP User (get_current_user): " . get_current_user() . "<br>";
echo "User ID: " . @exec('id') . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

// Check PHP settings
echo "<h3>PHP Upload Settings</h3>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "file_uploads: " . (ini_get('file_uploads') ? "✅ ON" : "❌ OFF") . "<br>";
echo "upload_tmp_dir: " . (ini_get('upload_tmp_dir') ?: 'Not set (using system default)') . "<br>";
echo "upload_tmp_dir writable: " . (is_writable(ini_get('upload_tmp_dir') ?: sys_get_temp_dir()) ? "✅ YES" : "❌ NO") . "<br>";

// Check open_basedir restrictions
echo "<h3>PHP Security Restrictions</h3>";
echo "open_basedir: " . (ini_get('open_basedir') ?: 'Not restricted') . "<br>";
echo "disable_functions: " . ini_get('disable_functions') . "<br>";
echo "safe_mode: " . (ini_get('safe_mode') ? "ON ⚠️" : "OFF ✅") . "<br>";

// Test actual upload capability
echo "<h3>Direct Upload Test</h3>";
echo '<form method="post" enctype="multipart/form-data">';
echo '   <input type="file" name="testfile">';
echo '   <input type="submit" value="Test Upload">';
echo '</form>';

if ($_FILES) {
    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";
    
    if ($_FILES['testfile']['error']) {
        echo "Upload Error: " . $_FILES['testfile']['error'] . "<br>";
    } else {
        echo "Upload Success!<br>";
        echo "Temp file: " . $_FILES['testfile']['tmp_name'] . "<br>";
        echo "Temp file exists: " . (file_exists($_FILES['testfile']['tmp_name']) ? "✅ YES" : "❌ NO") . "<br>";
        echo "Temp file readable: " . (is_readable($_FILES['testfile']['tmp_name']) ? "✅ YES" : "❌ NO") . "<br>";
    }
}
?>