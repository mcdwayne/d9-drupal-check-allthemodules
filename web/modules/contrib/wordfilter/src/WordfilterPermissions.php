<?php

namespace Drupal\wordfilter;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\wordfilter\Entity\WordfilterConfigurationInterface;

/**
 * Provides dynamic permissions for wordfilter configurations.
 */
class WordfilterPermissions {
  use StringTranslationTrait;

  /**
   * Returns an array of Wordfilter permissions.
   *
   * @return array
   *   The wordfilter permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function getPermissions() {
    $storage = \Drupal::entityTypeManager()->getStorage('wordfilter_configuration');
    $configs = $storage->loadMultiple();

    // Generate permissions for all available configurations.
    $perms = [];
    foreach ($configs as $config) {
      $perms += $this->buildPermissions($config);
    }

    return $perms;
  }

  /**
   * Returns a list of permissions for a given Wordfilter configuration.
   *
   * @param \Drupal\wordfilter\Entity\WordfilterConfigurationInterface $config
   *   The Wordfilter configuration.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(WordfilterConfigurationInterface $config) {
    $config_id = $config->id();
    $config_params = array('%label' => $config->label());

    return [
      "administer wordfilter configuration $config_id" => [
        'title' => $this->t('Administer configuration %label', $config_params),
        'description' => $this->t('View, edit and delete this configuration.'),
      ],
    ];
  }
}
