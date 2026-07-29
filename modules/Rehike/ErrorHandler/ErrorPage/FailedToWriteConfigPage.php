<?php
namespace Rehike\ErrorHandler\ErrorPage;

use Rehike\Logging\Common\FormattedString;
use Rehike\Logging\ExceptionLogger;
use Rehike\Exception\Network\InnertubeFailedRequestException;

use Throwable;

/**
 * Represents the error page for a failed InnerTube request (or the exception
 * class: InnertubeFailedRequestException).
 * 
 * @author Isabella Lulamoon <kawapure@gmail.com>
 * @author The Rehike Maintainers
 */
class FailedToWriteConfigPage extends AbstractErrorPage
{
    private InnertubeFailedRequestException $innertubeFailedException;

    #[\Override]
    public function getTitle(): string
    {
        return "Failed to write the configuration file";
    }
    
    #[\Override]
    public function shouldDisplayIssueTrackerLink(): bool
    {
        return false;
    }
    
    #[\Override]
    public function shouldDisplayMessageLogs(): bool
    {
        return false;
    }
}