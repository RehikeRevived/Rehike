<?php
namespace Rehike\Model\Traits;

/**
 * A common Runs trait that can be implemented and used
 * by all models.
 * 
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */
trait Runs
{
    /**
     * Create a run.
     *
     * @return object{text:mixed,navigationEndpoint?:object{commandMetadata:object{webCommandMetadata:object{url:mixed}}}}
     */
    public function createRun($text, $href = null): object
    {
        return (object)([
            "text" => $text
        ] + ((null != $href) ? [
            "navigationEndpoint" => (object)[
                "commandMetadata" => (object)[
                    "webCommandMetadata" => (object)[
                        "url" => $href
                    ]
                ]
            ]
        ] : []));
    }
}