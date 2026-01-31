<?php
require_once '../config.php';
require_once '../includes/cookie-manager.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $preference = $input['preference'] ?? '';
    $value = $input['value'] ?? '';
    
    if (empty($preference)) {
        throw new Exception('Preference name required');
    }
    
    // Validate allowed preferences
    $allowed_preferences = ['theme', 'language', 'notifications', 'auto_refresh', 'layout'];
    if (!in_array($preference, $allowed_preferences)) {
        throw new Exception('Invalid preference');
    }
    
    // Set the preference
    CookieManager::set($preference, $value);
    
    echo json_encode([
        'success' => true, 
        'message' => "Preference '$preference' updated to '$value'",
        'preference' => $preference,
        'value' => $value
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>