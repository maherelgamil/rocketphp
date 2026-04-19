<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RenderRocketErrorPages
{
    private const MESSAGES = [
        403 => ['title' => 'Forbidden', 'message' => 'You do not have access to this page.'],
        404 => ['title' => 'Not found', 'message' => 'The page you were looking for does not exist.'],
        419 => ['title' => 'Page expired', 'message' => 'Your session expired. Please refresh and try again.'],
        500 => ['title' => 'Something went wrong', 'message' => 'An unexpected error occurred.'],
    ];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $status = $response->getStatusCode();

        if (! isset(self::MESSAGES[$status])) {
            return $response;
        }

        if (! $request->isMethod('GET')) {
            return $response;
        }

        return Inertia::render('rocket/Error', [
            'status' => $status,
            'title' => self::MESSAGES[$status]['title'],
            'message' => self::MESSAGES[$status]['message'],
        ])->toResponse($request)->setStatusCode($status);
    }
}
