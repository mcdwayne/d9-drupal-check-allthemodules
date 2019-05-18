<?php

namespace Drupal\hidden_language;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the filter module.
 */
class HiddenLanguagePermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Returns an array of filter permissions.
   *
   * @return array
   */
  public function permissions() {
    $permissions = [];
    $languages = \Drupal::languageManager()->getLanguages();
    foreach ($languages as $langcode => $language) {
      $permissions["access hidden language $langcode"] = [
        'title' => $this->t('Access hidden language @language', ['@language' => $language->getName()]),
        'description' => $this->t('Access @language language when it is hidden.', ['@language' => $language->getName()]),
      ];
    }

    return $permissions;
  }

}
