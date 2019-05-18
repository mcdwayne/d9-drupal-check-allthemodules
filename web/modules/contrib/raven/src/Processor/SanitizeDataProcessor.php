<?php

namespace Drupal\raven\Processor;

use Raven_Processor_SanitizeDataProcessor;
use Raven_Client;

/**
 * Overrides the built-in data sanitization processor.
 */
class SanitizeDataProcessor extends Raven_Processor_SanitizeDataProcessor {

  const FIELDS_RE = '/(SESS|pass|authorization|password|passwd|secret|password_confirmation|card_number|auth_pw)/i';

  /**
   * {@inheritdoc}
   */
  public function __construct(Raven_Client $client) {
    parent::__construct($client);
    $this->fields_re = self::FIELDS_RE;
  }

  /**
   * {@inheritdoc}
   */
  public function sanitizeHttp(&$data) {
    $http = &$data['request'];
    if (!empty($http['cookies']) && is_array($http['cookies'])) {
      $cookies = &$http['cookies'];
      foreach ($cookies as $key => $value) {
        if (is_int(strpos($key, 'SESS'))) {
          $cookies[$key] = self::STRING_MASK;
        }
      }
    }
    if (!empty($http['data']) && is_array($http['data'])) {
      array_walk_recursive($http['data'], [$this, 'sanitize']);
    }
  }

}
