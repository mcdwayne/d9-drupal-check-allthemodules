<?php

namespace Drupal\language_access;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Provides language module permissions.
 */
class LanguageAccessPermissions implements ContainerInjectionInterface {


  /**
   * The REST resource config storage.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new LanguageAccessPermissions instance.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('language_manager'));
  }

  /**
   * Returns an array of language access permissions.
   *
   * @return array
   *   Permissions.
   */
  public function permissions() {
    $permissions = [];
    $languages = $this->languageManager->getLanguages();
    foreach ($languages as $language) {
      $permissions['access language ' . $language->getId()] = [
        'title' => t('Access language @language', ['@language' => $language->getName()]),
      ];
    }
    return $permissions;
  }

}
