<?php
namespace Rehike\Model\Watch\Watch8;

use Rehike\Model\Common\MButton;

class MCreatorBarEditButton extends MButton
{
    public string $style = "STYLE_TEXT_DARK";

    public object $navigationEndpoint;
    public string $itemTooltip;

    public function __construct(array $data)
    {
        $this->itemTooltip = $data["tooltip"];
        $this->icon = (object) [
            "iconType" => $data["icon"]
        ];
        $this->navigationEndpoint = (object) [
            "commandMetadata" => (object) [
                "webCommandMetadata" => (object) [
                    "url" => $data["url"]
                ]
            ]
        ];
        $this->customAttributes = [
            "target" => "_blank"
        ];
    }
}