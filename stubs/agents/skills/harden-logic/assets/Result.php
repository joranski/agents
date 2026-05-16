<?php

declare(strict_types=1);

namespace App\Architecture\Support;

use LogicException;
use Throwable;

/**
 * Immutable Railway Oriented Programming (ROP) wrapper.
 *
 * Construct with Result::success($data) or Result::failure($error).
 * Chain pipeline steps with then(); the chain short-circuits on the
 * first failure and the original failure is returned unchanged.
 *
 * This class is intentionally minimal — it is the contract that every
 * harden-logic pipeline step adheres to. Do not add domain logic here.
 */
final readonly class Result
{
    private function __construct(
        private bool $isSuccess,
        private mixed $value,
        private mixed $error,
    ) {}

    public static function success(mixed $data): self
    {
        return new self(true, $data, null);
    }

    public static function failure(mixed $error): self
    {
        return new self(false, null, $error);
    }

    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    public function isFailure(): bool
    {
        return ! $this->isSuccess;
    }

    /**
     * Return the success value.
     *
     * @throws LogicException when called on a failure Result. Callers
     *                        must branch on isSuccess() / isFailure()
     *                        before unwrapping, or chain via then().
     */
    public function unwrap(): mixed
    {
        if (! $this->isSuccess) {
            $message = $this->error instanceof Throwable
                ? $this->error->getMessage()
                : (is_string($this->error) ? $this->error : 'Result is in failure state');

            throw new LogicException('Cannot unwrap failure Result: '.$message);
        }

        return $this->value;
    }

    /**
     * Return the failure value, or null when the Result is a success.
     */
    public function unwrapError(): mixed
    {
        return $this->isSuccess ? null : $this->error;
    }

    /**
     * Pipe the success value into the next step.
     *
     * When this Result is a failure the next step is skipped and the
     * original failure Result is returned unchanged (short-circuit).
     *
     * The callable MUST return a Result. Returning anything else is a
     * programmer error and raises LogicException — silent type drift
     * would defeat the determinism guarantees of the pattern.
     *
     * @param  callable(mixed): self  $next
     */
    public function then(callable $next): self
    {
        if (! $this->isSuccess) {
            return $this;
        }

        $result = $next($this->value);

        if (! $result instanceof self) {
            throw new LogicException(
                'Result::then() callable must return a Result instance, got '
                .get_debug_type($result)
            );
        }

        return $result;
    }
}
