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
            <textarea name="description" rows="8" required class="{{ $input }}" data-rich-editor>{{ old('description') }}</textarea>
        </div>
        <div class="flex flex-col gap-1.5">
            <label class="{{ $label }}">Regular price</label>
            <input name="regular_price" type="number" step="0.01" required class="{{ $input }}">
        </div>
        <div class="flex flex-col gap-1.5">
            <label class="{{ $label }}">Business license price</label>
            <input name="extended_price" type="number" step="0.01" class="{{ $input }}">
            <label class="mt-1 flex items-center gap-2 text-sm text-on-surface-variant"><input type="checkbox" name="business_license_enabled" value="1" checked class="rounded text-primary focus:ring-primary">Offer the business license on this product</label>
        </div>
        <div class="flex flex-col gap-1.5">
            <label class="{{ $label }}">Updates &amp; support addon price <span class="font-normal normal-case text-on-surface-variant">(optional)</span></label>
            <input name="support_extension_price" type="number" step="0.01" min="1" placeholder="Leave blank to not offer" value="{{ old('support_extension_price') }}" class="{{ $input }}">
            <p class="text-xs text-on-surface-variant">Buyers can pay this extra amount for another 6 months of updates and support. It does not sell a second license.</p>
        </div>
        <div class="flex flex-col gap-1.5">
            <label class="{{ $label }}">Addon length (months)</label>
            <input name="support_extension_months" type="number" min="1" max="36" value="{{ old('support_extension_months', 6) }}" class="{{ $input }}">
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
            <label class="flex cursor-pointer flex-col items-center gap-2 rounded-xl border border-dashed border-outline-variant bg-surface p-8 text-center transition-colors hover:border-primary/60 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-primary/40">
                <span class="material-symbols-outlined text-[32px] text-primary" aria-hidden="true">upload_file</span>
                <span class="text-sm text-on-surface-variant" data-file-label>Drop your ZIP here or click to browse</span>
                <span class="text-xs text-on-surface-variant/70">ZIP only · up to 100 MB</span>
                <input name="archive" type="file" accept=".zip" required class="sr-only" data-file-input>
            </label>
        </div>
        <div class="flex flex-col gap-1.5 sm:col-span-2">
            <label class="{{ $label }}">Product images <span class="font-normal normal-case text-on-surface-variant">(optional — up to 6; the first becomes the cover)</span></label>
            <label class="flex cursor-pointer flex-col items-center gap-2 rounded-xl border border-dashed border-outline-variant bg-surface p-8 text-center transition-colors hover:border-primary/60 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-primary/40">
                <span class="material-symbols-outlined text-[32px] text-primary" aria-hidden="true">add_photo_alternate</span>
                <span class="text-sm text-on-surface-variant" data-file-label>Add screenshots or cover art</span>
                <span class="text-xs text-on-surface-variant/70">JPG, PNG or WebP · 5 MB each · recommended 1280×720 px (16:9) or larger — covers are displayed cropped to 16:9</span>
                <input name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple class="sr-only" data-file-input>
            </label>
        </div>
        <div class="flex flex-col gap-1.5 sm:col-span-2">
            <label class="{{ $label }}">Image URLs <span class="font-normal normal-case text-on-surface-variant">(optional — one https:// link per line, counted toward the 6-image limit)</span></label>
            <textarea name="image_urls" rows="2" placeholder="https://example.com/screenshot-1.png" class="{{ $input }} font-mono text-xs">{{ old('image_urls') }}</textarea>
        </div>
        <div class="flex flex-col gap-1.5">
            <label class="{{ $label }}">Live demo URL <span class="font-normal normal-case text-on-surface-variant">(optional)</span></label>
            <input name="demo_url" type="url" placeholder="https://demo.example.com" value="{{ old('demo_url') }}" class="{{ $input }}">
        </div>
        <div class="flex flex-col gap-1.5">
            <label class="{{ $label }}">Video URL <span class="font-normal normal-case text-on-surface-variant">(optional — YouTube or Vimeo)</span></label>
            <input name="video_url" type="url" placeholder="https://youtube.com/watch?v=..." value="{{ old('video_url') }}" class="{{ $input }}">
        </div>
        @if($errors->any())
            <div class="rounded-lg border border-error/30 bg-error-container/40 p-4 text-sm font-medium text-on-error-container sm:col-span-2">{{ $errors->first() }}</div>
        @endif
        <button class="rounded-xl bg-primary p-3.5 font-semibold text-on-primary shadow-lg shadow-primary/20 transition-all hover:opacity-90 active:scale-95 sm:col-span-2">Save private draft</button>
    </form>
</div>
</x-marketplace-layout>
