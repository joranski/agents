# Polyglot Architecture — Examples & Reference

## 1. Phone App Dumb View + FSM ViewModel (SwiftUI)

```swift
// FSM State
enum OrderState: Equatable {
    case idle
    case submitting
    case confirmed(orderId: String)
    case failed(reason: String)
}

// Dumb View
struct CheckoutView: View {
    @StateObject var viewModel: CheckoutViewModel

    var body: some View {
        VStack {
            switch viewModel.state {
            case .idle:
                Button("Place Order") { viewModel.send(.submitOrder) }
            case .submitting:
                ProgressView("Securing order...")
            case .confirmed(let id):
                Text("Order #\(id) Confirmed!").bold()
            case .failed(let err):
                Text("Error: \(err)").foregroundColor(.red)
            }
        }
    }
}
```

---

## 2. Python/PHP-to-Rust Hot-Path Offload Pattern

### Step 1: Rust Fast Binary (`src/main.rs`)
```rust
use std::io::{self, Read, Write};

fn transform_heavy(input: &[u8]) -> Vec<u8> {
    // High-performance CPU-intensive processing
    input.iter().map(|b| b.wrapping_add(1)).collect()
}

fn main() -> io::Result<()> {
    let mut buffer = Vec::new();
    io::stdin().read_to_end(&mut buffer)?;
    let output = transform_heavy(&buffer);
    io::stdout().write_all(&output)?;
    Ok(())
}
```

### Step 2: PHP Byte-for-Byte Parity Harness (`tests/Feature/RustParityTest.php`)
```php
it('guarantees byte-for-byte parity between PHP and Rust binary', function () {
    $samples = [
        'sample_input_1',
        'sample_input_2',
        random_bytes(1024),
    ];

    foreach ($samples as $input) {
        $phpResult = app(LegacyHeavyTransformer::class)->transform($input);

        $process = \Illuminate\Support\Facades\Process::input($input)
            ->run(base_path('bin/rust-transformer'));

        expect($process->successful())->toBeTrue();
        expect($process->output())->toEqual($phpResult);
    }
});
```
