<?php

namespace App\Services\Inbox;

use App\Enums\SenderCategory;
use App\Enums\SenderRecommendation;

class ClassificationResult
{
    public function __construct(
        public SenderCategory $category,
        public SenderRecommendation $recommendation,
        public string $reason,
        public array $gmailCategories = [],
    ) {
    }
}
