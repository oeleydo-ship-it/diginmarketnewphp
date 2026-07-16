<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Install — DiginMarket</title>
<style>
:root{color-scheme:light}
body{margin:0;font-family:ui-sans-serif,system-ui,Segoe UI,Roboto,sans-serif;background:#f4f5fb;color:#1c1e2a}
.wrap{max-width:640px;margin:0 auto;padding:48px 24px}
.card{background:#fff;border:1px solid #d7d9e5;border-radius:16px;padding:32px;margin-top:24px;box-shadow:0 1px 3px rgba(20,22,40,.06)}
h1{font-size:32px;margin:0}
p.lead{color:#626576;margin-top:8px}
h2{font-size:18px;margin:0 0 16px}
ul.reqs{list-style:none;margin:0;padding:0}
ul.reqs li{display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid #eef0f6;font-size:14px}
.ok{color:#0a7d4f;font-weight:600}.bad{color:#c0263a;font-weight:600}
label{display:block;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#555868;margin:16px 0 4px}
input{width:100%;box-sizing:border-box;border:1px solid #d7d9e5;background:#fafbff;border-radius:8px;padding:11px 12px;font-size:14px}
button{margin-top:24px;background:#3525cd;color:#fff;border:0;border-radius:8px;padding:13px 28px;font-size:15px;font-weight:600;cursor:pointer;width:100%}
button:disabled{background:#a9a6d8;cursor:not-allowed}
.errors{background:#fdf1f2;border:1px solid #f3c2c9;border-radius:8px;padding:12px 16px;font-size:14px;margin-top:16px;color:#a11a2c}
.badge{display:inline-block;background:#e5ecff;color:#2419d2;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;border-radius:999px;padding:6px 14px}
</style>
</head>
<body>
<div class="wrap">
 <span class="badge">DiginMarket Setup</span>
 <h1>Install your marketplace</h1>
 <p class="lead">Verify the server requirements, connect your database, then create the administrator account.</p>
 <div class="card">
  <h2>Server requirements</h2>
  <ul class="reqs">
   @foreach($requirements as $label=>$ok)
   <li><span>{{ $label }}</span><span class="{{ $ok?'ok':'bad' }}">{{ $ok?'Passed':'Failed' }}</span></li>
   @endforeach
  </ul>
 </div>
 <div class="card">
  <h2>Step 1 — Database</h2>
  @if(session('db_status'))<div class="errors" style="background:#eefaf3;border-color:#bfe6d2;color:#0a7d4f">{{ session('db_status') }}</div>@endif
  @error('database')<div class="errors">{{ $message }}</div>@enderror
  <form method="POST" action="{{ route('install.database') }}">
   @csrf
   <label for="connection">Database driver</label>
   <select id="connection" name="connection" style="width:100%;box-sizing:border-box;border:1px solid #d7d9e5;background:#fafbff;border-radius:8px;padding:11px 12px;font-size:14px">
    <option value="mysql" @selected(old('connection','mysql')==='mysql')>MySQL / MariaDB</option>
    <option value="sqlite" @selected(old('connection')==='sqlite')>SQLite (single file, small sites)</option>
   </select>
   <div id="mysql-fields">
    <p style="font-size:12px;color:#626576;margin:12px 0 0">MySQL settings — ignored when SQLite is selected.</p>
    <label for="host">Host</label><input id="host" name="host" value="{{ old('host','127.0.0.1') }}">
    <label for="port">Port</label><input id="port" name="port" type="number" value="{{ old('port',3306) }}">
    <label for="database">Database name</label><input id="database" name="database" value="{{ old('database') }}">
    <label for="username">Username</label><input id="username" name="username" value="{{ old('username') }}">
    <label for="password">Password</label><input id="password" name="password" type="password" value="">
   </div>
   <button type="submit">Test connection &amp; save</button>
  </form>
 </div>
 <div class="card">
  <h2>Step 2 — Administrator account</h2>
  @if($errors->any() && !$errors->has('database'))<div class="errors">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
  <form method="POST" action="{{ route('install.store') }}">
   @csrf
   <label for="site_name">Marketplace name</label><input id="site_name" name="site_name" required value="{{ old('site_name','DiginMarket') }}">
   <label for="admin_name">Admin name</label><input id="admin_name" name="admin_name" required value="{{ old('admin_name') }}">
   <label for="admin_email">Admin email</label><input id="admin_email" type="email" name="admin_email" required value="{{ old('admin_email') }}">
   <label for="admin_password">Admin password (min 10 characters)</label><input id="admin_password" type="password" name="admin_password" required minlength="10">
   <label for="admin_password_confirmation">Confirm password</label><input id="admin_password_confirmation" type="password" name="admin_password_confirmation" required minlength="10">
   <button type="submit" @disabled(collect($requirements)->contains(fn($ok)=>!$ok))>Install marketplace</button>
  </form>
 </div>
</div>
</body>
</html>
