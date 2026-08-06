<?php
namespace Rehike\Model\Common\Subscription;

/**
 * Implements a model for the subscription count element
 * that shows next to the actions.
 * 
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */
class MSubscriberCount
{
    public function __construct(
        public string $simpleText,
        public bool $branded = true,
        public string $direction = "horizontal",
    )
    {
    }
}