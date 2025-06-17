<?php
require_once 'db_connect.php'; // Include the database connection file

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['item_code'])) {
    $itemCode = $_GET['item_code'];

    // Prepare the SQL statement to delete the medication
    $stmt = $conn->prepare("DELETE FROM simc_library WHERE item_code = ?");
    $stmt->bind_param("s", $itemCode);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Medication deleted successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to delete medication."]);
    }

    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
}

$conn->close();
?>