<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAPI extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards): Response
    {
        try {
            // Attempt to authenticate the request
            $this->authenticate($request, $guards);

            // Add rate limiting here if needed
            // For example: if user has exceeded rate limit, throw exception

            // Add API version check if needed
            $this->checkApiVersion($request);

            return $next($request);
        } catch (AuthenticationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
                'errors' => ['token' => 'Invalid or expired token']
            ], 401);
        }
    }

    /**
     * Handle unauthenticated users
     *
     * @param  Request  $request
     * @param  array  $guards
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function unauthenticated($request, array $guards): void
    {
        throw new AuthenticationException(
            'Unauthenticated.',
            $guards,
            $this->redirectTo($request)
        );
    }

    /**
     * Check API version from request headers
     *
     * @param Request $request
     * @return void
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    protected function checkApiVersion(Request $request): void
    {
        $version = $request->header('Accept-Version');
        $supportedVersions = ['v1']; // Add more versions as needed

        if ($version && !in_array($version, $supportedVersions)) {
            abort(400, 'Unsupported API version. Supported versions: ' . implode(', ', $supportedVersions));
        }
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  Request  $request
     * @return string|null
     */
    protected function redirectTo(Request $request): ?string
    {
        return null; // API should not redirect, only return JSON response
    }
}
