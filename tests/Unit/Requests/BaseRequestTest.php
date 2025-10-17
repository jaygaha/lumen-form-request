<?php

declare(strict_types=1);

use JayGaha\LumenFormRequest\Requests\BaseRequest;

beforeEach(function () {
    $this->request = new BaseRequest();
});

describe('routeIs', function () {
    it('returns true when route name matches pattern', function () {
        $this->request->setRouteResolver(function () {
            return [
                null,
                ['as' => 'users.index'],
                []
            ];
        });

        expect($this->request->routeIs('users.index'))->toBeTrue();
    });

    it('returns true when route name matches wildcard pattern', function () {
        $this->request->setRouteResolver(function () {
            return [
                null,
                ['as' => 'users.show'],
                []
            ];
        });

        expect($this->request->routeIs('users.*'))->toBeTrue();
    });

    it('returns false when route name does not match pattern', function () {
        $this->request->setRouteResolver(function () {
            return [
                null,
                ['as' => 'posts.index'],
                []
            ];
        });

        expect($this->request->routeIs('users.index'))->toBeFalse();
    });

    it('returns false when route has no name', function () {
        $this->request->setRouteResolver(function () {
            return [
                null,
                [],
                []
            ];
        });

        expect($this->request->routeIs('users.index'))->toBeFalse();
    });

    it('checks multiple patterns', function () {
        $this->request->setRouteResolver(function () {
            return [
                null,
                ['as' => 'posts.create'],
                []
            ];
        });

        expect($this->request->routeIs('users.*', 'posts.*', 'admin.*'))->toBeTrue();
    });
});

describe('route', function () {
    it('returns full route array when no param specified', function () {
        $routeData = [
            null,
            ['as' => 'users.show'],
            ['id' => 123]
        ];

        $this->request->setRouteResolver(function () use ($routeData) {
            return $routeData;
        });

        expect($this->request->route())->toBe($routeData);
    });

    it('returns null when route is not available', function () {
        $this->request->setRouteResolver(function () {
            return null;
        });

        expect($this->request->route())->toBeNull();
    });

    it('returns route parameter value', function () {
        $this->request->setRouteResolver(function () {
            return [
                null,
                ['as' => 'users.show'],
                ['id' => '123', 'slug' => 'test-post']
            ];
        });

        expect($this->request->route('id'))->toBe('123');
        expect($this->request->route('slug'))->toBe('test-post');
    });

    it('returns default value when parameter does not exist', function () {
        $this->request->setRouteResolver(function () {
            return [
                null,
                ['as' => 'users.show'],
                ['id' => '123']
            ];
        });

        expect($this->request->route('missing', 'default'))->toBe('default');
    });

    it('returns null when parameter does not exist and no default', function () {
        $this->request->setRouteResolver(function () {
            return [
                null,
                ['as' => 'users.show'],
                ['id' => '123']
            ];
        });

        expect($this->request->route('missing'))->toBeNull();
    });
});

describe('fingerprint', function () {
    it('generates unique fingerprint for request', function () {
        $request = BaseRequest::create('https://example.com/users/123', 'GET');

        $request->setRouteResolver(function () {
            return [
                null,
                ['as' => 'users.show'],
                ['id' => 123]
            ];
        });

        $fingerprint = $request->fingerprint();

        expect($fingerprint)->toBeString();
        expect(strlen($fingerprint))->toBe(40); // SHA1 hash length
    });

    it('generates same fingerprint for identical requests', function () {
        $request1 = BaseRequest::create('https://example.com/users/123', 'GET');
        $request1->setRouteResolver(function () {
            return [null, ['as' => 'users.show'], ['id' => 123]];
        });

        $request2 = BaseRequest::create('https://example.com/users/123', 'GET');
        $request2->setRouteResolver(function () {
            return [null, ['as' => 'users.show'], ['id' => 123]];
        });

        expect($request1->fingerprint())->toBe($request2->fingerprint());
    });

    it('generates different fingerprints for different methods', function () {
        $getRequest = BaseRequest::create('https://example.com/users/123', 'GET');
        $getRequest->setRouteResolver(function () {
            return [null, ['as' => 'users.show'], ['id' => 123]];
        });

        $postRequest = BaseRequest::create('https://example.com/users/123', 'POST');
        $postRequest->setRouteResolver(function () {
            return [null, ['as' => 'users.store'], []];
        });

        expect($getRequest->fingerprint())->not->toBe($postRequest->fingerprint());
    });

    it('throws exception when route is not available', function () {
        $request = BaseRequest::create('https://example.com/users', 'GET');
        $request->setRouteResolver(function () {
            return null;
        });

        expect(fn() => $request->fingerprint())
            ->toThrow(RuntimeException::class, 'Unable to generate fingerprint. Route unavailable.');
    });
});

describe('offsetExists', function () {
    it('returns true when offset exists in request data', function () {
        $request = BaseRequest::create('https://example.com/users', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $request->setRouteResolver(function () {
            return [null, [], []];
        });

        expect($request->offsetExists('name'))->toBeTrue();
        expect($request->offsetExists('email'))->toBeTrue();
    });

    it('returns true when offset exists in route parameters', function () {
        $request = BaseRequest::create('https://example.com/users/123', 'GET');

        $request->setRouteResolver(function () {
            return [
                null,
                ['as' => 'users.show'],
                ['id' => 123, 'type' => 'admin']
            ];
        });

        expect($request->offsetExists('id'))->toBeTrue();
        expect($request->offsetExists('type'))->toBeTrue();
    });

    it('returns false when offset does not exist', function () {
        $request = BaseRequest::create('https://example.com/users', 'POST', [
            'name' => 'John Doe'
        ]);

        $request->setRouteResolver(function () {
            return [null, [], []];
        });

        expect($request->offsetExists('missing'))->toBeFalse();
    });

    it('prioritizes request data over route parameters', function () {
        $request = BaseRequest::create('https://example.com/users/123', 'POST', [
            'id' => 999
        ]);

        $request->setRouteResolver(function () {
            return [
                null,
                ['as' => 'users.update'],
                ['id' => 123]
            ];
        });

        expect($request->offsetExists('id'))->toBeTrue();
    });
});
