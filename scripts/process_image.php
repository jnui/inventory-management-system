<?php
require_once '../db_connection.php';

// Get absolute paths
$scriptDir = dirname(__FILE__);
$projectRoot = dirname($scriptDir);
$upload_dir = $projectRoot . '/uploads/images/';
$thumb_50_dir = $upload_dir . 'thumb_50/';
$thumb_150_dir = $upload_dir . 'thumb_150/';

// Define web-accessible paths (relative to web root)
$web_base_path = '/materials/';  // Base path for web access
$web_upload_path = $web_base_path . 'uploads/images/';
$web_thumb_50_path = $web_upload_path . 'thumb_50/';
$web_thumb_150_path = $web_upload_path . 'thumb_150/';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the paths being used
error_log("Upload directory: " . $upload_dir);
error_log("Thumb 50 directory: " . $thumb_50_dir);
error_log("Thumb 150 directory: " . $thumb_150_dir);
error_log("Web upload path: " . $web_upload_path);
error_log("Web thumb 50 path: " . $web_thumb_50_path);
error_log("Web thumb 150 path: " . $web_thumb_150_path);

// Create upload directories if they don't exist
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        throw new Exception("Failed to create upload directory: " . $upload_dir);
    }
    error_log("Created upload directory: " . $upload_dir);
}
if (!file_exists($thumb_50_dir)) {
    if (!mkdir($thumb_50_dir, 0755, true)) {
        throw new Exception("Failed to create thumb_50 directory: " . $thumb_50_dir);
    }
    error_log("Created thumb_50 directory: " . $thumb_50_dir);
}
if (!file_exists($thumb_150_dir)) {
    if (!mkdir($thumb_150_dir, 0755, true)) {
        throw new Exception("Failed to create thumb_150 directory: " . $thumb_150_dir);
    }
    error_log("Created thumb_150 directory: " . $thumb_150_dir);
}

function createThumbnail($source, $destination, $maxWidth) {
    if (!file_exists($source)) {
        throw new Exception("Source file does not exist: " . $source);
    }
    
    list($width, $height) = getimagesize($source);
    $ratio = $width / $height;
    
    $newWidth = (int)$maxWidth;
    $newHeight = (int)round($maxWidth / $ratio);
    
    $thumb = imagecreatetruecolor($newWidth, $newHeight);
    if (!$thumb) {
        throw new Exception("Failed to create thumbnail image");
    }
    
    // Preserve transparency for PNG
    imagealphablending($thumb, false);
    imagesavealpha($thumb, true);
    $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
    if ($transparent === false) {
        throw new Exception("Failed to allocate transparent color");
    }
    imagefilledrectangle($thumb, 0, 0, $newWidth, $newHeight, $transparent);
    
    // Load source image
    $sourceImage = imagecreatefromstring(file_get_contents($source));
    if (!$sourceImage) {
        throw new Exception("Failed to load source image");
    }
    
    // Resize
    if (!imagecopyresampled($thumb, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height)) {
        throw new Exception("Failed to resize image");
    }
    
    // Save
    if (!imagepng($thumb, $destination, 9)) {
        throw new Exception("Failed to save thumbnail: " . $destination);
    }
    
    // Clean up
    imagedestroy($thumb);
    imagedestroy($sourceImage);
    
    // Verify the file was created
    if (!file_exists($destination)) {
        throw new Exception("Thumbnail file was not created: " . $destination);
    }
    
    // Verify file size
    if (filesize($destination) === 0) {
        throw new Exception("Thumbnail file is empty: " . $destination);
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
        $file = $_FILES['image'];
        $consumable_id = $_POST['consumable_id'] ?? null;
        
        error_log("Received file upload: " . print_r($file, true));
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            // Generate unique filename
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                throw new Exception("Invalid file type. Only JPG, PNG, and GIF files are allowed.");
            }
            
            $filename = uniqid() . '.' . $extension;
            
            // Full size image path (filesystem)
            $full_path = $upload_dir . $filename;
            
            // Thumbnail paths (filesystem)
            $thumb_50_path = $thumb_50_dir . $filename;
            $thumb_150_path = $thumb_150_dir . $filename;
            
            // Web-accessible paths
            $web_full_path = $web_upload_path . $filename;
            $web_thumb_50_path = $web_thumb_50_path . $filename;
            $web_thumb_150_path = $web_thumb_150_path . $filename;
            
            error_log("Moving uploaded file to: " . $full_path);
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $full_path)) {
                throw new Exception("Failed to move uploaded file. Error: " . error_get_last()['message']);
            }
            
            // Create thumbnails
            createThumbnail($full_path, $thumb_50_path, 50);
            createThumbnail($full_path, $thumb_150_path, 150);
            
            // Store in database
            $stmt = $pdo->prepare("
                INSERT INTO image_uploads (original_filename, full_path, thumb_50_path, thumb_150_path)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $file['name'],
                $web_full_path,
                $web_thumb_50_path,
                $web_thumb_150_path
            ]);
            
            $image_id = $pdo->lastInsertId();
            
            // Update consumable_materials if consumable_id is provided
            if ($consumable_id) {
                $stmt = $pdo->prepare("
                    UPDATE consumable_materials 
                    SET image_full = ?, image_thumb_50 = ?, image_thumb_150 = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $web_full_path,
                    $web_thumb_50_path,
                    $web_thumb_150_path,
                    $consumable_id
                ]);
            }
            
            echo json_encode([
                'success' => true,
                'image_id' => $image_id,
                'full_path' => $web_full_path,
                'thumb_50_path' => $web_thumb_50_path,
                'thumb_150_path' => $web_thumb_150_path
            ]);
        } else {
            throw new Exception('Upload failed with error code: ' . $file['error']);
        }
    } else {
        throw new Exception('Invalid request');
    }
} catch (Exception $e) {
    error_log("Error in process_image.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 