<?php
require_once __DIR__ . '/upload_utils.php';
/**
 * Utility functions for handling image uploads with size and type checks.
 */
function save_scaled_image(array $file, string $dir, int $maxWidth = 1600, int $maxHeight = 1600, int $maxBytes = 2097152) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [false, 'Upload error'];
    }
    if ($file['size'] > $maxBytes) {
        return [false, 'File too large'];
    }
    $info = getimagesize($file['tmp_name']);
    if (!$info) {
        return [false, 'Invalid image'];
    }
    $mime = $info['mime'];
    if (!in_array($mime, ['image/jpeg', 'image/png'])) {
        return [false, 'Unsupported image type'];
    }
    if (!scan_file($file['tmp_name'])) {
        return [false, 'File failed virus scan'];
    }
    if (!is_dir($dir)) {
        mkdir($dir, 0770, true);
    }
    $ext = $mime === 'image/png' ? '.png' : '.jpg';
    $name = uniqid('img_', true) . $ext;
    $path = rtrim($dir, '/') . '/' . $name;
    $width = $info[0];
    $height = $info[1];
    $scale = min($maxWidth / $width, $maxHeight / $height, 1);
    if ($scale < 1) {
        $newWidth = (int)($width * $scale);
        $newHeight = (int)($height * $scale);
        $src = $mime === 'image/png' ? imagecreatefrompng($file['tmp_name']) : imagecreatefromjpeg($file['tmp_name']);
        $dst = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        if ($mime === 'image/png') {
            imagepng($dst, $path, 7);
        } else {
            imagejpeg($dst, $path, 85);
        }
        imagedestroy($src);
        imagedestroy($dst);
    } else {
        move_uploaded_file($file['tmp_name'], $path);
    }
    return [true, $name];
}
