<?php
require_once __DIR__.'/../config/auth.php';require_admin();
$pdo=db();
$orders=$pdo->query("SELECT * FROM orders WHERE status='done' ORDER BY updated_at DESC,id DESC")->fetchAll();
$itemStmt=$pdo->prepare("SELECT * FROM order_items WHERE order_id=? ORDER BY id");
$totalSales=0;$totalItems=0;
foreach($orders as &$o){$itemStmt->execute([$o['id']]);$o['items']=$itemStmt->fetchAll();$totalSales+=(float)$o['total'];foreach($o['items'] as $i)$totalItems+=(int)$i['quantity'];}unset($o);
if(isset($_GET['export'])&&$_GET['export']==='csv'){
 header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="completed-orders-'.date('Y-m-d').'.csv"');
 $out=fopen('php://output','w');fputcsv($out,['Order #','Completed','Drink','Milk','Qty','Unit Price','Line Total','Order Total']);
 foreach($orders as $o){$first=true;foreach($o['items'] as $i){fputcsv($out,[$o['order_number'],$o['updated_at'],$i['drink_name'],$i['milk_type'],(int)$i['quantity'],number_format((float)$i['price'],2,'.',''),number_format((float)$i['price']*(int)$i['quantity'],2,'.',''),$first?number_format((float)$o['total'],2,'.',''):'']);$first=false;}}
 fclose($out);exit;
}
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Completed Orders</title><link rel="stylesheet" href="../assets/style.css"></head><body class="admin-shell">
<header class="admin-header"><div class="container nav"><div class="logo">COFFEE<span>ADMIN</span></div><nav class="admin-nav"><a href="index.php">Dashboard</a><a href="drinks.php">Drinks</a><a href="milk.php">Milk Options</a><a href="completed.php">Completed Orders</a><a href="../kitchen/">Kitchen</a><a href="logout.php">Log out</a></nav></div></header>
<main class="container admin-main"><div class="page-title-row"><div><span class="eyebrow">Order History</span><h1 class="admin-page-title">Completed Orders</h1></div><a class="btn btn-yellow" href="?export=csv">DOWNLOAD CSV</a></div>
<div class="summary-grid"><div class="panel summary-box"><b>Completed Orders</b><strong><?=count($orders)?></strong></div><div class="panel summary-box"><b>Items Sold</b><strong><?=$totalItems?></strong></div><div class="panel summary-box"><b>Total Sales</b><strong>$<?=number_format($totalSales,2)?></strong></div><div class="panel summary-box"><b>Average Order</b><strong>$<?=number_format(count($orders)?$totalSales/count($orders):0,2)?></strong></div></div>
<div class="panel completed-table-wrap"><table class="table completed-table"><thead><tr><th>Order</th><th>Completed</th><th>Items / Details</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead><tbody>
<?php if(!$orders):?><tr><td colspan="6">No completed orders yet.</td></tr><?php endif;?>
<?php foreach($orders as $o): $qty=0;foreach($o['items'] as $i)$qty+=(int)$i['quantity'];?><tr><td><strong>#<?=h($o['order_number'])?></strong></td><td><?=h(date('M j, Y g:i A',strtotime($o['updated_at'])))?></td><td><?php foreach($o['items'] as $i):?><div class="history-item"><strong><?=h($i['quantity'])?>× <?=h($i['drink_name'])?></strong><?php if($i['milk_type']):?><span><?=h($i['milk_type'])?></span><?php endif;?> <small>@ $<?=number_format((float)$i['price'],2)?></small></div><?php endforeach;?></td><td><?=$qty?></td><td class="muted-cell"><?=count($o['items'])===1?'$'.number_format((float)$o['items'][0]['price'],2):'Mixed'?></td><td><strong>$<?=number_format((float)$o['total'],2)?></strong></td></tr><?php endforeach;?>
</tbody></table></div></main></body></html>
