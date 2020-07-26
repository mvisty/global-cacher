<?php

namespace Mvisty\GlobalCacher\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class GlobalHtmlCacher
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $lifeLimit = 200)
    {
        $requestPath = md5($request->url() . $request->getRequestUri()) . "." . $request->format();
        $globalCacherStorage = Storage::disk('globalCacher');

        if ($globalCacherStorage->exists($requestPath)) {
            // Return cached value
            $lifeFile = time() - $globalCacherStorage->lastModified($requestPath);
            if ($lifeFile > $lifeLimit) {
                $globalCacherStorage->delete($requestPath);
            } else {
                $globalCacherHtml = $globalCacherStorage->get($requestPath);
                return Response::make($globalCacherHtml, 200);
            }
        }

        $response = $next($request);

        // Make cached html

        $data = $response->original->render();

        $globalCacherStorage->put($requestPath, $data);

        return $response;
    }
}
