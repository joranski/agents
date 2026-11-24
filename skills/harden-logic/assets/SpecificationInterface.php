<?php

declare(strict_types=1);

namespace App\Architecture\Contracts;

/**
 * The base contract every Specification adheres to.
 *
 * A Specification answers a single boolean question about a subject and
 * is intentionally minimal — composition (and / or / not) lives on the
 * AbstractSpecification base class, not on this interface, so that any
 * implementation MAY skip the chaining mechanics if it is only ever
 * used standalone.
 *
 * Implementations MUST be deterministic and side-effect-free. Calling
 * isSatisfiedBy() twice with the same subject MUST return the same
 * boolean. Any rule that needs I/O belongs in a pipeline step, not in
 * a Specification.
 */
interface SpecificationInterface
{
    public function isSatisfiedBy(mixed $subject): bool;
}
