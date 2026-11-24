<?php

declare(strict_types=1);

namespace App\Architecture\Support;

use App\Architecture\Contracts\SpecificationInterface;

/**
 * Composite Specification satisfied only when BOTH operands are.
 *
 * Short-circuits: when the left operand is unsatisfied, the right
 * operand is not evaluated. This matters when operands perform
 * meaningful work (e.g. cached lookups) in their isSatisfiedBy().
 */
final class AndSpecification extends AbstractSpecification
{
    public function __construct(
        private readonly SpecificationInterface $left,
        private readonly SpecificationInterface $right,
    ) {}

    public function isSatisfiedBy(mixed $subject): bool
    {
        return $this->left->isSatisfiedBy($subject)
            && $this->right->isSatisfiedBy($subject);
    }
}
