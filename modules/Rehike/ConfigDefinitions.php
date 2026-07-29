<?php
namespace Rehike;

use Rehike\ConfigManager\Config;
use Rehike\ConfigManager\IConfigDefinitionsProvider;
use Rehike\PropertyAtPath;

use Rehike\ConfigManager\Properties\{
    BoolProp,
    EnumProp,
    PropGroup,
    DependentProp,
    StringProp
};

/** @internal */
class AppearanceConfigDefinitions implements IConfigDefinitionsProvider
{
    public function __construct(
        public BoolProp $smallPlayer = new BoolProp(true),
        public EnumProp $branding = new EnumProp("BRANDING_2024_RINGO2", [
            "BRANDING_2024_RINGO2",
            "BRANDING_2017_RINGO",
            "BRANDING_2015",
        ]),
        public EnumProp $uploadButtonType = new EnumProp("MENU", [
            "BUTTON",
            "ICON",
            "MENU"
        ]),
        public BoolProp $showTrending = new BoolProp(false),
        public BoolProp $showNewInfoOnChannelAboutPage = new BoolProp(true),
        public BoolProp $largeSearchResults = new BoolProp(true),
        public BoolProp $swapSearchViewsAndDate = new BoolProp(false),
        public BoolProp $showOldUploadedOnText = new BoolProp(false),
        public BoolProp $useLegacyRoboto = new BoolProp(false),
        public BoolProp $showVersionInFooter = new BoolProp(true),
        public BoolProp $usernamePrepends = new BoolProp(false),
        public BoolProp $useRyd = new BoolProp(true),
        public BoolProp $enableSponsorblockFixes = new BoolProp(true),
        public BoolProp $noViewsText = new BoolProp(false),
        public EnumProp $watchedVideoThumbnailOverlayStyle = new EnumProp("PROGRESS_BAR", [
            "PROGRESS_BAR",
            "WATCHED_BADGE",
        ]),
        public BoolProp $movingThumbnails = new BoolProp(true),
        public BoolProp $cssFixes = new BoolProp(true),
        public BoolProp $watchSidebarDates = new BoolProp(false),
        public BoolProp $watchSidebarVerification = new BoolProp(false),
        public BoolProp $oldBestOfYouTubeIcons = new BoolProp(false),
        public BoolProp $enableAdblock = new BoolProp(true),
    )
    {
    }
}

/** @internal */
class ExperimentsConfigDefinitions implements IConfigDefinitionsProvider
{
    // This property has a complex constructor, so it must be initialized in the
    // body of the constructor.
    public BoolProp $tickInjectionForScheduling;

    public function __construct(
        public BoolProp $useSignInV2 = new BoolProp(false),
        public BoolProp $asyncAttestationRequest = new BoolProp(true),
        public EnumProp $temp20240827_playerMode = new EnumProp("USE_WEB_V2", [
            "USE_WEB_V2",
            "USE_EMBEDDED_PLAYER_REQUEST",
            "USE_EMBEDDED_PLAYER_DIRECTLY",
        ]),
        public BoolProp $alwaysUseContentPoToken = new BoolProp(false),
    )
    {
        $this->tickInjectionForScheduling = (new BoolProp(false))->registerUpdateCb(function() {
            // When this configuration property changes, the contents of the PHP files
            // change virtually without being touched on disk, so we just manually
            // clear the opcache to recompile the scripts:
            if (function_exists("opcache_reset"))
                opcache_reset();
        });
    }
}

/** @internal */
class AdvancedDeveloperConfigDefinitions implements IConfigDefinitionsProvider
{
    public function __construct(
        public BoolProp $ignoreUnresolvedPromises = new BoolProp(false),
    )
    {
    }
}

/** @internal */
class AdvancedConfigDefinitions implements IConfigDefinitionsProvider
{
    public function __construct(
        public StringProp $dnsAddress = new StringProp("1.1.1.1"),
        public BoolProp $disableSslVerification = new BoolProp(false),
        public BoolProp $enableDebugger = new BoolProp(false),
        public AdvancedDeveloperConfigDefinitions $developer = new AdvancedDeveloperConfigDefinitions(),
    )
    {
    }
}

/** @internal */
class HiddenConfigDefinitions implements IConfigDefinitionsProvider
{
    public function __construct(
        public StringProp $language = new StringProp("en-US"),
        public StringProp $gl = new StringProp("US"),
        public BoolProp $securityIgnoreWindowsServerRunningAsSystem = new BoolProp(false),
        public BoolProp $disableRehike = new BoolProp(false),
        public BoolProp $enableProfiler = new BoolProp(false),
    )
    {
    }
}

/**
 * Defines Rehike configuration definitions.
 * 
 * @author Niko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */
class ConfigDefinitions implements IConfigDefinitionsProvider
{
    public function __construct(
        public AppearanceConfigDefinitions $appearance = new AppearanceConfigDefinitions(),
        public ExperimentsConfigDefinitions $experiments = new ExperimentsConfigDefinitions(),
        public AdvancedConfigDefinitions $advanced = new AdvancedConfigDefinitions(),
        public HiddenConfigDefinitions $hidden = new HiddenConfigDefinitions(),
    )
    {
    }
    
    public static function migrateOldOptions(): void
    {
        $changedAnything = false;
        
        $migrateAndRemoveOriginal = function(string $prop, \Closure $cb) use (&$changedAnything) {
            $originalProperty = null;
            $originalProperty = Config::getRawConfigProp($prop);
            if ($originalProperty !== null)
            {
                $cb($originalProperty);
                Config::removeConfigProp($prop);
                $changedAnything = true;
            }
        };
        
        $migrateAndRemoveOriginal("appearance.modernLogo", fn(bool $modernLogo) =>
            Config::get()->appearance->branding->setValue($modernLogo
                ? "BRANDING_2014_RINGO2"
                : "BRANDING_2015"
            )
        );
        
        if ($changedAnything)
        {
            Config::dumpConfig();
        }
    }
}
