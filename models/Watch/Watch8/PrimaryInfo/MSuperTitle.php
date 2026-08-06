<?php
namespace Rehike\Model\Watch\Watch8\PrimaryInfo;

use Rehike\Util\ParsingUtils;

/**
 * Define the super title (links that appear above the title, such as hashtags).
 */
class MSuperTitle
{
    /**
     * @var object[]
     */
    public array $items = [];

    public function __construct(object $superTitleLink)
    {
        if (isset($superTitleLink->runs))
        foreach ($superTitleLink->runs as $run)
        if (" " != $run->text)
        {
            $this->items[] = (object)[
                "text" => $run->text,
                "url" => ParsingUtils::getUrl($run)
            ];
        }
    }
}