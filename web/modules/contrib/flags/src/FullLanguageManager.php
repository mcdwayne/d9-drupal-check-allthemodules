<?php


namespace Drupal\flags;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\language\ConfigurableLanguageManagerInterface;

class FullLanguageManager implements FullLanguageManagerInterface {

  /**
   * @var ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * FullLanguageManager constructor.
   *
   * @param \Drupal\Core\Language\LanguageManager $languageManager
   * @param \Drupal\Core\Config\ConfigFactoryInterface
   */
  public function __construct(LanguageManagerInterface $languageManager, ConfigFactoryInterface $configFactory) {
    $this->languageManager = $languageManager;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllDefinedLanguages() {
    // Get list of all configured languages.
    $languages= [];

    // See Drupal\language\ConfigurableLanguageManager::getLanguages() for details
    $predefined = LanguageManager::getStandardLanguageList();

    foreach($predefined as $key => $value) {
      $languages[$key] = new TranslatableMarkup($value[0]);
    }

    $config_ids = $this->configFactory->listAll('language.entity.');
    foreach ($this->configFactory->loadMultiple($config_ids) as $config) {
      $data = $config->get();
      $languages[$data['id']] = new TranslatableMarkup($data['label']);
    }

    asort($languages);
    return $languages;
  }

}
