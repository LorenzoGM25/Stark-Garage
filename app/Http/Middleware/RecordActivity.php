<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordActivity
{
    private const METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldRecord($request, $response)) {
            $route = $request->route();
            $method = $request->method();
            $path = '/'.ltrim($request->path(), '/');
            $routeName = $route?->getName();

            $this->audit->log(
                'http.'.strtolower($method),
                null,
                trim(strtoupper($method).' '.$path.($routeName ? " ({$routeName})" : '')),
                [],
                [
                    'method' => $method,
                    'path' => $path,
                    'route' => $routeName,
                    'status' => $response->getStatusCode(),
                ],
            );
        }

        return $response;
    }

    private function shouldRecord(Request $request, Response $response): bool
    {
        if (! $request->user() || ! in_array($request->method(), self::METHODS, true)) {
            return false;
        }

        // Only log state changes that actually went through. Validation and
        // authorization failures are not useful noise for the owner.
        if ($response->getStatusCode() >= 400) {
            return false;
        }

        if ($request->hasSession() && $request->session()->has('errors')) {
            return false;
        }

        // Livewire already posts for every UI interaction; those changes are
        // recorded by the controllers/services that perform them.
        if ($request->is('livewire/*')) {
            return false;
        }

        // Skip when a controller already wrote a more descriptive entry.
        return ! $this->audit->alreadyLogged();
    }
}
