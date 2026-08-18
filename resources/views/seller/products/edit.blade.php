<x-marketplace-layout :title="'Manage ' . $product->title . ' — DiginMarket'">
<div class="mx-auto max-w-4xl px-6 py-12">
    <header class="mb-10">
        <a href="{{ route('seller.products.index') }}" class="mb-3 inline-flex items-center gap-1 text-sm font-medium text-on-surface-variant transition-colors hover:text-primary">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span> My products
        </a>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="font-mono text-xs font-medium uppercase tracking-wider text-primary">Seller Catalog</p>
                <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight">{{ $product->title }}</h1>
            </div>
            @php($status = $product->status->value)
            <span class="rounded px-3 py-1.5 font-mono text-xs font-bold uppercase tracking-wider {{ in_array($status, ['approved', 'published'], true) ? 'bg-secondary-container/40 text-on-secondary-container' : ($status === 'rejected' ? 'bg-error-container text-on-error-container' : 'bg-primary-container/20 text-primary') }}">{{ str($status)->headline() }}</span>
        </div>
    </header>
    @php($input = 'w-full rounded-lg border border-outline-variant bg-surface p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 disabled:opacity-60')
    @php($labelCls = 'font-mono text-[11px] font-medium uppercase tracking-wider text-on-surface-variant')
    @php($isPublished = $product->status->value === 'published')
    @php($unlocked = $isPublished && request()->boolean('unlock'))
    @php($locked = !auth()->user()->can('update', $product) || ($isPublished && !$unlocked))
    <section class="rounded-xl border border-outline-variant bg-surface-container-lowest p-8">
        <h2 class="mb-2 font-display text-lg font-semibold">Product Details</h2>
        @if($isPublished && !$unlocked)
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-tertiary-fixed/40 px-4 py-3 text-sm font-medium text-on-tertiary-fixed-variant">
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">lock</span>
                    This listing is live. Unlock it to edit the details — saving sends it back for review.
                </span>
                <a href="{{ route('seller.products.edit', [$product, 'unlock' => 1]) }}" class="flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-on-primary transition-all hover:opacity-90 active:scale-95">
                    <span class="material-symbols-outlined text-[16px]">lock_open</span> Unlock &amp; edit
                </a>
            </div>
        @elseif($unlocked)
            <p class="mb-5 flex items-center gap-2 rounded-lg border border-error/30 bg-error-container/40 px-4 py-3 text-sm font-medium text-on-error-container">
                <span class="material-symbols-outlined text-[18px]">warning</span>
                You are editing a live listing. Saving submits it for review and hides it from the marketplace until an administrator approves the changes.
            </p>
        @elseif($locked)
            <p class="mb-5 flex items-center gap-2 rounded-lg bg-tertiary-fixed/40 px-4 py-3 text-sm font-medium text-on-tertiary-fixed-variant">
                <span class="material-symbols-outlined text-[18px]">lock</span>
                This listing is awaiting review and cannot be edited right now.
            </p>
        @endif
        <form method="POST" action="{{ route('seller.products.update', $product) }}" class="grid gap-5 sm:grid-cols-2">
            @csrf @method('PUT')
            <div class="flex flex-col gap-1.5">
                <label class="{{ $labelCls }}">Title</label>
                <input name="title" required value="{{ old('title', $product->title) }}" @disabled($locked) class="{{ $input }}">
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="{{ $labelCls }}">Category</label>
                <select name="category_id" required @disabled($locked) class="{{ $input }}">
                    @foreach($categories as $category)<option value="{{ $category->id }}" @selected($product->category_id === $category->id)>{{ $category->name }}</option>@endforeach
                </select>
            </div>
            <div class="flex flex-col gap-1.5 sm:col-span-2">
                <label class="{{ $labelCls }}">Short description</label>
                <textarea name="short_description" required @disabled($locked) class="{{ $input }}">{{ old('short_description', $product->short_description) }}</textarea>
            </div>
            <div class="flex flex-col gap-1.5 sm:col-span-2">
                <label class="{{ $labelCls }}">Full description</label>
                <textarea name="description" rows="8" required @disabled($locked) class="{{ $input }}" data-rich-editor>{{ old('description', $product->description) }}</textarea>
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="{{ $labelCls }}">Live demo URL</label>
                <input name="demo_url" type="url" placeholder="https://demo.example.com" value="{{ old('demo_url', $product->demo_url) }}" @disabled($locked) class="{{ $input }}">
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="{{ $labelCls }}">Video URL (YouTube or Vimeo)</label>
                <input name="video_url" type="url" placeholder="https://youtube.com/watch?v=..." value="{{ old('video_url', $product->video_url) }}" @disabled($locked) class="{{ $input }}">
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="{{ $labelCls }}">Regular price</label>
                <input name="regular_price" type="number" step="0.01" required value="{{ old('regular_price', $product->regular_price) }}" @disabled($locked) class="{{ $input }}">
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="{{ $labelCls }}">Business license price</label>
                <input name="extended_price" type="number" step="0.01" value="{{ old('extended_price', $product->extended_price) }}" @disabled($locked) class="{{ $input }}">
                <label class="mt-1 flex items-center gap-2 text-sm text-on-surface-variant"><input type="checkbox" name="business_license_enabled" value="1" @checked(old('business_license_enabled', $product->business_license_enabled)) @disabled($locked) class="rounded text-primary focus:ring-primary">Offer the business license on this product</label>
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="{{ $labelCls }}">Updates &amp; support addon price <span class="normal-case text-on-surface-variant">(optional)</span></label>
                <input name="support_extension_price" type="number" step="0.01" min="1" placeholder="Leave blank to not offer" value="{{ old('support_extension_price', $product->support_extension_price) }}" @disabled($locked) class="{{ $input }}">
                <p class="text-xs text-on-surface-variant">Extra payment for another period of updates and support on an existing license. Leave blank to hide the addon.</p>
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="{{ $labelCls }}">Addon length (months)</label>
                <input name="support_extension_months" type="number" min="1" max="36" value="{{ old('support_extension_months', $product->support_extension_months ?? 6) }}" @disabled($locked) class="{{ $input }}">
            </div>
            @if($errors->any())
                <div class="rounded-lg border border-error/30 bg-error-container/40 p-4 text-sm font-medium text-on-error-container sm:col-span-2">{{ $errors->first() }}</div>
            @endif
            @unless($locked)
                <button class="rounded-xl bg-primary p-3.5 font-semibold text-on-primary shadow-lg shadow-primary/20 transition-all hover:opacity-90 active:scale-95 sm:col-span-2">{{ $unlocked ? 'Save & submit for review' : 'Save changes' }}</button>
            @endunless
        </form>
    </section>
    <section class="mt-8 rounded-xl border border-outline-variant bg-surface-container-lowest p-8">
        <h2 class="mb-2 font-display text-lg font-semibold">Images</h2>
        <p class="mb-5 text-sm text-on-surface-variant">Up to 6 screenshots or cover art (JPG/PNG/WebP, 5&nbsp;MB each). The first image is used as the cover across the marketplace.</p>
        @if($product->images->isNotEmpty())
            <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach($product->images as $image)
                    <div class="group relative overflow-hidden rounded-xl border border-outline-variant">
                        <img src="{{ $image->url() }}" alt="{{ $image->alt ?? $product->title }}" class="aspect-video w-full object-cover">
                        @if($loop->first)<span class="absolute left-2 top-2 rounded-full bg-primary px-2.5 py-0.5 font-mono text-[10px] font-bold uppercase tracking-wider text-on-primary">Cover</span>@endif
                        <form method="POST" action="{{ route('seller.products.images.destroy', [$product, $image]) }}" data-confirm="Remove this image?" class="absolute right-2 top-2">
                            @csrf @method('DELETE')
                            <button class="flex h-7 w-7 items-center justify-center rounded-full bg-surface/90 text-error shadow-sm transition-all hover:bg-error hover:text-on-error" aria-label="Remove image"><span class="material-symbols-outlined text-[16px]">delete</span></button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
        @if($product->images->count() < 6)
            <form method="POST" action="{{ route('seller.products.images.store', $product) }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">
                @csrf
                <label class="flex flex-1 cursor-pointer flex-col items-center gap-2 rounded-xl border border-dashed border-outline-variant bg-surface p-6 text-center transition-colors hover:border-primary/60 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-primary/40">
                    <span class="material-symbols-outlined text-[28px] text-primary" aria-hidden="true">add_photo_alternate</span>
                    <span class="text-sm text-on-surface-variant" data-file-label>Add images — they upload as soon as you choose them</span>
                    <span class="text-xs text-on-surface-variant/70">JPG, PNG or WebP · 5 MB each · recommended 1280×720 px (16:9) or larger</span>
                    <input name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple data-auto-submit data-file-input class="sr-only">
                </label>
                <div class="flex flex-1 flex-col gap-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wide text-on-surface-variant">Or paste image URLs (one per line)</label>
                    <textarea name="image_urls" rows="3" placeholder="https://example.com/screenshot.png" class="rounded-lg border border-outline-variant bg-surface px-3 py-2 font-mono text-xs outline-none focus:border-primary"></textarea>
                </div>
                <button class="rounded-xl bg-primary px-5 py-3 font-semibold text-on-primary transition-all hover:opacity-90 active:scale-95">Add images</button>
            </form>
        @endif
    </section>
    <section class="mt-8 rounded-xl border border-outline-variant bg-surface-container-lowest p-8">
        <h2 class="mb-5 font-display text-lg font-semibold">Versions</h2>
        <div class="space-y-3">
            @foreach($versions as $version)
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-outline-variant/50 bg-surface p-4">
                    <div>
                        <span class="font-mono text-sm font-semibold">v{{ $version->version_number }}</span>
                        <span class="ml-2 text-sm text-on-surface-variant">{{ $version->release_title }}</span>
                    </div>
                    @php($vs = $version->status->value)
                    <span class="rounded px-2 py-1 font-mono text-[10px] font-bold uppercase tracking-wider {{ $vs === 'published' ? 'bg-secondary-container/40 text-on-secondary-container' : ($vs === 'rejected' ? 'bg-error-container text-on-error-container' : ($vs === 'pending_review' ? 'bg-primary-container/20 text-primary' : 'bg-outline-variant/30 text-on-surface-variant')) }}">{{ str($vs)->headline() }}</span>
                </div>
            @endforeach
        </div>
        @can('addVersion', $product)
            <h3 class="mb-4 mt-8 font-display font-semibold">Release a new version</h3>
            <form method="POST" action="{{ route('seller.products.versions.store', $product) }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2">
                @csrf
                <div class="flex flex-col gap-1.5">
                    <label class="{{ $labelCls }}">Version number</label>
                    <input name="version_number" required placeholder="e.g. 1.1.0" class="{{ $input }} font-mono">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="{{ $labelCls }}">Release title</label>
                    <input name="release_title" required class="{{ $input }}">
                </div>
                <div class="flex flex-col gap-1.5 sm:col-span-2">
                    <label class="{{ $labelCls }}">Release notes</label>
                    <textarea name="release_notes" class="{{ $input }}"></textarea>
                </div>
                <div class="flex flex-col gap-1.5 sm:col-span-2">
                    <label class="{{ $labelCls }}">Updated ZIP</label>
                    <input name="archive" type="file" accept=".zip" required class="{{ $input }}">
                </div>
                <button class="rounded-xl bg-primary p-3.5 font-semibold text-on-primary shadow-lg shadow-primary/20 transition-all hover:opacity-90 active:scale-95 sm:col-span-2">Submit version for review</button>
            </form>
        @endcan
    </section>
</div>
</x-marketplace-layout>
