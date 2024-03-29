<?php
require_once(__DIR__ . "/../../../lib/functions.php");
error_log("add_to_cart received data: " . var_export($_REQUEST, true));
if (session_status() != PHP_SESSION_ACTIVE) {
    session_start();
}
//handle the potentially incoming post request
$item_id = (int)se($_POST, "item_id", null, false);
$desired_quantity = (int)se($_POST, "desired_quantity", 0, false);
$price = (int)se($_POST, "price", 0, false);

$response = ["status" => 400, "message" => "Invalid data"];
http_response_code(400);
if (isset($item_id) && $desired_quantity > 0) {
    if (is_logged_in()) {
        $db = getDB();
        //note adding to cart doesn't verify price or quantity
        $stmt = $db->prepare("INSERT INTO Shop_Cart (item_id, quantity, user_id, price) VALUES(:iid, :q, :uid, :price) ON DUPLICATE KEY UPDATE quantity = quantity + :q");
        $stmt->bindValue(":iid", $item_id, PDO::PARAM_INT);
        $stmt->bindValue(":q", $desired_quantity, PDO::PARAM_INT);
        $stmt->bindValue(":price", $price, PDO::PARAM_INT);
        $stmt->bindValue(":uid", get_user_id(), PDO::PARAM_INT);
        try {
            $stmt->execute();
            $response["status"] = 200;
            $response["message"] = "Item added to cart";
            http_response_code(200);
        } catch (PDOException $e) {
            //echo $e;
            error_log("Add to cart error: " . var_export($e, true));
            $response["message"] = "$e";
        }
    } else {
        http_response_code(403);
        $response["status"] = 403;
        $response["message"] = "Must be logged in to add to cart";
    }
}
echo json_encode($response);