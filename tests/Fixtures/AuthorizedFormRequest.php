<?php

declare(strict_types=1);

namespace JayGaha\LumenFormRequest\Tests\Fixtures;

use JayGaha\LumenFormRequest\Requests\FormRequest;
use Illuminate\Container\Container;

class AuthorizedFormRequest extends FormRequest
{
    public Container $app;

    protected function authorize(): bool
    {
        return false;
    }

    protected function rules(): array
    {
        return ['name' => 'required'];
    }
}
