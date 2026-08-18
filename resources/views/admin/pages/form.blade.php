<x-admin-layout :title="($isHomepageEditor ?? false) ? 'Edit Homepage' : ($page->exists ? 'Edit Page' : 'New Page')">
<div class="mx-auto max-w-4xl px-5 py-8 md:px-8 lg:py-10">
 @php($isHomepageEditor = $isHomepageEditor ?? false)
 @php($homepageSettings = $homepageSettings ?? ($page->settings ?? \App\Models\Page::homepageSettingsDefaults()))
 <h1 class="text-3xl font-extrabold tracking-tight md:text-4xl">{{ $isHomepageEditor ? 'Edit Homepage' : ($page->exists?'Edit Page':'New Page') }}</h1>
 @if($errors->any())<div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
 @php($input='mt-1 w-full rounded-lg border border-[#d7d9e5] bg-[#fafbff] px-3 py-2.5 text-sm outline-none focus:border-[#3525cd]')
 @php($label='text-xs font-semibold uppercase tracking-wide text-[#555868]')
 <form method="POST" action="{{ $isHomepageEditor ? route('admin.pages.homepage.update') : ($page->exists?route('admin.pages.update',$page):route('admin.pages.store')) }}" class="mt-8 space-y-4 rounded-xl border border-[#d7d9e5] bg-white p-6 shadow-sm">
  @csrf @if($page->exists || $isHomepageEditor)@method('PUT')@endif
  @if($isHomepageEditor)
  <div class="rounded-xl border border-[#d7d9e5] bg-[#fafbff] p-4 text-sm text-[#555868]">Homepage content is managed from this special CMS record. Regular content pages remain editable from the same admin area.</div>
  <div><label class="{{ $label }}">Hero title</label><input name="title" required value="{{ old('title',$page->title) }}" class="{{ $input }}"></div>
  <div><label class="{{ $label }}">Hero subtitle</label><textarea name="excerpt" rows="3" class="{{ $input }}">{{ old('excerpt',$page->excerpt) }}</textarea></div>
  <div class="grid gap-4 sm:grid-cols-2">
   <div><label class="{{ $label }}">Hero badge text</label><input name="settings[hero_badge_text]" value="{{ old('settings.hero_badge_text',$homepageSettings['hero_badge_text'] ?? '') }}" class="{{ $input }}"></div>
   <div><label class="{{ $label }}">Status</label><select name="status" class="{{ $input }}"><option value="draft" @selected(old('status',$page->status ?: 'published')==='draft')>Draft</option><option value="published" @selected(old('status',$page->status ?: 'published')==='published')>Published</option></select></div>
  </div>
  <div class="grid gap-4 sm:grid-cols-2">
   <div><label class="{{ $label }}">Primary CTA text</label><input name="settings[cta_primary_text]" value="{{ old('settings.cta_primary_text',$homepageSettings['cta_primary_text'] ?? '') }}" class="{{ $input }}"></div>
   <div><label class="{{ $label }}">Primary CTA URL</label><input name="settings[cta_primary_url]" value="{{ old('settings.cta_primary_url',$homepageSettings['cta_primary_url'] ?? '') }}" class="{{ $input }} font-mono"></div>
   <div><label class="{{ $label }}">Secondary CTA text</label><input name="settings[cta_secondary_text]" value="{{ old('settings.cta_secondary_text',$homepageSettings['cta_secondary_text'] ?? '') }}" class="{{ $input }}"></div>
   <div><label class="{{ $label }}">Secondary CTA URL</label><input name="settings[cta_secondary_url]" value="{{ old('settings.cta_secondary_url',$homepageSettings['cta_secondary_url'] ?? '') }}" class="{{ $input }} font-mono"></div>
  </div>
  <div class="rounded-xl border border-[#e2e4ec] p-4">
   <p class="text-sm font-semibold text-[#1f2233]">Homepage sections</p>
   <div class="mt-4 space-y-4">
    @foreach([['show_categories','categories_title','categories_subtitle','Categories'],['show_trending','trending_title','trending_subtitle','Trending products'],['show_new_arrivals','new_arrivals_title','new_arrivals_subtitle','New arrivals'],['show_featured_creators','featured_creators_title','featured_creators_subtitle','Featured creators']] as [$toggleKey,$titleKey,$subtitleKey,$sectionLabel])
    <div class="rounded-lg border border-[#e2e4ec] bg-[#fafbff] p-4">
     <input type="hidden" name="settings[{{ $toggleKey }}]" value="0">
     <label class="flex items-center gap-3 text-sm font-semibold text-[#1f2233]"><input type="checkbox" name="settings[{{ $toggleKey }}]" value="1" @checked(old("settings.$toggleKey",$homepageSettings[$toggleKey] ?? false))> Show {{ $sectionLabel }}</label>
     <div class="mt-3 grid gap-4 sm:grid-cols-2">
      <div><label class="{{ $label }}">Section title</label><input name="settings[{{ $titleKey }}]" value="{{ old("settings.$titleKey",$homepageSettings[$titleKey] ?? '') }}" class="{{ $input }}"></div>
      <div><label class="{{ $label }}">Section subtitle</label><input name="settings[{{ $subtitleKey }}]" value="{{ old("settings.$subtitleKey",$homepageSettings[$subtitleKey] ?? '') }}" class="{{ $input }}"></div>
     </div>
    </div>
    @endforeach
   </div>
  </div>
  <div><label class="{{ $label }}">Extra homepage body</label><textarea name="body" rows="12" class="{{ $input }}" data-rich-editor>{{ old('body',$page->body) }}</textarea><p class="mt-2 text-xs text-[#777a8a]">Optional rich content block rendered below the homepage stats section.</p></div>
  @else
  <div><label class="{{ $label }}">Title</label><input name="title" required value="{{ old('title',$page->title ?: request('title')) }}" class="{{ $input }}"></div>
  <div><label class="{{ $label }}">Slug</label><input name="slug" required value="{{ old('slug',$page->slug ?: request('slug')) }}" class="{{ $input }} font-mono"></div>
  <div><label class="{{ $label }}">Excerpt</label><input name="excerpt" value="{{ old('excerpt',$page->excerpt) }}" class="{{ $input }}"></div>
  <div><label class="{{ $label }}">Body</label><textarea name="body" required rows="14" class="{{ $input }}" data-rich-editor>{{ old('body',$page->body) }}</textarea></div>
  @endif
  <div class="grid gap-4 sm:grid-cols-2">
   <div><label class="{{ $label }}">Meta title</label><input name="meta_title" value="{{ old('meta_title',$page->meta_title) }}" class="{{ $input }}"></div>
   <div><label class="{{ $label }}">Meta description</label><input name="meta_description" value="{{ old('meta_description',$page->meta_description) }}" class="{{ $input }}"></div>
  </div>
  @unless($isHomepageEditor)
  <div><label class="{{ $label }}">Status</label><select name="status" class="{{ $input }}"><option value="draft" @selected(old('status',$page->status)==='draft')>Draft</option><option value="published" @selected(old('status',$page->status)==='published')>Published</option></select></div>
  @endunless
  <button class="rounded-lg bg-[#3525cd] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#2a1da8]">{{ $isHomepageEditor ? 'Save homepage' : ($page->exists?'Save changes':'Create page') }}</button>
 </form>
</div>
</x-admin-layout>
