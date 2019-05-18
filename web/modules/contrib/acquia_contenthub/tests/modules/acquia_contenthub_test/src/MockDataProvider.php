<?php

namespace Drupal\acquia_contenthub_test;

/**
 * Contains test related mock data.
 */
class MockDataProvider {

  const VALID_HOSTNAME = 'https://dev.contenhub.com';

  const VALID_WEBHOOK_URL = 'https://webhook.is-valid.com';

  const ALREADY_REGISTERED_WEBHOOK = 'https://already-registered.webhook.com';

  const VALID_SECRET = 'valid-secret-key';

  const VALID_API_KEY = 'valid-api-key';

  const VALID_CLIENT_NAME = 'valid-client-name';

  const SETTINGS_UUID = '9657377c-30e1-4a5b-9396-0fade30d90e5';

  /**
   * Provide filter mock data.
   *
   * @return array
   *   The filter.
   */
  public static function mockFilter(): array {
    return [
      'name' => 'filter_1',
      'uuid' => 'cfcd1dc9-7891-4e61-90cc-61ab43ca03c7',
    ];
  }

  /**
   * Generates random uuid.
   *
   * @return string
   *   The uuid.
   */
  public static function randomUuid(): string {
    return \Drupal::getContainer()->get('uuid')->generate();
  }

}
