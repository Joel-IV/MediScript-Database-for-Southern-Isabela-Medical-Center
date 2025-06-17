<?php
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_code = $_POST['item_code'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $route = $_POST['route'];
    $iv = $_POST['iv'];
    $high_alert = $_POST['high_alert'];
    $s2 = $_POST['s2'];
    $yellow_rx = $_POST['yellow_rx'];

    $query = "UPDATE simc_library 
              SET item_description = ?, pharmacologic_category = ?, route = ?, if_intravenous = ?, high_alert_medication = ?, requiring_s2 = ?, requiring_yellow_rx = ?
              WHERE item_code = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssssssss', $description, $category, $route, $iv, $high_alert, $s2, $yellow_rx, $item_code);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>