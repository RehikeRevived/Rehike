<?php
namespace Rehike\Model\Watch\Watch8;

use Rehike\Util\ParsingUtils;
use Rehike\ConfigManager\Config;
use Rehike\Util\ExtractUtils;
use Rehike\i18n\i18n;

use Rehike\Model\Watch\Watch8\{
    LikeButton\MLikeButtonRenderer,
    PrimaryInfo\MActionButton,
    PrimaryInfo\MOwner,
    PrimaryInfo\MSuperTitle
};
use Rehike\Model\Watch\Watch8\PrimaryInfo\MPrivacyBadge;
use Rehike\Model\Watch\WatchBakery;

/**
 * Implement the model for the primary info renderer.
 * 
 * This is generally a restructuring of the Kevlar formatted data
 * returned by InnerTube with WEB v2 as parameters.
 * 
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */
class MVideoPrimaryInfoRenderer
{
    public object|string|null $title = "";

    public ?string $viewCount = "";

    public ?MSuperTitle $superTitle = null;

    public ?MPrivacyBadge $privacyBadge = null;

    public ?MOwner $owner = null;

    /** @var MActionButton[] */
    public array $actionButtons = [];

    public MLikeButtonRenderer $likeButtonRenderer;

    public function __construct(WatchBakery $bakery, string $videoId)
    {
        $info = &$bakery->primaryInfo ?? null;
        $i18n = i18n::getNamespace("watch");

        if (!is_null($info))
        {
            $this->title = $info->title ?? null;

            // Also set title of the whole page from this property
            $bakery->title = ParsingUtils::getText($this->title);

            if (isset($info->viewCount->videoViewCountRenderer))

            $this->viewCount = ParsingUtils::getText($info->viewCount->videoViewCountRenderer->viewCount);
            if (Config::get()->appearance->noViewsText->getValue())
            {
                $number = (int)ExtractUtils::isolateViewCnt($this->viewCount);
                if (is_int($number))
                {
                    $this->viewCount = $i18n->formatNumber($number);
                }
            }
            $this->superTitle = isset($info->superTitleLink) ? new MSuperTitle($info->superTitleLink) : null;
            $this->likeButtonRenderer = new MLikeButtonRenderer($bakery, $info->videoActions->menuRenderer, $videoId);
            
            if ($bakery->getHasMultipleOwners())
            {
                $this->owner = MOwner::fromFirstCollaborator($bakery);
            }
            else
            {
                $this->owner = MOwner::fromSingleUser($bakery);
            }

            if (isset($info->badges))
            foreach ($info->badges as $badge)
            {
                if ($icon = @$badge->metadataBadgeRenderer->icon->iconType)
                {
                    if (str_starts_with($icon, "PRIVACY_"))
                    {
                        $this->privacyBadge = new MPrivacyBadge($icon);
                    }
                }
            }

            // Create action butttons
            $orderedButtonQueue = [];

            /*
             * Updated to just hardcode this (it's generally fine, any video can be added to a playlist)
             * 
             * To elaborate, the previous implementation iterated the available action buttons from
             * the InnerTube response, which usually worked.
             * 
             * However, as of August 2022, YouTube has been experimenting with a rewrite of the action
             * buttons architecture, seemingly in an attempt to fix the Kevlar frontend watch page
             * imploding cuz they have 12 action buttons with full labels on the screen and typically
             * only report and sometimes transcript in the overflow menu.
             * 
             * No, of course they didn't bother fixing this in the most intuitive ways, like having
             * hidden or dynamic label visibility (even though they always tell you what they are
             * if you hover over the buttons anyways so THERE IS NO FUCKING POINT TO THE ADDITIONAL
             * LABEL), instead they added a new type of button called "menuFlexibleItemRenderer" inside
             * a new container of "flexibleItems".
             * 
             * It seems that, instead of the most obvious change of going from:
             * 
             * 👍 12K 👎 DISLIKE ➡️ SHARE ⬇️ DOWNLOAD ❤️ THANKS ✂️ CLIP ➕ SAVE  ...
             * 
             * to:
             * 
             * 👍 12K 👎  ➡️  ⬇️  ❤️  ✂️  ➕   ...
             * 
             * or (they will never actually use the overflow menu tho):
             * 
             * 👍 12K 👎  ➡️  ➕   ...
             *          ┌───────────────┐
             *          │ 🚩 Report     │
             *          │ 🗒️ Transcript │
             *          │ ⬇️ Download   │
             *          │ ❤️ Thanks     │
             *          │ ✂️ Clip       │
             *          └───────────────┘
             * 
             * They instead decided to rework the API to "lazily" fix their broken ass frontend,
             * and this is why sometimes "add to" would randomly not render, because this experiment
             * only applies to ~1/10th of the unique visitor IDs.
             */
            if (!$bakery->isKidsVideo)
            {
                $orderedButtonQueue[] = MActionButton::buildAddtoButton($videoId);
            }

            // Share button should always be built unless this is a
            // Kids video
            if (!$bakery->isKidsVideo)
            {
                $shareButton = MActionButton::buildShareButton();

                if (null != $shareButton) $orderedButtonQueue[] = $shareButton;
            }

            // Report button shows as an action button for livestreams, rather than
            // residing in the overflow menu.
            if ($bakery->isLive)
            {
                $orderedButtonQueue[] = MActionButton::buildReportButton();
            }
            else
            {
                $orderedButtonQueue[] = MActionButton::buildMoreButton();
            }

            $this->actionButtons = &$orderedButtonQueue;
        }
    }
}