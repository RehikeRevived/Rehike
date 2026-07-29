<?php
namespace Rehike\ConfigManager\Properties;

/**
 * Enumerated property.
 * 
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */
class EnumProp extends AbstractAssociativeProp
{
    protected string $defaultValue = "";

    /**
     * @var string[]
     */
    protected array $validValues = [];

    /**
     * @param string[] $validValues
     */
    public function __construct(string $defaultValue, array $validValues)
    {
        $this->defaultValue = $defaultValue;
        $this->validValues = $validValues;
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

    /**
     * Get the default value of the property.
     */
    #[\Override]
    public function getDefaultValue(): mixed
    {
        return $this->defaultValue;
    }

    public function getValidValues(): array
    {
        return $this->validValues;
    }

    #[\Override]
    public function getType(): string
    {
        return "enum";
    }
}