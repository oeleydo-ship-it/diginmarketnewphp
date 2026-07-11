<?php
namespace App\Enums;
enum ProductVersionStatus: string { case Draft='draft'; case Processing='processing'; case PendingReview='pending_review'; case Approved='approved'; case Rejected='rejected'; case Published='published'; }