<?php
namespace Rehike\Player;

require_once "Constants.php";

use Rehike\Player\Exception\CacherException;
use Rehike\ConfigManager\Config;
use Rehike\FileSystem;

/**
 * Manage caching and player information storage.
 * 
 * In order to make the player retrival process more
 * efficient, necessary information is stored rather than
 * queried from the server each time it is requested.
 * 
 * However, server-side variables change all the time, so
 * this storage cannot be permanent. Cache should persist
 * for a maximum of 24 hours.
 * 
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */
class Cacher
{
    /**
     * Get the current cached contents.
     */
    public static function get(): object
    {
        $root = PlayerCore::$cacheDestDir;
        $name = PlayerCore::$cacheDestName;
        $path = "$root/$name.json";

        if (!DEBUG && FileSystem::fileExists($path))
        {
            $result = json_decode(FileSystem::getFileContents($path));

            if (
                null != $result &&
                time() < $result->expire
            )
            {
                return $result->content; // file contents
            }
            else
            {
                throw new CacherException(
                    "Failed to get cached contents from file \"$path\" " .
                    "(or cache has expired)"
                );
            }
        }
        else
        {
            throw new CacherException(
                "Cache file \"$path\" does not exist"
            );
        }
    }

    /**
     * Create or update the cache file with the current
     * information.
     */
    public static function write(object $object): void
    {
        $root = PlayerCore::$cacheDestDir;
        $name = PlayerCore::$cacheDestName;
        $path = "$root/$name.json";

        $expire = PlayerCore::$cacheMaxTime;

        try
        {
            self::writeJson($path, self::expireWrap($expire, $object));
        }
        catch (CacherException $e) { throw $e; }
    }

    /**
     * Wrap an object in an expirable container.
     */
    public static function expireWrap(int $duration, object $object): object
    {
        $expireTime = time() + $duration;

        $result = (object)[
            "expire" => $expireTime,
            "content" => $object
        ];

        return $result;
    }

    /**
     * Serialise an object and write it to the path.
     */
    public static function writeJson(string $path, object|array $object): void
    {
        try
        {
            if (is_array($object))
                $object = (object)$object;

            $object = json_encode($object, JSON_PRETTY_PRINT);

            FileSystem::writeFile($path, $object);
        }
        catch (CacherException $e)
        { 
            throw $e;
        }
    }
}