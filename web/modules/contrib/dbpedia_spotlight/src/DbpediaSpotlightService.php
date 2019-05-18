<?php

namespace Drupal\dbpedia_spotlight;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client;

/**
 * Class DbpediaSpotlightService.
 */

class DbpediaSpotlightService {

  protected $http_client;
  protected $json;


  public function __construct(Client $http_client, Json $json) {
    $this->http_client = $http_client;
    $this->json = $json;
  }

  function dbpedia_spotlight_service($text) {

    if (!empty($text)) {

      $dbpedia_endpoint = 'http://model.dbpedia-spotlight.org/en/annotate';
      $dbpedia_uri = "http://dbpedia.org/resource/";

      $parameters = array(
        'text' => $text,
        'confidence' => 0.5,
        'support' => 0,
        'spotter' => 'Default',
        'disambiguator' => 'Default',
        'policy' => 'whitelist',
        'types' => '',
        'sparql' => '',
        );

       try {
          $response = $this->http_client->request('get', $dbpedia_endpoint,
            array('headers' => array('Accept' => 'application/json'), 'query' =>  $parameters)
          );
          $result = (string) $response->getBody();
          if (empty($result)) {
            return FALSE;
          }
        }
        catch (RequestException $e) {
          return FALSE;
        }

      if (isset($result)) {
        $data = $this->json->decode($result);
        if (isset($data['Resources']) || !empty(isset($data['Resources']))) {
          $Resources = $data['Resources'];
          $terms = array();
          foreach ($Resources as $Resource) {
            $parts = explode('/', $Resource['@URI']);
            $names[] = $parts[count($parts) - 1];
          }
          return $names;
        }

      }
    }
  }
}
