<?php

namespace Drupal\m200wad_api;

interface m200wadClientInterface {

  /**
   * Utilizes Drupal's httpClient to connect to 200 Words a day 
   * Info: https://200wordsaday.com/
   * API Docs: https://200wordsaday.com/makers
   * 
   * @param string $method
   *   get, post, patch, delete, etc. See Guzzle documentation.
   * @param string $endpoint
   *   The 200wad API endpoint (ex. api/texts)
   * @param array $query
   *   Query string parameters the endpoint allows (ex. ['per_page' => 50]
   * @param array $body (converted to JSON)
   *   Utilized for some endpoints
   * @return object
   *   \GuzzleHttp\Psr7\Response body
   */
   public function connect($method, $endpoint, $query, $body);
}
