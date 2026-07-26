<?php
namespace Rehike\ConfigManager\Properties;

/**
 * Defines a base for a configuration property.
 * 
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */
abstract class AbstractConfigProperty
{
    protected string $propertyFullPath;

    public function getFullPath(): string
    {
        return $this->propertyFullPath;
    }

    /**
     * Sets the full path of the property. This function is only provided for
     * internal use. The path of a property cannot be reassigned.
     * 
     * @internal
     */
    public function setFullPath(string $path): void
    {
        if (!isset($this->propertyFullPath))
        {
            $this->propertyFullPath = $path;
        }
        else
        {
            throw new \Exception(
                "The full path of a configuration may not be reassigned."
            );
        }
    }

    abstract public function getType(): string;
}