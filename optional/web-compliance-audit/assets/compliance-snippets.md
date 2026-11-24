# Production Web Compliance Snippets (React & Blade / FluxUI)

Copy-paste ready, legally vetted UI components for ADA, Privacy/GPC, and Statutory Disclosures.

---

## 1. California Proposition 65 Safe Harbor Warning

### React (`Prop65Warning.tsx`)
```tsx
import React from 'react';

interface Prop65Props {
  chemicalName?: string; // e.g. "Lead" or "BPA"
  targetHarms?: 'cancer' | 'reproductive' | 'both';
  isCaliforniaShipping?: boolean;
}

export function Prop65Warning({
  chemicalName = "Lead and lead compounds",
  targetHarms = "both",
  isCaliforniaShipping = true,
}: Prop65Props) {
  if (!isCaliforniaShipping) return null;

  const harmText =
    targetHarms === "both"
      ? "cancer and birth defects or other reproductive harm"
      : targetHarms === "cancer"
      ? "cancer"
      : "birth defects or other reproductive harm";

  return (
    <aside
      aria-label="California Proposition 65 Warning"
      className="my-3 flex items-start gap-2.5 rounded-lg border border-amber-300 bg-amber-50 p-3 text-xs text-amber-950 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200"
    >
      <span aria-hidden="true" className="text-base leading-none">⚠️</span>
      <div>
        <strong className="font-semibold uppercase tracking-wide">WARNING:</strong>{" "}
        This product can expose you to chemicals including {chemicalName}, which is known to the State of California to cause {harmText}. For more information go to{" "}
        <a
          href="https://www.P65Warnings.ca.gov"
          target="_blank"
          rel="noopener noreferrer"
          className="font-medium underline hover:text-amber-900 dark:hover:text-amber-100"
        >
          www.P65Warnings.ca.gov
        </a>.
      </div>
    </aside>
  );
}
```

### Blade / FluxUI (`prop65-warning.blade.php`)
```blade
@props(['chemical' => 'Lead and lead compounds', 'harms' => 'both', 'shippingCa' => true])

@if ($shippingCa)
    <div role="note" aria-label="California Proposition 65 Warning" class="my-3 flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 p-3 text-xs text-amber-950 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
        <span aria-hidden="true" class="text-base leading-none">⚠️</span>
        <div>
            <strong class="font-bold uppercase tracking-wide">WARNING:</strong>
            This product can expose you to chemicals including {{ $chemical }}, which is known to the State of California to cause
            {{ $harms === 'both' ? 'cancer and birth defects or other reproductive harm' : ($harms === 'cancer' ? 'cancer' : 'birth defects or other reproductive harm') }}.
            For more information go to
            <a href="https://www.P65Warnings.ca.gov" target="_blank" rel="noopener noreferrer" class="font-semibold underline">
                www.P65Warnings.ca.gov
            </a>.
        </div>
    </div>
@endif
```

---

## 2. Global Privacy Control (Sec-GPC) & Cookie Consent Banner

### React (`CookieGpcBanner.tsx`)
```tsx
import React, { useEffect, useState } from 'react';

export function CookieGpcBanner() {
  const [hasConsent, setHasConsent] = useState<boolean | null>(null);
  const [gpcDetected, setGpcDetected] = useState(false);

  useEffect(() => {
    // Detect Global Privacy Control (navigator.globalPrivacyControl)
    const isGpc = typeof navigator !== 'undefined' && (navigator as any).globalPrivacyControl === true;
    if (isGpc) {
      setGpcDetected(true);
      setHasConsent(false); // Prioritize browser signal
      localStorage.setItem('cookie_consent', 'rejected_gpc');
      return;
    }

    const saved = localStorage.getItem('cookie_consent');
    if (saved) setHasConsent(saved === 'accepted');
  }, []);

  const handleAccept = () => {
    localStorage.setItem('cookie_consent', 'accepted');
    setHasConsent(true);
    // Dynamically trigger marketing script injection here
  };

  const handleDecline = () => {
    localStorage.setItem('cookie_consent', 'declined');
    setHasConsent(false);
  };

  if (hasConsent !== null) return null;

  return (
    <div
      role="region"
      aria-label="Privacy and Cookie Consent"
      className="fixed bottom-0 inset-x-0 z-50 border-t border-zinc-200 bg-white/95 p-4 backdrop-blur shadow-2xl dark:border-zinc-800 dark:bg-zinc-900/95 text-xs text-zinc-700 dark:text-zinc-300"
    >
      <div className="max-w-6xl mx-auto flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div className="space-y-1">
          <p className="font-medium text-zinc-900 dark:text-zinc-100">Your Privacy Choices</p>
          <p>
            {gpcDetected
              ? "Global Privacy Control (GPC) signal detected. Non-essential tracking and data sales are automatically disabled."
              : "We use essential cookies to operate. With your permission, we use analytics and performance cookies to improve your experience."}
          </p>
        </div>
        <div className="flex items-center gap-2 shrink-0">
          <button
            type="button"
            onClick={handleDecline}
            className="px-3 py-1.5 rounded-lg border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition"
          >
            Do Not Sell or Share / Reject
          </button>
          {!gpcDetected && (
            <button
              type="button"
              onClick={handleAccept}
              className="px-3 py-1.5 rounded-lg font-medium bg-indigo-600 text-white hover:bg-indigo-700 transition"
            >
              Accept All
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
```

---

## 3. ADA Accessibility Primitives (Skip Link & Live Announcer)

### React Skip-to-Content & Live Region
```tsx
import React from 'react';

export function SkipToContent({ targetId = "main-content" }: { targetId?: string }) {
  return (
    <a
      href={`#${targetId}`}
      className="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:px-4 focus:py-2 focus:bg-indigo-600 focus:text-white focus:rounded-lg focus:shadow-lg focus:outline-none focus:ring-2 focus:ring-white"
    >
      Skip to main content
    </a>
  );
}

export function LiveErrorAnnouncer({ message }: { message?: string | null }) {
  return (
    <div
      role="status"
      aria-live="polite"
      aria-atomic="true"
      className="sr-only"
    >
      {message ? `Form error: ${message}` : ''}
    </div>
  );
}
```

### Blade / HTML Skip-to-Content
```html
<a
    href="#main-content"
    class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:px-4 focus:py-2 focus:bg-indigo-600 focus:text-white focus:rounded-lg focus:shadow-lg focus:outline-none focus:ring-2 focus:ring-white"
>
    Skip to main content
</a>
```
