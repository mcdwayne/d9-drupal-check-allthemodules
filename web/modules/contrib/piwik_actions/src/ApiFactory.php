<?php

namespace Drupal\piwik_actions;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 *
 */
class ApiFactory {

  /**
   *
   */
  public static function getVisits(ConfigFactoryInterface $config) {
    return new Visits(ApiFactory::getEndpoint($config->get('piwik_actions.settings')));
  }

  /**
   *
   */
  private static function getEndpoint($config) {
    $params = [
      'module' => 'API',
      'period' => 'range',
      'format' => 'JSON',
      'idSite' => $config->get('site_id'),
      'token_auth' => $config->get('token'),
      'filter_limit' => '-1',
    ];
    return $config->get('endpoint') . '?' . http_build_query($params);
  }

}
