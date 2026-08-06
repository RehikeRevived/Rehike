<?php
namespace Rehike\Model\Masthead;

use Rehike\Model\Common\MButton;
use Rehike\i18n\i18n;

class MMastheadSearchbox
{
    public string $placeholder;
    public MButton $button;
    public bool $autofocus;
    public string $query;

    public function __construct()
    {
        $i18n = i18n::getNamespace("masthead");
    
        $this->placeholder = $i18n->get("searchboxPlaceholder");
        $this->button = new MButton([
            "style" => "STYLE_DEFAULT",
            "size" => "SIZE_DEFAULT",
            "text" => (object) [
                "simpleText" => $i18n->get("searchboxPlaceholder")
            ],
            "targetId" => "search-btn",
            "class" => [
                "search-btn-component",
                "search-button"
            ],
            "customAttributes" => [
                "type" => "submit",
                "onclick" => "if (document.getElementById('masthead-search-term').value == '') return false; document.getElementById('masthead-search').submit(); return false;;return true;",
                "tabindex" => "2",
                "dir" => "ltr"
            ]
        ]);
    }
    
    public function setSearchQuery(string $query): void
    {
        $this->query = $query;
    }
}