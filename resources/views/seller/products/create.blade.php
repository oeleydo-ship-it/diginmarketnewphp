<x-marketplace-layout title="Create Product — DiginMarket">
<div class="mx-auto max-w-3xl px-6 py-12">
    <header class="mb-10">
        <p class="font-mono text-xs font-medium uppercase tracking-wider text-primary">Seller Catalog</p>
        <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight md:text-4xl">Create a Product</h1>
        <p class="mt-3 text-on-surface-variant">The uploaded ZIP is stored privately and must pass review before publication.</p>
    </header>
    <form method="POST" action="{{ route('seller.products.store') }}" enctype="multipart/form-data" class="grid gap-5 rounded-xl border border-outline-variant bg-surface-container-lowest p-8 sm:grid-cols-2">
        @csrf
        @php($input = 'w-full rounded-lg border border-outline-variant bg-surface p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20')
        @php($label = 'font-mono text-[11px] font-medium uppercase tracking-wider text-on-surface-variant')
        <div class="flex flex-col gap-1.5">
            <label class="{{ $label }}">Title</label>
            <input name="title" required class="{{ $input }}">
        </div>
        <div class="flex flex-col gap-1.5">
            <label class="{{ $label }}">Category</label>
            <select name="category_id" required class="{{ $input }}">
                @foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
            </select>
        </div>
        <div class="flex flex-col gap-1.5 sm:col-span-2">
            <label class="{{ $label }}">Short description</label>
            <textarea name="short_description" required class="{{ $input }}"></textarea>
        </div>
        <div class="flex flex-col gap-1.5 sm:col-span-2">
            <label class="{{ $label }}">Full description</label>
            <textarea name="description" rows="8" required class="{{ $input }}"></textarea>
        </div>
        <div class="flex flex-col gap-1.5">
            <label class="{{ $label }}">Regular price</label>
            <input name="regular_price" type="number" step="0.01" required class="{{ $input }}">
        </div>
        <div class="flex flex-col gap-1.5">
            <label class="{{ $label }}">Extended price</label>
            <input name="extended_price" type="number" step="0.01" class="{{ $input }}">
        </div>
        <div class="flex flex-col gap-1.5">
            <label class="{{ $label }}">Version</label>
            <input name="version_number" value="1.0.0" required class="{{ $input }} font-mono">
        </div>
        <div class="flex flex-col gap-1.5">
            <label class="{{ $label }}">Release title</label>
            <input name="release_title" value="Initial release" required class="{{ $input }}">
        </div>
        <div class="flex flex-col gap-1.5 sm:col-span-2">
            <label class="{{ $label }}">Release notes</label>
            <textarea name="release_notes" class="{{ $input }}"></textarea>
        </div>
        <div class="flex flex-col gap-1.5 sm:col-span-2">
            <label class="{{ $label }}">Product ZIP</label>
            <label class="flex cursor-pointer flex-col items-center gap-2 rounded-xl border border-dashed border-outline-variant bg-surface p-8 text-center transition-colors hover:border-primary/60">
                <span class="material-symbols-outlined text-[32px] text-primary">upload_file</span>
                <span class="text-sm text-on-surface-variant">Drop your ZIP here or click to browse</span>
                <input name="archive" type="file" accept=".zip" required class="text-sm">
            </label>
        </div>
        @if($errors->any())
            <div class="rounded-lg border border-error/30 bg-error-container/40 p-4 text-sm font-medium text-on-error-container sm:col-span-2">{{ $errors->first() }}</div>
        @endif
        <button class="rounded-xl bg-primary p-3.5 font-semibold text-on-primary shadow-lg shadow-primary/20 transition-all hover:opacity-90 active:scale-95 sm:col-span-2">Save private draft</button>
    </form>
</div>
</x-marketplace-layout>
