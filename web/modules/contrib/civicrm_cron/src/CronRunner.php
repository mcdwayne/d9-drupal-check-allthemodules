<?php

namespace Drupal\civicrm_cron;

use Drupal\civicrm\Civicrm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;

/**
 * Class CronRunner.
 *
 * Runs the cron.
 */
class CronRunner {

  /**
   * CiviCRM service.
   *
   * @var \Drupal\civicrm\Civicrm
   */
  protected $civicrm;

  /**
   * HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * CiviCRM cron settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * CronRunner constructor.
   *
   * @param \Drupal\civicrm\Civicrm $civicrm
   *   CiviCRM service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   * @param \GuzzleHttp\ClientInterface $client
   *   HTTP client service.
   */
  public function __construct(Civicrm $civicrm, ConfigFactoryInterface $configFactory, ClientInterface $client) {
    $this->config = $configFactory->get('civicrm_cron.settings');
    $this->client = $client;
  }

  /**
   * Runs CiviCRM cron.
   *
   * @param string $key
   *   Optional, the CiviCRM site key to utilize. Omit to use the site key from
   *   configuration.
   *
   * @throws \Exception
   *   If the cron run fails. Inspect the exception message for further details.
   */
  public function runCron($key = NULL) {
    if (!$key) {
      $key = $this->config->get('sitekey');
    }

    $options = ['absolute' => TRUE];
    $url = Url::fromRoute('civicrm_cron.passthrough', [], $options)->toString();

    $response = NULL;

    try {
      $response = $this->client->request('GET', $url, ['query' => ['key' => $key]]);
    }
    catch (ClientException $e) {
      if ($e->hasResponse()) {
        $response = $e->getResponse();
      }
    }

    if (!$response) {
      throw new \Exception('Unknown error');
    }

    if (!$response->getHeader('X-CiviCRM-Cron')) {
      throw new \Exception($response->getBody());
    }
  }

}
