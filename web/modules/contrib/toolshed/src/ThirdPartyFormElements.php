<?php

namespace Drupal\toolshed;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for creating third party configuration settings form snippets.
 */
abstract class ThirdPartyFormElements extends PluginBase implements ThirdPartyFormElementsInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function isApplicable(ConfigEntityInterface $entity, $op) {
    return TRUE;
  }

  /**
   * Return a value to use as the default settings for the third party settings.
   *
   * @var mixed
   */
  abstract protected function defaultSettings();

  /**
   * Get the current values for the third party settings.
   *
   * @param Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The entity for which the third party settings are being added.
   *
   * @return array
   *   The current third party settings associated with this entity.
   */
  public function getSettings(ConfigEntityInterface $entity) {
    return $entity->getThirdPartySetting(
      $this->pluginDefinition['provider'],
      $this->pluginDefinition['name'],
      $this->defaultSettings()
    );
  }

}
