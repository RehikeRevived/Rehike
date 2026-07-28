<?php
namespace Rehike\Model\Feed;

use Rehike\ConfigManager\Config;
use Rehike\Model\Appbar\MAppbarNav;
use Rehike\i18n\i18n;
use Rehike\Model\Appbar\MAppbarNavItem;
use Rehike\SignInV2\SignIn;

class MFeedAppbarNav extends MAppbarNav
{
    public function __construct(string $feedId)
    {
        $i18n = i18n::getNamespace("appbar");

        $this->addItem(
            $i18n->get("tabHome"),
            "/", $feedId == "FEwhat_to_watch"
                ? MAppbarNavItem::StatusSelected
                : MAppbarNavItem::StatusUnselected
        );

        if (Config::get()->appearance->showTrending->getValue())
        {
            $this->addItem(
                $i18n->get("tabTrending"),
                "/feed/trending",
                $feedId == "FEtrending"
                    ? MAppbarNavItem::StatusSelected
                    : MAppbarNavItem::StatusUnselected
            );
        }

        if (SignIn::isSignedIn()) 
            $this->addItem(
                $i18n->get("tabSubscriptions"),
                "/feed/subscriptions",
                $feedId == "FEsubscriptions"
                    ? MAppbarNavItem::StatusSelected
                    : MAppbarNavItem::StatusUnselected
            );
    }
}