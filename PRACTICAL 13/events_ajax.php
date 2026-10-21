<?php
require "auth.php";
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    http_response_code(403);
    exit("Access denied. Admins only.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Practical 13 - AJAX Event Management</title>
<style>
*{box-sizing:border-box}body{font-family:Arial,sans-serif;margin:0;background:#f5f6fa;color:#222}
header{background:#941616;color:white;padding:18px 5%;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap}
header h1{font-size:22px;margin:0}header a{color:white}main{max-width:1100px;margin:24px auto;padding:0 16px}
.panel{background:white;padding:20px;border-radius:10px;box-shadow:0 3px 14px #0001;margin-bottom:20px}
.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}label{display:block;font-weight:bold;margin-bottom:6px}
input,select{width:100%;padding:10px;border:1px solid #ccc;border-radius:5px;font:inherit}
button{border:0;border-radius:5px;padding:10px 14px;cursor:pointer;font:inherit;background:#941616;color:white}
button.secondary{background:#555}button.danger{background:#b3261e}button:disabled{opacity:.6;cursor:wait}
.actions,.small-buttons{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}
.small-buttons{margin:0}.small-buttons button{padding:7px 10px}
#message{padding:12px;border-radius:5px;margin-bottom:16px;display:none}
#message.success{display:block;background:#e8f5e9;color:#1b5e20}#message.error{display:block;background:#ffebee;color:#b71c1c}
#message.info{display:block;background:#e3f2fd;color:#0d47a1}.search-row{display:flex;gap:10px;align-items:end;margin-bottom:16px}
.search-row>div{flex:1}.table-wrap{overflow-x:auto}table{border-collapse:collapse;width:100%;min-width:700px}
th,td{text-align:left;border-bottom:1px solid #ddd;padding:11px 9px;vertical-align:top}th{background:#f2f2f2}
#loading{display:none;padding:12px 0}.muted{color:#666;font-size:13px}
@media(max-width:650px){.grid{grid-template-columns:1fr}.search-row{flex-direction:column;align-items:stretch}}
</style>
</head>
<body>
<header><h1>StudentHub — Practical 13</h1>
<div>Welcome, <?php echo htmlspecialchars($_SESSION["name"] ?? "Admin", ENT_QUOTES, "UTF-8"); ?> |
<a href="admin.php">Admin Dashboard</a> | <a href="logout.php">Logout</a></div></header>
<main>
<div id="message" role="status" aria-live="polite"></div>
<section class="panel">
<h2 id="formTitle">Add Event</h2>
<form id="eventForm">
<input type="hidden" id="event_id">
<div class="grid">
<div><label for="event_name">Event name *</label><input id="event_name" maxlength="100" required></div>
<div><label for="event_date">Event date *</label><input type="date" id="event_date" required></div>
<div><label for="category">Category *</label><select id="category" required>
<option value="">Choose category</option><option>Technical</option><option>Cultural</option><option>Sports</option><option>Workshop</option><option>Other</option></select></div>
<div><label for="venue">Venue *</label><input id="venue" maxlength="100" required></div>
</div>
<div class="actions"><button id="saveButton" type="submit">Add Event</button><button class="secondary" id="cancelButton" type="button" hidden>Cancel Edit</button></div>
<p class="muted">All fields are required. CRUD requests run asynchronously without reloading the page.</p>
</form></section>
<section class="panel"><h2>Events</h2>
<div class="search-row"><div><label for="search">Search events</label><input id="search" type="search" placeholder="Search name, category, or venue..."></div><button class="secondary" id="refreshButton" type="button">Refresh</button></div>
<div id="loading">Loading events...</div><div class="table-wrap"><table>
<thead><tr><th>ID</th><th>Event</th><th>Date</th><th>Category</th><th>Venue</th><th>Actions</th></tr></thead>
<tbody id="eventsBody"><tr><td colspan="6">Events will appear here.</td></tr></tbody>
</table></div></section>
</main>
<script>
"use strict";
const API="events_api.php", form=document.getElementById("eventForm"), message=document.getElementById("message");
const body=document.getElementById("eventsBody"), loading=document.getElementById("loading"), save=document.getElementById("saveButton");
let timer=null;
function msg(text,type="info"){message.textContent=text;message.className=type}
function clearMsg(){message.textContent="";message.className=""}
async function request(url,options={}){
 const response=await fetch(url,options);let data;
 try{data=await response.json()}catch(e){throw new Error("Server did not return valid JSON. Check PHP errors/API URL.")}
 if(!response.ok||data.success!==true)throw new Error(data.message||("HTTP "+response.status));
 return data;
}
function cell(value){const td=document.createElement("td");td.textContent=value??"";return td}
async function loadEvents(query=""){
 loading.style.display="block";body.replaceChildren();
 try{
  const data=await request(API+"?search="+encodeURIComponent(query));
  if(!data.events.length){const tr=document.createElement("tr"),td=cell("No events found.");td.colSpan=6;tr.appendChild(td);body.appendChild(tr)}
  data.events.forEach(ev=>{
   const tr=document.createElement("tr");
   [ev.event_id,ev.event_name,ev.event_date,ev.category,ev.venue].forEach(v=>tr.appendChild(cell(v)));
   const td=document.createElement("td"),actions=document.createElement("div");actions.className="small-buttons";
   const edit=document.createElement("button");edit.type="button";edit.className="secondary";edit.textContent="Edit";
   edit.onclick=()=>editEvent(ev);
   const del=document.createElement("button");del.type="button";del.className="danger";del.textContent="Delete";
   del.onclick=()=>deleteEvent(ev.event_id,ev.event_name);
   actions.append(edit,del);td.appendChild(actions);tr.appendChild(td);body.appendChild(tr);
  });
 }catch(e){body.replaceChildren();const tr=document.createElement("tr"),td=cell("Could not load events: "+e.message);td.colSpan=6;tr.appendChild(td);body.appendChild(tr);msg(e.message,"error")}
 finally{loading.style.display="none"}
}
function editEvent(ev){
 document.getElementById("event_id").value=ev.event_id;
 document.getElementById("event_name").value=ev.event_name;
 document.getElementById("event_date").value=ev.event_date;
 document.getElementById("category").value=ev.category;
 document.getElementById("venue").value=ev.venue;
 document.getElementById("formTitle").textContent="Edit Event #"+ev.event_id;
 save.textContent="Save Changes";document.getElementById("cancelButton").hidden=false;clearMsg();
}
function resetForm(){form.reset();document.getElementById("event_id").value="";document.getElementById("formTitle").textContent="Add Event";save.textContent="Add Event";document.getElementById("cancelButton").hidden=true}
form.addEventListener("submit",async e=>{
 e.preventDefault();clearMsg();
 const id=document.getElementById("event_id").value;
 const data={action:id?"update":"add",event_name:document.getElementById("event_name").value.trim(),
 event_date:document.getElementById("event_date").value,category:document.getElementById("category").value,
 venue:document.getElementById("venue").value.trim()};
 if(id)data.event_id=Number(id);
 if(!data.event_name||!data.event_date||!data.category||!data.venue){msg("Please fill in all fields.","error");return}
 if(data.event_name.length>100||data.venue.length>100){msg("Event name and venue must be 100 characters or fewer.","error");return}
 save.disabled=true;save.textContent="Saving...";
 try{const result=await request(API,{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify(data)});
 msg(result.message,"success");resetForm();await loadEvents(document.getElementById("search").value.trim())}
 catch(err){msg(err.message,"error")}
 finally{save.disabled=false;if(!document.getElementById("event_id").value)save.textContent="Add Event";else save.textContent="Save Changes"}
});
async function deleteEvent(id,name){
 if(!confirm('Delete event "'+name+'"?'))return;clearMsg();
 try{const result=await request(API,{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({action:"delete",event_id:Number(id)})});
 msg(result.message,"success");await loadEvents(document.getElementById("search").value.trim())}
 catch(err){msg(err.message,"error")}
}
document.getElementById("cancelButton").onclick=()=>{resetForm();clearMsg()};
document.getElementById("refreshButton").onclick=()=>{clearMsg();loadEvents(document.getElementById("search").value.trim())};
document.getElementById("search").addEventListener("input",()=>{clearTimeout(timer);timer=setTimeout(()=>loadEvents(document.getElementById("search").value.trim()),350)});
loadEvents();
</script>
</body></html>
