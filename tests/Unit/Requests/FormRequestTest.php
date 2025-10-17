<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Illuminate\Container\Container;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\MessageBag;
use JayGaha\LumenFormRequest\Requests\FormRequest;
use JayGaha\LumenFormRequest\Tests\Fixtures\TestFormRequest;
use JayGaha\LumenFormRequest\Tests\Fixtures\AuthorizedFormRequest;
use JayGaha\LumenFormRequest\Tests\Fixtures\PrepareValidationFormRequest;

beforeEach(function () {
    $this->container = Mockery::mock(Container::class);
    $this->validator = Mockery::mock(Validator::class);
});

afterEach(function () {
    Mockery::close();
});

describe('validate method', function () {
    it('passes validation with valid data', function () {
        $request = TestFormRequest::create('/test', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $container = Mockery::mock(Container::class);
        $validator = Mockery::mock(Validator::class);

        $container->shouldReceive('make')
            ->with('validator')
            ->once()
            ->andReturnSelf();

        $container->shouldReceive('make')
            ->with(
                Mockery::type('array'),
                Mockery::type('array'),
                Mockery::type('array'),
                Mockery::type('array')
            )
            ->once()
            ->andReturn($validator);

        $validator->shouldReceive('fails')->once()->andReturn(false);

        $request->app = $container;
        $request->setContainer($container);

        expect(fn() => $request->validate())->not->toThrow(ValidationException::class);
    });

    it('throws authorization exception when not authorized', function () {
        $request = AuthorizedFormRequest::create('/test', 'POST', [
            'name' => 'John Doe',
        ]);

        $container = Mockery::mock(Container::class);
        $request->app = $container;
        $request->setContainer($container);

        expect(fn() => $request->validate())
            ->toThrow(AuthorizationException::class);
    });

    it('throws validation exception with invalid data', function () {
        $request = TestFormRequest::create('/test', 'POST', [
            'name' => '',
            'email' => 'invalid-email',
        ]);

        $container = Mockery::mock(Container::class);
        $validator = Mockery::mock(Validator::class);
        $messageBag = Mockery::mock(MessageBag::class);
        $translator = Mockery::mock(\Illuminate\Contracts\Translation\Translator::class);

        $container->shouldReceive('make')
            ->with('validator')
            ->once()
            ->andReturnSelf();

        $container->shouldReceive('make')
            ->once()
            ->andReturn($validator);

        // Setup all mocks BEFORE creating ValidationException
        // These need to handle multiple calls because ValidationException constructor is called multiple times
        $validator->shouldReceive('fails')->once()->andReturn(true);
        $validator->shouldReceive('errors')->andReturn($messageBag);
        $validator->shouldReceive('getMessageBag')->andReturn($messageBag);
        $validator->shouldReceive('getTranslator')->andReturn($translator);
        $validator->shouldReceive('failed')->andReturn([]);

        $messageBag->shouldReceive('messages')->andReturn([
            'name' => ['The name field is mandatory.'],
            'email' => ['The email must be a valid email address.'],
        ]);
        $messageBag->shouldReceive('all')->andReturn([
            'The name field is mandatory.',
            'The email must be a valid email address.',
        ]);

        $translator->shouldReceive('get')
            ->with('The given data was invalid.')
            ->andReturn('The given data was invalid.');

        $translator->shouldReceive('choice')
            ->andReturn('(and 1 more error)');

        // Now create the exception - it will use the mocked methods above
        $exception = new ValidationException($validator);

        // Mock validate to throw the exception we just created
        $validator->shouldReceive('validate')->andThrow($exception);

        $request->app = $container;
        $request->setContainer($container);

        expect(fn() => $request->validate())
            ->toThrow(ValidationException::class);
    });

    it('calls prepareForValidation before validation', function () {
        $request = PrepareValidationFormRequest::create('/test', 'POST', [
            'name' => 'john',
        ]);

        $container = Mockery::mock(Container::class);
        $validator = Mockery::mock(Validator::class);

        $container->shouldReceive('make')
            ->with('validator')
            ->once()
            ->andReturnSelf();

        $container->shouldReceive('make')
            ->once()
            ->andReturn($validator);

        $validator->shouldReceive('fails')->once()->andReturn(false);

        $request->app = $container;
        $request->setContainer($container);
        $request->validate();

        expect($request->input('prepared'))->toBeTrue();
        expect($request->input('name'))->toBe('JOHN');
    });
});

describe('validated method', function () {
    it('returns validated data', function () {
        $request = new TestFormRequest();
        $validator = Mockery::mock(Validator::class);

        $validatedData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $validator->shouldReceive('validated')
            ->once()
            ->andReturn($validatedData);

        // Use reflection to set protected validator property
        $reflection = new ReflectionClass($request);
        $property = $reflection->getProperty('validator');
        $property->setAccessible(true);
        $property->setValue($request, $validator);

        expect($request->validated())->toBe($validatedData);
    });
});

describe('errorResponse method', function () {
    it('returns json response with validation errors', function () {
        $request = new TestFormRequest();
        $validator = Mockery::mock(Validator::class);
        $messageBag = Mockery::mock(MessageBag::class);
        $translator = Mockery::mock(\Illuminate\Contracts\Translation\Translator::class);

        $errors = [
            'name' => ['The name field is required.'],
            'email' => ['The email must be a valid email address.'],
        ];

        // Setup mocks for ValidationException construction
        $validator->shouldReceive('errors')
            ->andReturn($messageBag);
        $validator->shouldReceive('getMessageBag')
            ->andReturn($messageBag);
        $validator->shouldReceive('getTranslator')
            ->andReturn($translator);

        $messageBag->shouldReceive('all')
            ->andReturn(['The name field is required.']);
        $messageBag->shouldReceive('messages')
            ->andReturn($errors);

        $translator->shouldReceive('get')
            ->with('The given data was invalid.')
            ->andReturn('The given data was invalid.');

        // Create exception with mocked validator
        $exception = new ValidationException($validator);

        $validator->shouldReceive('validate')
            ->once()
            ->andThrow($exception);

        // Use reflection to set protected validator property
        $reflection = new ReflectionClass($request);
        $property = $reflection->getProperty('validator');
        $property->setAccessible(true);
        $property->setValue($request, $validator);

        // Use reflection to call protected method
        $method = $reflection->getMethod('errorResponse');
        $method->setAccessible(true);
        $response = $method->invoke($request);

        expect($response)->toBeInstanceOf(JsonResponse::class);
        expect($response->getStatusCode())->toBe(422);

        $data = $response->getData(true);
        expect($data)->toHaveKey('message');
        expect($data)->toHaveKey('errors');
        expect($data['errors'])->toBe($errors);
    });

    it('uses custom status code from validation exception', function () {
        $request = new TestFormRequest();
        $validator = Mockery::mock(Validator::class);
        $messageBag = Mockery::mock(MessageBag::class);
        $translator = Mockery::mock(\Illuminate\Contracts\Translation\Translator::class);

        // Setup mocks for ValidationException construction
        $validator->shouldReceive('errors')
            ->andReturn($messageBag);
        $validator->shouldReceive('getMessageBag')
            ->andReturn($messageBag);
        $validator->shouldReceive('getTranslator')
            ->andReturn($translator);

        $messageBag->shouldReceive('all')
            ->andReturn([]);
        $messageBag->shouldReceive('messages')
            ->andReturn([]);

        $translator->shouldReceive('get')
            ->with('The given data was invalid.')
            ->andReturn('The given data was invalid.');

        // Create exception with custom status
        $exception = new ValidationException($validator);
        $exception->status = 400;

        $validator->shouldReceive('validate')
            ->once()
            ->andThrow($exception);

        // Use reflection
        $reflection = new ReflectionClass($request);
        $property = $reflection->getProperty('validator');
        $property->setAccessible(true);
        $property->setValue($request, $validator);

        $method = $reflection->getMethod('errorResponse');
        $method->setAccessible(true);
        $response = $method->invoke($request);

        expect($response->getStatusCode())->toBe(400);
    });
});

describe('authorization', function () {
    it('authorize returns true by default', function () {
        $request = new TestFormRequest();

        $reflection = new ReflectionClass($request);
        $method = $reflection->getMethod('authorize');
        $method->setAccessible(true);

        expect($method->invoke($request))->toBeTrue();
    });

    it('can override authorize to return false', function () {
        $request = new AuthorizedFormRequest();

        $reflection = new ReflectionClass($request);
        $method = $reflection->getMethod('authorize');
        $method->setAccessible(true);

        expect($method->invoke($request))->toBeFalse();
    });
});

describe('container', function () {
    it('sets container instance', function () {
        $request = new TestFormRequest();
        $container = Mockery::mock(Container::class);

        $request->setContainer($container);

        $reflection = new ReflectionClass($request);
        $property = $reflection->getProperty('container');
        $property->setAccessible(true);

        expect($property->getValue($request))->toBe($container);
    });
});

describe('rules, messages, and attributes', function () {
    it('returns defined rules', function () {
        $request = new TestFormRequest();

        $reflection = new ReflectionClass($request);
        $method = $reflection->getMethod('rules');
        $method->setAccessible(true);

        $rules = $method->invoke($request);

        expect($rules)->toBeArray();
        expect($rules)->toHaveKey('name');
        expect($rules)->toHaveKey('email');
    });

    it('returns custom messages', function () {
        $request = new TestFormRequest();

        $reflection = new ReflectionClass($request);
        $method = $reflection->getMethod('messages');
        $method->setAccessible(true);

        $messages = $method->invoke($request);

        expect($messages)->toBeArray();
        expect($messages)->toHaveKey('name.required');
    });

    it('returns custom attributes', function () {
        $request = new TestFormRequest();

        $reflection = new ReflectionClass($request);
        $method = $reflection->getMethod('attributes');
        $method->setAccessible(true);

        $attributes = $method->invoke($request);

        expect($attributes)->toBeArray();
        expect($attributes)->toHaveKey('email');
    });

    it('returns empty array for messages by default', function () {
        $request = new class extends FormRequest {
            protected function rules(): array
            {
                return [];
            }
        };

        $reflection = new ReflectionClass($request);
        $method = $reflection->getMethod('messages');
        $method->setAccessible(true);

        expect($method->invoke($request))->toBe([]);
    });

    it('returns empty array for attributes by default', function () {
        $request = new class extends FormRequest {
            protected function rules(): array
            {
                return [];
            }
        };

        $reflection = new ReflectionClass($request);
        $method = $reflection->getMethod('attributes');
        $method->setAccessible(true);

        expect($method->invoke($request))->toBe([]);
    });
});
