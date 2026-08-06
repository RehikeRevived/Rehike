<?php
namespace Rehike\Model\Channels\Channels4\Sidebar;

use Rehike\i18n\i18n;

class MRelatedChannelsSeeMoreButton
{
    public string $title;
    public string $href;

    public function __construct(string $href)
    {
        $strings = i18n::getNamespace("channels");

        $this->title = $strings->get("seeAll");
        $this->href = $href;
    }
}