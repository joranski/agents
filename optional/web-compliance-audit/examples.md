# Web Compliance Audit — Checklists & Remediation Reference

## Dynamic Annual Currency Rule
When performing a compliance audit, the agent must determine the current calendar year (e.g., 2026) and verify recently enacted state or federal standards:
- **Accessibility:** WCAG 2.2 Level AA requirements (focus appearance 2.4.11, minimum target size 24x24px 2.5.8, redundant entry 3.3.7).
- **Privacy:** State comprehensive privacy laws (California CPRA, Colorado, Virginia, Connecticut, Texas, Florida, Oregon, Montana) and EU GDPR.
- **FTC Click-to-Cancel:** Negative option rule requiring 1-click subscription cancellation as easy as enrollment.

---

## The 4 Lawsuit-Prevention Pillars Checklist

### Pillar 1: ADA Title III / WCAG 2.2 Level AA
- [ ] **Skip Links:** Skip-to-content anchor present as first focusable element.
- [ ] **Icon Buttons:** Every `<button>` containing only an icon has an `aria-label` or inner `sr-only` span.
- [ ] **Keyboard Traps:** Modal dialogs trap focus inside while open; close on `Escape`; restore focus to trigger element on exit.
- [ ] **Form Error Announcements:** Validation errors linked via `aria-describedby` and announced via `aria-live="polite"`.
- [ ] **Color Contrast:** Text meets 4.5:1 ratio (3:1 for large text); interactive component boundaries meet 3:1.
- [ ] **Target Size:** Interactive targets are at least 24x24 CSS pixels.

### Pillar 2: Privacy, Cookies & Global Privacy Control (GPC)
- [ ] **GPC Detection:** App inspects `navigator.globalPrivacyControl` (or `Sec-GPC: 1` request header). If active, third-party marketing tags are blocked automatically.
- [ ] **Prior-Consent Tag Loading:** Google Analytics, Meta Pixel, TikTok Pixels are NOT injected until user clicks "Accept".
- [ ] **Footer Disclosures:** Visible *"Do Not Sell or Share My Personal Information"* link in footer opening opt-out preferences.

### Pillar 3: Regional Statutory Disclosures
- [ ] **California Prop 65:** If physical goods containing listed substances ship to California, display the exact Safe Harbor warning with yellow triangle `⚠️` and `www.P65Warnings.ca.gov` prior to checkout.
- [ ] **FTC Click-to-Cancel:** Subscription cancellations must not require phone calls or retention obstacle courses if signup occurred online.

---

## Actionable Remediation Output Format

When an audit is performed, output issues in this strict structured table:

| Severity | Pillar | File:Line | Defect | Exact Remediation |
|---|---|---|---|---|
| **Critical** | Privacy / GPC | `resources/views/app.blade.php:14` | Meta Pixel injected before user consent | Wrap in consent gate check |
| **High** | ADA / WCAG | `resources/js/Pages/Cart.tsx:88` | Icon button `<button><TrashIcon /></button>` lacks label | Add `aria-label="Remove item from cart"` |
| **High** | Statutory | `resources/js/Pages/Checkout.tsx:120` | California order lacks Prop 65 warning | Insert `<Prop65Warning />` before payment CTA |
| **Medium** | ADA / WCAG | `resources/views/layouts/app.blade.php:2` | Missing skip-to-content anchor | Prepend `<a href="#main-content" class="sr-only focus:not-sr-only ...">` |

Always offer: *"Would you like me to auto-patch these findings now?"*
Ready-to-use component primitives: `assets/compliance-snippets.md`.
