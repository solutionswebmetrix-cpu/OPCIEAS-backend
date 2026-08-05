<?php

if (!defined('ABSPATH')) {
    require_once __DIR__ . '/../config/config.php';
}

if (!function_exists('handle_file_upload')) {
function handle_file_upload($file, $dir = 'products')
{
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload limit',
            UPLOAD_ERR_PARTIAL    => 'File upload was incomplete',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Server missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION  => 'File upload stopped by extension',
        ];
        return ['success' => false, 'path' => '', 'error' => $errors[$file['error']] ?? 'Unknown upload error'];
    }

    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return ['success' => false, 'path' => '', 'error' => 'File size exceeds maximum allowed (' . (UPLOAD_MAX_SIZE / (1024*1024)) . 'MB)'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExt = array_merge(ALLOWED_IMAGE_EXT, ALLOWED_DOC_EXT);
    if (!in_array($ext, $allowedExt)) {
        return ['success' => false, 'path' => '', 'error' => 'File extension not allowed. Allowed: ' . implode(', ', $allowedExt)];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $imageMimes = ['image/jpeg', 'image/png', 'image/webp'];
    $docMimes = ['application/pdf'];
    $allMimes = array_merge($imageMimes, $docMimes);
    if (!in_array($mime, $allMimes)) {
        return ['success' => false, 'path' => '', 'error' => 'Invalid file MIME type'];
    }

    $uploadBaseDir = __DIR__ . '/../uploads';
    $targetDir = $uploadBaseDir . '/' . $dir;
    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0755, true)) {
            return ['success' => false, 'path' => '', 'error' => 'Failed to create target directory'];
        }
    }

    $filename = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $targetPath = $targetDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'path' => '', 'error' => 'Failed to move uploaded file'];
    }

    $relativePath = 'uploads/' . $dir . '/' . $filename;
    return ['success' => true, 'path' => $relativePath, 'error' => ''];
}
}

if (!function_exists('handle_multiple_uploads')) {
function handle_multiple_uploads($files, $dir = 'products')
{
    $results = [];
    $fileCount = is_array($files['name']) ? count($files['name']) : 0;
    for ($i = 0; $i < $fileCount; $i++) {
        $file = [
            'name'     => $files['name'][$i],
            'type'     => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error'    => $files['error'][$i],
            'size'     => $files['size'][$i],
        ];
        $results[] = handle_file_upload($file, $dir);
    }
    return $results;
}
}
