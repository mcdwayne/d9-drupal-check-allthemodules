<?php

namespace Drupal\onlinepbx\Controller;

/**
 * @file
 * HttpClient, based on https://github.com/xtratio/onlinepbx-api.
 */
use Drupal\Component\Serialization\Json;

/**
 * HTTP client.
 */
class ApiClient {
  const API_TIMEOUT = 60;
  const API_NO_RESPONSE_TIMEOUT_MS = 350;
  const API_BASE_PROTO = "https";
  const API_BASE = 'api.onlinepbx.ru/';

  protected $baseUrl;
  protected $authKey;
  protected $needNew;
  protected $noResponse;
  protected $secretKey;
  protected $secretKeyId;

  /**
   * Construct.
   */
  public function __construct($domain, $authKey, $needNew = FALSE, $noResponse = FALSE) {
    if ('/' != substr($domain, strlen($domain) - 1, 1)) {
      $domain .= '/';
    }

    $this->baseUrl = self::API_BASE . $domain;
    $this->authKey = $authKey;
    $this->needNew = $needNew;
    $this->noResponse = $noResponse;
  }

  /**
   * Авторизация, сохраняет секретные ключи в полях экземпляра класса.
   */
  private function auth() {
    $data = ['auth_key' => $this->authKey];
    if ($this->needNew) {
      $data['new'] = 'true';
    }

    try {
      $result = $this->sendHttpRequest("auth.json", 'POST', $data, [], FALSE);
      if ($result["status"] != 1) {
        \Drupal::logger('onlinepbx')->error("Api autharization error.");
      }
      $this->secretKey = $result["data"]["key"];
      $this->secretKeyId = $result["data"]["key_id"];
    }
    catch (\Exception $e) {
      \Drupal::logger('onlinepbx')->error("sendHttpRequest Exception");
    }
  }

  /**
   * Send Request.
   */
  public function sendRequest($path, $post) {
    if (!isset($this->secretKey) || !isset($this->secretKeyId) || empty($this->secretKey) || empty($this->secretKeyId)) {
      $this->auth();
    }

    if (is_array($post)) {
      foreach ($post as $key => $val) {
        if (is_string($key) && preg_match('/^@(.+)/', $val, $m)) {
          $post[$key] = ['name' => basename($m[1]), 'data' => base64_encode(file_get_contents($m[1]))];
        }
      }
    }

    $signUrl = $this->baseUrl . $path;
    $date = @date('r');
    $data = http_build_query($post);
    $content_type = 'application/x-www-form-urlencoded';
    $content_md5 = hash('md5', $data);
    $signData = 'POST' . "\n{$content_md5}\n{$content_type}\n{$date}\n{$signUrl}\n";
    $signature = base64_encode(hash_hmac('sha1', $signData, $this->secretKey, FALSE));
    $headers = [
      'Date: ' . $date,
      'Accept: application/json',
      'Content-Type: ' . $content_type,
      'x-pbx-authentication: ' . $this->secretKeyId . ':' . $signature,
      'Content-MD5: ' . $content_md5,
    ];
    return $this->sendHttpRequest($path, 'POST', $data, $headers);
  }

  /**
   * Make HTTP request.
   */
  private function sendHttpRequest($path, $method, $parameters = [], array $headers = []) {
    $allowedMethods = ['GET', 'POST'];
    if (!in_array($method, $allowedMethods)) {
      return FALSE;
    }

    $url = self::API_BASE_PROTO . "://" . $this->baseUrl . $path;
    if ($method === 'GET' && count($parameters)) {
      $url .= '?' . http_build_query($parameters);
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_TIMEOUT, (int) self::API_TIMEOUT);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

    if ($method === 'POST') {

      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
      curl_setopt($ch, CURLOPT_POST, TRUE);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
    }

    if (count($headers)) {
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    if ($path != "auth.json" && $this->noResponse) {
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, FALSE);
      curl_setopt($ch, CURLOPT_TIMEOUT_MS, (int) self::API_NO_RESPONSE_TIMEOUT_MS);
      curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
      curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
      $response = curl_exec($ch);
      curl_close($ch);
      return ['noResponse-flag'];
    }

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $errno = curl_errno($ch);

    curl_close($ch);

    return Json::decode($response);
  }

}
