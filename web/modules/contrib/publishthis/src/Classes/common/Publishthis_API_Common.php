<?php
namespace Drupal\publishthis\Classes\common;

abstract class Publishthis_API_Common {

  protected $_api_url;
  
  abstract function _request( $url, $return_errors=false );
  abstract function GetToken();
  abstract function LogMessage( $message, $level='' );
  abstract function get_client_info( $params = array() );

  /**
   * Get API url value
   *
   * @return string API url
   */
  function api_url() {
    return $this->_api_url;
  }

  /**
   * Use this method to get published feeds by ids.
   * https://helpcenter.publishthis.com/developers/api-documentation/get-method-rest-content-web/
   */
  function get_feeds_by_ids($feed_ids) {
    $feeds = [];

    if ( !is_array( $feed_ids ) ) {
      return $feeds;
    }

    $ids = implode( ',', $feed_ids );

    $params = ['token' => $this->GetToken()];

    $url = $this->_compose_api_call_url( '/content/web/' . $ids, $params );

    try {
      $response = $this->_request($url);

      if (empty($response)) {
        return $feeds;
      }

      $_feed = [
        'feedId' => $response->id,
        'publishTypeId' => $response->publishTypeId, 
        'title' => $response->title,
        'summary' => $response->featuredDocument->summary,
        'imageUrl' => $response->featuredDocument->imageUrl,
        'docId' => $response->featuredDocument->docId,
        'publishDate' => $response->featuredDocument->publishDate,
        'source_url' => $response->featuredDocument->url
      ];
      $feeds [] = $_feed;

    } 
    catch (Exception $ex) {
      $this->LogMessage($ex->getMessage());
    }

    return $feeds;
  }


  /**
   * Returns token status message
   */
  function validate_token( $token ) {
    $token = str_replace(["+","&","#"], ["\+", "\&", "\#"], $token);
    $status = [
      'valid' => true, 
      'message' => 'API token is valid.'
    ];

    if ( empty( $token ) ) {
      return [
        'valid' => false, 
        'message' => 'Your settings are not completed yet. You will not be able to use the plugin until you complete the settings.'
      ];
    }

    $params = ['token' => $token ];

    $url = $this->_compose_api_call_url('/client', $params);
    
    try {
      $return_errors = TRUE;
      $response = $this->_request ( $url,  $return_errors);

      if(!is_object($response) &&  empty($response)) {
        $message = [
          'message' => 'Invalid token ' .$token,
          'status' => 'error',
        ];
        
        $this->LogMessage($message, '3');
        $result = [
          'valid' => false, 
          'message' => 'We could not authenticate your API token, please correct the error and try again.'
        ];
      }
      else {
        $result = $status;
      }
    } catch (Exception $ex) { 
        $message = [
          'message' => 'Invalid token ' .$token,
          'status' => 'error',
        ];
        
        $this->LogMessage($message, '3');
        $result = [
          'valid' => false, 
          'message' => 'We could not authenticate your API token, please correct the error and try again.'
        ];
    }    
    return $result;
  }

  /**
   * Compose request url
   *
   * @param string  $method API call-specific url part
   * @param array   $params Additional params to append to url
   * @return API request URL
   */
  protected function _compose_api_call_url($method, $params = []) {
    
    if (empty($params)) {
      $params = [];
      $params['token'] = $this->GetToken();
    }
    $url_params = [];
    foreach($params as $k=>$v) $url_params[] = $k.'='.$v;
    $url = $this->_api_url . $method . '?' . implode('&', $url_params );
    // add debug message about call
    $called_from = '';
    $backtrace = debug_backtrace();
    if ( isset( $backtrace[1]['function'] ) ) $called_from = $backtrace[1]['function'];
    
    if (!in_array($called_from, ['validate_token'])) {
      $message = [
        'message' => 'PublishThis API call - Called from: ' . $called_from . '<br>URL: ' . $url,
        'status' => 'info'
      ];
      $this->LogMessage($message, '6');     
    }
    return $url;
  }
}
