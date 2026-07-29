<?php
namespace Rehike\TemplateUtilsDelegate;

use Rehike\Util\{
    Base64,
    CasingUtils,
    ResourceUtils,
    ParsingUtils
};

use Rehike\SignInV2\SignIn;

/**
 * Defines the `rehike` variable exposed to Twig-land.
 * 
 * This class implements all alises to other utility classes. The parent
 * class handles unique properties and all methods.
 * 
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */
class RehikeUtilsDelegate extends RehikeUtilsDelegateBase
{
    public function __construct(
        public Base64 $base64 = new Base64(),
        public CasingUtils $casing = new CasingUtils(),
        public ResourceUtils $resource = new ResourceUtils(),
        public ParsingUtils $parsing = new ParsingUtils(),
        public RehikeUtilsI18nDelegate $i18n = new RehikeUtilsI18nDelegate(),
        public SignIn $signin = new SignIn(),
    )
    {
        parent::__construct();
    }
}