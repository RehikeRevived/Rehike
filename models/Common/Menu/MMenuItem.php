<?php
namespace Rehike\Model\Common\Menu;

#[\AllowDynamicProperties]
class MMenuItem
{
    public string $label;

    /** @var string[] */
    public array $class = [];

    public bool $hasIcon = false;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        foreach ($data as $key => $val)
        {
            $this->{$key} = $val;
        }
    }
}