<?php
namespace Rehike\Boot;

use Rehike\{
    Async\Promise,
    Async\EventLoop\EventLoop,
    Debugger\Debugger,
    DisableRehike\DisableRehike,
    Logging\LogFileManager,
    YtApp,
};
use Rehike\{
    Signin\AuthManager as LegacyAuthManager,
    SignInV2\SignIn,
};
use Rehike\Async\Promise\PromiseStatus;
use Rehike\ConfigManager\Config;

/**
 * Main bootstrapper insertion point for Rehike.
 * 
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */
final class Bootloader
{
    /**
     * Start a new Rehike session.
     */
    public static function startSession(): void
    {
        \Rehike\Profiler::start("rhboot");
        self::boot();
        \Rehike\Profiler::end("rhboot");
        self::postboot();
        self::shutdown();
    }
    
    public static function handleAsyncControllerRequest(Promise $controllerPromise): void
    {
        EventLoop::run();
        //assert($controllerPromise->status == PromiseStatus::RESOLVED);
    }

    /**
     * Performs an early shutdown.
     */
    public static function doEarlyShutdown(): void
    {
        // Silence shutdown errors such as the unhandled promise error
        if (class_exists("Rehike\\Async\\Promise", false))
        {
            if (\Rehike\Async\Promise::isCurrentlyInPromise())
            {
                if (class_exists("Rehike\\Async\\Promise\\PromiseResolutionTracker", false))
                {
                    \Rehike\Async\Promise\PromiseResolutionTracker::disable();
                }
            }
        }

        if (class_exists("Rehike\\ErrorHandler\\ErrorHandler"))
        {
            \Rehike\ErrorHandler\ErrorHandler::disable();
        }

        // Perform general shutdown tasks.
        self::shutdown(true);

        // Close the server
        exit();
    }

    /**
     * Finishes the HTTP request without ending the PHP script.
     * 
     * This must be called before any output is sent to the server. A good idea
     * is to rely on the automatic output buffering and call the function with
     * the default arguments.
     * 
     * @see https://stackoverflow.com/a/15273676
     */
    public static function finishRequest(bool $handleOutputBuffering = true, ?string &$output = null): void
    {
        ignore_user_abort(true);
        set_time_limit(0);

        if ($handleOutputBuffering)
        {
            $contentLength = ob_get_length();
        }
        else
        {
            $contentLength = strlen($output);
        }

        header("Connection: close");
        header("Content-Length: $contentLength");

        // Compressed responses are not yet supported.
        header("Content-Encoding: none");

        if ($handleOutputBuffering)
        {
            if (ob_get_level() > 1)
                ob_end_flush();

            @ob_flush();
        }

        flush();

        // Required for PHP-FPM (PHP > 5.3.3)
        if (function_exists("fastcgi_finish_request"))
            fastcgi_finish_request();
    }

    /**
     * Sets up everything necessary to load a Rehike page.
     */
    private static function boot(): void
    {
        $stage1Exception = null;
        try
        {
            self::runSetupStage1();
        }
        catch (\Throwable $e)
        {
            // Swallow any exceptions that occur from stage 1 setup if the user
            // is requesting to disable Rehike.
            $stage1Exception = $e;
        }

        // If the user requested to enable polymer, then just fast track to
        // that. We will try to initialize i18n and the config manager (which it
        // depends on) in this case, but we don't really care if that works out
        // successfully. We don't want DisableRehike to ever fail.
        if (DisableRehike::shouldDisable())
        {
            DisableRehike::disableForSession();
            EventLoop::run();
            self::shutdown();
        }
        else if (null !== $stage1Exception)
        {
            throw $stage1Exception;
        }

        self::runSetupStage2();
    }

    /**
     * Manages main application behaviour after the initial boot process is
     * done.
     */
    private static function postboot(): void
    {
        if (DisableRehike::shouldPersistentlyEnableRehikeFromCurrentUrl())
        {
            DisableRehike::enableRehike();
        }

        // Pass control to the router, which will enter the router.
        require "router.php";
    }

    /**
     * Ran after all page logic is done.
     */
    private static function shutdown(bool $early = false): void
    {
        Debugger::shutdown();
        
        if (Config::getConfigProp("hidden.enableProfiler"))
        {
            header(
                "X-Rehike-Profiler-Result: " . 
                json_encode(\Rehike\Profiler::getTimings())
            );
        }

        self::finishRequest();

        LogFileManager::pruneLogFiles();
        ShutdownEvents::runAllEvents();

        exit;
    }

    /**
     * Runs the first common stage of application startup. These startup tasks
     * are lightweight and always desirable.
     *
     * This sets up the most common system components, such as the configuration
     * manager and internationalization system.
     */
    public static function runSetupStage1(): void
    {
        // Create the global YtApp instance. The constructor of YtApp will set
        // the global instance as well.
        $yt = new YtApp();

        // Getting the network DNS working is necessary for DisableRehike to
        // work, so if this fails to initialize, then DisableRehike will not
        // work either. In the future, we should figure out a way of reporting
        // a failure at this specific point.
        Tasks::initNetworkDns();

        Tasks::initConfigManager();
        Tasks::setupI18n();
    }

    /**
     * Runs the second common stage of application startup. These startup tasks
     * are still lightweight, but are not desirable for minimal system operation
     * (i.e. DisableRehike)
     * 
     * This setups up common system components such as the networking manager,
     * resource constants store, and template manager.
     * 
     * This stage and all subsequent stages are skipped if DisableRehike is used
     * via the URL parameter "enable_polymer".
     */
    public static function runSetupStage2(): void
    {
        Tasks::initResourceConstants();
        Tasks::setupTemplateManager();
        Tasks::setupControllerCoreSpf();
        Debugger::init(YtApp::getInstance());
    }

    /**
     * Runs the third common stage of application startup. These startup tasks
     * are heavy ones which may take a lot of time. They may perform network
     * requests.
     *
     * This sets up the player manager, initializes the visitor data token for
     * new signed out YouTube browsing sessions, and initializes the sign in
     * system.
     *
     * This stage is executed by the page controller on a case by case basis.
     * For example, the static resource controller will avoid these tasks, which
     * can be timely and are not necessary to its function.
     */
    public static function runSetupStage3(): void
    {
        Tasks::setupPlayer();

        // The visitor data setup requires the player to be initialized, so it
        // must go after that.
        Tasks::setupVisitorData();

        Tasks::initSignIn();

        /*
         * TODO: This should be removed when V1 is deprecated.
         */
        $yt = YtApp::getInstance();
        if (Config::getConfigProp("experiments.useSignInV2") !== true)
        {
            LegacyAuthManager::use($yt);
            
            $yt->sv2SessionInfo = SignIn::getSessionInfo();
        }
        else
        {
            $yt->sv2SessionInfo = SignIn::getSessionInfo();
        }
    }

    /**
     * Runs the final common stage of application startup.
     * 
     * This sets up the sign in manager.
     */
    public static function setupSignIn(): void
    {
        Tasks::initSignIn();
    }
}