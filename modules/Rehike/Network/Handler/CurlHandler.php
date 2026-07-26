<?php
namespace Rehike\Network\Handler;

use Rehike\Attributes\Override;

use Rehike\Network\{
    Enum\NetworkResult,
    Handler\NetworkHandler,
    Handler\Curl\EventLoopRunner,
    Handler\Curl\RequestTransformer,
    Handler\Curl\RequestWrapper,
    Internal\Request,
    Internal\Response,
    IRequest,
    IResponse,
};

use const PHP_VERSION_ID;
use Generator;
use Fiber; // PHP 8.1
use function usleep;

// cURL imports:
use function curl_multi_init;
use function curl_multi_add_handle;
use function curl_multi_exec;
use function curl_multi_select;
use function curl_multi_getcontent;
use function curl_multi_remove_handle;
use function curl_multi_close;
use function curl_getinfo;
use function curl_close;
use const CURLM_OK;
use const CURLINFO_HTTP_CODE;
use CurlMultiHandle;
use Rehike\Async\Promise\PromiseStatus;

/**
 * Implements a cURL-compatible network handler for the network library.
 * 
 * The cURL handler uses curl_multi integration with PHP in order to
 * perform a network request. This already operates asynchronously, but
 * most PHP-land uses are blocking.
 * 
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */
class CurlHandler extends NetworkHandler
{
    /** 
     * Stores all active requests.
     * 
     * @var RequestWrapper[] 
     */
    private array $requests = [];
    
    /**
     * Marks {@see $requests} as dirty.
     */
    private bool $requestsDirty = false;

    #[Override]
    public function addRequest(IRequest $request): void
    {
        $this->requests[] = $this->convertRequest($request);
        $this->requestsDirty = true;
    }

    #[Override]
    public function clearRequests(): void
    {
        $this->requests = [];
        
        // XXX(isabella): $requestsDirty is intentionally not set here in order
        // to allow ongoing requests to clean up.
    }

    /**
     * Convert a Request to a cURL handle.
     */
    protected function convertRequest(IRequest $request): RequestWrapper
    {
        return RequestTransformer::convert($request);
    }

    /**
     * Convert a cURL response to our own Response object.
     */
    protected function makeResponse(
            int $curlCode,
            int $status,
            string $raw,
            RequestWrapper $wrapper,
    ): IResponse
    {
        $result = new Response(
            $wrapper->instance,
            $status,
            $raw,
            $wrapper->responseHeaders
        );
        $result->resultCode = $this->makeResultCode($curlCode);
        return $result;
    }

    /**
     * Convert a cURL status code to a NetworkResult code.
     */
    protected function makeResultCode(int $curlCode): NetworkResult
    {
        switch ($curlCode)
        {
            case 0: // CURLE_OK
                return NetworkResult::SUCCESS;
            case 3: // CURL_URL_MALFORMAT
                return NetworkResult::E_MALFORMED_URL;
            case 5: // CURL_COULDNT_RESOLVE_PROXY
                return NetworkResult::E_COULDNT_RESOLVE_PROXY;
            case 6: // CURL_COULDNT_RESOLVE_HOST
                return NetworkResult::E_COULDNT_RESOLVE_HOST;
            case 7: // CURL_COULDNT_CONNECT
                return NetworkResult::E_COULDNT_CONNECT;
        }

        return NetworkResult::E_FAILED;
    }

    /**
     * Resolve a Request with a Response.
     */
    protected function sendResponse(Request $request, IResponse $response): void
    {
        $request->resolve($response);
    }

    #[Override]
    public function onRun(): Generator/*<void>*/
    {
        // Defined in CurlHandler
        $requests = &$this->requests;

        $halfOfList = floor(count($this->requests) / 2);

        if (count($requests) == 0)
        {
            $this->fulfill();
            return;
        }
        
        $knownRequests = [];

        $mhFiber = curl_multi_init();
        $mhNormal = curl_multi_init();
        $codesMap = [];

        // Register all queued requests in the handle array
        foreach ($requests as $index => $request)
        {
            $index > $halfOfList
                ? curl_multi_add_handle($mhFiber, $request->handle)
                : curl_multi_add_handle($mhNormal, $request->handle);

            $knownRequests[] = $request;
        }

        // Initialize fiber:
        $fiber = new Fiber(function(CurlMultiHandle $mh) {
            $active = null;

            do
            {
                curl_multi_exec($mh, $active);
                $info = curl_multi_info_read($mh);
                if ($info)
                    $codesMap[(int)$info["handle"]] = $info["result"];
                curl_multi_select($mh);
                Fiber::suspend();
            }
            while ($active);
        });
        $fiber->start($mhFiber);
        
        // We've already handled all existing requests, so clear the dirty flag.
        $this->requestsDirty = false;

        do
        {
            $status = curl_multi_exec($mhNormal, $active);
            $info = curl_multi_info_read($mhNormal);
            if ($info)
                $codesMap[(int)$info["handle"]] = $info["result"];

            if ($active)
            {
                if (-1 == curl_multi_select($mhNormal))
                {
                    usleep(10);
                }
                
                yield;
                
                // Now we're resuming. If some more requests came through,
                // we need to add them to the active stream now.
                if ($this->requestsDirty)
                {
                    // Since our list can grow or shrink, we will now recompute the
                    // half of list variable. We'll get a good average from our starting
                    // value and the would-be starting value if we had this many to begin
                    // with, since we obviously can't move between handlers.
                    $halfOfList = floor($halfOfList + floor(count($this->requests) / 2) / 2);
                    
                    foreach ($requests as $index => $request)
                    {
                        if (in_array($request, $knownRequests))
                        {
                            continue;
                        }
                        
                        $index > $halfOfList
                            ? curl_multi_add_handle($mhFiber, $request->handle)
                            : curl_multi_add_handle($mhNormal, $request->handle);

                        $knownRequests[] = $request;
                    }
                    
                    $this->requestsDirty = false;
                }
            }
        }
        while ($active && CURLM_OK == $status);
        
        // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! //
        //                *** END OF EVENT LOOP AREA ***                     //
        //         The code should never "yield" past this point.            //
        //        All code here must be synchronous as we will be            //
        //                   processing responses.                           //
        // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! //

        // Close fiber:
        do
        {
            $fiber->resume();
        }
        while (!$fiber->isTerminated());

        // Report each of the responses.
        foreach ($requests as $index => $request)
        {
            $code = isset($codesMap[(int)$request->handle])
                ? $codesMap[(int)$request->handle]
                : 0;

            $response = $this->makeResponse(
                $code,
                curl_getinfo($request->handle, CURLINFO_HTTP_CODE),
                curl_multi_getcontent($request->handle),
                $request
            );

            $this->sendResponse($request->instance, $response);

            $index > $halfOfList
                ? curl_multi_remove_handle($mhFiber, $request->handle)
                : curl_multi_remove_handle($mhNormal, $request->handle);
        }

        curl_multi_close($mhNormal);
        curl_multi_close($mhFiber);
        
        foreach ($this->requests as $request)
        {
            if ($request->instance->getPromise()->status == PromiseStatus::PENDING)
            {
                // XXX(isabella): New requests can STILL be added before we fulfill,
                // i.e. from a synchronous Promise::then() callback in sendResponse()
                // when deferred promises are disabled in the async library. In this
                // case, we will have to reset the network handler without clearing
                // requests and let them run like normal.
                $this->restartManager();
                return;
            }
        }

        $this->fulfill();
    }
}