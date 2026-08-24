<?php
/**
 * AJAX endpoint for updating image order
 * Only accessible to authenticated admins
 */

require_once '../auth.php';
require_once '../config/db.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied. Admin privileges required.']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate input
if (!$data || !isset($data['order']) || !is_array($data['order'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input. Expected order array.']);
    exit;
}

// Validate order data structure
$orderData = [];
foreach ($data['order'] as $index => $item) {
    if (!isset($item['filename']) || !is_string($item['filename'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid order item. Missing filename.']);
        exit;
    }
    
    // Sanitize filename and create order data
    $filename = basename($item['filename']); // Prevent directory traversal
    $orderData[] = [
        'filename' => $filename,
        'order' => $index + 1 // 1-based ordering
    ];
}

try {
    // Update database
    $db = Database::getInstance();
    $success = $db->updateOrder($orderData);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Image order updated successfully.',
            'updated_count' => count($orderData)
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update image order in database.'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Reorder error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error occurred while updating order.'
    ]);
}