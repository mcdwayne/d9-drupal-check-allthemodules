<?php

namespace Drupal\entity_slug\Plugin\Slugifier;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginDependencyTrait;

/**
 * Abstract base class SlugifierBase
 *
 * @package Drupal\entity_slug\Plugin\Slugifier
 */
abstract class SlugifierBase extends PluginBase implements SlugifierInterface {

  use PluginDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function information() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    // By default, we're not reacting to anything and so we should leave
    // everything as it was.
    return FALSE;
  }
}
