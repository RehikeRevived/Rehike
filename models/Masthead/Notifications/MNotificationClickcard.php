<?php
namespace Rehike\Model\Masthead\Notifications;

use Rehike\i18n\i18n;

class MNotificationClickcard
{
    public string $template = "masthead_notifications";
    public string $id = "yt-masthead-notifications";
    public string $cardAction = "yt.www.notifications.inbox.handleNotificationsClick";
    public array $cardClass = [
        "yt-scrollbar",
        "yt-notification-inbox-clickcard"
    ];
    public string $cardId = "yt-masthead-notifications-clickcard";

    /**
     * @var object{title:string,button:MNotificationSettingsButton}
     */
    public object $content;

    public function __construct()
    {
        $i18n = i18n::getNamespace("masthead");

        $this->content = (object) [];
        $this->content->title = $i18n->get("notificationsTitle");
        $this->content->button = new MNotificationSettingsButton();
    }
}