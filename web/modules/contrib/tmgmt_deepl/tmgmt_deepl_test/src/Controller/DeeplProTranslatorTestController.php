<?php

namespace Drupal\tmgmt_deepl_test\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Mock services for DeepL Pro translator.
 */
class DeeplProTranslatorTestController {

  /**
   * Get usage data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   - Request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   - return json object with error or usage data.
   */
  public function getUsageData(Request $request) {
    // Authorization failed.
    if ($request->get('auth_key') != 'correct key') {
      return new JsonResponse(403);
    }

    // Sample response with usage data.
    $response = [
      'character_count' => 180118,
      'character_limit' => 1250000,
    ];
    return new JsonResponse($response);
  }

  /**
   * Helper to trigger mok response error.
   *
   * @param string $domain
   *   - Domain.
   * @param string $reason
   *   - Reason.
   * @param string $message
   *   - Message.
   * @param string $locationType
   *   - Location type.
   * @param string $location
   *   - Location.
   */
  public function triggerResponseError($domain, $reason, $message, $locationType = NULL, $location = NULL) {

    $response = [
      'error' => [
        'errors' => [
          'domain' => $domain,
          'reason' => $reason,
          'message' => $message,
        ],
        'code' => 400,
        'message' => $message,
      ],
    ];

    if (!empty($locationType)) {
      $response['error']['errors']['locationType'] = $locationType;
    }
    if (!empty($location)) {
      $response['error']['errors']['location'] = $location;
    }

    return new JsonResponse($response);
  }

  /**
   * Mock service to translate request.
   */
  public function translate(Request $request) {

    $this->getUsageData($request);

    if (!$request->query->has('text')) {
      $this->trigger_response_error('global', 'required', 'Required parameter: text', 'parameter', 'text');
    }
    if (!$request->query->has('source_lang')) {
      $this->trigger_response_error('global', 'required', 'Required parameter: source_lang', 'parameter', 'source_lang');
    }
    if (!$request->query->has('target_lang')) {
      $this->trigger_response_error('global', 'required', 'Required parameter: target_lang', 'parameter', 'target_lang');
    }

    $translations = [
      'DE' => 'Hallo Welt',
      'EN' => 'Hello World',
      'FR' => 'Bonjour tout le monde',
    ];

    $response = [
      'translations' => [
        ['text' => $translations[$_GET['target_lang']]],
      ],
    ];

    return new JsonResponse($response);
  }

}
