@props(['title' => 'Admin'])
<!DOCTYPE html>
<html lang="{{ str_replace('_','-',app()->getLocale()) }}">
<head>
 <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
 <title>{{ $title }} · DiginMarket</title>
 <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
 <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
 @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="bg-[#f8f9ff] font-sans text-[#111827] antialiased">
 <div class="min-h-screen lg:grid lg:grid-cols-[280px_1fr]">
  <aside data-admin-sidebar class="fixed inset-y-0 left-0 z-40 hidden w-[280px] flex-col border-r border-[#d7d9e5] bg-[#f8f9ff] p-5 lg:flex">
   <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-4 px-2 py-3">
    <span class="material-symbols-outlined flex h-12 w-12 items-center justify-center rounded-xl bg-[#4338db] text-white">storefront</span>
    <span><strong class="block text-lg text-[#251bd5]">{{ config('app.name', 'DiginMarket') }}</strong><small class="text-[#555868]">Admin Management</small></span>
   </a>
   <nav class="mt-10 flex-1 space-y-1 overflow-y-auto text-[14px] font-medium">
    @php($links=[['admin.dashboard','dashboard','Dashboard'],['admin.orders.index','shopping_cart','Orders'],['admin.products.review','inventory_2','Product review'],['admin.categories.index','category','Categories'],['admin.sellers.index','store','Seller applications'],['admin.users.index','group','Customers'],['admin.support.index','support_agent','Support'],['admin.refunds.index','assignment_return','Refunds'],['admin.disputes.index','gavel','Disputes'],['admin.withdrawals.index','payments','Withdrawals'],['admin.coupons.index','sell','Coupons'],['admin.pages.index','article','Content pages'],['admin.blog.index','rss_feed','Blog'],['admin.menus.index','list','Menus'],['admin.audits.index','history','Audit log'],['admin.system','monitor_heart','System health'],['admin.settings.index','settings','Settings']])
    @foreach($links as [$route,$icon,$label])
     @continue(!Route::has($route))
     <a href="{{ route($route) }}" class="flex items-center gap-4 rounded-lg px-4 py-3.5 {{ request()->routeIs(str_replace('.index','.*',$route)) || request()->routeIs($route) ? 'bg-[#e5ecff] font-semibold text-[#2419d2]' : 'text-[#303244] hover:bg-[#eef1fa]' }}"><span class="material-symbols-outlined">{{ $icon }}</span>{{ $label }}</a>
    @endforeach
   </nav>
   <div class="mt-auto space-y-2 border-t border-[#d7d9e5] pt-5 text-[15px]">
    <a href="{{ route('support.index') }}" class="flex items-center gap-4 rounded-lg px-4 py-3 hover:bg-[#eef1fa]"><span class="material-symbols-outlined">help</span>Help Center</a>
    <form method="POST" action="{{ route('logout') }}">@csrf<button class="flex w-full items-center gap-4 rounded-lg px-4 py-3 text-left text-[#b42318] hover:bg-red-50"><span class="material-symbols-outlined">logout</span>Logout</button></form>
   </div>
  </aside>
  <div class="min-w-0 lg:col-start-2">
   <header class="sticky top-0 z-30 flex h-20 items-center justify-between border-b border-[#d7d9e5] bg-[#f8f9ff]/95 px-5 backdrop-blur md:px-8">
    <div class="flex items-center gap-3"><button data-admin-toggle class="rounded-lg p-2 hover:bg-[#eef1fa] lg:hidden" aria-label="Open navigation"><span class="material-symbols-outlined">menu</span></button><form action="{{ route('admin.orders.index') }}" class="relative hidden sm:block"><span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-[#555868]">search</span><input name="q" class="w-[min(42vw,480px)] rounded-xl border-0 bg-[#edf2ff] py-3 pl-12 pr-4 text-sm outline-none ring-[#4338db]/20 focus:ring-2" placeholder="Search orders, IDs, customers..."></form></div>
    <div class="flex items-center gap-4"><span class="material-symbols-outlined">notifications</span><span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#4338db] font-bold text-white">{{ str(auth()->user()->name)->substr(0,1)->upper() }}</span><strong class="hidden text-sm sm:block">Admin Panel</strong></div>
   </header>
   <main>@if(session('status'))<div class="mx-auto mt-5 max-w-[1400px] px-5 md:px-8"><div class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"><span class="material-symbols-outlined">check_circle</span>{{ session('status') }}</div></div>@endif{{ $slot }}</main>
  </div>
 </div>
</body></html>
