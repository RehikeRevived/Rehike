<?php
namespace Rehike\Model\Footer;

class MFooterLink
{
    public string $simpleText;

    public object $navigationEndpoint;

    public function __construct(string $text, string $url)
    {
        $this->simpleText = $text;
        $this->navigationEndpoint = (object) [
            "commandMetadata" => (object) [
                "webCommandMetadata" => (object) [
                    "url" => $url
                ]
            ]
        ];
    }
}