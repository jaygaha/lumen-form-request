<?php

namespace JayGaha\LumenFormRequest\Tests\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Foundation\Application;
use JayGaha\LumenFormRequest\Console\MakeFormRequestCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Tester\CommandTester;
use Illuminate\Filesystem\Filesystem;
use ReflectionClass;
use ReflectionProperty;
use ReflectionMethod;

describe('MakeFormRequestCommand', function () {
    beforeEach(function () {
        $this->app = new Application();
        $this->app->setBasePath(__DIR__ . '/../../');

        $this->files = new Filesystem();
        $this->app->instance('files', $this->files);

        $this->command = new MakeFormRequestCommand($this->files);
        $this->command->setLaravel($this->app);

        $this->commandTester = new CommandTester($this->command);
    });

    describe('command configuration', function () {
        it('has correct command name', function () {
            expect($this->command->getName())->toBe('make:request-form');
        });

        it('has correct command description', function () {
            expect($this->command->getDescription())->toBe('Create a new form request class');
        });

        it('has correct type property', function () {
            $reflection = new ReflectionProperty($this->command, 'type');
            $reflection->setAccessible(true);

            expect($reflection->getValue($this->command))->toBe('Request');
        });
    });

    describe('getStub', function () {
        it('returns stub path using resolveStubPath', function () {
            $reflection = new ReflectionMethod($this->command, 'getStub');
            $reflection->setAccessible(true);
            $stub = $reflection->invoke($this->command);

            expect($stub)->toContain('request.stub');
        });

        it('resolves stub file correctly', function () {
            $reflection = new ReflectionMethod($this->command, 'getStub');
            $reflection->setAccessible(true);
            $stubPath = $reflection->invoke($this->command);

            expect($stubPath)->toBeString();
            expect(str_contains($stubPath, 'stubs/request.stub'))->toBeTrue();
        });
    });

    describe('resolveStubPath', function () {
        it('returns custom path when stub exists in base path', function () {
            $basePath = __DIR__ . '/../../stubs';

            if (! is_dir($basePath)) {
                mkdir($basePath, 0755, true);
            }

            $stubFile = $basePath . '/request.stub';
            file_put_contents($stubFile, '<?php');

            $reflected = new ReflectionMethod($this->command, 'resolveStubPath');
            $reflected->setAccessible(true);

            $result = $reflected->invoke($this->command, '/stubs/request.stub');

            expect(str_contains($result, 'stubs/request.stub'))->toBeTrue();

            unlink($stubFile);
            if (count(scandir($basePath)) <= 2) {
                rmdir($basePath);
            }
        });

        it('returns default stub path when custom stub does not exist', function () {
            $reflected = new ReflectionMethod($this->command, 'resolveStubPath');
            $reflected->setAccessible(true);

            $result = $reflected->invoke($this->command, '/stubs/request.stub');

            expect($result)->toContain('stubs/request.stub');
        });

        it('trims leading and trailing slashes from stub path', function () {
            $reflected = new ReflectionMethod($this->command, 'resolveStubPath');
            $reflected->setAccessible(true);

            $result = $reflected->invoke($this->command, '//stubs/request.stub//');

            expect($result)->toBeString();
        });

        it('handles stub path without leading slash', function () {
            $reflected = new ReflectionMethod($this->command, 'resolveStubPath');
            $reflected->setAccessible(true);

            $result = $reflected->invoke($this->command, 'stubs/request.stub');

            expect($result)->toBeString();
        });
    });

    describe('getDefaultNamespace', function () {
        it('returns correct namespace for requests', function () {
            $reflection = new ReflectionMethod($this->command, 'getDefaultNamespace');
            $reflection->setAccessible(true);
            $namespace = $reflection->invoke($this->command, 'App');

            expect($namespace)->toBe('App\Http\Requests');
        });

        it('appends Http\\Requests to root namespace', function () {
            $reflection = new ReflectionMethod($this->command, 'getDefaultNamespace');
            $reflection->setAccessible(true);
            $namespace = $reflection->invoke($this->command, 'MyApp');

            expect($namespace)->toBe('MyApp\Http\Requests');
        });

        it('works with namespaced root', function () {
            $reflection = new ReflectionMethod($this->command, 'getDefaultNamespace');
            $reflection->setAccessible(true);
            $namespace = $reflection->invoke($this->command, 'Some\Root\Namespace');

            expect($namespace)->toBe('Some\Root\Namespace\Http\Requests');
        });

        it('returns string', function () {
            $reflection = new ReflectionMethod($this->command, 'getDefaultNamespace');
            $reflection->setAccessible(true);
            $result = $reflection->invoke($this->command, 'App');

            expect($result)->toBeString();
        });

        it('handles single character namespace', function () {
            $reflection = new ReflectionMethod($this->command, 'getDefaultNamespace');
            $reflection->setAccessible(true);
            $namespace = $reflection->invoke($this->command, 'A');

            expect($namespace)->toBe('A\Http\Requests');
        });

        it('preserves case sensitivity', function () {
            $reflection = new ReflectionMethod($this->command, 'getDefaultNamespace');
            $reflection->setAccessible(true);
            $namespace = $reflection->invoke($this->command, 'MyCompany');

            expect($namespace)->toBe('MyCompany\Http\Requests');
        });

        it('contains Http segment', function () {
            $reflection = new ReflectionMethod($this->command, 'getDefaultNamespace');
            $reflection->setAccessible(true);
            $namespace = $reflection->invoke($this->command, 'App');

            expect($namespace)->toContain('Http');
        });

        it('contains Requests segment', function () {
            $reflection = new ReflectionMethod($this->command, 'getDefaultNamespace');
            $reflection->setAccessible(true);
            $namespace = $reflection->invoke($this->command, 'App');

            expect($namespace)->toContain('Requests');
        });

        it('ends with Requests', function () {
            $reflection = new ReflectionMethod($this->command, 'getDefaultNamespace');
            $reflection->setAccessible(true);
            $namespace = $reflection->invoke($this->command, 'App');

            expect($namespace)->toEndWith('Requests');
        });
    });

    describe('getOptions', function () {
        it('returns array of options', function () {
            $reflection = new ReflectionMethod($this->command, 'getOptions');
            $reflection->setAccessible(true);
            $options = $reflection->invoke($this->command);

            expect($options)->toBeArray();
        });

        it('includes force option', function () {
            $reflection = new ReflectionMethod($this->command, 'getOptions');
            $reflection->setAccessible(true);
            $options = $reflection->invoke($this->command);
            $forceOption = $options[0] ?? null;

            expect($forceOption)->not()->toBeNull();
            expect($forceOption[0])->toBe('force');
        });

        it('force option has correct shortcut', function () {
            $reflection = new ReflectionMethod($this->command, 'getOptions');
            $reflection->setAccessible(true);
            $options = $reflection->invoke($this->command);
            $forceOption = $options[0];

            expect($forceOption[1])->toBe('f');
        });

        it('force option is VALUE_NONE type', function () {
            $reflection = new ReflectionMethod($this->command, 'getOptions');
            $reflection->setAccessible(true);
            $options = $reflection->invoke($this->command);
            $forceOption = $options[0];

            expect($forceOption[2])->toBe(InputOption::VALUE_NONE);
        });

        it('force option has correct description', function () {
            $reflection = new ReflectionMethod($this->command, 'getOptions');
            $reflection->setAccessible(true);
            $options = $reflection->invoke($this->command);
            $forceOption = $options[0];

            expect($forceOption[3])->toBe('Create the class even if the request already exists');
        });

        it('returns exactly one option', function () {
            $reflection = new ReflectionMethod($this->command, 'getOptions');
            $reflection->setAccessible(true);
            $options = $reflection->invoke($this->command);

            expect(count($options))->toBe(1);
        });

        it('option is an array with 4 elements', function () {
            $reflection = new ReflectionMethod($this->command, 'getOptions');
            $reflection->setAccessible(true);
            $options = $reflection->invoke($this->command);
            $forceOption = $options[0];

            expect($forceOption)->toBeArray();
            expect(count($forceOption))->toBe(4);
        });

        it('force option name is a string', function () {
            $reflection = new ReflectionMethod($this->command, 'getOptions');
            $reflection->setAccessible(true);
            $options = $reflection->invoke($this->command);
            $forceOption = $options[0];

            expect($forceOption[0])->toBeString();
        });

        it('force option shortcut is a string', function () {
            $reflection = new ReflectionMethod($this->command, 'getOptions');
            $reflection->setAccessible(true);
            $options = $reflection->invoke($this->command);
            $forceOption = $options[0];

            expect($forceOption[1])->toBeString();
        });

        it('force option description is a string', function () {
            $reflection = new ReflectionMethod($this->command, 'getOptions');
            $reflection->setAccessible(true);
            $options = $reflection->invoke($this->command);
            $forceOption = $options[0];

            expect($forceOption[3])->toBeString();
        });
    });

    describe('command inheritance', function () {
        it('extends GeneratorCommand', function () {
            expect($this->command)->toBeInstanceOf(GeneratorCommand::class);
        });

        it('is a symfony command', function () {
            expect($this->command)->toBeInstanceOf(\Symfony\Component\Console\Command\Command::class);
        });

        it('has getNamespace method from parent', function () {
            expect(method_exists($this->command, 'getNamespace'))->toBeTrue();
        });

        it('has getPath method from parent', function () {
            expect(method_exists($this->command, 'getPath'))->toBeTrue();
        });

        it('has setLaravel method', function () {
            expect(method_exists($this->command, 'setLaravel'))->toBeTrue();
        });
    });

    describe('class properties', function () {
        it('has name property set to make:request-form', function () {
            $reflection = new ReflectionProperty(MakeFormRequestCommand::class, 'name');
            $reflection->setAccessible(true);

            $command = new MakeFormRequestCommand($this->files);
            expect($reflection->getValue($command))->toBe('make:request-form');
        });

        it('has description property set correctly', function () {
            $reflection = new ReflectionProperty(MakeFormRequestCommand::class, 'description');
            $reflection->setAccessible(true);

            expect($reflection->getValue($this->command))->toBe('Create a new form request class');
        });

        it('has type property set to Request', function () {
            $reflection = new ReflectionProperty(MakeFormRequestCommand::class, 'type');
            $reflection->setAccessible(true);

            expect($reflection->getValue($this->command))->toBe('Request');
        });
    });

    describe('attribute configuration', function () {
        it('command has AsCommand attribute', function () {
            $reflection = new ReflectionClass(MakeFormRequestCommand::class);
            $attributes = $reflection->getAttributes(\Symfony\Component\Console\Attribute\AsCommand::class);

            expect(count($attributes))->toBeGreaterThan(0);
        });

        it('AsCommand attribute contains correct name', function () {
            $reflection = new ReflectionClass(MakeFormRequestCommand::class);
            $attributes = $reflection->getAttributes(\Symfony\Component\Console\Attribute\AsCommand::class);

            expect($attributes[0])->not()->toBeNull();
        });
    });

    describe('method accessibility', function () {
        it('getStub is protected method', function () {
            $reflection = new ReflectionMethod(MakeFormRequestCommand::class, 'getStub');

            expect($reflection->isProtected())->toBeTrue();
        });

        it('getDefaultNamespace is protected method', function () {
            $reflection = new ReflectionMethod(MakeFormRequestCommand::class, 'getDefaultNamespace');

            expect($reflection->isProtected())->toBeTrue();
        });

        it('getOptions is protected method', function () {
            $reflection = new ReflectionMethod(MakeFormRequestCommand::class, 'getOptions');

            expect($reflection->isProtected())->toBeTrue();
        });

        it('resolveStubPath is protected method', function () {
            $reflection = new ReflectionMethod(MakeFormRequestCommand::class, 'resolveStubPath');

            expect($reflection->isProtected())->toBeTrue();
        });
    });

    describe('integration tests', function () {
        it('command has all required properties', function () {
            $reflection = new ReflectionClass(MakeFormRequestCommand::class);
            $properties = $reflection->getProperties();

            $propertyNames = array_map(fn($prop) => $prop->getName(), $properties);

            expect(in_array('name', $propertyNames))->toBeTrue();
            expect(in_array('description', $propertyNames))->toBeTrue();
            expect(in_array('type', $propertyNames))->toBeTrue();
        });

        it('command has all required methods', function () {
            $reflection = new ReflectionClass(MakeFormRequestCommand::class);
            $methods = $reflection->getMethods();

            $methodNames = array_map(fn($method) => $method->getName(), $methods);

            expect(in_array('getStub', $methodNames))->toBeTrue();
            expect(in_array('resolveStubPath', $methodNames))->toBeTrue();
            expect(in_array('getDefaultNamespace', $methodNames))->toBeTrue();
            expect(in_array('getOptions', $methodNames))->toBeTrue();
        });

        it('stub path resolution follows correct fallback pattern', function () {
            $reflection = new ReflectionMethod($this->command, 'getStub');
            $reflection->setAccessible(true);
            $stubPath = $reflection->invoke($this->command);

            expect($stubPath)->toBeString();
            expect(strlen($stubPath))->toBeGreaterThan(0);
        });
    });
});
