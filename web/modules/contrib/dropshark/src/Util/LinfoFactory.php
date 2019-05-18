<?php

namespace Drupal\dropshark\Util;

use Drupal\Core\Config\ConfigFactoryInterface;
use Linfo\Linfo;

/**
 * Class LinfoFactory.
 */
class LinfoFactory {

  /**
   * Path to the Linfo library autoloader file.
   *
   * @var string
   */
  protected $autoloadPath;

  /**
   * The Linfo instance.
   *
   * This value will initially be NULL, then hold the Linfo instance once it's
   * created. If unable to instantiate Linfo, the value will be FALSE.
   *
   * @var false|\Linfo\Linfo|null
   */
  protected $linfo;

  /**
   * LinfoFactory constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Configuration factory service.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->autoloadPath = $configFactory->get('dropshark.settings')
      ->get('linfo_path');
  }

  /**
   * Creates the Linfo instance.
   *
   * @return false|\Linfo\Linfo
   *   The instantiated Linfo object, or FALSE if not available.
   */
  public function createInstance() {
    if ($this->linfo === NULL) {
      // If not available via Composer, attempt to use the built-in autoloader.
      if (!class_exists('\Linfo\Linfo')) {
        if (file_exists($this->autoloadPath)) {
          include $this->autoloadPath;
        }
      }

      // Instantiate Linfo if available.
      if (class_exists('\Linfo\Linfo')) {
        $settings['show']['mounts_options'] = FALSE;
        $this->linfo = new Linfo($settings);
      }
      else {
        $this->linfo = FALSE;
      }
    }

    return $this->linfo;
  }

}
