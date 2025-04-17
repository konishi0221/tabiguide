<?php
require_once '../db.php';
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $result = $mysqli->query("SELECT stores.*, categories.name AS category_name, categories.color
                              FROM stores
                              LEFT JOIN categories ON stores.category_id = categories.id
                              WHERE stores.is_visible = 1
                              ORDER BY stores.id ASC");
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    $id = $_GET["id"];
    $stmt = $mysqli->prepare("DELETE FROM stores WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo json_encode(["success" => true]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $mysqli->prepare("INSERT INTO stores (name, lat, lng, category_id, description, is_visible) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siddsi", $data["name"], $data["lat"], $data["lng"], $data["category_id"], $data["description"], $data["is_visible"]);
    $stmt->execute();
    echo json_encode(["id" => $mysqli->insert_id, "name" => $data["name"], "lat" => $data["lat"], "lng" => $data["lng"], "color" => "#808080"]);
    exit;
}
?>
