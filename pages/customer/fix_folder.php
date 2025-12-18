<?php
$path = '/home/mukaila.shittu/public_html/Final_project_web/assets/images/avatars/';

if (!is_dir($path)) {
    mkdir($path, 0755, true);
}

// This forces the permissions to 755 via PHP
if (chmod($path, 0755)) {
    echo "Success! Folder permissions reset to 755.<br>";
} else {
    echo "Failed to reset permissions. Please contact host support.<br>";
}

// Check if it's writable now
if (is_writable($path)) {
    echo "The folder is now WRITABLE. You can upload now.";
} else {
    echo "The folder is still NOT writable.";
}
?>
