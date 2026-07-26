<?php
namespace Rehike\Async;

use Rehike\Async\Debugging\IPromiseStackTrace;
use Rehike\Async\Promise\PromiseStatus;

use Throwable;

/**
 * Interface for Promise objects.
 * 
 * @property PromiseStatus $status  Represents the current status of a promise.
 * @property T $result  The promise result. This should only be accessed if the
 *                      promise is resolved.
 * @property Throwable $reason  The promise failure reason. This should only be
 *                              accessed if the promise is rejected.
 * 
 * @template T
 * 
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */
interface IPromise/*<T>*/
{
    /**
     * Register a function to be called upon a promise's
     * resolution.
     * 
     * @param callable(mixed): void $cb
     * @return IPromise<T>
     */
    public function then(callable $cb): IPromise/*<T>*/;

    /**
     * Register a function to be called upon an error occurring
     * during a promise's resolution.
     * 
     * @param callable(Throwable): void $cb
     * @return IPromise<T>
     */
    public function catch(callable $cb): IPromise/*<T>*/;

    /**
     * Resolve a promise.
     * 
     * @param ?T $data
     */
    public function resolve(/*?T*/ mixed $data = null): void;

    /**
     * Reject a Promise (error).
     * 
     * @internal
     */
    public function reject(string|Throwable $e): void;
}