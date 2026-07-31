<?php
namespace Rehike\Controller\core;

use Rehike\ControllerV2\IController;

use Rehike\ControllerV2\BaseController;
use Rehike\YtApp;

/**
 * 
 * 
 * @author Isabella Lulamoon <kawapure@gmail.com>
 * @author The Rehike Maintainers
 */
class PageController extends BaseController
{
    /**
     * Stores all information that is sent to Twig for rendering the page.
     */
    protected YtApp $yt;

    /**
     * Defines the default page template.
     * 
     * This may be overridden for certain contexts in an onGet()
     * callback.
     */
    public string $template = "";
    
    public function getYtApp(): YtApp
    {
        return $this->yt;
    }
    
    public function getTemplate(): string
    {
        return $this->template;
    }
    
    public function setTemplate(string $newTemplate): void
    {
        $this->template = $newTemplate;
    }
}