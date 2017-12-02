<?php

namespace App\Http\Middleware;

use Closure;
use App\OAuth\TokenHandler;
use GuzzleHttp\Client;

class OAuthResourceMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
      $http = new Client;
      if ($request->hasHeader('Authorization')) {
        $accessToken = $request->bearerToken();
        $tokenHandler = new TokenHandler($accessToken);
        if ($tokenHandler->verify())
        {
          return $next($request);
        }
        else
        {
          abort(401, 'Unauthenticated.');
        }
      } else {
        abort(401, 'Unauthenticated.');
      }
    }
}
