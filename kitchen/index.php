<?php require_once __DIR__.'/../config/auth.php';require_admin();?><!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Kitchen Display</title><link rel="stylesheet" href="../assets/style.css"></head>
<body class="kitchen"><header class="kitchen-head"><div class="container"><div class="logo">COFFEE<span>KITCHEN</span></div><div class="kitchen-links"><a href="../admin/completed.php">Completed Orders</a><a href="../admin/">Admin</a></div></div></header>
<main class="container board"><section class="column"><h2>Ordered</h2><div id="ordered"></div></section><section class="column"><h2>Making</h2><div id="making"></div></section><section class="column"><h2>Done <span id="done-count"></span></h2><div id="done"></div></section></main>
<script>
function esc(v){return String(v??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]))}
function money(v){return '$'+Number(v).toFixed(2)}
function time(v){return new Date(v.replace(' ','T')).toLocaleTimeString([], {hour:'numeric',minute:'2-digit'})}
function itemCount(items){return items.reduce((n,i)=>n+Number(i.quantity),0)}
async function load(){
 const r=await fetch('../api/orders.php',{cache:'no-store'});const d=await r.json();if(!d.success)return;
 ['ordered','making','done'].forEach(s=>document.getElementById(s).innerHTML='');
 const doneOrders=d.orders.filter(o=>o.status==='done');document.getElementById('done-count').textContent=`(${doneOrders.length})`;
 d.orders.forEach(o=>{
   const target=document.getElementById(o.status);if(!target)return;
   const items=o.items.map(i=>`<div class="order-item"><strong>${esc(i.quantity)}x ${esc(i.drink_name)}</strong><div class="milk">${esc(i.milk_type||'')}</div><div class="item-price">${money(Number(i.price)*Number(i.quantity))}</div></div>`).join('');
   if(o.status==='done'){
     const el=document.createElement('details');el.className='order-card done-card';
     el.innerHTML=`<summary><span><strong>#${esc(o.order_number)} · ${esc(o.customer_name||'No name')}</strong><small>${itemCount(o.items)} item${itemCount(o.items)===1?'':'s'} · ${time(o.updated_at||o.created_at)}</small></span><b>${money(o.total)}</b></summary><div class="done-details">${items}<div class="order-total">Total ${money(o.total)}</div></div>`;
     target.appendChild(el);return;
   }
   const el=document.createElement('article');el.className='order-card';const next=o.status==='ordered'?'making':'done';const label=o.status==='ordered'?'START MAKING':'MARK DONE';
   el.innerHTML=`<div class="order-top"><div><div class="order-num">#${esc(o.order_number)}</div><div class="order-name">${esc(o.customer_name||'No name')}</div></div><div class="order-time">${time(o.created_at)}</div></div>${items}<div class="order-total">${money(o.total)}</div><button class="btn btn-yellow" style="width:100%" onclick="setStatus(${Number(o.id)},'${next}')">${label}</button>`;target.appendChild(el);
 });
}
async function setStatus(id,status){await fetch('../api/status.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id,status})});load()}
load();setInterval(load,4000);
</script></body></html>
