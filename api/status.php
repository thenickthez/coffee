<?php
header('Content-Type: application/json');require_once __DIR__.'/../config/database.php';require_once __DIR__.'/../config/auth.php';require_admin();
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit;}$d=json_decode(file_get_contents('php://input'),true);$id=(int)($d['id']??0);$status=$d['status']??'';if(!$id||!in_array($status,['ordered','making','done'],true)){http_response_code(400);echo json_encode(['success'=>false]);exit;}$s=db()->prepare("UPDATE orders SET status=? WHERE id=?");$s->execute([$status,$id]);echo json_encode(['success'=>true]);
