<?php

namespace Drupal\formfactorykits\Kits\Traits;

/**
 * Trait AjaxTrait
 *
 * @package Drupal\formfactorykits\Kits\Traits
 */
trait AjaxTrait {

  /**
   * @param array $default
   *
   * @return array
   */
  public function getAjaxSettings(array $default = []) {
    return $this->get('ajax', $default);
  }

  /**
   * @param array $array
   *
   * @return mixed
   */
  public function setAjaxSettings(array $array) {
    return $this->set('ajax', $array);
  }

  /**
   * @param string $key
   * @param mixed $value
   *
   * @return static
   */
  public function setAjaxSetting($key, $value) {
    $settings = $this->getAjaxSettings();
    $settings[$key] = $value;
    return $this->setAjaxSettings($settings);
  }

  /**
   * @param $mixed
   * @return static
   */
  public function setAjaxCallback($mixed) {
    return $this->setAjaxSetting('callback', $mixed);
  }
}
