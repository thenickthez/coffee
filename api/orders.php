<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

function respond(array $payload, int $status = 200): never {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $status = $_GET['status'] ?? '';
    if (in_array($status, ['ordered', 'making', 'done'], true)) {
        $stmt = db()->prepare("SELECT * FROM orders WHERE status = ? ORDER BY id DESC LIMIT 100");
        $stmt->execute([$status]);
        $orders = $stmt->fetchAll();
    } else {
        // Keep all open orders on the kitchen board plus the most recent completed orders.
        $open = db()->query("SELECT * FROM orders WHERE status IN ('ordered','making') ORDER BY id ASC LIMIT 100")->fetchAll();
        $done = db()->query("SELECT * FROM orders WHERE status = 'done' ORDER BY updated_at DESC, id DESC LIMIT 30")->fetchAll();
        $orders = array_merge($open, $done);
    }

    $itemStmt = db()->prepare("SELECT * FROM order_items WHERE order_id = ? ORDER BY id");
    foreach ($orders as &$order) {
        $itemStmt->execute([$order['id']]);
        $order['items'] = $itemStmt->fetchAll();
    }
    unset($order);
    respond(['success' => true, 'orders' => $orders]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'error' => 'Method not allowed.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$items = is_array($data) ? ($data['items'] ?? []) : [];
$customerName = trim((string)($data['customer_name'] ?? ''));
if ($customerName === '' || mb_strlen($customerName) > 100) respond(['success' => false, 'error' => 'Please enter your name.'], 400);
if (!is_array($items) || !$items) respond(['success' => false, 'error' => 'Order is empty.'], 400);

$pdo = db();
$drinkStmt = $pdo->prepare("SELECT id, name, price FROM drinks WHERE id = ? AND active = 1 LIMIT 1");
$milkStmt = $pdo->prepare("SELECT id, name FROM milk_types WHERE id = ? AND active = 1 LIMIT 1");
$validatedItems = [];$total = 0.0;
foreach ($items as $item) {
    $drinkId = filter_var($item['drink_id'] ?? null, FILTER_VALIDATE_INT);
    $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);
    $milkId = isset($item['milk_type_id']) && $item['milk_type_id'] !== null ? filter_var($item['milk_type_id'], FILTER_VALIDATE_INT) : null;
    if (!$drinkId || !$quantity || $quantity < 1 || $quantity > 25) respond(['success'=>false,'error'=>'One or more order items are invalid.'],400);
    $drinkStmt->execute([$drinkId]);$drink=$drinkStmt->fetch();
    if(!$drink) respond(['success'=>false,'error'=>'A selected drink is no longer available.'],400);
    $milkName=null;
    if($milkId!==null){if(!$milkId) respond(['success'=>false,'error'=>'Invalid milk selection.'],400);$milkStmt->execute([$milkId]);$milk=$milkStmt->fetch();if(!$milk) respond(['success'=>false,'error'=>'A selected milk option is no longer available.'],400);$milkName=$milk['name'];}
    $price=(float)$drink['price'];$total+=$price*$quantity;
    $validatedItems[]=['drink_id'=>(int)$drink['id'],'drink_name'=>$drink['name'],'milk_type'=>$milkName,'price'=>$price,'quantity'=>$quantity];
}
$pdo->beginTransaction();
try {
    $last=$pdo->query("SELECT order_number FROM orders ORDER BY order_number DESC LIMIT 1 FOR UPDATE")->fetchColumn();$next=$last?((int)$last+1):1001;
    $stmt=$pdo->prepare("INSERT INTO orders (order_number,customer_name,total) VALUES (?,?,?)");$stmt->execute([$next,$customerName,round($total,2)]);$orderId=(int)$pdo->lastInsertId();
    $itemInsert=$pdo->prepare("INSERT INTO order_items (order_id,drink_id,drink_name,milk_type,price,quantity) VALUES (?,?,?,?,?,?)");
    foreach($validatedItems as $item){$itemInsert->execute([$orderId,$item['drink_id'],$item['drink_name'],$item['milk_type'],$item['price'],$item['quantity']]);}
    $pdo->commit();respond(['success'=>true,'order_number'=>$next]);
} catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();respond(['success'=>false,'error'=>'Could not save the order. Please try again.'],500);}
