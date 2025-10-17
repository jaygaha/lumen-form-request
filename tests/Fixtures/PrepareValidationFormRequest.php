<?php

declare(strict_types=1);

namespace JayGaha\LumenFormRequest\Tests\Fixtures;

use JayGaha\LumenFormRequest\Requests\FormRequest;
use Illuminate\Container\Container;

class PrepareValidationFormRequest extends FormRequest
{
    public Container $app;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'prepared' => true,
            'name' => strtoupper($this->input('name', '')),
        ]);
    }

    protected function rules(): array
    {
        return ['name' => 'required'];
    }
}
