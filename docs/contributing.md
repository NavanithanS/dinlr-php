# Contributing

Contributions are welcome!

## Setup

```bash
git clone https://github.com/NavanithanS/dinlr-php.git
cd dinlr-php
composer install
git checkout -b feature/your-feature
```

## Standards

- PSR-4 autoloading, PSR-12 code style
- PHPDoc on all public methods
- Tests required for new features
- No breaking changes without a major version bump

## Quality Checks

```bash
composer test      # PHPUnit tests
composer cs        # Check code style
composer cs-fix    # Fix code style
composer analyse   # PHPStan static analysis
```

## Pull Request Process

1. Branch from `master`
2. Add tests for new functionality
3. Ensure all checks pass (`test`, `cs`, `analyse`)
4. Submit PR with a clear description of the change and why

## Reporting Issues

Include:
- PHP version and library version
- Full error message and stack trace
- Minimal code that reproduces the issue
- Expected vs actual behaviour
