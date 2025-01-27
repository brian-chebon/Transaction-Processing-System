<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Cache\RateLimiting\Limit;

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
            // Check API version
            $this->checkApiVersion($request);

            // Apply rate limiting
            if (!$this->checkRateLimit($request)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Too many requests',
                    'retry_after' => RateLimiter::availableIn($this->getRateLimitKey($request))
                ], 429);
            }

            // Authenticate request
            $this->authenticate($request, $guards);

            // High-value transaction rate limiting
            if ($this->isHighValueTransaction($request)) {
                if (!$this->checkHighValueRateLimit($request)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'High-value transaction limit exceeded'
                    ], 429);
                }
            }

            // Log the request
            $this->logRequest($request);

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
     * Check API version compatibility.
     *
     * @param Request $request
     * @return bool
     */
    protected function checkApiVersion(Request $request): bool
    {
        $version = $request->header('Accept-Version', 'v1');
        $supportedVersions = ['v1']; // Add more versions as needed

        if (!in_array($version, $supportedVersions)) {
            abort(400, 'Unsupported API version');
        }

        return true;
    }

    /**
     * Apply rate limiting to the request.
     *
     * @param Request $request
     * @return bool
     */
    protected function checkRateLimit(Request $request): bool
    {
        $key = $this->getRateLimitKey($request);

        if (RateLimiter::tooManyAttempts($key, config('sanctum.limiters.api.max_attempts'))) {
            return false;
        }

        RateLimiter::hit($key, config('sanctum.limiters.api.decay_minutes') * 60);
        return true;
    }

    /**
     * Apply special rate limiting for high-value transactions.
     *
     * @param Request $request
     * @return bool
     */
    protected function checkHighValueRateLimit(Request $request): bool
    {
        $key = 'high_value:' . $this->getRateLimitKey($request);

        if (RateLimiter::tooManyAttempts($key, config('sanctum.limiters.high_value.max_attempts'))) {
            return false;
        }

        RateLimiter::hit($key, config('sanctum.limiters.high_value.decay_minutes') * 60);
        return true;
    }

    /**
     * Check if the transaction is high-value.
     *
     * @param Request $request
     * @return bool
     */
    protected function isHighValueTransaction(Request $request): bool
    {
        if ($request->is('api/*/transactions') && $request->isMethod('post')) {
            return $request->input('amount', 0) >= config('app.high_value_threshold', 10000);
        }
        return false;
    }

    /**
     * Get rate limit key for the request.
     *
     * @param Request $request
     * @return string
     */
    protected function getRateLimitKey(Request $request): string
    {
        return sha1($request->user()?->id ?? $request->ip());
    }

    /**
     * Log the API request.
     *
     * @param Request $request
     * @return void
     */
    protected function logRequest(Request $request): void
    {
        Log::info('API Request', [
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent()
        ]);
    }
}
