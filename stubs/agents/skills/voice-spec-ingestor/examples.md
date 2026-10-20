# Voice Spec Ingestor — Normalization & Plan Synthesis

## 1. Normalization Prompt Template

```markdown
You are a Principal Software Architect ingesting a voice transcript.
Here is the codebase lexicon (valid symbols):
{LEXICON_JSON}

Raw Voice Transcript:
"""
{RAW_TRANSCRIPT_TEXT}
"""

Task:
1. Extract user intent, constraints, and edge cases.
2. Replace all phonetic or colloquial descriptions with exact symbol matches from the lexicon.
3. Eliminate conversation filler ("um", "like", "maybe we could...").
4. Output a normalized architectural specification.
```

---

## 2. Before & After Normalization Example

### Raw Voice Input
> "Hey so on the checkout screen when someone hits pay... um if they're on the annual plan we gotta make sure the discount code thing actually validates with Stripe and doesn't crash if the coupon is expired. And flip the membership status to active."

### Normalized Architectural Spec
- **Target Component:** `resources/views/livewire/checkout.blade.php` (Livewire Volt + Flux UI)
- **Domain Action:** `App\Domain\Billing\Actions\ProcessSubscriptionCheckout`
- **Specification Gate:** `App\Domain\Billing\Specifications\CouponIsValidSpec`
- **State Mutation:** `App\Domain\Subscriptions\Enums\MembershipState::Pending` → `MembershipState::Active`
- **Failure Mode:** Catch expired coupon, return `Result::failure("Coupon has expired")`, display `<flux:error>`.

---

## 3. Synthesized 8-Step Plan Outline (Anti-Cheat Contract)

```markdown
# Checkout Discount Verification Implementation Plan

## Anti-Cheat Contract
- No hollow wrappers: implement concrete validation against Stripe API mocks.
- No disabled assertions: test must assert both success and invalid coupon failure.
- Copy stubs: copy Result.php if needed via CLI.

### Step 1: Write failing test for expired coupon failure
### Step 2: Implement CouponIsValidSpec gate
### Step 3: Run surgical test: vendor/bin/pest --filter=CouponIsValidSpecTest
### Step 4: Write failing test for checkout action pipeline
### Step 5: Implement ProcessSubscriptionCheckout action with Result pipeline
### Step 6: Wire Livewire Volt component to display error in <flux:error>
### Step 7: Run surgical component test: vendor/bin/pest --filter=CheckoutVoltTest
### Step 8: Apply 50% complexity cut and commit
```
