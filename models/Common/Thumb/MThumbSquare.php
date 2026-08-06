<?php
namespace Rehike\Model\Common\Thumb;

class MThumbSquare extends MThumbSimple
{
    public string $type = "square";

    public function __construct(array $data)
    {
        $this->image = $data["image"] ?? "";
        $this->width = $data["size"] ?? 0;
        $this->height = $data["size"] ?? 0;
        $this->alt = $data["alt"] ?? "";
        $this->delayload = $data["delayload"] ?? false;
    }
}