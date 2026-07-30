<?php
namespace Rehike\Model\Traits;

/**
 * A common NavigationEndpoint trait that can be implemented and used
 * by all models.
 * 
 * @author Aubrey Pankow <aubyomori@gmail.com>
 * @author The Rehike Maintainers
 */
class NavigationEndpoint
{
    /**
     * @return object{commandMetadata:object{webCommandMetadata:object{url:string}}}
     */
    public static function createEndpoint(string $url): object
    {
        return (object) [
            "commandMetadata" => (object) [
                "webCommandMetadata" => (object) [
                    "url" => $url
                ]
            ]
        ];
    }
}