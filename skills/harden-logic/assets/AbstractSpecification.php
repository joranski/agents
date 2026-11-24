<?php

declare(strict_types=1);

namespace App\Architecture\Support;

use App\Architecture\Contracts\SpecificationInterface;

/**
 * Base class providing boolean composition for Specifications.
 *
 * Concrete Specifications extend this class and implement
 * isSatisfiedBy(); they then gain ->and(), ->or(), and ->not() for
 * free, returning new composite Specifications without mutating self.
 *
 * Example:
 *
 *     $rule = (new IsAdult())->and(new HasVerifiedEmail())->or(new IsAdmin());
 *     $rule->isSatisfiedBy($user);
 */
abstract class AbstractSpecification implements SpecificationInterface
{
    abstract public function isSatisfiedBy(mixed $subject): bool;

    final public function and(SpecificationInterface $other): SpecificationInterface
    {
        return new AndSpecification($this, $other);
    }

    final public function or(SpecificationInterface $other): SpecificationInterface
    {
        return new OrSpecification($this, $other);
    }

    final public function not(): SpecificationInterface
    {
        return new NotSpecification($this);
    }
}
