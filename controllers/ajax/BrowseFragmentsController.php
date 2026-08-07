<?php
namespace Rehike\Controller\ajax;

use Rehike\YtApp;
use Rehike\ControllerV2\RequestMetadata;

use Rehike\Controller\core\AjaxController;

use Rehike\Network;
use Rehike\Util\RichShelfUtils;
use Rehike\Util\Base64Url;
use Rehike\Model\Browse\InnertubeBrowseConverter;
use Com\Youtube\Innertube\Helpers\VideosContinuationWrapper;

use function Rehike\Async\async;

use Rehike\ControllerV2\{
    IGetController,
    IPostController,
};
use Rehike\Model\ViewModelConverter\LockupViewModelConverter;

/**
 * Controller for browse AJAX requests.
 * 
 * @author Aubrey Pankow <aubyomori@gmail.com>
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */
class BrowseFragmentsController extends AjaxController implements IGetController, IPostController
{
    public string $template = "ajax/browse";

    public function onGet(YtApp $yt, RequestMetadata $request): void
    {
        $this->onPost($yt, $request);
    }

    public function onPost(YtApp $yt, RequestMetadata $request): void
    {
        async(function() use (&$yt, $request)
        {
            if (!isset($request->params->continuation)) self::error();
            $continuation = $request->params->continuation;

            $list = false;
            $wrap = false;
            $targetPlaylistPage = false;

            // TODO(leymonaide): In the future, RHCUSTOM continuations should be
            // standardized to avoid difficult to manage fragmenting across the
            // codebase.
            if (str_starts_with($continuation, "RHCUSTOM"))
            {
                $payload = substr($continuation, strlen("RHCUSTOM"));
                $obj = json_decode(Base64Url::decode($payload));

                if (@$obj->style && "targetPlaylistPage" == $obj->style)
                {
                    $targetPlaylistPage = true;
                }

                $continuation = $obj->token;
            }
            else
            {
                $contWrapper = new VideosContinuationWrapper();
                $contWrapper->mergeFromString(Base64Url::decode($continuation));

                if ($contWrapper->getContinuation() != "")
                {
                    $continuation = $contWrapper->getContinuation();
                    $list = $contWrapper->getList();
                    $wrap = $contWrapper->getWrapInGrid();
                }
            }

            $response = yield Network::innertubeRequest(
                action: "browse",
                body: [
                    "continuation" => $continuation
                ]
            
            );
            $ytdata = $response->getJson();

            if (isset($ytdata->onResponseReceivedActions))
            {
                foreach ($ytdata->onResponseReceivedActions as $action)
                {
                    if (isset($action->appendContinuationItemsAction))
                    {
                        foreach ($action->appendContinuationItemsAction->continuationItems as &$item)
                        {
                            switch (true)
                            {
                                case isset($item->continuationItemRenderer):
                                    if (!$list && !$wrap)
                                    {
                                        $yt->page->continuation = $item->continuationItemRenderer->continuationEndpoint->continuationCommand->token;
                                    }
                                    else
                                    {
                                        $nContWrapper = new VideosContinuationWrapper();
                                        $nContWrapper->setContinuation($yt->page->continuation = $item->continuationItemRenderer->continuationEndpoint->continuationCommand->token);
                                        $nContWrapper->setList($list);
                                        $nContWrapper->setWrapInGrid($wrap);
                                        $yt->page->continuation = Base64Url::encode($nContWrapper->serializeToString());
                                    }
                                    break;
                                case isset($item->richItemRenderer):
                                    $item = RichShelfUtils::reformatShelfItem($item, $list);
                                    break;
                                case isset($item->richSectionRenderer->content->richShelfRenderer):
                                    $item = RichShelfUtils::reformatShelf($item, $list);
                                    break;
                            }
                        }
                        $yt->page->items = $action->appendContinuationItemsAction->continuationItems;
                    }
                }
            }
            else
            {
                self::error();
            }

            $yt->page->items =
                InnertubeBrowseConverter::generalLockupConverter(
                    $yt->page->items,
                    [
                        "listView" => $list,
                        "channelRendererUnbrandedSubscribeButton" => true
                    ] + ($list ? ["lockupStyle" => LockupViewModelConverter::STYLE_LIST] : [])
                      + ($targetPlaylistPage ? ["targetPlaylistPage" => true] : []),
                );

            if ($wrap)
            {
                $yt->page->items = [
                    (object)[
                        "itemSectionRenderer" => (object)[
                            "contents" => [
                                (object) [
                                    "shelfRenderer" => (object) [
                                        "content" => (object) [
                                            "gridRenderer" => (object) [
                                                "items" => $yt->page->items
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];
            }
    
            $yt->page->target = $request->params->target_id;
            $yt->page->response = $ytdata;
        });
    }
}