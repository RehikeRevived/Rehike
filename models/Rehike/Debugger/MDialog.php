<?php
namespace Rehike\Model\Rehike\Debugger;

/**
 * Implements the dialog model for the Rehike debugger.
 * 
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Developers
 */
class MDialog
{
    /**
     * The dialog's header.
     */
    public MDialogHeader $header;

    /**
     * Stores whether or not the debugger is in condensed mode.
     */
    public bool $condensed;

    /**
     * An array of tabs.
     * 
     * @var MTab[]
     */
    public array $tabs = [];

    public function __construct(bool $condensed)
    {
        $this->condensed = $condensed;
        $this->header = new MDialogHeader($condensed);
    }

    /**
     * Add a tab to the dialog.
     * 
     * @return MTabContent Reference to the tab's content.
     */
    public function &addTab(MTab $tab): MTabContent
    {
        $this->tabs[] = $tab;

        return $this->tabs[count($this->tabs) - 1]->content;
    }
}