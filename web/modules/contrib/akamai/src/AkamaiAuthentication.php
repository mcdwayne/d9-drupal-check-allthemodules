<?php

namespace Drupal\akamai;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Akamai\Open\EdgeGrid\Authentication;
use Akamai\Open\EdgeGrid\Authentication\Exception\ConfigException;

/**
 * Connects to the Akamai EdgeGrid.
 *
 * Akamai's PHP Client library expects an authentication object which it then
 * integrates with a Guzzle client to create signed requests. This class
 * integrates Drupal configuration with that Authentication class, so that
 * standard Drupal config patterns can be used.
 */
class AkamaiAuthentication extends Authentication {

  /**
   * AkamaiAuthentication factory method, following superclass patterns.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   A config factory, for getting client authentication details.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   A messenger service.
   * @param \Drupal\akamai\KeyProviderInterface $key_provider
   *   A akamai.key_provider service.
   *
   * @return \Drupal\akamai\AkamaiAuthentication
   *   An authentication object.
   */
  public static function create(ConfigFactoryInterface $config, MessengerInterface $messenger, KeyProviderInterface $key_provider) {
    // Following the pattern in the superclass.
    $auth = new static();
    $config = $config->get('akamai.settings');
    $storage_method = $config->get('storage_method');
    if ($storage_method == 'file') {
      $section = $config->get('edgerc_section') ?: 'default';
      $path = $config->get('edgerc_path') ?: NULL;
      try {
        $auth = static::createFromEdgeRcFile($section, $path);
      }
      catch (ConfigException $e) {
        $messenger->addWarning($e->getMessage());
      }
    }
    elseif ($storage_method == 'key' && $key_provider->hasKeyRepository()) {
      $keys = ['access_token', 'client_token', 'client_secret'];
      $key_values = [];
      $missing_values = FALSE;
      foreach ($keys as $key) {
        $key_values[$key] = $key_provider->getKey($config->get($key));

        if (!isset($key_values[$key])) {
          $messenger->addWarning(t('Missing @key.', ['@key' => $key]));
          $missing_values = TRUE;
        }
      }

      if (!$missing_values) {
        $auth->setHost($config->get('rest_api_url'));
        // Set the auth credentials up.
        // @see Authentication::createFromEdgeRcFile()
        $auth->setAuth(
          $key_values['client_token'],
          $key_values['client_secret'],
          $key_values['access_token']
        );
      }
    }

    return $auth;
  }

  /**
   * Returns the auth config.
   *
   * @return string[]
   *   An array with keys client_token, client_secret, access_token.
   */
  public function getAuth() {
    return $this->auth;
  }

}
