<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Administrator setup — DiginMarket</title>
<style>
body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f4f5fb;color:#1c1e2a;font-family:ui-sans-serif,system-ui,Segoe UI,Roboto,sans-serif}
.card{box-sizing:border-box;width:min(100% - 32px,440px);background:#fff;border:1px solid #d7d9e5;border-radius:16px;padding:32px;box-shadow:0 8px 28px rgba(20,22,40,.08)}
h1{font-size:28px;margin:0 0 8px}p{color:#626576;margin:0 0 24px;line-height:1.5}
label{display:block;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#555868;margin:16px 0 4px}
input{width:100%;box-sizing:border-box;border:1px solid #d7d9e5;background:#fafbff;border-radius:8px;padding:11px 12px;font-size:14px}
button{margin-top:24px;background:#3525cd;color:#fff;border:0;border-radius:8px;padding:13px 28px;font-size:15px;font-weight:600;cursor:pointer;width:100%}
.errors{background:#fdf1f2;border:1px solid #f3c2c9;border-radius:8px;padding:12px 16px;font-size:14px;color:#a11a2c}
</style>
</head>
<body>
<main class="card">
 <h1>Create your administrator account</h1>
 <p>Your database is ready. Create the account that will manage this marketplace.</p>
 @if($errors->any())<div class="errors">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
 <form method="POST" action="{{ route('admin-setup.store') }}">
  @csrf
  <label for="name">Name</label><input id="name" name="name" autocomplete="name" required value="{{ old('name') }}">
  <label for="email">Email</label><input id="email" type="email" name="email" autocomplete="email" required value="{{ old('email') }}">
  <label for="password">Password (minimum 10 characters)</label><input id="password" type="password" name="password" autocomplete="new-password" required minlength="10">
  <label for="password_confirmation">Confirm password</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required minlength="10">
  <button type="submit">Create administrator</button>
 </form>
</main>
</body>
</html>
