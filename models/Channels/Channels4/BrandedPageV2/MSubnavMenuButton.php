<?php
namespace Rehike\Model\Channels\Channels4\BrandedPageV2;

class MSubnavMenuButton
{
    /**
     * @var array<string|int, object>
     */
    public array $items = [];

    public function __construct(
        public string $type,
        public string $title,
        $array = [],
    )
    {
        foreach ($array as $itemTitle => $href)
        {
            $this->addMenu(new MSubnavMenuButtonMenu(
                $itemTitle, $href
            ));
        }
    }

    public function addMenu(MSubnavMenuButtonMenu $menu): void
    {
        $this->items[] = $menu;
    }

    public static function fromData(array $data): self
    {
        $items = [];

        foreach ($data as $item)
        {
            if ($item->selected)
            {
                $title = $item->title;
            }
            else
            {
                $items[$item->title] = $item->endpoint->commandMetadata->webCommandMetadata->url;
            }
        }

        return new self("view", $title, $items);
    }
}