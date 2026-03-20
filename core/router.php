<?php

/**
 * Router
 * 
 * Simple function-based router with parameter support.
 * 
 * Usage:
 *   route('GET',  '/users/{id}', 'users_show', ['middleware_auth']);
 *   route('POST', '/login',      'auth_login_post', ['middleware_guest']);
 *   dispatch();
 */

$_ROUTES = [];

/**
 * Register a route.
 */
function route(string $method, string $path, string|callable $handler, array $middlewares = []): void
{
    global $_ROUTES;
    $_ROUTES[] = [
        'method'      => strtoupper($method),
        'path'        => $path,
        'handler'     => $handler,
        'middlewares'  => $middlewares,
    ];
}

/**
 * Match the current request against registered routes and dispatch.
 */
function dispatch(): void
{
    global $_ROUTES;

    $method = strtoupper($_SERVER['REQUEST_METHOD']);
    $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri    = rtrim($uri, '/') ?: '/';

    foreach ($_ROUTES as $r) {
        if ($r['method'] !== $method) continue;

        $params = match_route($r['path'], $uri);
        if ($params === false) continue;

        // Run middleware chain
        foreach ($r['middlewares'] as $mw) {
            if (is_callable($mw)) {
                $mw();
            }
        }

        // Call the handler
        $handler = $r['handler'];
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
        } else {
            abort(500, "Handler not callable: {$handler}");
        }
        return;
    }

    // No route matched
    abort(404);
}

/**
 * Match a route pattern against a URI.
 * Returns an array of extracted params on match, or false.
 * 
 * Pattern: /users/{id}/edit  →  URI: /users/42/edit  →  ['42']
 */
function match_route(string $pattern, string $uri): array|false
{
    $pattern = rtrim($pattern, '/') ?: '/';

    // Exact match (no params)
    if ($pattern === $uri) return [];

    // Convert {param} to regex groups
    $regex = preg_replace('#\{(\w+)\}#', '([^/]+)', $pattern);
    $regex = '#^' . $regex . '$#';

    if (preg_match($regex, $uri, $matches)) {
        array_shift($matches); // Remove full match
        return $matches;
    }

    return false;
}
