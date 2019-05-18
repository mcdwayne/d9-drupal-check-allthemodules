<?php

namespace Drupal\myjdownloader;

use Drupal\Component\Serialization\Json;

/**
 * MyJd Helper functions.
 */
class MyJdHelper {

  /**
   * Get Configuration Name.
   */
  public static function getConfigName() {
    return 'myjdownloader.settings';
  }

  /**
   * Get Configuration Object.
   *
   * @param bool $editable
   *   IF TRUE, return editable object.
   *
   * @return \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   *   Configuration object
   */
  public static function getConfig($editable = FALSE) {
    if ($editable) {
      $config = \Drupal::configFactory()->getEditable(static::getConfigName());
    }
    else {
      $config = \Drupal::config(static::getConfigName());
    }
    return $config;
  }

  /**
   * Get jDownloader state.
   *
   * @return array
   *   Result.
   */
  public static function getState() {
    $mydjapi = new MyJDAPI();
    $results = [];
    $res = $mydjapi->callAction('/downloads/getJDState');
    if (!$res) {
      return NULL;
    }
    $results['Info']['State'] = Json::decode($res)['data'];

    $res = $mydjapi->callAction('/update/isUpdateAvailable');
    $results['Info']['Update Available'] = Json::decode($res)['data'] ? "Yes" : "No";

    $res = $mydjapi->callAction('/downloads/packageCount');
    $results['Info']['Package Count'] = Json::decode($res)['data'];

    $res = $mydjapi->callAction('/downloads/speed');
    $v = Json::decode($res)['data'];
    $results['Info']['Speed'] = (round($v / 1000, 2) . "K") . " (" . $v . ")";

    $res = $mydjapi->callAction('/system/getSystemInfos');
    $results['System Info'] = Json::decode($res)['data'];

    $res = $mydjapi->callAction('/downloadsV2/queryPackages', []);
    $res = Json::decode($res)['data'];
    foreach ($res as $i => $data) {
      $results['Packages'][$i + 1] = $data['name'];
    }

    return $results;
  }

  /**
   * Add a link to the jDownloader.
   *
   * @param string|array $link
   *   Link or links to add.
   * @param null|array $settings
   *   Additional settings.
   *
   * @return bool|mixed|string
   *   Result.
   *
   * @throws \Exception
   *   If link is empty or bad type.
   */
  public static function addLink($link, array $settings = NULL) {
    $mydjapi = new MyJDAPI();
    $res = $mydjapi->addLinks($link, $settings);
    if ($res) {
      $res = $mydjapi->queryLinks();
    }
    return $res;
  }

  /**
   * Generic function Make a call to API function on my.jdownloader.org.
   *
   * @param string $action
   *   The call path.
   * @param false|array $params
   *   Additional settings.
   *
   * @return bool|mixed|string
   *   Result.
   */
  public static function callAction($action, $params = FALSE) {
    $mydjapi = new MyJDAPI();
    return $mydjapi->callAction($action, $params);
  }

}
