# Laravel Skebby SMS

Package for using Skebby SMS gateway with Laravel projects.

## Requirements

- PHP >= 7.0
- Laravel 8.x, 9.x, 10.x, or 11.x

## Installation

Install this package using Composer:

```bash
composer require alagaccia/skebby
```

### Publish Configuration (Optional)

The package will automatically register itself with Laravel. Optionally, you can publish the configuration file:

```bash
php artisan vendor:publish --tag=skebby-config
```

This will create `config/skebby.php` in your Laravel application.

## Configuration

Add the following environment variables to your `.env` file:

```env
SKEBBY_USER="your-skebby-username"
SKEBBY_PWD="your-skebby-password" 
SKEBBY_ALIAS="your-skebby-alias"
SKEBBY_QUALITY="TI"
```

## Usage

### Using the Facade

```php
use Skebby;

// Send SMS
$result = Skebby::send('1234567890', 'Hello from Laravel!');

// Get account info
$info = Skebby::getInfo();

// Get remaining credits
$remaining = Skebby::getRemaining();
```

### Using Dependency Injection

```php
use AndreaLagaccia\Skebby\Skebby;

class SmsController extends Controller
{
    public function sendSms(Skebby $skebby)
    {
        $result = $skebby->send('1234567890', 'Hello from Laravel!');
        return response()->json($result);
    }
}
```

### Direct Instantiation

```php
use alagaccia\skebby\Skebby;

$skebby = new Skebby();
$result = $skebby->send('1234567890', 'Hello World!');
```

## Advanced Usage

### Error Handling

The package includes comprehensive error handling with custom exceptions:

```php
use alagaccia\skebby\Skebby;
use alagaccia\skebby\Exceptions\SkebbyException;

try {
    $skebby = new Skebby();
    $result = $skebby->send('1234567890', 'Hello World!');
    echo "SMS sent successfully!";
} catch (SkebbyException $e) {
    echo "SMS sending failed: " . $e->getMessage();
    // Handle specific error codes
    if ($e->getCode() == 401) {
        echo "Authentication failed - check your credentials";
    }
}
```

### Message Types

You can specify different message types when sending SMS:

```php
use Skebby;

// Send with specific message type
$result = Skebby::send('1234567890', 'Hello!', Skebby::MESSAGE_CLASSIC_PLUS);

// Send with campaign name for tracking
$result = Skebby::send('1234567890', 'Hello!', null, 'Summer Campaign 2024');

// Send with both message type and campaign name
$result = Skebby::send('1234567890', 'Hello!', Skebby::MESSAGE_CLASSIC_PLUS, 'Summer Campaign 2024');

// Available message types:
// Skebby::MESSAGE_CLASSIC_PLUS  ('GP') - Classic Plus
// Skebby::MESSAGE_CLASSIC       ('TI') - Classic (default)
// Skebby::MESSAGE_BASIC         ('SI') - Basic
// Skebby::MESSAGE_EXPORT        ('EE') - Export
// Skebby::MESSAGE_ADVERTISING   ('AD') - Advertising
```

### Credit Management

Check remaining credits for different message types:

```php
use Skebby;

// Get remaining credits for current message type
$remaining = Skebby::getRemaining();

// Get remaining credits for specific message type
$remaining = Skebby::getRemaining(Skebby::MESSAGE_CLASSIC_PLUS);

// Get all remaining credits
$allCredits = Skebby::getAllRemainingCredits();
// Returns: ['GP' => 100, 'TI' => 200, 'SI' => 300, ...]
```

### Authentication Cache

The package automatically caches authentication credentials for the duration of the request to improve performance:

```php
use Skebby;

// Clear authentication cache if needed
Skebby::clearAuthCache();
```

## Configuration Options

The configuration file supports the following options:

```php
return [
    'SKEBBY_USER' => env('SKEBBY_USER'),
    'SKEBBY_PWD' => env('SKEBBY_PWD'),
    'SKEBBY_ALIAS' => env('SKEBBY_ALIAS'),
    'SKEBBY_QUALITY' => env('SKEBBY_QUALITY', 'TI'), // Default message type
];
```

## API Reference

### Skebby Class Methods

- `send(string $phone, string $message, ?string $messageType = null, ?string $campaignName = null): ?array`
- `getInfo(): ?array` - Get account information
- `getRemaining(?string $messageType = null): int` - Get remaining credits
- `getAllRemainingCredits(): array` - Get all remaining credits
- `login(): array` - Authenticate with API
- `clearAuthCache(): void` - Clear authentication cache
