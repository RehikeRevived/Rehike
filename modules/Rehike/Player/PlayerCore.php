<?php
namespace Rehike\Player;

require_once "Constants.php";

use Rehike\Player\{
    Exception\CacherException,
    Exception\UpdaterException
};

/**
 * A library for locating the YouTube player application and retrieving
 * necessary startup state.
 * 
 * @author Isabella Lulamoon <kawapure@gmail.com>
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 *
 * @version 4.0
 */
class PlayerCore
{
    public static string $playerJsRegex =
        "#/s/player/[a-zA-Z0-9/\-_.]*base.js#";
    public static string $playerCssRegex =
        "#/s/player/[a-zA-Z0-9/\-_.]*(www-player|www-player-webp|player-rtl).css#";
    public static string $embedJsRegex =
        "#/s/(player|embeds)/[a-zA-Z0-9/\-_.]*www-embed-player(-es6)?.js#";
    public static string $embedJsRegex2 =
        "#/s/(player|embeds)/[a-zA-Z0-9/\-_.]*player(-|_)embed((-|_)es6)?.*?.js#";
    public static string $stsRegex =
        "/signatureTimestamp:?\s*([0-9]*)/";

    public static string $cacheDestDir = "cache";
    public static string $cacheDestName = "player_cache"; // .json
    public static int $cacheMaxTime = 18000; // 5 hours in seconds

    /**
     * The main function: get the necessary player information!
     */
    public static function getInfo(): PlayerInfo
    {
        // Try getting information from the cacher:
        try
        {
            return PlayerInfo::from(Cacher::get());
        }
        catch (CacherException $e)
        {
            /*
             * If the cache is unavailable (doesn't exist,
             * expired, etc.) then request the data from
             * YouTube's servers.
             *
             * This function does also throw an exception, but
             * it should be treated as fatal since there's nothing
             * else to do here.
             */
            $remoteInfo = PlayerUpdater::requestPlayerInfo();

            // Attempt to write this to cache:
            // (and if it fails, do nothing)
            try
            {
                Cacher::write($remoteInfo);
            }
            catch (CacherException $e) {}

            // If everything went right, then return the remote info:
            return PlayerInfo::from($remoteInfo);
        }
    }
}