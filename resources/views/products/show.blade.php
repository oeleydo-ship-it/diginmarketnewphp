<x-nexus-layout :title="$product->seo_title ?: $product->title" :description="$product->seo_description ?: $product->short_description">
@php
    $sellerName = $product->seller->sellerProfile?->display_name ?? $product->seller->name;
    $latestVersion = $product->versions->sortByDesc('published_at')->first();
    $reviewCount = $product->reviews->count();
    $commentCount = $product->comments->count();
@endphp
<div class="mx-auto max-w-7xl px-6 pb-10 pt-8">

    <!-- Breadcrumbs -->
    <nav class="mb-6 flex items-center gap-2 text-sm text-on-surface-variant">
        <a href="{{ route('home') }}" class="transition-colors hover:text-primary">Marketplace</a>
        <span class="material-symbols-outlined text-[16px]">chevron_right</span>
        <a href="{{ route('categories.show', $product->category->slug) }}" class="transition-colors hover:text-primary">{{ $product->category->name }}</a>
        <span class="material-symbols-outlined text-[16px]">chevron_right</span>
        <span class="truncate font-semibold text-on-surface">{{ $product->title }}</span>
    </nav>

    <!-- Product Header -->
    <div class="mb-6">
        <h1 class="font-display text-3xl font-semibold tracking-tight text-on-surface">{{ $product->title }}</h1>
        <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-on-surface-variant">
            <span class="flex items-center gap-2">
                by <a href="{{ route('sellers.show', $product->seller->sellerProfile?->username ?? '#') }}" class="font-semibold text-primary hover:underline">{{ $sellerName }}</a>
            </span>
            <span class="flex items-center gap-1.5">
                <x-rating-stars :rating="$product->average_rating" :size="16" />
                <span>{{ number_format((float) $product->average_rating, 1) }} ({{ $reviewCount }} {{ str('review')->plural($reviewCount) }})</span>
            </span>
            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">shopping_bag</span>{{ number_format($product->sales_count) }} sales</span>
            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">visibility</span>{{ number_format($product->views_count) }} views</span>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <!-- Left Column -->
        <div class="space-y-6 lg:col-span-8">
            <!-- Preview -->
            <section class="overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-sm">
                <x-product-thumb :product="$product" class="aspect-video w-full" />
                @if($product->videoEmbedUrl())
                    <div class="aspect-video w-full border-t border-outline-variant">
                        <iframe src="{{ $product->videoEmbedUrl() }}" title="{{ $product->title }} video preview" class="h-full w-full" loading="lazy" allow="accelerometer; encrypted-media; picture-in-picture; fullscreen" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                    </div>
                @endif
                @if($product->images->count() > 1)
                    <div class="flex gap-3 overflow-x-auto border-t border-outline-variant p-4">
                        @foreach($product->images as $image)
                            <a href="{{ $image->url() }}" target="_blank" rel="noopener" class="shrink-0 overflow-hidden rounded-lg border border-outline-variant transition-all hover:border-primary" aria-label="View screenshot {{ $loop->iteration }} full size">
                                <img src="{{ $image->url() }}" alt="{{ $image->alt ?? $product->title.' screenshot '.$loop->iteration }}" class="h-20 w-32 object-cover" loading="lazy">
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>

            <!-- Tabs -->
            <section data-tabs class="overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-sm">
                <div class="flex overflow-x-auto border-b border-outline-variant">
                    <button data-tab="description" class="tab-active shrink-0 border-b-2 border-transparent px-6 py-4 font-semibold text-on-surface-variant transition-colors hover:text-on-surface">Description</button>
                    <button data-tab="reviews" class="shrink-0 border-b-2 border-transparent px-6 py-4 font-semibold text-on-surface-variant transition-colors hover:text-on-surface">Reviews ({{ $reviewCount }})</button>
                    <button data-tab="comments" class="shrink-0 border-b-2 border-transparent px-6 py-4 font-semibold text-on-surface-variant transition-colors hover:text-on-surface">Comments ({{ $commentCount }})</button>
                    <button data-tab="changelog" class="shrink-0 border-b-2 border-transparent px-6 py-4 font-semibold text-on-surface-variant transition-colors hover:text-on-surface">Changelog</button>
                </div>

                <!-- Description -->
                <div data-tab-panel="description" class="space-y-6 p-6">
                    <h2 class="font-display text-2xl font-semibold text-on-surface">Product Overview</h2>
                    <p class="text-on-surface-variant">{{ $product->short_description }}</p>
                    <article class="whitespace-pre-line leading-7 text-on-surface-variant">{{ $product->description }}</article>
                    <div class="flex flex-wrap items-center gap-3 border-t border-outline-variant pt-6">
                        <span class="rounded bg-primary/10 px-3 py-1.5 font-mono text-xs font-semibold tracking-wider text-primary">{{ str($product->category->name)->upper() }}</span>
                        @if($latestVersion)
                            <span class="rounded bg-secondary/10 px-3 py-1.5 font-mono text-xs font-semibold tracking-wider text-secondary">V{{ $latestVersion->version_number }}</span>
                        @endif
                        <span class="rounded bg-tertiary/10 px-3 py-1.5 font-mono text-xs font-semibold tracking-wider text-tertiary">6 MONTHS SUPPORT</span>
                    </div>
                </div>

                <!-- Reviews -->
                <div data-tab-panel="reviews" class="hidden space-y-5 p-6">
                    <div class="flex items-center gap-5 rounded-xl bg-surface-container-low p-5">
                        <span class="font-display text-5xl font-bold text-on-surface">{{ number_format((float) $product->average_rating, 1) }}</span>
                        <div>
                            <x-rating-stars :rating="$product->average_rating" :size="20" />
                            <p class="mt-1 text-sm text-on-surface-variant">Based on {{ $reviewCount }} verified {{ str('review')->plural($reviewCount) }}</p>
                        </div>
                    </div>
                    @forelse($product->reviews as $review)
                        <article class="rounded-xl border border-outline-variant p-5">
                            <div class="flex items-start justify-between gap-4">
                                <strong class="text-on-surface">{{ $review->title }}</strong>
                                <x-rating-stars :rating="$review->rating" :size="16" class="shrink-0" />
                            </div>
                            <p class="mt-2 text-on-surface-variant">{{ $review->content }}</p>
                            <p class="mt-3 font-mono text-xs uppercase tracking-wider text-on-surface-variant">Verified purchase · {{ $review->user->name }} · {{ $review->created_at->format('M j, Y') }}</p>
                            @if($review->seller_response)
                                <div class="mt-4 rounded-lg bg-surface-container-low p-4">
                                    <p class="text-xs font-semibold text-primary">Response from {{ $sellerName }}</p>
                                    <p class="mt-1 text-sm text-on-surface-variant">{{ $review->seller_response }}</p>
                                </div>
                            @endif
                        </article>
                    @empty
                        <p class="text-on-surface-variant">No reviews yet. Verified buyers can leave a review from their purchases page.</p>
                    @endforelse
                </div>

                <!-- Comments -->
                <div data-tab-panel="comments" class="hidden space-y-5 p-6">
                    @auth
                        <form method="POST" action="{{ route('comments.store', $product) }}">
                            @csrf
                            <textarea name="content" required rows="3" placeholder="Ask the seller a question..."
                                class="w-full rounded-xl border border-outline-variant bg-surface-container-low p-4 text-on-surface placeholder:text-on-surface-variant focus:outline-none focus:ring-2 focus:ring-primary/20"></textarea>
                            <button class="mt-3 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-on-primary transition-all hover:opacity-90 active:scale-95">Post Comment</button>
                        </form>
                    @else
                        <p class="rounded-xl bg-surface-container-low p-4 text-sm text-on-surface-variant"><a class="font-semibold text-primary hover:underline" href="{{ route('login') }}">Sign in</a> to ask the seller a question.</p>
                    @endauth
                    @forelse($product->comments as $comment)
                        <article class="rounded-xl border border-outline-variant p-5">
                            <div class="flex items-center gap-2">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-surface-container-highest text-xs font-bold text-primary">{{ str($comment->user->name)->substr(0, 1)->upper() }}</span>
                                <strong class="text-on-surface">{{ $comment->user->name }}</strong>
                                <span class="text-xs text-on-surface-variant">{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="mt-3 text-on-surface-variant">{{ $comment->content }}</p>
                            @foreach($comment->replies as $reply)
                                <div class="ml-6 mt-4 border-l-2 border-primary/30 pl-4">
                                    <div class="flex items-center gap-2">
                                        <strong class="text-sm text-on-surface">{{ $reply->user->name }}</strong>
                                        @if($reply->is_seller_answer)
                                            <span class="rounded bg-secondary-container/30 px-2 py-0.5 text-[10px] font-bold uppercase text-on-secondary-container">Seller</span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-sm text-on-surface-variant">{{ $reply->content }}</p>
                                </div>
                            @endforeach
                        </article>
                    @empty
                        <p class="text-on-surface-variant">No comments yet — be the first to ask a question.</p>
                    @endforelse
                </div>

                <!-- Changelog -->
                <div data-tab-panel="changelog" class="hidden space-y-4 p-6">
                    @forelse($product->versions->sortByDesc('published_at') as $version)
                        <article class="flex gap-4 rounded-xl border border-outline-variant p-5">
                            <span class="h-fit shrink-0 rounded bg-primary/10 px-3 py-1.5 font-mono text-xs font-semibold text-primary">v{{ $version->version_number }}</span>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <strong class="text-on-surface">{{ $version->release_title }}</strong>
                                    @if($version->published_at)
                                        <span class="font-mono text-xs uppercase text-on-surface-variant">{{ $version->published_at->format('d M Y') }}</span>
                                    @endif
                                </div>
                                <p class="mt-1 whitespace-pre-line text-sm text-on-surface-variant">{{ $version->release_notes }}</p>
                            </div>
                        </article>
                    @empty
                        <p class="text-on-surface-variant">No release notes published yet.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <!-- Right Column: Sidebar -->
        <aside class="space-y-6 lg:col-span-4">
            <!-- Purchase Widget -->
            <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-6 shadow-sm lg:sticky lg:top-24">
                @auth
                <form method="POST" action="{{ route('cart.add', $product) }}">
                    @csrf
                @endauth
                    <span class="mb-4 block text-[17px] font-semibold text-on-surface">Select License</span>
                    <div class="space-y-3">
                        @foreach($licenseTypes as $license)
                            @continue($license->slug === 'extended' && ! $product->business_license_enabled)
                            @php $price = $license->slug === 'extended' ? ($product->extended_price ?: $product->regular_price) : $product->regular_price; @endphp
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-outline-variant p-4 transition-all hover:border-primary/50 has-[:checked]:border-primary has-[:checked]:bg-primary/5 has-[:checked]:ring-1 has-[:checked]:ring-primary">
                                <input type="radio" name="license_type_id" value="{{ $license->id }}" @checked($loop->first) class="mt-1 text-primary focus:ring-primary" @guest disabled @endguest>
                                <span class="flex-1">
                                    <span class="flex items-center justify-between">
                                        <span class="font-semibold text-on-surface">{{ $license->name }}</span>
                                        <span class="font-display text-xl font-bold text-primary">${{ number_format((float) $price, 0) }}</span>
                                    </span>
                                    <span class="mt-1 block text-sm text-on-surface-variant">{{ $license->description }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <div class="mt-5 space-y-3">
                        @if($product->demo_url)
                            <a href="{{ $product->demo_url }}" target="_blank" rel="noopener nofollow" class="flex w-full items-center justify-center gap-2 rounded-xl bg-secondary-container py-3.5 font-semibold text-on-secondary-container shadow-md transition-all hover:brightness-105 active:scale-[0.98]">
                                <span class="material-symbols-outlined text-[20px]">visibility</span> Live Preview
                            </a>
                        @endif
                        @auth
                            <button class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3.5 font-semibold text-on-primary shadow-md transition-all hover:brightness-110 active:scale-[0.98]">
                                <span class="material-symbols-outlined">shopping_cart</span> Add to Cart
                            </button>
                        @else
                            <a href="{{ route('login') }}" class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3.5 font-semibold text-on-primary shadow-md transition-all hover:brightness-110 active:scale-[0.98]">
                                <span class="material-symbols-outlined">shopping_cart</span> Sign in to Purchase
                            </a>
                        @endauth
                    </div>
                @auth
                </form>
                <form method="POST" action="{{ route('wishlist.toggle', $product) }}" class="mt-3">
                    @csrf
                    <button class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-primary py-3 font-semibold text-primary transition-all hover:bg-primary/5 active:scale-[0.98]">
                        <span class="material-symbols-outlined text-[20px]">favorite</span> Add to Wishlist
                    </button>
                </form>
                @endauth
                <div class="mt-6 space-y-3 border-t border-outline-variant pt-6">
                    <div class="flex items-center gap-3 text-on-surface-variant">
                        <span class="material-symbols-outlined text-secondary">verified_user</span>
                        <span class="text-sm">Reviewed by the DiginMarket quality team</span>
                    </div>
                    <div class="flex items-center gap-3 text-on-surface-variant">
                        <span class="material-symbols-outlined text-secondary">history</span>
                        <span class="text-sm">6 months support included</span>
                    </div>
                    <div class="flex items-center gap-3 text-on-surface-variant">
                        <span class="material-symbols-outlined text-secondary">download</span>
                        <span class="text-sm">Instant download after checkout</span>
                    </div>
                </div>
            </div>

            <!-- Product Specifications -->
            <div class="overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-sm">
                <div class="border-b border-outline-variant bg-surface-container-low p-5">
                    <h3 class="text-[17px] font-semibold">Product Specifications</h3>
                </div>
                <div class="divide-y divide-outline-variant">
                    @if($latestVersion?->published_at)
                        <div class="flex items-center justify-between px-5 py-3.5">
                            <span class="text-sm text-on-surface-variant">Last Update</span>
                            <span class="font-mono text-xs uppercase text-on-surface">{{ $latestVersion->published_at->format('d M Y') }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between px-5 py-3.5">
                        <span class="text-sm text-on-surface-variant">Published</span>
                        <span class="font-mono text-xs uppercase text-on-surface">{{ $product->published_at->format('d M Y') }}</span>
                    </div>
                    @if($latestVersion)
                        <div class="flex items-center justify-between px-5 py-3.5">
                            <span class="text-sm text-on-surface-variant">Version</span>
                            <span class="font-mono text-xs text-on-surface">{{ $latestVersion->version_number }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between px-5 py-3.5">
                        <span class="text-sm text-on-surface-variant">Category</span>
                        <span class="font-mono text-xs uppercase text-on-surface">{{ $product->category->name }}</span>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3.5">
                        <span class="text-sm text-on-surface-variant">Sales</span>
                        <span class="font-mono text-xs text-secondary">{{ number_format($product->sales_count) }}</span>
                    </div>
                </div>
            </div>

            <!-- Seller Widget -->
            <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-6 shadow-sm">
                <div class="flex items-center gap-4">
                    <span class="flex h-16 w-16 items-center justify-center rounded-xl bg-primary-container font-display text-2xl font-bold text-on-primary-container">{{ str($sellerName)->substr(0, 1)->upper() }}</span>
                    <div>
                        <h4 class="text-[17px] font-semibold leading-tight text-on-surface">{{ $sellerName }}</h4>
                        @if($product->seller->sellerProfile?->status?->value === 'approved')
                            <span class="mt-1 inline-block rounded bg-secondary-container px-2 py-0.5 text-[10px] font-bold uppercase text-on-secondary-container">Verified Seller</span>
                        @endif
                        <p class="mt-1 text-sm text-on-surface-variant">Member since {{ $product->seller->created_at->format('Y') }}</p>
                    </div>
                </div>
                @if($product->seller->sellerProfile?->biography)
                    <p class="mt-4 line-clamp-3 text-sm text-on-surface-variant">{{ $product->seller->sellerProfile->biography }}</p>
                @endif
                <a href="{{ route('sellers.show', $product->seller->sellerProfile?->username ?? '#') }}"
                    class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-surface-container-high py-3 font-semibold text-on-surface transition-colors hover:bg-surface-container-highest">
                    <span class="material-symbols-outlined text-[20px]">storefront</span> View Storefront
                </a>
            </div>
        </aside>
    </div>

    <!-- Related Products -->
    @if($related->isNotEmpty())
        <section class="mt-20">
            <div class="mb-8 flex items-end justify-between">
                <div>
                    <h2 class="font-display text-3xl font-semibold tracking-tight text-on-surface">More From This Category</h2>
                    <p class="mt-1 text-on-surface-variant">Hand-picked {{ str($product->category->name)->lower() }} for your next big project.</p>
                </div>
                <a href="{{ route('categories.show', $product->category->slug) }}" class="flex items-center gap-1 font-semibold text-primary hover:underline">
                    View All <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </a>
            </div>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                @foreach($related as $item)
                    <x-product-card :product="$item" />
                @endforeach
            </div>
        </section>
    @endif
</div>
</x-nexus-layout>
