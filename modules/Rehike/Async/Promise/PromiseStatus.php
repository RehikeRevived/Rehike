<?php
namespace Rehike\Async\Promise;

/**
 * States for Promise resolution status.
 * 
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */
enum PromiseStatus
{
    case PENDING;
    case RESOLVED;
    case REJECTED;
}