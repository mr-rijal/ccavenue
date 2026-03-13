# CCAvenue for Laravel

Laravel package for [CCAvenue](https://www.ccavenue.com/) payment gateway integration. Supports Laravel 11, 12, and 13.

## Requirements

- PHP 8.1+
- Laravel 11.x, 12.x, or 13.x

## Installation

Install via Composer:

```bash
composer require mr-rijal/ccavenue
```

Publish the config and views:

```bash
php artisan vendor:publish --tag=ccavenue-config
```

## Configuration

Add your CCAvenue credentials to `.env`:

```env
CCAVENUE_MERCHANT_ID=your_merchant_id
CCAVENUE_ACCESS_CODE=your_access_code
CCAVENUE_WORKING_KEY=your_working_key
CCAVENUE_REDIRECT_URL=payment/success
CCAVENUE_CANCEL_URL=payment/cancel
CCAVENUE_CURRENCY=INR
CCAVENUE_LANGUAGE=EN
CCAVENUE_TEST_MODE=true
```

Config is merged under the `ccavenue` key (published to `config/ccavenue.php`). Set `CCAVENUE_TEST_MODE=false` for production.

For Laravel 5.x, add the response route to CSRF exceptions in `app/Http/Middleware/VerifyCsrfToken.php` (or use the published middleware). The config option `remove_csrf_check` is also available.

## Usage

Resolve the payment gateway and create a purchase:

```php
use MrRijal\CCAvenue\CCAvenue;

$ccavenue = app(CCAvenue::class);
$response = $ccavenue->purchase([
    'order_id' => 'ORD-' . uniqid(),
    'amount'   => 1000.00,
    // ... other CCAvenue parameters
]);
```

Handle the redirect response from CCAvenue in your callback controller and decode the response:

```php
$ccavenue = app(CCAvenue::class);
$result = $ccavenue->response($request);
```

Or use the facade: `CCAvenue::purchase([...])`, `CCAvenue::response($request)`.

## Development

```bash
# Run tests
composer test

# Code style (PHPCS)
composer check-style
composer fix-style

# Laravel Pint
composer pint
```

## License

This package is open-sourced software licensed under the [MIT License](LICENSE).

## Author

**Prashant Rijal**  
[https://prashantrijal.com.np](https://prashantrijal.com.np)

## Support

- [Report an issue](https://github.com/mr-rijal/ccavenue/issues)
- [Source code](https://github.com/mr-rijal/ccavenue)
