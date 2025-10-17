<?php

declare(strict_types=1);

namespace JayGaha\LumenFormRequest\Tests\Fixtures;

use JayGaha\LumenFormRequest\Requests\FormRequest;
use Illuminate\Container\Container;

class TestFormRequest extends FormRequest
{
    public Container $app;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'The name field is mandatory.',
        ];
    }

    protected function attributes(): array
    {
        return [
            'email' => 'email address',
        ];
    }
}
