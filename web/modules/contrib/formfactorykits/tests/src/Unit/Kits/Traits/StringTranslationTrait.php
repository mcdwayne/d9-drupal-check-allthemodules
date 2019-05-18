<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Traits;

use Drupal\Core\DependencyInjection\ContainerNotInitializedException;
use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\TranslationManager;

/**
 * Trait StringTranslationTrait
 *
 * @package Drupal\Tests\formfactorykits\Unit\Kits\Traits
 */
trait StringTranslationTrait {
  /**
   * @return TranslationInterface
   */
  public function getTranslationManager() {
    try {
      $manager = \Drupal::service('string_translation');
      $manager->reset();
      return $manager;
    }
    catch (ContainerNotInitializedException $e) {
      return new TranslationManager(new LanguageDefault([]));
    }
  }

  /**
   * @param string $string
   *
   * @return TranslatableMarkup
   */
  public function t($string) {
    return new TranslatableMarkup($string, [], [], $this->getTranslationManager());
  }
}
