<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 4/17/16
 * Time: 8:34 AM
 */

namespace Drupal\forena\FrxPlugin\AjaxCommand;


use Drupal\forena\Token\ReportReplacer;

abstract class AjaxCommandBase implements AjaxCommandInterface {

  protected static $replacer;

  /**
   * @param $settings
   * @param $key
   * @return array
   *   The settings in the array
   */
  public function getSetting(&$settings, $key) {
    $value = NULL;
    if (isset($settings[$key])) {
      $value = $settings[$key];
      unset($settings[$key]);
    }
    static::replacer()->replaceNested($value);
    return $value;
  }

  /**
   * Get json text. 
   * @param array $settings
   * @param string $default_key
   *   The key to look for if the text
   * @return 
   *
   */
  public function getJSONText(&$settings, $default_key='') {
    $data = [];
    if ($default_key && isset($settings[$default_key])) {
      $data = $settings[$default_key];
      unset($settings[$default_key]);
    }
    if (!empty($settings['text'])) {
      $data = $settings['text'];
      unset($settings['text']);
    }
    if (!is_array($data) || !is_object($data)) {
      $data = @json_decode($data);
      if (!$data) $data = [];
    }
    static::replacer()->replaceNested($data);
    return $data;
  }

  /**
   * Report Replacer.
   * @return ReportReplacer
   */
  static public function replacer() {
    if (static::$replacer === NULL) {
      static::$replacer = new ReportReplacer();
    }
    return static::$replacer;
  }
}