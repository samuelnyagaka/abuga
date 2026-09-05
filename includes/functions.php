<?php
/**
 * Shared Helper Functions
 * Tailor Business Management & Marketing System
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once __DIR__ . '/db.php';

/**
 * Escape HTML output safely
 * @param string|null $string
 * @return string
 */
function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize plain user text input
 * @param string $input
 * @return string
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return trim(strip_tags((string)$input));
}

/**
 * Generate CSRF Token for session
 * @return string
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate submitted CSRF Token
 * @param string|null $token
 * @return bool
 */
function validate_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output a hidden CSRF input field
 * @return string
 */
function csrf_input() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Cache-busting URL generator for CSS and JS files
 * @param string $relativePath e.g. "assets/css/style.css"
 * @return string
 */
function asset_url($relativePath) {
    $fullPath = dirname(__DIR__) . '/' . ltrim($relativePath, '/');
    $version = file_exists($fullPath) ? filemtime($fullPath) : time();
    return BASE_URL . '/' . ltrim($relativePath, '/') . '?v=' . $version;
}

/**
 * Format currency in Kenyan Shillings
 * @param float|int $amount
 * @return string
 */
function format_currency($amount) {
    return CURRENCY_SYMBOL . number_format((float)$amount, 2);
}

/**
 * Format date for display in Africa/Nairobi context
 * @param string|null $dateStr
 * @param string $format
 * @return string
 */
function format_date($dateStr, $format = 'd M Y') {
    if (empty($dateStr)) return '-';
    $timestamp = strtotime($dateStr);
    return $timestamp ? date($format, $timestamp) : '-';
}

/**
 * Format datetime for display
 * @param string|null $datetimeStr
 * @return string
 */
function format_datetime($datetimeStr) {
    if (empty($datetimeStr)) return '-';
    $timestamp = strtotime($datetimeStr);
    return $timestamp ? date('d M Y, h:i A', $timestamp) : '-';
}

/**
 * Human friendly relative time
 * @param string|int $time
 * @return string
 */
function time_ago($time) {
    $timestamp = is_numeric($time) ? $time : strtotime($time);
    if (!$timestamp) return '-';
    
    $diff = time() - $timestamp;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' mins ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('d M Y', $timestamp);
}

/**
 * Send Transactional Email using Brevo (Sendinblue) HTTPS v3 API
 * InfinityFree blocks standard SMTP and PHP mail(). All emails use cURL HTTPS REST.
 * 
 * @param string $toEmail
 * @param string $toName
 * @param string $subject
 * @param string $htmlContent
 * @param string|null $replyToEmail
 * @return array ['success' => bool, 'message' => string]
 */
function send_brevo_email($toEmail, $toName, $subject, $htmlContent, $replyToEmail = null) {
    // Check if dynamic Brevo key is stored in database settings
    $apiKey = BREVO_API_KEY;
    if (empty($apiKey)) {
        $setting = db_fetch_one("SELECT key_value FROM settings WHERE key_name = 'brevo_api_key' LIMIT 1");
        if ($setting && !empty($setting['key_value'])) {
            $apiKey = $setting['key_value'];
        }
    }

    $senderEmail = BREVO_SENDER_EMAIL;
    $senderName = BREVO_SENDER_NAME;

    // If no API key configured, simulate email sending and log to file (won't crash the user)
    if (empty($apiKey)) {
        $logDir = dirname(__DIR__) . '/uploads';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logEntry = "[" . date('Y-m-d H:i:s') . "] TO: {$toName} <{$toEmail}> | SUBJECT: {$subject}\n";
        @file_put_contents($logDir . '/email_simulated.log', $logEntry, FILE_APPEND);
        return [
            'success' => true,
            'simulated' => true,
            'message' => 'Brevo API key not set. Email logged to uploads/email_simulated.log (simulation mode).'
        ];
    }

    $payload = [
        'sender' => [
            'name' => $senderName,
            'email' => $senderEmail
        ],
        'to' => [
            [
                'email' => $toEmail,
                'name' => $toName ?: $toEmail
            ]
        ],
        'subject' => $subject,
        'htmlContent' => $htmlContent
    ];

    if (!empty($replyToEmail)) {
        $payload['replyTo'] = [
            'email' => $replyToEmail,
            'name' => $toName
        ];
    }

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'api-key: ' . $apiKey,
            'content-type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("Brevo cURL error: " . $curlError);
        return ['success' => false, 'message' => 'Connection to Brevo failed: ' . $curlError];
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        return ['success' => true, 'message' => 'Email sent successfully via Brevo.'];
    } else {
        error_log("Brevo API error [{$httpCode}]: " . $response);
        return ['success' => false, 'message' => 'Brevo returned error status: ' . $httpCode];
    }
}

/**
 * Process, resize and compress uploaded image using PHP GD
 * Keeps files under free hosting storage limits.
 * 
 * @param array $file $_FILES['input_name']
 * @param string $subFolder e.g. 'samples' or 'gallery'
 * @param int $maxDim Max width/height
 * @param int $quality JPEG quality (1-100)
 * @return array ['success' => bool, 'path' => string, 'error' => string]
 */
function upload_and_resize_image($file, $subFolder = 'general', $maxDim = 1200, $quality = 82) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'No file uploaded or upload error occurred.'];
    }

    if ($file['size'] > MAX_UPLOAD_SIZE_BYTES) {
        return ['success' => false, 'error' => 'File size exceeds maximum allowed 4MB.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!array_key_exists($mime, $allowedMimes)) {
        return ['success' => false, 'error' => 'Invalid image format. Only JPG, PNG, and WebP are supported.'];
    }

    $ext = $allowedMimes[$mime];
    $targetDir = dirname(__DIR__) . '/uploads/' . trim($subFolder, '/') . '/';
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0755, true);
    }

    $newFileName = 'img_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetFilePath = $targetDir . $newFileName;
    $relativeFilePath = 'uploads/' . trim($subFolder, '/') . '/' . $newFileName;

    // Check if GD extension is available for resizing
    if (!extension_loaded('gd')) {
        // Fallback: move uploaded file directly
        if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
            return ['success' => true, 'path' => $relativeFilePath];
        }
        return ['success' => false, 'error' => 'Failed to save file.'];
    }

    // Load source image
    switch ($mime) {
        case 'image/jpeg':
            $sourceImg = @imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'image/png':
            $sourceImg = @imagecreatefrompng($file['tmp_name']);
            break;
        case 'image/webp':
            $sourceImg = @imagecreatefromwebp($file['tmp_name']);
            break;
        default:
            $sourceImg = false;
    }

    if (!$sourceImg) {
        if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
            return ['success' => true, 'path' => $relativeFilePath];
        }
        return ['success' => false, 'error' => 'Could not process image.'];
    }

    $origWidth = imagesx($sourceImg);
    $origHeight = imagesy($sourceImg);

    // Calculate dimensions
    if ($origWidth > $maxDim || $origHeight > $maxDim) {
        if ($origWidth > $origHeight) {
            $newWidth = $maxDim;
            $newHeight = (int)round(($origHeight / $origWidth) * $maxDim);
        } else {
            $newHeight = $maxDim;
            $newWidth = (int)round(($origWidth / $origHeight) * $maxDim);
        }
    } else {
        $newWidth = $origWidth;
        $newHeight = $origHeight;
    }

    $resizedCanvas = imagecreatetruecolor($newWidth, $newHeight);

    // Preserve transparency for PNG and WebP
    if ($mime === 'image/png' || $mime === 'image/webp') {
        imagealphablending($resizedCanvas, false);
        imagesavealpha($resizedCanvas, true);
        $transparent = imagecolorallocatealpha($resizedCanvas, 255, 255, 255, 127);
        imagefilledrectangle($resizedCanvas, 0, 0, $newWidth, $newHeight, $transparent);
    }

    imagecopyresampled($resizedCanvas, $sourceImg, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

    // Save compressed image
    $saved = false;
    if ($ext === 'png') {
        $saved = imagepng($resizedCanvas, $targetFilePath, 8); // PNG compression 0-9
    } elseif ($ext === 'webp') {
        $saved = imagewebp($resizedCanvas, $targetFilePath, $quality);
    } else {
        $saved = imagejpeg($resizedCanvas, $targetFilePath, $quality);
    }

    imagedestroy($sourceImg);
    imagedestroy($resizedCanvas);

    if ($saved) {
        return ['success' => true, 'path' => $relativeFilePath];
    } else {
        return ['success' => false, 'error' => 'Failed to save resized image.'];
    }
}

/**
 * Generate a WhatsApp Click-to-Chat URL
 * @param string $message
 * @param string|null $phone
 * @return string
 */
function whatsapp_url($message = '', $phone = null) {
    $number = $phone ?: BUSINESS_WHATSAPP;
    // Clean any non-digit characters
    $cleanNumber = preg_replace('/[^0-9]/', '', $number);
    $url = 'https://wa.me/' . $cleanNumber;
    if (!empty($message)) {
        $url .= '?text=' . urlencode($message);
    }
    return $url;
}

/**
 * Set a session flash message
 * @param string $type 'success', 'danger', 'info', 'warning'
 * @param string $message
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Display and clear session flash messages with a 5-second auto-fading confirmation modal
 * @return string HTML alerts and auto-fading confirmation modal
 */
function display_flash_messages() {
    if (empty($_SESSION['flash_messages'])) {
        return '';
    }

    $flashes = $_SESSION['flash_messages'];
    $_SESSION['flash_messages'] = [];

    // Map primary flash type for overall modal tone
    $firstType = $flashes[0]['type'] ?? 'info';
    
    $color_map = [
        'success' => [
            'icon'  => 'bi-check-circle-fill',
            'title' => 'Action Confirmed',
            'color' => '#198754',
            'bg'    => '#d1e7dd',
            'badge' => 'bg-success'
        ],
        'danger'  => [
            'icon'  => 'bi-exclamation-octagon-fill',
            'title' => 'Action Alert / Error',
            'color' => '#dc3545',
            'bg'    => '#f8d7da',
            'badge' => 'bg-danger'
        ],
        'warning' => [
            'icon'  => 'bi-exclamation-triangle-fill',
            'title' => 'System Warning',
            'color' => '#fd7e14',
            'bg'    => '#fff3cd',
            'badge' => 'bg-warning'
        ],
        'info'    => [
            'icon'  => 'bi-info-circle-fill',
            'title' => 'System Notice',
            'color' => '#0dcaf0',
            'bg'    => '#cff4fc',
            'badge' => 'bg-info'
        ],
    ];

    $cfg = $color_map[$firstType] ?? $color_map['info'];

    // Also build inline notifications for persistence and accessibility
    $inlineAlerts = '';
    $modalItems = '';
    foreach ($flashes as $f) {
        $type = e($f['type']);
        $msg = e($f['message']);
        $inlineAlerts .= "
            <div class='alert alert-{$type} alert-dismissible fade show shadow-sm' role='alert'>
                {$msg}
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>
        ";
        $modalItems .= "<div class='py-1 d-flex align-items-start gap-2'><i class='bi bi-arrow-right-circle text-primary flex-shrink-0' style='margin-top:3px;'></i><span>{$msg}</span></div>";
    }

    $modalId = 'systemAutoFadeModal_' . uniqid();
    $progressId = 'progress_' . $modalId;
    $countId = 'count_' . $modalId;

    $html = "
    <!-- Flash Messages Inline Alerts -->
    <div class='flash-messages-container mb-3'>
        {$inlineAlerts}
    </div>

    <!-- 5-Second Auto-Fading Confirmation Modal -->
    <div class='modal fade' id='{$modalId}' tabindex='-1' aria-labelledby='{$modalId}Label' aria-hidden='true'>
      <div class='modal-dialog modal-dialog-centered' style='max-width: 480px;'>
        <div class='modal-content border-0 shadow-lg rounded-4 overflow-hidden'>
          <div class='modal-header border-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-start'>
            <div class='d-flex align-items-center gap-3'>
              <div class='rounded-circle d-flex align-items-center justify-content-center flex-shrink-0' style='width: 44px; height: 44px; background-color: {$cfg['bg']}; color: {$cfg['color']}; font-size: 1.4rem;'>
                <i class='bi {$cfg['icon']}'></i>
              </div>
              <div>
                <h5 class='modal-title fw-bold mb-0 text-dark' id='{$modalId}Label'>{$cfg['title']}</h5>
                <small class='text-muted'>System Confirmation</small>
              </div>
            </div>
            <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
          </div>

          <div class='modal-body px-4 py-3'>
            <div class='text-secondary fs-6'>
                {$modalItems}
            </div>
          </div>

          <div class='modal-footer border-0 pt-1 pb-3 px-4 d-flex justify-content-between align-items-center' style='background-color: #f8f9fa;'>
            <span class='small text-muted d-flex align-items-center gap-1'>
              <i class='bi bi-stopwatch text-primary'></i>
              <span>Auto-fading in <strong id='{$countId}' class='text-dark'>5</strong>s...</span>
            </span>
            <button type='button' class='btn btn-sm btn-outline-secondary px-3 rounded-pill' data-bs-dismiss='modal'>
              Dismiss Now
            </button>
          </div>

          <!-- 5-Second Linear Progress Bar -->
          <div class='progress' style='height: 5px; border-radius: 0; background: rgba(0,0,0,0.06);'>
            <div id='{$progressId}' class='progress-bar {$cfg['badge']}' role='progressbar' style='width: 100%; transition: width 5s linear;'></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Auto-Fade Trigger Script (5 Seconds) -->
    <script>
    (function() {
      function triggerAutoFade() {
        var modalEl = document.getElementById('{$modalId}');
        if (!modalEl) return;
        if (typeof bootstrap === 'undefined') {
          window.addEventListener('load', triggerAutoFade);
          return;
        }

        var modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl, {
          backdrop: true,
          keyboard: true
        });
        modalInstance.show();

        var progressBar = document.getElementById('{$progressId}');
        var countdownNum = document.getElementById('{$countId}');

        // Animate linear bar shrink to 0% over 5s
        setTimeout(function() {
          if (progressBar) {
            progressBar.style.width = '0%';
          }
        }, 50);

        var timeLeft = 5;
        var interval = setInterval(function() {
          timeLeft--;
          if (countdownNum) {
            countdownNum.textContent = timeLeft > 0 ? timeLeft : 0;
          }
          if (timeLeft <= 0) {
            clearInterval(interval);
          }
        }, 1000);

        // Auto fade out smoothly at 5000ms
        var autoCloseTimer = setTimeout(function() {
          modalInstance.hide();
        }, 5000);

        modalEl.addEventListener('hidden.bs.modal', function() {
          clearInterval(interval);
          clearTimeout(autoCloseTimer);
        }, { once: true });
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', triggerAutoFade);
      } else {
        triggerAutoFade();
      }
    })();
    </script>
    ";

    return $html;
}

/**
 * Get CSS badge HTML for order status
 * @param string $status
 * @return string
 */
function get_order_status_badge($status) {
    $badges = [
        'lead'              => '<span class="badge bg-secondary">Lead</span>',
        'quoted'            => '<span class="badge bg-info text-dark">Quoted</span>',
        'confirmed'         => '<span class="badge bg-primary">Confirmed</span>',
        'in_production'     => '<span class="badge bg-warning text-dark">In Production</span>',
        'ready_for_pickup'  => '<span class="badge bg-purple text-white" style="background:#8b5cf6;">Ready for Delivery</span>',
        'completed'         => '<span class="badge bg-success">Completed</span>',
        'cancelled'         => '<span class="badge bg-danger">Cancelled</span>',
    ];
    return $badges[$status] ?? '<span class="badge bg-light text-dark">' . e(ucfirst($status)) . '</span>';
}

/**
 * Get CSS badge HTML for payment status
 * @param string $status
 * @return string
 */
function get_payment_status_badge($status) {
    $badges = [
        'unpaid'   => '<span class="badge bg-danger">Unpaid</span>',
        'partial'  => '<span class="badge bg-warning text-dark">Partial / Deposit</span>',
        'paid'     => '<span class="badge bg-success">Fully Paid</span>',
    ];
    return $badges[$status] ?? '<span class="badge bg-light text-dark">' . e(ucfirst($status)) . '</span>';
}
