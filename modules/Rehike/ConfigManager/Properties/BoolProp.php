<?php
namespace Rehike\ConfigManager\Properties;

/**
 * Boolean property.
 * 
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */
class BoolProp extends AbstractAssociativeProp
{
    protected bool $defaultValue = false;

    public function __construct(bool $defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * Gets the value of the property.
     */
    public function getValue(): ?bool
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
    public function setValue(bool $value): void
    {
        $this->setValueCommonInternal($value);
    }

    /**
     * Get the default value of the property.
     */
    public function getDefaultValue(): bool
    {
        return $this->defaultValue;
    }

    public function getType(): string
    {
        return "bool";
    }
}