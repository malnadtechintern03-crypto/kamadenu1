<?php
/**
 * Kamadenu Goushala Platform - Secure Image Upload Service
 */

declare(strict_types=1);

class UploadService {

    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 MB

    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    /**
     * Upload single file from $_FILES array to specified folder under /uploads/.
     * 
     * @param array $file $_FILES['input_name']
     * @param string $folder Target subfolder inside /uploads/ (e.g. 'cows', 'gallery', 'breeds', 'products', 'blog', 'logo')
     * @return string Generated safe filename stored on disk
     * @throws Exception If validation fails
     */
    public static function upload(array $file, string $folder): string {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception('Invalid upload parameters.');
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new Exception('No file was uploaded.');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('Uploaded file exceeds maximum allowed size of 5 MB.');
            default:
                throw new Exception('An unknown error occurred during upload (Code: ' . $file['error'] . ').');
        }

        if ($file['size'] > self::MAX_FILE_SIZE) {
            throw new Exception('File exceeds the maximum allowable size of 5 MB.');
        }

        // Validate MIME type with finfo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!isset(self::ALLOWED_MIME_TYPES[$mimeType])) {
            throw new Exception('Invalid file format (' . htmlspecialchars($mimeType) . '). Allowed: JPG, PNG, WEBP.');
        }

        // Additional security check: ensure it is a valid image with getimagesize
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new Exception('Uploaded file is corrupted or not a valid image.');
        }

        $extension = self::ALLOWED_MIME_TYPES[$mimeType];

        // Sanitize destination folder to prevent path traversal
        $safeFolder = preg_replace('/[^a-zA-Z0-9_-]/', '', $folder);
        $targetDir = dirname(__DIR__) . '/uploads/' . $safeFolder;

        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                throw new Exception('Failed to create upload target directory.');
            }
        }

        // Generate cryptographically secure randomized filename
        $randomName = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $targetDir . '/' . $randomName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Failed to move uploaded file to destination.');
        }

        return $randomName;
    }

    /**
     * Delete an existing image file from /uploads/
     */
    public static function delete(?string $filename, string $folder): bool {
        if (empty($filename)) {
            return false;
        }

        $safeFilename = basename($filename);
        $safeFolder = preg_replace('/[^a-zA-Z0-9_-]/', '', $folder);
        $filePath = dirname(__DIR__) . '/uploads/' . $safeFolder . '/' . $safeFilename;

        if (is_file($filePath)) {
            return @unlink($filePath);
        }

        return false;
    }
}
