<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Illuminate\Http\Request;
use JayGaha\LumenFormRequest\Requests\FormRequest;
use JayGaha\LumenFormRequest\Providers\FormRequestServiceProvider;
use JayGaha\LumenFormRequest\Tests\Fixtures\SampleFormRequest;

beforeEach(function () {
    $this->app = Mockery::mock(Container::class);
    $this->provider = new FormRequestServiceProvider($this->app);
});

afterEach(function () {
    Mockery::close();
});

describe('boot method', function () {
    it('registers resolving callback for FormRequest', function () {
        $resolvingCallback = null;
        $afterResolvingCallback = null;

        $this->app->shouldReceive('resolving')
            ->once()
            ->with(FormRequest::class, Mockery::type('Closure'))
            ->andReturnUsing(function ($abstract, $callback) use (&$resolvingCallback) {
                $resolvingCallback = $callback;
            });

        $this->app->shouldReceive('afterResolving')
            ->once()
            ->with(FormRequest::class, Mockery::type('Closure'))
            ->andReturnUsing(function ($abstract, $callback) use (&$afterResolvingCallback) {
                $afterResolvingCallback = $callback;
            });

        $this->provider->boot();

        expect($resolvingCallback)->toBeCallable();
        expect($afterResolvingCallback)->toBeCallable();
    });

    it('creates FormRequest from app request during resolution', function () {
        $resolvingCallback = null;

        $this->app->shouldReceive('resolving')
            ->once()
            ->with(FormRequest::class, Mockery::type('Closure'))
            ->andReturnUsing(function ($abstract, $callback) use (&$resolvingCallback) {
                $resolvingCallback = $callback;
            });

        $this->app->shouldReceive('afterResolving')
            ->once()
            ->with(FormRequest::class, Mockery::any());

        $this->provider->boot();

        // Create a test scenario
        $baseRequest = Request::create('/test', 'POST', ['name' => 'John']);
        $formRequest = new SampleFormRequest();

        $this->app->shouldReceive('offsetGet')
            ->with('request')
            ->once()
            ->andReturn($baseRequest);

        // Execute the resolving callback
        $resolvingCallback($formRequest, $this->app);

        expect($formRequest->all())->toHaveKey('name');
        expect($formRequest->input('name'))->toBe('John');
    });

    it('sets container on FormRequest during resolution', function () {
        $resolvingCallback = null;

        $this->app->shouldReceive('resolving')
            ->once()
            ->with(FormRequest::class, Mockery::type('Closure'))
            ->andReturnUsing(function ($abstract, $callback) use (&$resolvingCallback) {
                $resolvingCallback = $callback;
            });

        $this->app->shouldReceive('afterResolving')
            ->once()
            ->with(FormRequest::class, Mockery::any());

        $this->provider->boot();

        $baseRequest = Request::create('/test', 'POST');
        $formRequest = new SampleFormRequest();

        $this->app->shouldReceive('offsetGet')
            ->with('request')
            ->once()
            ->andReturn($baseRequest);

        $resolvingCallback($formRequest, $this->app);

        // Use reflection to check if container is set
        $reflection = new ReflectionClass($formRequest);
        $property = $reflection->getProperty('container');
        $property->setAccessible(true);

        expect($property->getValue($formRequest))->toBe($this->app);
    });

    it('validates FormRequest after resolution', function () {
        $afterResolvingCallback = null;

        $this->app->shouldReceive('resolving')
            ->once()
            ->with(FormRequest::class, Mockery::any());

        $this->app->shouldReceive('afterResolving')
            ->once()
            ->with(FormRequest::class, Mockery::type('Closure'))
            ->andReturnUsing(function ($abstract, $callback) use (&$afterResolvingCallback) {
                $afterResolvingCallback = $callback;
            });

        $this->provider->boot();

        $formRequest = Mockery::mock(SampleFormRequest::class)->makePartial();
        $formRequest->shouldReceive('validate')
            ->once()
            ->andReturn();

        $afterResolvingCallback($formRequest);

        // If we get here without exceptions, the test passes
        expect(true)->toBeTrue();
    });
});

describe('register method', function () {
    it('does not throw exception', function () {
        expect(fn() => $this->provider->register())->not->toThrow(Exception::class);
    });

    it('completes without errors', function () {
        $this->provider->register();
        expect(true)->toBeTrue();
    });
});

describe('integration', function () {
    it('full lifecycle processes FormRequest correctly', function () {
        $resolvingCallback = null;
        $afterResolvingCallback = null;

        // Setup callbacks
        $this->app->shouldReceive('resolving')
            ->once()
            ->with(FormRequest::class, Mockery::type('Closure'))
            ->andReturnUsing(function ($abstract, $callback) use (&$resolvingCallback) {
                $resolvingCallback = $callback;
            });

        $this->app->shouldReceive('afterResolving')
            ->once()
            ->with(FormRequest::class, Mockery::type('Closure'))
            ->andReturnUsing(function ($abstract, $callback) use (&$afterResolvingCallback) {
                $afterResolvingCallback = $callback;
            });

        // Boot the provider
        $this->provider->boot();

        // Create request with valid data
        $baseRequest = Request::create('/test', 'POST', ['name' => 'John Doe']);
        $formRequest = Mockery::mock(SampleFormRequest::class)->makePartial();

        $this->app->shouldReceive('offsetGet')
            ->with('request')
            ->once()
            ->andReturn($baseRequest);

        // Mock validation
        $formRequest->shouldReceive('validate')
            ->once()
            ->andReturn();

        // Execute lifecycle
        $resolvingCallback($formRequest, $this->app);
        $afterResolvingCallback($formRequest);

        expect(true)->toBeTrue();
    });
});

describe('service provider properties', function () {
    it('has correct app instance', function () {
        expect($this->provider)->toHaveProperty('app');
    });

    it('is instance of ServiceProvider', function () {
        expect($this->provider)->toBeInstanceOf(\Illuminate\Support\ServiceProvider::class);
    });
});
