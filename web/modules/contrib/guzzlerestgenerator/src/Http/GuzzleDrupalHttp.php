<?php

namespace Drupal\guzzlerestgenerator\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/** 
 * Get a response code from any URL using Guzzle in Drupal 8!
 * 
 * Usage: 
 * In the head of your document:
 * 
 * use Drupal\guzzle_rest\Http\GuzzleDrupalHttp;
 * 
 * In the area you want to return the result, using any URL for $url:
 *
 * $check = new GuzzleDrupalHttp();
 * $response = $check->performRequest($requestUrl, $requestMethod, $requestHeaders, $requestPayloadData);
 *  
 **/

class GuzzleDrupalHttp {
  use StringTranslationTrait;
  
  public function performRequest($requestUrl, $requestMethod = 'GET', $requestHeaders = '', $requestPayloadData = '') {
    $client = new \GuzzleHttp\Client();
    try {
      
      // Massage $requestHeaders to generate $headers array, ready for REST call
      foreach(explode("\r\n", $requestHeaders) as $row) {
          if(preg_match('/(.*?): (.*)/', $row, $matches)) {
              $headers[$matches[1]] = $matches[2];
          }
      }
      
      if($requestPayloadData != ''){
        // Massage $requestPayloadData to generate $body array, ready for REST call
        foreach(explode("\r\n", $requestPayloadData) as $row) {
            if(preg_match('/(.*?): (.*)/', $row, $matches)) {
                $body[$matches[1]] = $matches[2];
            }
        }
      }else{
        $body = '';
      }
      
      switch($requestMethod){
        case 'GET':
          $res = $client->get($requestUrl, [
            'http_errors' => false,
            'headers' => $headers
          ]);
          break;
        case 'POST':
          $res = $client->post($requestUrl, [
            'http_errors' => false,
            'headers' => $headers,
            'body' => $body
          ]);
          break;
        case 'PUT':
          $res = $client->put($requestUrl, [
              'http_errors' => false,
              'headers' => $headers,
              'body' => $body
          ]);
          break;
        default:
          throw new Exception('Invalid Request Method');
      }
      return($res);
    } catch (RequestException $e) {
      return($this->t('Error'));
    }
  }
}