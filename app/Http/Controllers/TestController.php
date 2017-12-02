<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class TestController extends Controller
{
    public function test(Request $request)
    {
      $http = new Client;
      $accessToken = $request->bearerToken();

      if (Redis::exists('repo:oauth:token'.$accessToken))
      {
        Redis::set('repo:debug', 'From Redis');
        return json_encode([
          'user_id' => Redis::hget('repo:oauth:token'.$accessToken, 'user_id')
        ]);
      }
      try {
        $response = $http->get('http://192.168.1.60:81/api/user/token/verify', [
          'headers' => [
              'Authorization' => 'Bearer '. $accessToken,
              'Accept' => 'application/json'
          ],
        ]);
        // return json_encode($request->bearerToken());
        // Cookie::set(''):
        $sessionId = uniqid();
        $responseData = json_decode($response->getBody()->getContents());
        if (!isset($responseData->token)) {
          return json_encode([
            'status' => 'Authentication Failure',
            'result' => $responseData
          ]);
        }
        $token = $responseData->token;
        $user = $responseData->user;
        Redis::hmset(
          'repo:oauth:token'.$accessToken,
          'user_id', $user->user_id,
          'expiry', $token->expires_at
        );
        Redis::expireat('repo:oauth:token'.$accessToken, Carbon::parse($token->expires_at)->timestamp);
        Redis::set('repo:debug', 'From OAuth');
        return json_encode($responseData);
      } catch (ClientException $e) {
        Redis::set('repo:debug', 'OAuth Fail');
        // return json_encode($e->getResponse()->getBody()->getContents());
        abort(401, 'Authentication fail');
      }
    }

    public function testMiddleware(Request $request)
    {
      return json_encode([
        'user_id' => Redis::hget('repo:oauth:token'.$request->bearerToken(), 'user_id'),
        'message' => 'From Middleware'
      ]);
    }
}
