# Auto-Research Optimizer — Examples & Harnesses

## 1. Minimal Benchmark Runner (Laravel Artisan)

```php
// routes/console.php or app/Console/Commands/BenchmarkQuery.php
Artisan::command('benchmark:hotpath', function () {
    \DB::flushQueryLog();
    \DB::enableQueryLog();

    $start = microtime(true);
    $memBefore = memory_get_usage();

    // The isolated hot path
    app(\App\Domain\Reports\Actions\GenerateDailySummary::class)->execute();

    $duration = (microtime(true) - $start) * 1000;
    $memUsed = (memory_get_peak_usage() - $memBefore) / 1024 / 1024;
    $queries = count(\DB::getQueryLog());

    $this->line(sprintf("METRIC_MS=%.2f METRIC_MB=%.2f METRIC_QUERIES=%d", $duration, $memUsed, $queries));
});
```

---

## 2. Autonomous Loop Runner (`scripts/optimize-loop.sh`)

```bash
#!/usr/bin/env bash
set -euo pipefail

BASELINE=85.4
TARGET_METRIC="METRIC_MS"

run_benchmark() {
    php artisan benchmark:hotpath | grep "$TARGET_METRIC" | cut -d'=' -f2 | cut -d' ' -f1
}

echo "Current baseline: $BASELINE ms"
CURRENT=$(run_benchmark)
echo "Measured current: $CURRENT ms"

# Compare using bc or awk
IMPROVED=$(awk -v cur="$CURRENT" -v base="$BASELINE" 'BEGIN {print (cur < base * 0.95) ? 1 : 0}')

if [ "$IMPROVED" -eq 1 ]; then
    echo "Optimization succeeded! Improved from $BASELINE to $CURRENT ms."
    git commit -am "perf: improve hot path from ${BASELINE}ms to ${CURRENT}ms"
else
    echo "Regression or negligible gain ($CURRENT ms vs $BASELINE ms). Reverting..."
    git checkout -- .
fi
```

---

## 3. Benchmark History Log (`docs/research/benchmarks.csv`)

```csv
timestamp,hypothesis,metric_name,baseline,result,verdict
2026-10-20T10:00:00Z,"Add index on orders(user_id, status)",latency_ms,85.4,18.2,ACCEPTED
2026-10-20T10:15:00Z,"In-memory caching of permissions",latency_ms,18.2,22.1,REVERTED
2026-10-20T10:30:00Z,"Select only id and total columns",latency_ms,18.2,12.4,ACCEPTED
```
