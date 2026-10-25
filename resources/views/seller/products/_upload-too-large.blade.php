@if(request()->boolean('upload_too_large'))
    <div role="alert" class="mb-6 rounded-lg border border-error/30 bg-error-container/40 p-4 text-sm font-medium text-on-error-container">
        That upload was larger than the server accepts ({{ \App\Support\UploadLimits::postMaxMb() }} MB for the whole form), so nothing was saved.
        Use a smaller ZIP (up to {{ \App\Support\UploadLimits::archiveMb() }} MB) or fewer images and try again.
    </div>
@endif
