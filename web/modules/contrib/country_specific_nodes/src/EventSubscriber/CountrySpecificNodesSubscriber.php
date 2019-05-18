<?php

/**
 * @file
 * User country session handler service.
 */

namespace Drupal\country_specific_nodes\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a CountrySpecificNodesSubscriber.
 *
 * @see Symfony\Component\HttpKernel\KernelEvents for details
 */
class CountrySpecificNodesSubscriber implements EventSubscriberInterface {
  /**
   * Put Ip address in $_SESSION
   */
  public function setUserSessionIp() {
    unset($_SESSION['ip_country_code']);
    if (!isset($_SESSION['ip_country_code'])) {
      // Create a session variable to store user country.
      $_SESSION['ip_country_code'] = '';

      // Get user IP address.
      $ip = \Drupal::request()->getClientIp();

      // Get country code based on user IP address.
      $country_code = ip2country_get_country($ip);

      // Set country code, if not set default.
      if (empty($country_code)) {
        $csn_config = \Drupal::getContainer()->get('config.factory')->getEditable('country_specific_nodes.settings');
        $country_code = $csn_config->get('country_specific_nodes_def_cn');
      }
      $_SESSION['ip_country_code'] = $country_code;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('setUserSessionIp', 20);
    return $events;
  }

}
