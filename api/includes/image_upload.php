<?php
/**
 * image_upload.php
 * ---------------------------------------------------------
 * Free image hosting helper (ImgBB) — replaces local
 * move_uploaded_file() so images survive on Vercel's
 * read-only/ephemeral filesystem.
 *
 * Flow:
 *   1. Get a free API key from https://api.imgbb.com/ (sign up free)
 *   2. Add to Vercel Project Settings -> Environment Variables:
 *        IMGBB_API_KEY = your_key_here
 *   3. That's it. upload_product_image() below does the rest.
 *
 * The returned value is a FULL URL (e.g. https://i.ibb.co/xxxx/name.png)
 * which is saved directly into the `image` column in the database.
 * All existing <img src="/uploads/...">-style code keeps working for
 * OLD rows (still local filenames), because render_image_src() below
 * detects whether the stored value is already a full URL or an old
 * local filename and builds the right <img src> either way.
 * ---------------------------------------------------------
 */

/**
 * Uploads a single $_FILES[...] entry to ImgBB and returns the hosted URL.
 * Also applies the same watermark logic locally BEFORE upload, if GD is available.
 *
 * @param array $fileArray   e.g. $_FILES['product_image']
 * @param string|null $watermarkText Optional watermark text (pass null to skip)
 * @return array ['success' => bool, 'url' => string|null, 'error' => string|null]
 */
function upload_product_image(array $fileArray, ?string $watermarkText = "RajaRam & Sons"): array {
    if (!isset($fileArray['tmp_name']) || $fileArray['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'url' => null, 'error' => 'No valid file uploaded.'];
    }

    $apiKey = $_ENV['IMGBB_API_KEY'] ?? getenv('IMGBB_API_KEY');
    if (!$apiKey) {
        return ['success' => false, 'url' => null, 'error' => 'IMGBB_API_KEY not set in environment variables.'];
    }

    $tmpPath = $fileArray['tmp_name'];

    // Apply watermark locally on the temp file first (works fine on /tmp which IS writable)
    if ($watermarkText !== null) {
        apply_watermark_to_file($tmpPath, $fileArray['name'], $watermarkText);
    }

    $imageData = file_get_contents($tmpPath);
    if ($imageData === false) {
        return ['success' => false, 'url' => null, 'error' => 'Could not read uploaded file.'];
    }

    $base64Image = base64_encode($imageData);

    $ch = curl_init('https://api.imgbb.com/1/upload');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'key'   => $apiKey,
        'image' => $base64Image,
        'name'  => pathinfo($fileArray['name'], PATHINFO_FILENAME) . '_' . time(),
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['success' => false, 'url' => null, 'error' => 'Upload request failed: ' . $curlError];
    }

    $result = json_decode($response, true);

    if (!empty($result['success']) && !empty($result['data']['url'])) {
        return ['success' => true, 'url' => $result['data']['url'], 'error' => null];
    }

    $errMsg = $result['error']['message'] ?? 'Unknown error from image host.';
    return ['success' => false, 'url' => null, 'error' => $errMsg];
}

/**
 * Applies the RajaRam & Sons watermark directly on a file path (in place).
 * Safe no-op if GD extension isn't available.
 */
function apply_watermark_to_file(string $filePath, string $originalName, string $text): bool {
    if (!function_exists('imagecreatefrompng')) {
        return false;
    }

    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    switch ($extension) {
        case 'jpg':
        case 'jpeg': $image = @imagecreatefromjpeg($filePath); break;
        case 'png':  $image = @imagecreatefrompng($filePath); break;
        case 'webp': $image = @imagecreatefromwebp($filePath); break;
        default: return false;
    }

    if (!$image) return false;

    $font_size = 5;
    $img_width = imagesx($image);
    $img_height = imagesy($image);
    $text_width = imagefontwidth($font_size) * strlen($text);
    $text_height = imagefontheight($font_size);

    $x = $img_width - $text_width - 20;
    $y = $img_height - $text_height - 20;

    $shadow_color = imagecolorallocate($image, 10, 25, 47);
    $text_color = imagecolorallocate($image, 183, 145, 95);

    imagestring($image, $font_size, $x + 1, $y + 1, $text, $shadow_color);
    imagestring($image, $font_size, $x, $y, $text, $text_color);

    switch ($extension) {
        case 'jpg':
        case 'jpeg': imagejpeg($image, $filePath, 90); break;
        case 'png':  imagepng($image, $filePath); break;
        case 'webp': imagewebp($image, $filePath); break;
    }

    imagedestroy($image);
    return true;
}

/**
 * Given whatever is stored in the DB `image` column (old local filename
 * OR new full hosted URL), returns the correct <img src> value.
 * Use this everywhere instead of hardcoding "/uploads/" . $product['image'].
 */
function render_image_src(?string $storedValue): string {
    if (empty($storedValue)) {
        return '/img/no-image.png'; // fallback placeholder, adjust path if needed
    }
    // Already a full URL (new ImgBB-hosted image)
    if (preg_match('/^https?:\/\//i', $storedValue)) {
        return $storedValue;
    }
    // Old-style local filename -> keep old path for backward compatibility
    return '/uploads/' . $storedValue;
}
