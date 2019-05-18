<?php

namespace Drupal\ip2country\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Component\Serialization\Json;
use Drupal\ip2country\Ip2CountryManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for user routes.
 */
class Ip2CountryController extends ControllerBase {

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The country_manager service.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManager;

  /**
   * The ip2country.manager service.
   *
   * @var \Drupal\ip2country\Ip2CountryManagerInterface
   */
  protected $ip2countryManager;

  /**
   * Constructs an Ip2CountryController.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger factory service.
   * @param \Drupal\Core\Locale\CountryManagerInterface $countryManager
   *   The country_manager service.
   * @param \Drupal\ip2country\Ip2CountryManagerInterface $ip2countryManager
   *   The ip2country.manager service.
   */
  public function __construct(LoggerChannelFactoryInterface $logger, CountryManagerInterface $countryManager, Ip2CountryManagerInterface $ip2countryManager) {
    $this->logger = $logger;
    $this->countryManager = $countryManager;
    $this->ip2countryManager = $ip2countryManager;
    // Utility functions for loading IP/Country DB from external sources.
    $this->moduleHandler()->loadInclude('ip2country', 'inc');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory'),
      $container->get('country_manager'),
      $container->get('ip2country.manager')
    );
  }

  /**
   * AJAX callback to update the IP to Country database.
   *
   * @param string $rir
   *   String with name of IP registry. One of 'afrinic', 'arin', 'lacnic',
   *   'ripe'. Not case sensitive.
   *
   * @return string
   *   JSON object for display by jQuery script.
   */
  public function updateDatabaseAction($rir) {

    $ip2country_config = $this->config('ip2country.settings');

    // Update DB from RIR.
    $status = ip2country_update_database($rir);

    if ($status != FALSE) {
      if ($ip2country_config->get('watchdog')) {
        $this->logger->get('ip2country')->notice('Manual database update from @registry server.', ['@registry' => mb_strtoupper($rir)]);
      }
      print Json::encode([
        'count'   => t('@rows rows affected.',
                        ['@rows' => ip2country_get_count()]),
        'server'  => $rir,
        'message' => t('The IP to Country database has been updated from @server.',
                       ['@server' => mb_strtoupper($rir)]),
      ]);
    }
    else {
      if ($ip2country_config->get('watchdog')) {
        $this->logger->get('ip2country')->notice('Manual database update from @registry server FAILED.', ['@registry' => mb_strtoupper($rir)]);
      }
      print Json::encode([
        'count'   => t('@rows rows affected.', ['@rows' => 0]),
        'server'  => $rir,
        'message' => t('The IP to Country database update failed.'),
      ]);
    }
    exit();
  }

  /**
   * AJAX callback to lookup an IP address in the database.
   *
   * @param string $ip_address
   *   String with IP address.
   *
   * @return string
   *   JSON object for display by jQuery script.
   */
  public function lookupAction($ip_address) {

    // Return results of manual lookup.
    $country_code = $this->ip2countryManager->getCountry($ip_address);
    if ($country_code) {
      $country_list = $this->countryManager->getList();
      $country_name = $country_list[$country_code];
      print Json::encode([
        'message' => t('IP Address @ip is assigned to @country (@code).',
                       ['@ip'      => $ip_address,
                        '@country' => $country_name,
                        '@code'    => $country_code]),
      ]);
    }
    else {
      print Json::encode([
        'message' => t('IP Address @ip is not assigned to a country.',
                       ['@ip' => $ip_address]),
      ]);
    }
    exit();
  }

}
