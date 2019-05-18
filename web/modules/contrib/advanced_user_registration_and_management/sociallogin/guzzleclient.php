<?php

use LoginRadiusSDK\Clients\IHttpClient;
use LoginRadiusSDK\LoginRadius;
use LoginRadiusSDK\LoginRadiusException;

class LRGuzzleClient implements IHttpClient {

  public function request($path, $query_array = array(), $options = array()) {
    $parse_url = parse_url($path);

    if (isset($parse_url['scheme']) && !empty($parse_url['scheme'])) {
      $validate_url = $path;
    }
    else {
      $validate_url = API_DOMAIN . $path;
    }
    $method = isset($options['method']) ? strtolower($options['method']) : 'get';
    $post_data = isset($options['post_data']) ? $options['post_data'] : array();
    $content_type = isset($options['content_type']) ? $options['content_type'] : 'form_params';
    $ssl_verify = isset($options['ssl_verify']) ? $options['ssl_verify'] : FALSE;

    $httpclient_options['verify'] = $ssl_verify;
    if ($method == 'post') {
      $httpclient_options[$content_type] = $post_data;
    }
    if ($query_array !== FALSE) {
      $validate_url .= '?' . LoginRadius::queryBuild(LoginRadius::authentication($query_array));
    }

    try {
      $response = \Drupal::httpClient()
        ->$method($validate_url, $httpclient_options);
      $data = (string) $response->getBody();
      return $data;
    }
    catch (RequestException $e) {    
      throw new LoginRadiusException($e->getMessage(), $e);
    }
    catch (ClientException $e) {
      throw new LoginRadiusException($e->getMessage(), $e);
    }
    catch (Exception $e) {  
      throw new LoginRadiusException($e->getMessage(), $e);
    }
  }
}