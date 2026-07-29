<?php
namespace Rehike\ConfigManager\Properties;

use Closure;
use Rehike\ConfigManager\Config;

/**
 * Defines a base for a configuration property.
 * 
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */
abstract class AbstractAssociativeProp extends AbstractConfigProperty
{
    protected ?Closure $onUpdateHandler = null;
    
    /**
     * Gets the value of the property.
     * 
     * This is a non-typed, common version of the method as the body is common
     * to all implementations.
     */
    protected function getValueCommonInternal(): mixed
    {
        return Config::getRawConfigProp($this->getFullPath());
    }

    /**
     * Sets the value of the property for the application session.
     * 
     * This is a non-typed, common version of the method as the body is common
     * to all implementations.
     */
    protected function setValueCommonInternal(mixed $value): void
    {
        Config::setConfigProp($this->getFullPath(), $value);
    }

    /**
     * Gets the default value of the property.
     */
    abstract public function getDefaultValue(): mixed;

    /**
     * Gets the type of the property.
     */
    #[\Override]
    abstract public function getType(): string;
    
    /**
     * Registers an update callback.
     */
    public function registerUpdateCb(callable|Closure $onUpdate): static
    {
        if (!$onUpdate instanceof Closure)
        {
            $this->onUpdateHandler = Closure::fromCallable($onUpdate);
        }
        else
        {
            $this->onUpdateHandler = $onUpdate;
        }
        
        return $this;
    }
    
    /**
     * Method to be called whenever the configuration property changes.
     */
    public function onUpdate(): mixed
    {
        if ($this->onUpdateHandler)
        {
            return ($this->onUpdateHandler)();
        }
        
        return null;
    }
}