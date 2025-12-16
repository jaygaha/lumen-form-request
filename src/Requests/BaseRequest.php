<?php

declare(strict_types=1);

namespace JayGaha\LumenFormRequest\Requests;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class BaseRequest extends Request
{
    /**
     * Determine if the route name matches a given pattern.
     *
     * @param  mixed  $patterns
     */
    public function routeIs(...$patterns): bool
    {
        if (! Arr::exists($route = $this->route()[1], 'as')) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $route['as'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the route handling the request.
     *
     * @param  string|null  $param
     * @param  mixed  $default
     */
    public function route($param = null, $default = null): mixed
    {
        $route = ($this->getRouteResolver())();

        if (is_null($route) || is_null($param)) {
            return $route;
        }

        return Arr::get($route[2], $param, $default);
    }

    /**
     * Get a unique fingerprint for the request / route / IP address.
     *
     * @throws RuntimeException
     */
    public function fingerprint(): string
    {
        if (! $this->route()) {
            throw new RuntimeException('Unable to generate fingerprint. Route unavailable.');
        }

        return sha1(implode('|', [
            $this->getMethod(), $this->root(), $this->path(), $this->ip(),
        ]));
    }

    /**
     * Determine if the given offset exists.
     *
     * @param  string  $offset
     */
    public function offsetExists($offset): bool
    {
        return Arr::has(
            $this->all() + $this->route()[2],
            $offset
        );
    }
}
