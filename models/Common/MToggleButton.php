<?php
namespace Rehike\Model\Common;

/**
 * Implements a model for the common toggle button
 * 
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */
class MToggleButton extends MButton
{
    protected bool $hideNotToggled = false;

    public bool $isToggled = false;

    public function __construct(bool $isToggled = false, array $array = [])
    {
        parent::__construct();

        $this->isToggled = $isToggled;

        if ($this->hideNotToggled && !$isToggled)
        {
            $this->class[] = "hid";
        }
    }
}