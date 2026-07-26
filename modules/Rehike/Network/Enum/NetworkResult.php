<?php
namespace Rehike\Network\Enum;

/**
 * Network result status codes.
 *
 * @author Isabella Lulamoon <kawapure@gmail.com>
 * @author The Rehike Maintainers
 */
enum NetworkResult
{
    case SUCCESS;
    case E_FAILED;
    case E_MALFORMED_URL;
    case E_COULDNT_RESOLVE_PROXY;
    case E_COULDNT_RESOLVE_HOST;
    case E_COULDNT_CONNECT;
    case E_UNIMPL;
}