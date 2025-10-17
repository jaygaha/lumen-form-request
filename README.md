# Lumen Form Request

Bring Laravel-style Form Request validation to Lumen! This package provides full Form Request functionality including validation, authorization, and the `make:request-form` artisan command for Lumen 11.

Form request is a package for Lumen that lets developer validate form requests like Laravel does.

> Purposes to create a form request class that existing Lumen `form-request` packages do not support.

## Laravel vs Lumen

This package brings Laravel's FormRequest functionality to Lumen applications. While Lumen is designed to be a micro-framework, it often lacks some of the convenience features that Laravel provides. This package fills that gap by providing a complete FormRequest implementation that works seamlessly with Lumen 11.

### Why Use This Package?

- **Consistency**: Use the same validation patterns as Laravel applications
- **Productivity**: Reduce boilerplate validation code
- **Maintainability**: Centralize validation logic in dedicated classes
- **Reusability**: Share validation rules across different endpoints
- **Testing**: Easy to test validation logic in isolation

## Features

- Full Laravel Form Request functionality
- Lumen 11 support
- `make:request-form` artisan command
- Validation rules and messages
- Authorization checks
- Input data normalization
- Fully tested

## Installation

You can install the package via composer:

```bash
composer require jaygaha/lumen-form-request
```

### 1. Register Service Provider

Add the service provider to the `bootstrap/app.php` file:

```php
$app->register(JayGaha\LumenFormRequest\Providers\FormRequestServiceProvider::class);
```

That's it! You're ready to use Form Requests in your Lumen application.

### 2. Enable Validation (if not already enabled)

Make sure validation is enabled in your Lumen application. Add this to your `bootstrap/app.php`:

```php
$app->withFacades();
$app->withEloquent();
```

## Quick Start

1. Generate a Form Request

You can generate a new Form Request class using the `make:request-form` artisan command:

```bash
php artisan make:request-form CreateUserFormRequest
```

This will create a new Form Request class in the `app/Http/Requests` directory.
```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use JayGaha\LumenFormRequest\Requests\FormRequest;

class CreateUserFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            //
        ];
    }
}

```

## Support

If you find this package useful, please consider:

- Starring the repository
- Reporting bugs
- Suggesting new features
- Contributing to the project

## Contributing

Contributions are welcome! Please open an issue or submit a pull request.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.