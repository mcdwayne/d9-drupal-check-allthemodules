<?php

/**
 * @file
 * Contains \Drupal\ip_ban\IpBanSetBanInterface.
 */

namespace Drupal\ip_ban;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class IpBanSetBanManager implements IpBanSetBanInterface {
  
  /**
   * The user's ban value.
   *
   * @var int
   *   The user's ban value (one of IP_BAN_NOBAN (0), IP_BAN_READONLY (1), 
   *   or IP_BAN_BANNED (2), which are defined in the .module file.
   */
  private $banvalue;
  
  /**
   * {@inheritdoc}
   */
  public function iPBanSetValue() {
    // If code is being run from drush, we don't want to take any action.
    if ((PHP_SAPI === "cli") && function_exists('drush_main')) {
      return;
    }    
    // If user has permission to bypass ban, set to IP_BAN_NOBAN and return
    if (\Drupal::currentUser()->hasPermission('ignore ip_ban')) {
      $this->banvalue = IP_BAN_NOBAN;
      return;
    }
    $test_ip = \Drupal::config('ip_ban.settings')->get('ip_ban_test_ip');
    // Grab the test IP address or the user's real address.
    $ip = empty($test_ip) ? \Drupal::request()->getClientIp() : $test_ip;
    $country_code = ip2country_get_country($ip);
    // Determine if the current user is banned or read only.
    // Individual IP complete ban trumps individual read only IP, both of which
    // trump a country setting.
    if (!isset($this->banvalue)) {
      $banvalue = (int) \Drupal::config('ip_ban.settings')->get('ip_ban_' . $country_code);
      $this->banvalue = $banvalue;
      // Check the read-only IP list.
      $readonly_ips = \Drupal::config('ip_ban.settings')->get('ip_ban_readonly_ips');
      if (!empty($readonly_ips)) {
        $ip_readonly_array = explode(PHP_EOL, $readonly_ips);
        if (in_array($ip, $ip_readonly_array)) {
          $this->$banvalue = IP_BAN_READONLY;
        }
      }
      // Check the complete ban list.
      $banned_ips = \Drupal::config('ip_ban.settings')->get('ip_ban_additional_ips');
      if (!empty($banned_ips)) {
        $ip_ban_array = explode(PHP_EOL, $banned_ips);
        if (in_array($ip, $ip_ban_array)) {
          $this->banvalue = IP_BAN_BANNED;
        }
      }
    }
    return;
  }
  
  /**
   * {@inheritdoc}
   */  
  public function getBanValue() {
    return $this->banvalue;
  }

  /**
   * {@inheritdoc}
   */
  public function iPBanDetermineAction() {
    if ($this->banvalue == IP_BAN_READONLY) {
      $uri = \Drupal::service('path.current')->getPath();
      if (($uri == 'user') || strpos($uri, 'user/') !== FALSE) {
        $path = \Drupal::config('ip_ban.settings')->get('ip_ban_readonly_path');
        $response = new RedirectResponse($path);
        $response->send();
        exit;
      }
    }
    if ($this->banvalue == IP_BAN_BANNED) {
      // Always allow access to the banned page.
      $complete_ban_path = \Drupal::config('ip_ban.settings')->get('ip_ban_completeban_path');
      if (!empty($complete_ban_path) && \Drupal::service('path.current')->getPath() != \Drupal::service('path.alias_manager')->getPathByAlias($complete_ban_path)) {
        $response = new RedirectResponse($complete_ban_path);
        $response->send();
      }
      else {
        drupal_set_message(t(\Drupal::config('ip_ban.settings')->get('ip_ban_completeban')), 'error');
      }
    }
  }
  
}