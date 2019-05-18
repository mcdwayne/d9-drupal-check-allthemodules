<?php
/**
 * A service factory to determine the correct version of the Drupal client
 * service.
 *
 * This will return the DrupalClient or a test service depending on if testing
 * mode is enabled.
 */

namespace Drupal\akismet\Client;

use Drupal\Core\Config\ConfigFactory;
use GuzzleHttp\ClientInterface;

class DrupalClientFactory {

  /**
   * Factory method to select the correct Akismet client service.
   *
   * @param ConfigFactory $config_factory
   *   The configuration factory in order to retrieve Akismet settings data.
   * @param ClientInterface $http_client
   *   An http client
   * @return DrupalClientInterface
   */
  public static function createDrupalClient(ConfigFactory $config_factory, ClientInterface $http_client) {
    $akismet_settings = $config_factory->get('akismet.settings');
    $state = \Drupal::state();
    if ($state->get('akismet.testing_use_local_invalid') ?: FALSE) {
      return new DrupalTestInvalid($config_factory, $http_client);
    }
    else if ($state->get('akismet.testing_use_local') ?: FALSE) {
      return new DrupalTestLocalClient($config_factory, $http_client);
    } else if ($akismet_settings->get('test_mode.enabled')) {
      return new DrupalTestClient($config_factory, $http_client);
    }
    else {
      return new DrupalClient($config_factory, $http_client);
    }
  }
}
