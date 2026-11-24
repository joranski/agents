<?php

declare(strict_types=1);

namespace App\Architecture\Support;

use App\Architecture\Contracts\SpecificationInterface;

/**
 * Composite Specification that inverts its operand.
 *
 * Equivalent to ! $operand->isSatisfiedBy($subject) but composes
 * fluently: ->not()->and(...), etc.
 */
final class NotSpecification extends AbstractSpecification
{
    public function __construct(
        private readonly SpecificationInterface $operand,
    ) {}

    public function isSatisfiedBy(mixed $subject): bool
    {
        return ! $this->operand->isSatisfiedBy($subject);
    }
}
