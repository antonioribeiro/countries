# GitHub Actions Badges

Add these badges to your README.md file to show the CI status:

```markdown
[![Laravel](https://github.com/antonioribeiro/countries/workflows/Laravel/badge.svg)](https://github.com/antonioribeiro/countries/actions?query=workflow%3ALaravel)
[![Cross Platform](https://github.com/antonioribeiro/countries/workflows/Cross%20Platform/badge.svg)](https://github.com/antonioribeiro/countries/actions?query=workflow%3A"Cross%20Platform")
[![Latest Stable Version](https://poser.pugx.org/pragmarx/countries/v/stable)](https://packagist.org/packages/pragmarx/countries)
[![Total Downloads](https://poser.pugx.org/pragmarx/countries/downloads)](https://packagist.org/packages/pragmarx/countries)
[![License](https://poser.pugx.org/pragmarx/countries/license)](https://packagist.org/packages/pragmarx/countries)
```

## Workflow Summary

- **Laravel Workflow**: Tests Laravel compatibility (PHP 8.1-8.4 × Laravel 10-12)
- **Cross Platform Workflow**: Cross-platform testing (Ubuntu, Windows, macOS)
- **Code Quality**: Prettier formatting with PHP plugin
- **Security**: Dependency vulnerability scanning
- **Static Analysis**: PHPStan level 5 analysis
- **Coverage**: Code coverage reporting with Codecov

## Development Commands

### PHP Testing
```bash
composer test                    # Run PHPUnit tests
composer install                 # Install PHP dependencies
```

### Code Formatting (Prettier)
```bash
npm install                      # Install Node.js dependencies
npm run format                   # Format all code
npm run format:check             # Check code formatting
npm run format:php               # Format only PHP files
npm run format:check:php         # Check only PHP files
```

### Legacy Commands (still available)
```bash
composer check-style             # Check formatting via Prettier
composer fix-style               # Fix formatting via Prettier
```