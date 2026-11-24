<?php

declare(strict_types=1);

namespace App\Architecture\Support;

use App\Architecture\Contracts\SpecificationInterface;

/**
 * Composite Specification satisfied when EITHER operand is.
 *
 * Short-circuits: when the left operand is satisfied, the right
 * operand is not evaluated.
 */
final class OrSpecification extends AbstractSpecification
{
    public function __construct(
        private readonly SpecificationInterface $left,
        private readonly SpecificationInterface $right,
    ) {}

    public function isSatisfiedBy(mixed $subject): bool
    {
        return $this->left->isSatisfiedBy($subject)
            || $this->right->isSatisfiedBy($subject);
    }
}
