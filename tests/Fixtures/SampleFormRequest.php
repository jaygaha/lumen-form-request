<?php

declare(strict_types=1);

namespace JayGaha\LumenFormRequest\Tests\Fixtures;

use JayGaha\LumenFormRequest\Requests\FormRequest;

class SampleFormRequest extends FormRequest
{
    protected function rules(): array
    {
        return ['name' => 'required'];
    }
}
