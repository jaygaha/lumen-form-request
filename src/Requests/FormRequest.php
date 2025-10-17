<?php

declare(strict_types=1);

namespace JayGaha\LumenFormRequest\Requests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Validation\Validator;

abstract class FormRequest extends BaseRequest
{
    /**
     * The container instance.
     *
     * @var Container
     */
    protected Container $container;

    /**
     * The validator instance.
     *
     * @var Validator
     */
    protected Validator $validator;

    /**
     * The error message to be used if the validation fails.
     *
     * @var string
     */
    protected string $message = 'The given data was invalid.';

    /**
     * The HTTP status code to be used if the validation fails.
     *
     * @var int
     */
    protected int $statusCode = 422;

    /**
     * Get the error response for the request.
     */
    protected function errorResponse(): ?JsonResponse
    {
        try {
            $this->validator->validate();
        } catch (ValidationException $e) {
            $this->message = $e->getMessage();
            $this->statusCode = $e->status;
        }

        return new JsonResponse([
            'message' => $this->message,
            'errors' => $this->validator->errors()->messages(),
        ], $this->statusCode);
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @throws AuthorizationException
     */
    protected function failedAuthorization(): void
    {
        throw new AuthorizationException();
    }

    /**
     * Handle a failed validation attempt.
     *
     * @throws ValidationException
     */
    protected function validationFailed(): void
    {
        throw new ValidationException($this->validator, $this->errorResponse());
    }

    /**
     * Handle a successful validation attempt.
     */
    protected function validationPassed(): void
    {
        //
    }

    /**
     * Get the validated data from the request.
     */
    public function validated(): array
    {
        return $this->validator->validated();
    }

    /**
     * Validate the request.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function validate(): void
    {
        if (false === $this->authorize()) {
            $this->failedAuthorization();
        }

        $this->prepareForValidation();

        $this->validator = $this->container
                            ->make('validator')
                            ->make($this->all(), $this->rules(), $this->messages(), $this->attributes());

        if ($this->validator->fails()) {
            $this->validationFailed();
        }

        $this->validationPassed();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // no default action
    }

    /**
     * Set the container instance.
     */
    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    protected function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    abstract protected function rules(): array;

    /**
     * Get the error messages for the defined validation rules.
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * Get the custom attribute names for the defined validation rules.
     */
    protected function attributes(): array
    {
        return [];
    }
}
