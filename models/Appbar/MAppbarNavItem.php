<?php
namespace Rehike\Model\Appbar;

/**
 * Model for an item in an appbar navigation section.
 * 
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */
class MAppbarNavItem
{
    public string $title;
    public string $href;

    /**
     * @var self::*
     */
    public int $status = self::StatusUnselected;

    public const StatusUnselected = 0;
    public const StatusPartiallySelected = 1;
    public const StatusSelected = 2;

    /**
     * @param self::* $status
     */
    public function __construct(string $title, string $href, int $status = self::StatusUnselected)
    {
        $this->title = $title;
        $this->href = $href;
        $this->status = $status;
    }
}