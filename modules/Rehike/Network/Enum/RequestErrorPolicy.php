<?php
namespace Rehike\Network\Enum;

/**
 * Error policy for Requests.
 * 
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */
enum RequestErrorPolicy
{
    case THROW;
    case IGNORE;
}