<?php
namespace Rehike\ConfigManager\Properties;

use Iterator;
use Rehike\ConfigManager\IConfigDefinitionsProvider;

/**
 * Represents a list of grouped properties, which have a semantic association.
 * 
 * Note that this has no effect on loading or the configuration API, only the
 * configuration GUI is affected by this.
 * 
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */
class PropGroup extends AbstractConfigProperty implements Iterator
{
    private IConfigDefinitionsProvider $definitionsProvider;
    private array $items;
    private int $currentItem = 0;

    public function __construct(IConfigDefinitionsProvider $items)
    {
        $this->definitionsProvider = $items;
        $this->items = (array)$items;
    }

    public function getProperties(): IConfigDefinitionsProvider
    {
        return $this->definitionsProvider;
    }

    #[\Override]
    public function getType(): string
    {
        return self::class;
    }

    #[\Override]
    public function current(): AbstractConfigProperty
    {
        return $this->items[$this->currentItem];
    }
    
    #[\Override]
    public function key(): int
    {
        return $this->currentItem;
    }

    #[\Override]
    public function rewind(): void
    {
        $this->currentItem = 0;
    }

    #[\Override]
    public function next(): void
    {
        $this->currentItem++;
    }

    #[\Override]
    public function valid(): bool
    {
        return isset($this->items[$this->currentItem]);
    }
}