<?php

namespace Drupal\snipcart;

use function GuzzleHttp\json_decode;
use GuzzleHttp\RequestOptions;

/**
 * Snipcart request validation service implementation.
 */
class SnipcartRequestValidationService implements SnipcartRequestValidationServiceInterface {


  /**
   * {@inheritdoc}
   */
  public function validateRequest($request) {

    $snipcartAPIKey = \Drupal::config('snipcart.settings')->get('api_key');

    $snipcartRequestToken = $request->headers->get('X-Snipcart-RequestToken');

    $client = \Drupal::httpClient();
    $response = $client->request('GET', 'https://app.snipcart.com/api/requestvalidation/' . $snipcartRequestToken, [
      RequestOptions::AUTH => [$snipcartAPIKey, '', 'basic'],
      RequestOptions::HEADERS => ['Accept' => 'application/json'],
      RequestOptions::HTTP_ERRORS => false,
    ]);

    if($response->getStatusCode() === 200){
      try{
        $body = $response->getBody();
        $body = json_decode($body);
        return $body->token === $snipcartRequestToken;
      }catch(\Exception $e){

      }
    }

    return FALSE;


  }
}
