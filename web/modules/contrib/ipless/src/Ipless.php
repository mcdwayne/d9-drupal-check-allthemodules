<?php

namespace Drupal\ipless;

use Less_Parser;

/**
 * Description of Ipless2.
 */
class Ipless implements IplessInterface {

  /**
   * Force generation of CSS.
   * 
   * @var bool
   */
  protected $forced = FALSE;

  /**
   * Get Instance.
   * 
   * @return \static Ipless object.
   */
  public static function getInstance() {
    return new static();
  }

  /**
   * Force CSS generation.
   * 
   * @param bool $value Force less compilation.
   */
  public function setForced($value = TRUE) {
    $this->forced = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function generate() {
    if (!$this->checkLib()) {
      return;
    }

    if(!\Drupal::config('system.performance')->get('ipless.enabled')) {
      return;
    }
    
    if ($this->forced || \Drupal::config('system.performance')->get('ipless.modedev')) {
      $this->generateCss();
    }
  }

  /**
   * Check that the library Less php is installed.
   * 
   * @return bool
   */
  protected function checkLib() {
    if (!class_exists('Less_Parser')) {
      drupal_set_message(t('The class lessc is not installed.'), 'warning');
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Generate Less files.
   */
  protected function generateCss() {
    $assetRenderer = \Drupal::service('ipless.asset.renderer');
    $assetRenderer->render($this->forced);
  }

}
