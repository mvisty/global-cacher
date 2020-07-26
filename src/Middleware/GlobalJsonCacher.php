<?php

namespace Mvisty\GlobalCacher\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class GlobalJsonCacher
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
        $isJson = $request->wantsJson();

        if (!$isJson) {
            return $next($request);
        }

        $requestPath = md5(
            $request->url() .
            $request->getRequestUri().
            implode($request->all())) . "." . $request->format()
        ;
        $globalCacherStorage = Storage::disk('globalCacher');

        if ($globalCacherStorage->exists($requestPath)) {
            // Return cached value
            $lifeFile = time() - $globalCacherStorage->lastModified($requestPath);
            if ($lifeFile > $lifeLimit) {
                $globalCacherStorage->delete($requestPath);
            } else {
                $globalCacherHtml = \GuzzleHttp\json_decode($globalCacherStorage->get($requestPath));
                return Response::json($globalCacherHtml, 200);
            }
        }

        $response = $next($request);

        // Make cached html
        if ($response instanceof JsonResponse) {
            $data = \GuzzleHttp\json_encode($response->getData());
        }
        $globalCacherStorage->put($requestPath, $data);

        return $response;
    }
}
