<?php
namespace Rehike\Model\Common\Menu;

class MMenu
{
    /** @var MMenuItem[] */
    public array $items = [];

    /** @var string[] */
    public array $containerClass = [];

    public string $menuId;

    /** @var string[] */
    public array $menuClass = [];

    /**
     * @param array{items:array<string,mixed>,containerClass:string[],menuId:string,menuClass:string[]} $data
     */
    public function __construct(array $data)
    {
        foreach ($data["items"] as $item)
        {
            $this->items[] = new MMenuItem($item);
        }

        $this->containerClass = $data["containerClass"];
        $this->menuId = $data["menuId"];
        $this->menuClass = $data["menuClass"];
    }
}