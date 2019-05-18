<?php

namespace Drupal\onlinepbx\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\Yaml\Yaml;

/**
 * Users Controller.
 */
class OnpbxUsers extends ControllerBase {

  /**
   * Replace user name.
   */
  public static function userName($phone) {
    $data = &drupal_static("OnpbxUsers::userName($phone)");
    if (!isset($data)) {
      $users = self::getCached();
      $data = $phone;
      if (isset($users[$phone])) {
        $data = $users[$phone];
      }
      $users = Yaml::parse(\Drupal::config('onlinepbx.settings')->get('users'));
      if (isset($users[$phone])) {
        $data = $users[$phone];
      }
    }
    return $data;
  }

  /**
   * Page.
   */
  public static function getCached($skip = FALSE) {
    $data = &drupal_static('OnpbxUsers::get()');
    if (!isset($data)) {
      $cache_key = 'onlinepbx:OnpbxUsers';
      if ($skip) {
        $cache_key .= rand();
      }
      if ($cache = \Drupal::cache()->get($cache_key)) {
        $data = $cache->data;
      }
      else {
        $data = self::users();
        \Drupal::cache()->set($cache_key, $data);
      }
    }
    return $data;
  }

  /**
   * API get Users.
   */
  public static function users() {
    $resut = [];
    $path = "user/get.json";
    $phones = Api::request($path, []);
    if ($data = Api::isOk($phones)) {
      foreach ($data as $key => $phone) {
        $num = Api::request($path, ['num' => $phone]);
        $resut[$phone] = FALSE;
        if ($info = Api::isOk($num)) {
          $resut[$phone] = $info['name'];
        }
      }
    }
    ksort($resut);
    return $resut;
  }

}
