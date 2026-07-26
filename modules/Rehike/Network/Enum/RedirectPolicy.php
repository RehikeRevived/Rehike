<?php
namespace Rehike\Network\Enum;

/**
 * Redirect policy enum for the Rehike network library.
 * 
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */
enum RedirectPolicy
{
    case FOLLOW;
    case MANUAL;
}