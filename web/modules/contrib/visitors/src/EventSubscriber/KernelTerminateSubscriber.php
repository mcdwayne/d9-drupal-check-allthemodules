<?php

/**
 * @file
 * Contains Drupal\visitors\EventSubscriber\KernelTerminateSubscriber.
 */

namespace Drupal\visitors\EventSubscriber;

use Drupal;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\CronInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;

/**
 * Store visitors data when a request terminates.
 */
class KernelTerminateSubscriber implements EventSubscriberInterface {
  /**
   * The currently active request object.
   *
   * Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Store visitors data when a request terminates.
   *
   * @param Symfony\Component\HttpKernel\Event\PostResponseEvent $event
   *   The Event to process.
   */
  public function onTerminate(PostResponseEvent $event) {
    $this->request = $event->getRequest();

    $user = \Drupal::currentUser();
    $not_admin = !in_array('administrator', $user->getRoles());
    $log_admin = !\Drupal::config('visitors.config')->get('exclude_administer_users');

    if ($log_admin || $not_admin) {
      $ip_str = $this->_getIpStr();
      $fields = array(
        'visitors_uid'        => $user->id(),
        'visitors_ip'         => $ip_str,
        'visitors_date_time'  => time(),
        'visitors_url'        => $this->_getUrl(),
        'visitors_referer'    => $this->_getReferer(),
        'visitors_path'       => Url::fromRoute('<current>')->toString(),
        'visitors_title'      => $this->_getTitle(),
        'visitors_user_agent' => $this->_getUserAgent()
      );

      if (\Drupal::service('module_handler')->moduleExists('visitors_geoip')) {
        $geoip_data = $this->_getGeoipData($ip_str);

        $fields['visitors_continent_code'] = $geoip_data['continent_code'];
        $fields['visitors_country_code']   = $geoip_data['country_code'];
        $fields['visitors_country_code3']  = $geoip_data['country_code3'];
        $fields['visitors_country_name']   = $geoip_data['country_name'];
        $fields['visitors_region']         = $geoip_data['region'];
        $fields['visitors_city']           = $geoip_data['city'];
        $fields['visitors_postal_code']    = $geoip_data['postal_code'];
        $fields['visitors_latitude']       = $geoip_data['latitude'];
        $fields['visitors_longitude']      = $geoip_data['longitude'];
        $fields['visitors_dma_code']       = $geoip_data['dma_code'];
        $fields['visitors_area_code']      = $geoip_data['area_code'];
      }

      db_insert('visitors')
        ->fields($fields)
        ->execute();
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events["kernel.terminate"] = ['onTerminate'];

    return $events;
  }

  /**
   * Get the title of the current page.
   *
   * @return string
   *   Title of the current page.
   */
  protected function _getTitle() {
    $title = \Drupal::routeMatch()->getRouteObject()->getDefault("_title");
    return htmlspecialchars_decode($title, ENT_QUOTES);
  }

  /**
   * Get full path request uri.
   *
   * @return string
   *   Full path.
   */
  protected function _getUrl() {
    return
      urldecode(sprintf('http://%s%s', $_SERVER['HTTP_HOST'], $this->request->getRequestUri()));
  }

  /**
   * Get the address of the page (if any) which referred the user agent to the
   * current page.
   *
   * @return string
   *   Referer, or empty string if referer does not exist.
   */
  protected function _getReferer() {
    return
      isset($_SERVER['HTTP_REFERER']) ? urldecode($_SERVER['HTTP_REFERER']) : '';
  }

  /**
   * Converts a string containing an visitors (IPv4) Internet Protocol dotted
   * address into a proper address.
   *
   * @return string
   */
  protected function _getIpStr() {
    return sprintf("%u", ip2long($this->request->getClientIp()));
  }

  /**
   * Get visitor user agent.
   *
   * @return string
   *   string user agent, or empty string if user agent does not exist
   */
  protected function _getUserAgent() {
    return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
  }

  /**
   * Retrieve geoip data for ip.
   *
   * @param ip
   *   A string containing an ip address.
   *
   * @return array
   *   Geoip data array.
   */
  protected function _getGeoipData($ip) {
    $result = array(
      'continent_code' => '',
      'country_code'   => '',
      'country_code3'  => '',
      'country_name'   => '',
      'region'         => '',
      'city'           => '',
      'postal_code'    => '',
      'latitude'       => '0',
      'longitude'      => '0',
      'dma_code'       => '0',
      'area_code'      => '0'
    );

    if (function_exists('geoip_record_by_name')) {
      $data = @geoip_record_by_name($ip);
      if ((!is_null($data)) && ($data !== FALSE)) {
        /* Transform city value from iso-8859-1 into the utf8. */
        $data['city'] = utf8_encode($data['city']);

        $result = $data;
      }
    }

    return $result;
  }
}

