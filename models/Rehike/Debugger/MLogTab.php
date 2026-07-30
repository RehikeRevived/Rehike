<?php
namespace Rehike\Model\Rehike\Debugger;

use Rehike\Logging\DebugLogger;

/**
 * Implements the log messages tab.
 * 
 * @author Isabella Lulamoon <kawapure@gmail.com>
 * @author The Rehike Developers
 */
class MLogTab extends MTabContent
{
    public function __construct()
    {
        $this->richDebuggerRenderer[] = new class {
            public bool $isLogTab = true;
            
            /**
             * @return string[]
             */
            public function getLogs(): array
            {
                return DebugLogger::getLogs();
            }
        };
    }
}