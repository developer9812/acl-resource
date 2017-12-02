<?php

namespace App\OAuth;

use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;
use Carbon\Carbon;
use GuzzleHttp\Exception\ClientException;

/**
 *
 */
class TokenHandler
{

  private $token;

  public function __construct(string $token)
  {
      $this->token = $token;
  }

  public function verify()
  {
    if (Redis::exists('repo:oauth:token'.$this->token))
    {
      Redis::set('repo:debug', 'From Redis');
      return true;
    }
    else
    {
      return $this->checkOauthServer();
    }
  }

  private function checkOauthServer()
  {
    $http = new Client;
    try {
      $response = $http->get('http://192.168.1.60:81/api/user/token/verify', [
        'headers' => [
            'Authorization' => 'Bearer '. $this->token,
            'Accept' => 'application/json'
        ],
      ]);
      $responseData = json_decode($response->getBody()->getContents());
      if (!isset($responseData->token)) {
        Redis::set('repo:debug:token', 'No Token');
        return false;
      }
      Redis::set('repo:debug:token', $this->token);
      $token = $responseData->token;
      $user = $responseData->user;
      Redis::hmset(
        'repo:oauth:token'.$this->token,
        'user_id', $user->user_id,
        'expiry', $token->expires_at
      );
      Redis::expireat('repo:oauth:token'.$this->token, Carbon::parse($token->expires_at)->timestamp);
      Redis::set('repo:debug', 'From OAuth');
      return true;
    } catch (ClientException $e) {
      Redis::set('repo:debug', 'OAuth Fail');
      return false;
    }
  }

}


 ?>
