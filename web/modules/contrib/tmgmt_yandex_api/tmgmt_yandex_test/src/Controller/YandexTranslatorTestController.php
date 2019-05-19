<?php
/**
 * @file
 * Contains \Drupal\tmgmt_microsoft_test\Controller\MicrosoftTranslatorTestController.
 */

namespace Drupal\tmgmt_yandex_test\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mock services for Yandex translator.
 */
class YandexTranslatorTestController {


  /**
   * Helper to trigger mock response error.
   *
   * @param string $domain
   * @param string $reason
   * @param string $message
   * @param string $locationType
   * @param string $location
   */
  public function trigger_response_error($domain, $reason, $message, $locationType = NULL, $location = NULL) {

    $response = array(
      'error' => array(
        'errors' => array(
          'domain' => $domain,
          'reason' => $reason,
          'message' => $message,
        ),
        'code' => 400,
        'message' => $message,
      ),
    );

    if (!empty($locationType)) {
      $response['error']['errors']['locationType'] = $locationType;
    }
    if (!empty($location)) {
      $response['error']['errors']['location'] = $location;
    }

    return new JsonResponse($response);
  }

  /**
   * Page callback for getting the supported languages.
   */
  public function get_languages(Request $request) {

    $headers = getallheaders();

    if ($headers['Authorization'] == 'Bearer correct token') {
      $response_string = '<ArrayOfstring xmlns="http://schemas.microsoft.com/2003/10/Serialization/Arrays" xmlns:i="http://www.w3.org/2001/XMLSchema-instance"><string>ar</string><string>bg</string><string>ca</string><string>zh-CHS</string><string>zh-CHT</string><string>cs</string><string>da</string><string>nl</string><string>en</string><string>et</string><string>fi</string><string>fr</string><string>de</string><string>el</string><string>ht</string><string>he</string><string>hi</string><string>hu</string><string>id</string><string>it</string><string>ja</string><string>ko</string><string>lv</string><string>lt</string><string>no</string><string>pl</string><string>pt</string><string>ro</string><string>ru</string><string>sk</string><string>sl</string><string>es</string><string>sv</string><string>th</string><string>tr</string><string>uk</string><string>vi</string></ArrayOfstring>';
      $response = new Response($response_string);
      return $response;
    }
    else {
      $response = new Response('Bad request', '400', array('status' => 'Invalid token'));
      return $response;
    }
  }


  /**
   * Simulate a translation sent back to plugin.
   */
  public function translate() {
    $headers = getallheaders();
    if ($headers['Authorization'] == 'Bearer correct token') {
      $translated_text = 'Hallo Welt';

      $response_str = '<string xmlns="http://schemas.microsoft.com/2003/10/Serialization/">' . $translated_text . '</string>';
      $response = new Response($response_str, '200');
      return $response;
    }
    else {
      $response = new Response('Bad request', '400', array('status' => 'Invalid token'));
      return $response;
    }
  }
}
