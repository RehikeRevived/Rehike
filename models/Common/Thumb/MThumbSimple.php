<?php
namespace Rehike\Model\Common\Thumb;

class MThumbSimple
{
    public string $type = "simple";

    public string $image;

    public float $width;

    public float $height;

    public string $alt;

    public bool $delayload = false;

    public function __construct(array $data)
    {
        $this->image = $data["image"] ?? "";
        $this->width = $data["width"] ?? 0;
        $this->height = $data["height"] ?? 0;
        $this->alt = $data["alt"] ?? "";
        $this->delayload = $data["delayload"] ?? false;
    }
}