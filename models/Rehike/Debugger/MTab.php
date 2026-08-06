<?php
namespace Rehike\Model\Rehike\Debugger;

/**
 * Implements the tab wrapper. General use should use the
 * createTab method of a MTabContent child.
 * 
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Developers
 */
class MTab
{
    /**
     * The title of a tab.
     */
    public string $title;

    /**
     * A unique identifier for this tab.
     */
    public string $id = "";

    /**
     * Determines if the tab should be selected by default.
     */
    public bool $selected = false;

    /**
     * Stores the content of the tab.
     */
    public MTabContent $content;

    /**
     * Construct a new tab wrapper.
     * 
     * @param string      $title    Title of the tab
     * @param string      $id       Unique ID for the tab
     * @param MTabContent $content  Content of the tab
     * @param bool        $selected Whether or not to select the tab.
     */
    public function __construct(
        string $title,
        string $id,
        MTabContent $content,
        bool $selected
    )
    {
        $this->title = $title;
        $this->id = $id;
        $this->content = $content;
        $this->selected = $selected;
    }
}