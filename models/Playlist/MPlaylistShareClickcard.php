<?php
namespace Rehike\Model\Playlist;

use Rehike\Model\Common\MAbstractClickcard;

class MPlaylistShareClickcard extends MAbstractClickcard
{
    public string $template = "playlist_share";
    public array $cardClass = [
        "yt-card"
    ];
    public string $class = "pl-header-sharepanel-content";
    public object $targetWrapper;

    public function __construct()
    {
        $this->targetWrapper = (object) [
            "position" => "bottomright",
            "orientation" => "vertical"
        ];
    }
}