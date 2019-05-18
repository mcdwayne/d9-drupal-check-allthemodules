<?php

namespace Drupal\payex\Service;
use Drupal\Core\Language\LanguageManager;
use Drupal\payex\Entity\PayExSetting;

/**
 * Class PayExApiFactory
 *
 * Factory for creating instances of PayExApi
 */
class PayExApiFactory {

  /**
   * The Drupal language manager.
   *
   * @var LanguageManager
   */
  protected $languageManager;

  /**
   * Cnstructs a PayExApiFactory class.
   *
   * @param LanguageManager $languageManager
   *   The Drupal lanaguage manager.
   */
  public function __construct(LanguageManager $languageManager) {
    $this->languageManager = $languageManager;
  }

  /**
   * Gets an instance of PayExApi class with a specific setting
   *
   * @param string $id
   *   The id of the setting to use for the PayExApi class.
   *
   * @return bool|PayExApi
   *   Instance of a PayExApi class ready to use or FALSE if config doesn't exist.
   */
  public function get($id) {
    $config = PayExSetting::load($id);
    if (!$config) {
      return FALSE;
    }
    return new PayExApi($config, $this->languageManager);
  }
}
