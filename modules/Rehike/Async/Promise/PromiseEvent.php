<?php
namespace Rehike\Async\Promise;

use Rehike\Async\{
    Promise,
    Deferred,
};

use Rehike\Async\EventLoop\{
    Event,
    EventLoop,
};

use Rehike\Async\Debugging\PromiseStackTrace;

use Exception;
use Throwable;
use Generator;
use ReflectionFunction;

/**
 * Implements a simple event wrapper for any event that interacts
 * with a Promise (i.e. most events).
 * 
 * @template T
 * 
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */
abstract class PromiseEvent/*<T>*/ extends Event 
{
    /** @var Promise<T> */
    private Promise/*<T>*/ $promise;

    use Deferred
    { 
        getPromise as public;
        resolve as protected;
        reject as protected;
    }

    public function __construct()
    {
        parent::__construct();
        
        $this->initPromise();
    }

    /**
     * Create a new PromiseEvent from an anonymous Promise.
     * 
     * This will not accept a non-generator callback, which is handled
     * in Promise::__construct(). Generator functions return
     * Generator objects after being ran, but they are not Generators
     * by default and it will cause a hang.
     * 
     * @internal
     * 
     * @param Promise<T> $p
     * @param callable(callable(mixed), callable(Throwable|string)): Generator $cb
     * @param callable(mixed): void $res Resolve API
     * @param callable(Throwable|string): void $rej Reject API
     * 
     * @return PromiseEvent<T>
     */
    public static function fromAnonPromise(
            Promise/*<T>*/ $p,
            callable $cb,
            callable $res,
            callable $rej
    ): PromiseEvent/*<T>*/
    {
        if (!(new ReflectionFunction($cb))->isGenerator())
        {
            throw new Exception(
                "Anonymous promise must be constructed from a " .
                "generator. Add \"if (false) yield;\" to your function" .
                "or update the external handler."
            );
        }

        return new class($p, $cb, $res, $rej) extends PromiseEvent/*<T>*/ {
            /**
             * @var Promise<T>
             */
            private Promise/*<T>*/ $promise;
            
            /**
             * Callback hack.
             * 
             * PHP actually doesn't allow class members to be typed
             * with callable at all, unlike C# with delegate or
             * TypeScript with its arrow-function-like syntax.
             *  
             * @var callable(callable(mixed), callable(Throwable|string)): Generator
             */
            private $onRunCb;

            /** @var callable(mixed): void */
            private $resolveApi;

            /** @var callable(Throwable|string): void */
            private $rejectApi;

            /**
             * @param Promise<T> $p
             * @param callable(callable(mixed), callable(Throwable|string)): Generator $cb
             * @param callable(mixed): void $res
             * @param callable(Throwable|string): void $rej
             */
            final public function __construct(
                    Promise/*<T>*/ $p,
                    callable &$cb,
                    callable $res,
                    callable $rej
            )
            {
                parent::__construct();

                $this->promise = $p;
                $this->onRunCb = &$cb;

                // Wrap the internal Promise APIs so that they automatically
                // fulfill the Event upon being called.
                $this->resolveApi = self::wrapPromiseApi(
                    $this, $res
                );
                $this->rejectApi = self::wrapPromiseApi(
                    $this, $rej
                );

                EventLoop::addEvent($this);
            }

            final protected function onRun(): Generator/*<T>*/
            {
                return ($this->onRunCb)($this->resolveApi, $this->rejectApi);
            }
        };
    }

    /**
     * Wrap a Promise's API to also fulfill the event.
     * 
     * This is useful for anonymous Promises, so that they don't
     * need to directly interface with the Event API.
     * 
     * @param PromiseEvent<T> $myself
     * @param callable(mixed ...): void $api
     * @return callable(mixed ...): void
     */
    protected static function wrapPromiseApi(
            PromiseEvent/*<T>*/ $myself,
            callable $api
    ): callable
    {
        /** @param mixed[] $args */
        return function (mixed ...$args) use ($myself, $api): void {
            try
            {
                $api(...$args);
            }
            finally
            {
                $myself->resolve();
                $myself->fulfill();
            }
        };
    }
}

PromiseStackTrace::registerSkippedFile(__FILE__);