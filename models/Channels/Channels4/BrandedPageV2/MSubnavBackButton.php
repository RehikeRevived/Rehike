<?php
namespace Rehike\Model\Channels\Channels4\BrandedPageV2;

class MSubnavBackButton
{
    public string $accessibilityLabel = "Back";

    public function __construct(
        public ?string $href,
    )
    {
    }
}
