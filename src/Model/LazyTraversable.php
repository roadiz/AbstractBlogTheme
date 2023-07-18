<?php

declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Model;

use IteratorAggregate;
use Traversable;

/**
 * @template T
 * @implements IteratorAggregate<T>
 */
final class LazyTraversable implements IteratorAggregate
{
    /**
     * @var callable callable
     */
    private $callable;

    /**
     * @var Traversable<T>|null
     */
    private $traversable = null;

    /**
     * @param callable $callable
     */
    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public function getIterator()
    {
        if (null === $this->traversable) {
            if (!\is_callable($this->callable)) {
                throw new \RuntimeException('LazyTraversable callable must be a callable');
            }
            $traversable = ($this->callable)();
            if (!($traversable instanceof \Traversable) && !\is_array($traversable)) {
                throw new \RuntimeException(
                    'LazyTraversable callable must return a Traversable or array'
                );
            }
            if (\is_array($traversable)) {
                $this->traversable = new \ArrayIterator($traversable);
            } else {
                $this->traversable = $traversable;
            }
        }

        return $this->traversable;
    }
}
