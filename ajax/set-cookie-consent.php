<?php
require_once '../config.php';
require_once '../includes/cookie-manager.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $consent = $input['consent'] ?? 'rejected';
    
    if ($consent === 'accepted') {
        CookieManager::set('cookie_consent', 'true', 365);
        CookieManager::set('analytics_enabled', 'true', 365);
        echo json_encode(['success' => true, 'message' => 'Cookies accepted']);
    } else {
        CookieManager::set('cookie_consent', 'false', 365);
        CookieManager::set('analytics_enabled', 'false', 365);
        echo json_encode(['success' => true, 'message' => 'Non-essential cookies rejected']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>