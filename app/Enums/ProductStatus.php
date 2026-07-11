<?php
namespace App\Enums;
enum ProductStatus: string { case Draft='draft'; case Submitted='submitted'; case UnderReview='under_review'; case ChangesRequested='changes_requested'; case Approved='approved'; case Rejected='rejected'; case Published='published'; case Paused='paused'; case Suspended='suspended'; case Archived='archived'; }