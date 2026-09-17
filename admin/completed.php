<?php
require_once __DIR__.'/../config/auth.php';require_admin();
$pdo=db();

// CSRF token for destructive actions.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$deleteNotice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_orders'])) {
    $token = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Invalid request token. Please refresh the page and try again.');
    }

    $ids = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['order_ids'] ?? [])), fn($id) => $id > 0)));
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        // Only completed orders can be deleted from this page. order_items are removed by ON DELETE CASCADE.
        $stmt = $pdo->prepare("DELETE FROM orders WHERE status='done' AND id IN ($placeholders)");
        $stmt->execute($ids);
        $deleted = $stmt->rowCount();
        $deleteNotice = $deleted === 1 ? '1 completed order deleted.' : $deleted.' completed orders deleted.';
    } else {
        $deleteNotice = 'Select at least one order to delete.';
    }
}

$orders=$pdo->query("SELECT * FROM orders WHERE status='done' ORDER BY updated_at DESC,id DESC")->fetchAll();
$itemStmt=$pdo->prepare("SELECT * FROM order_items WHERE order_id=? ORDER BY id");
$totalSales=0;$totalItems=0;
foreach($orders as &$o){$itemStmt->execute([$o['id']]);$o['items']=$itemStmt->fetchAll();$totalSales+=(float)$o['total'];foreach($o['items'] as $i)$totalItems+=(int)$i['quantity'];}unset($o);
if(isset($_GET['export'])&&$_GET['export']==='csv'){
 header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="completed-orders-'.date('Y-m-d').'.csv"');
 $out=fopen('php://output','w');fputcsv($out,['Order #','Name','Completed','Drink','Milk','Qty','Unit Price','Line Total','Order Total']);
 foreach($orders as $o){$first=true;foreach($o['items'] as $i){fputcsv($out,[$o['order_number'],$o['customer_name'],$o['updated_at'],$i['drink_name'],$i['milk_type'],(int)$i['quantity'],number_format((float)$i['price'],2,'.',''),number_format((float)$i['price']*(int)$i['quantity'],2,'.',''),$first?number_format((float)$o['total'],2,'.',''):'']);$first=false;}}
 fclose($out);exit;
}
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Completed Orders</title><link rel="stylesheet" href="../assets/style.css"></head><body class="admin-shell">
<header class="admin-header"><div class="container nav"><div class="logo">COFFEE<span>ADMIN</span></div><nav class="admin-nav"><a href="index.php">Dashboard</a><a href="drinks.php">Drinks</a><a href="milk.php">Milk Options</a><a href="completed.php">Completed Orders</a><a href="../kitchen/">Kitchen</a></nav></div></header>
<main class="container admin-main"><?php if($deleteNotice):?><div class="notice"><?=h($deleteNotice)?></div><?php endif;?><div class="page-title-row"><div><span class="eyebrow">Order History</span><h1 class="admin-page-title">Completed Orders</h1></div><a class="btn btn-yellow" href="?export=csv">DOWNLOAD CSV</a></div>
<div class="summary-grid"><div class="panel summary-box"><b>Completed Orders</b><strong><?=count($orders)?></strong></div><div class="panel summary-box"><b>Items Sold</b><strong><?=$totalItems?></strong></div><div class="panel summary-box"><b>Total Sales</b><strong>$<?=number_format($totalSales,2)?></strong></div><div class="panel summary-box"><b>Average Order</b><strong>$<?=number_format(count($orders)?$totalSales/count($orders):0,2)?></strong></div></div>
<form method="post" id="completed-orders-form">
<input type="hidden" name="csrf_token" value="<?=h($_SESSION['csrf_token'])?>">
<input type="hidden" name="delete_orders" value="1">
<div class="bulk-order-actions">
  <label class="bulk-select-label"><input type="checkbox" id="select-all-orders"> <span>Select all</span></label>
  <span id="selected-count">0 selected</span>
  <button type="submit" class="btn btn-danger" id="bulk-delete-btn" disabled>DELETE SELECTED</button>
</div>
<div class="panel completed-table-wrap"><table class="table completed-table"><thead><tr><th class="select-col"><span class="sr-only">Select</span></th><th>Order</th><th>Name</th><th>Completed</th><th>Items / Details</th><th>Qty</th><th>Price</th><th>Total</th><th>Delete</th></tr></thead><tbody>
<?php if(!$orders):?><tr><td colspan="9">No completed orders yet.</td></tr><?php endif;?>
<?php foreach($orders as $o): $qty=0;foreach($o['items'] as $i)$qty+=(int)$i['quantity'];?><tr><td class="select-col"><input class="order-checkbox" type="checkbox" name="order_ids[]" value="<?=h($o['id'])?>" aria-label="Select order <?=h($o['order_number'])?>"></td><td><strong>#<?=h($o['order_number'])?></strong></td><td><strong><?=h($o['customer_name'] ?: '—')?></strong></td><td><?=h(date('M j, Y g:i A',strtotime($o['updated_at'])))?></td><td><?php foreach($o['items'] as $i):?><div class="history-item"><strong><?=h($i['quantity'])?>× <?=h($i['drink_name'])?></strong><?php if($i['milk_type']):?><span><?=h($i['milk_type'])?></span><?php endif;?> <small>@ $<?=number_format((float)$i['price'],2)?></small></div><?php endforeach;?></td><td><?=$qty?></td><td class="muted-cell"><?=count($o['items'])===1?'$'.number_format((float)$o['items'][0]['price'],2):'Mixed'?></td><td><strong>$<?=number_format((float)$o['total'],2)?></strong></td><td><button type="submit" class="btn btn-danger btn-delete-order" data-order-id="<?=h($o['id'])?>" data-order-number="<?=h($o['order_number'])?>">DELETE</button></td></tr><?php endforeach;?>
</tbody></table></div></form>
<script>
const form=document.getElementById('completed-orders-form');
const selectAll=document.getElementById('select-all-orders');
const boxes=[...document.querySelectorAll('.order-checkbox')];
const bulkBtn=document.getElementById('bulk-delete-btn');
const countEl=document.getElementById('selected-count');
function updateSelection(){const checked=boxes.filter(b=>b.checked).length;countEl.textContent=checked+' selected';bulkBtn.disabled=checked===0;selectAll.checked=boxes.length>0&&checked===boxes.length;selectAll.indeterminate=checked>0&&checked<boxes.length;}
selectAll.addEventListener('change',()=>{boxes.forEach(b=>b.checked=selectAll.checked);updateSelection();});
boxes.forEach(b=>b.addEventListener('change',updateSelection));
document.querySelectorAll('.btn-delete-order').forEach(btn=>btn.addEventListener('click',e=>{e.preventDefault();boxes.forEach(b=>b.checked=b.value===btn.dataset.orderId);updateSelection();if(confirm('Delete completed order #'+btn.dataset.orderNumber+'? This cannot be undone.')) form.requestSubmit();}));
form.addEventListener('submit',e=>{if(document.activeElement?.classList.contains('btn-delete-order'))return;const checked=boxes.filter(b=>b.checked).length;if(!checked){e.preventDefault();return;}if(!confirm('Delete '+checked+' completed order'+(checked===1?'':'s')+'? This cannot be undone.'))e.preventDefault();});
updateSelection();
</script></main><footer class="admin-footer"><div class="container admin-footer-inner"><div class="admin-footer-name">COFFEE <span>ADMIN</span></div><div class="admin-footer-links"><a href="password.php">Change Password</a><a href="logout.php">Log out</a></div></div></footer></body></html>
