# WebsiteSQL Utilities

A comprehensive collection of utility factory classes for common web development tasks.

## Table of Contents

- [WebsiteSQL Utilities](#websitesql-utilities)
	- [Table of Contents](#table-of-contents)
	- [Installation](#installation)
	- [Usage](#usage)
		- [Basic Setup](#basic-setup)
		- [UUID Generation](#uuid-generation)
		- [String Manipulation](#string-manipulation)
		- [DateTime Handling](#datetime-handling)
		- [Pagination](#pagination)
		- [Security](#security)
		- [File Operations](#file-operations)
		- [Validation](#validation)
	- [API Reference](#api-reference)
		- [UuidFactory](#uuidfactory)
		- [StringFactory](#stringfactory)
		- [DateTimeFactory](#datetimefactory)
		- [PaginationFactory](#paginationfactory)
		- [SecurityFactory](#securityfactory)
		- [FileFactory](#filefactory)
		- [ValidationFactory](#validationfactory)
	- [Contributing](#contributing)
	- [License](#license)

## Installation

You can install the package via composer:

```bash
composer require websitesql/utilities
```

## Usage

### Basic Setup

The `Utilities` class serves as an entry point to access all other specialized factory classes. It provides static methods that instantiate and return factory objects for different domains.

```php
use WebsiteSQL\Utilities\Utilities;

// Access different factories
$uuid = Utilities::uuid();
$string = Utilities::string('Hello World');
$datetime = Utilities::datetime('2023-10-10');
```

### UUID Generation

Generate and manipulate UUIDs of different versions:

```php
// Generate a version 4 UUID
$uuid = Utilities::uuid()->generate('4')->toString();
// Output: e.g. "f47ac10b-58cc-4372-a567-0e02b2c3d479"

// Generate a version 1 UUID
$uuid = Utilities::uuid()->generate('1')->toString();

// Create from an existing UUID string
$uuid = Utilities::uuid()->fromString('f47ac10b-58cc-4372-a567-0e02b2c3d479');
```

### String Manipulation

Perform various string operations with a fluent interface:

```php
// Generate a random string
$randomString = Utilities::string()->random(12, 'alphanumeric')->toString();
// Output: e.g. "a1B2c3D4e5F6"

// Convert any string to a slug
$slug = Utilities::string('Hello World!')->slugify()->toString();
// Output: "hello-world"

// Encrypt a string
$secret = 'my-secret-key';
$encrypted = Utilities::string('sensitive data')->encrypt($secret)->toString();

// Decrypt a string
$decrypted = Utilities::string($encrypted)->decrypt($secret)->toString();
// Output: "sensitive data"

// Truncate a long string
$truncated = Utilities::string('This is a very long text that should be truncated')
    ->truncate(20, '...')
    ->toString();
// Output: "This is a very long..."
```

### DateTime Handling

Manipulate dates and times with ease:

```php
// Format a date
$date = Utilities::datetime('2023-10-15')->format('Y-m-d');
// Output: "2023-10-15"

// Get time ago
$timeAgo = Utilities::datetime('2023-01-15')->timeAgo();
// Output: e.g. "9 months ago"

// Chain operations
$nextMonth = Utilities::datetime()
    ->modify('+1 month')
    ->setTimezone('America/New_York')
    ->format('Y-m-d H:i:s');
```

### Pagination

Handle pagination and searching in arrays:

```php
// Sample data
$data = [
    ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
    ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
    // More items...
];

// Basic pagination
$result = Utilities::pagination($data)
    ->paginate(0, 10)
    ->getResult();

// Search and paginate
$result = Utilities::pagination($data)
    ->search('john', ['name', 'email'])
    ->paginate(0, 5)
    ->getResult();
```

### Security

Handle security-related operations:

```php
// Parse cookies from a header
$cookies = Utilities::security()->parseCookies('name=value; session=abc123');

// Generate a cookie header
$cookieHeader = Utilities::security()->generateCookieHeader('session', 'abc123', [
    'domain' => 'example.com',
    'expires' => new DateTime('+1 day'),
    'secure' => true
]);

// Hash and verify passwords
$hash = Utilities::security()->hashPassword('my-secure-password');
$valid = Utilities::security()->verifyPassword('my-secure-password', $hash);

// Generate and validate CSRF tokens
$token = Utilities::security()->generateCSRFToken();
$isValid = Utilities::security()->validateCSRFToken($userToken, $storedToken);
```

### File Operations

Work with files efficiently:

```php
// Read a file
$content = Utilities::file('/path/to/file.txt')->read();

// Write to a file
Utilities::file('/path/to/output.txt')
    ->open(null, 'w')
    ->write('Hello World!')
    ->close();

// Read a CSV file with headers
$data = Utilities::file('/path/to/data.csv')->readCsv(true);

// Write data to a CSV file
$data = [
    ['name' => 'John', 'email' => 'john@example.com'],
    ['name' => 'Jane', 'email' => 'jane@example.com']
];
Utilities::file('/path/to/output.csv')->writeCsv($data);
```

### Validation

Validate user input:

```php
$validator = Utilities::validation();

// Validate an email
$isValid = $validator->email('user@example.com');

// Validate form data
if (
    $validator->required($_POST['name'], 'name') &&
    $validator->email($_POST['email']) &&
    $validator->minLength($_POST['password'], 8, 'password')
) {
    // All validation passed
} else {
    $errors = $validator->getErrors();
    // Handle errors
}
```

## API Reference

### UuidFactory

| Method | Description |
|--------|-------------|
| `generate(string $version = '4'): UuidFactory` | Generates a UUID of the specified version |
| `toString(): string` | Returns the UUID as a string |
| `fromString(string $uuid): UuidFactory` | Creates a UUID object from a string |
| `getVersion(): int` | Returns the UUID version |

### StringFactory

| Method | Description |
|--------|-------------|
| `random(int $length = 10, string $charset = 'alphanumeric'): StringFactory` | Generates a random string |
| `slugify(): StringFactory` | Converts a string to a URL-friendly slug |
| `encrypt(string $key, string $method = 'aes-256-cbc'): StringFactory` | Encrypts the string |
| `decrypt(string $key, string $method = 'aes-256-cbc'): StringFactory` | Decrypts the string |
| `truncate(int $length = 100, string $append = '...'): StringFactory` | Truncates the string to specified length |
| `toString(): string` | Returns the string value |

### DateTimeFactory

| Method | Description |
|--------|-------------|
| `format(string $format = 'Y-m-d H:i:s'): string` | Formats the date using the specified format |
| `timeAgo(): string` | Returns a human-readable time difference |
| `setTimezone(string $timezone): DateTimeFactory` | Sets the timezone |
| `modify(string $modifier): DateTimeFactory` | Modifies the date/time |
| `diff(string $datetime): \DateInterval` | Returns the difference between dates |
| `getDateTime(): DateTime` | Returns the PHP DateTime object |
| `toString(string $format = 'Y-m-d H:i:s'): string` | Returns the formatted date string |

### PaginationFactory

| Method | Description |
|--------|-------------|
| `setData(array $data): PaginationFactory` | Sets the data to paginate |
| `search(string $searchTerm, array $searchColumns = []): PaginationFactory` | Filters data by search term |
| `paginate(int $offset = 0, int $limit = 10): PaginationFactory` | Sets pagination parameters |
| `getResult(): array` | Returns the paginated results |

### SecurityFactory

| Method | Description |
|--------|-------------|
| `parseCookies(string $cookieHeader): array` | Parses cookies from a header string |
| `parseAuthorization(string $authorizationHeader): ?string` | Extracts token from Authorization header |
| `calculateExpiryDate(DateTime $createdAt, int $maxAge, int $refreshAge): DateTime` | Calculates token expiry date |
| `generateCookieHeader(string $name, string $value, array $options = []): string` | Generates a cookie header |
| `hashPassword(string $password): string` | Hashes a password using bcrypt |
| `verifyPassword(string $password, string $hash): bool` | Verifies a password against a hash |
| `generateCSRFToken(): string` | Generates a CSRF token |
| `validateCSRFToken(string $token, string $storedToken): bool` | Validates a CSRF token |

### FileFactory

| Method | Description |
|--------|-------------|
| `open(string $filePath = null, string $mode = 'r'): FileFactory` | Opens a file |
| `read(int $length = null): string` | Reads the file contents |
| `write(string $data): FileFactory` | Writes data to the file |
| `close(): void` | Closes the file handle |
| `readCsv(bool $hasHeader = true): array` | Reads and parses a CSV file |
| `writeCsv(array $data, bool $includeHeader = true): FileFactory` | Writes data to a CSV file |

### ValidationFactory

| Method | Description |
|--------|-------------|
| `date(string $date, string $format = 'm/d/Y', bool $strict = true): bool` | Validates a date string |
| `email(string $email): bool` | Validates an email address |
| `url(string $url): bool` | Validates a URL |
| `ip(string $ip): bool` | Validates an IP address |
| `required(string $value, string $fieldName): bool` | Checks if a value is not empty |
| `minLength(string $value, int $length, string $fieldName): bool` | Validates minimum string length |
| `maxLength(string $value, int $length, string $fieldName): bool` | Validates maximum string length |
| `numeric(string $value, string $fieldName): bool` | Validates if a value is numeric |
| `getErrors(): array` | Returns validation errors |
| `hasErrors(): bool` | Checks if there are validation errors |
| `clearErrors(): ValidationFactory` | Clears validation errors |

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). Please see the [License File](LICENSE.md) for more information.
