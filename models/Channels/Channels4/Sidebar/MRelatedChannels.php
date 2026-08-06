<?php
namespace Rehike\Model\Channels\Channels4\Sidebar;

use Rehike\Model\Browse\InnertubeBrowseConverter;

class MRelatedChannels
{
    public string|object|null $title = "";

    /**
     * @var object[]
     */
    public array $items = [];

    public ?MRelatedChannelsSeeMoreButton $seeMoreButton = null;

    public static function fromShelf(object $shelf): self
    {
        $me = new self();

        // Convert the shelf first
        $shelf = InnertubeBrowseConverter::shelfRenderer($shelf, [
            "channelRendererNoMeta" => true,
            "channelRendererUnbrandedSubscribeButton" => true,
            "channelRendererNoSubscribeCount" => true
        ]);

        if (isset($shelf->title))
        {
            $me->title = $shelf->title;
        }

        $items = $shelf->content->horizontalListRenderer->items
        ??       $shelf->content->expandedShelfContentsRenderer->items
        ??       null;

        if (!is_null($items))
        foreach ($items as $i => $item)
        {
            $me->items[] = $item->gridChannelRenderer
            ??             $item->channelRenderer
            ??             null;

            // Break at the 10th item
            if ($i >= 9)
            {
                $me->seeMoreButton = new MRelatedChannelsSeeMoreButton(
                    @$shelf->endpoint->commandMetadata->webCommandMetadata->url
                );
                break;
            }
        }

        return $me;
    }
}