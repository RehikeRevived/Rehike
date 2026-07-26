<?php
namespace Rehike\ConfigManager\Properties;

use Override;

/**
 * String property.
 * 
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */
class StringProp extends AbstractAssociativeProp
{
    protected string $defaultValue = "";

    public function __construct(string $defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * Gets the value of the property.
     */
    public function getValue(): ?string
    {
        try
        {
            return $this->getValueCommonInternal();
        }
        catch (\TypeError $e)
        {
            return null;
        }
    }

    /**
     * Sets the value of the property for the application session.
     *
     * To commit the new value to permanent storage, call
     * {@see Config::dumpConfig()}.
     */
    public function setValue(string $value): void
    {
        $this->setValueCommonInternal($value);
    }

    /** @inheritDoc */
    #[Override]
    public function getDefaultValue(): string
    {
        return $this->defaultValue;
    }

    /** @inheritDoc */
    #[Override]
    public function getType(): string
    {
        return "string";
    }
}