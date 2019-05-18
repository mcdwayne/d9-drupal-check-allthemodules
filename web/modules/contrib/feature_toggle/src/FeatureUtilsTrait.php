<?php

namespace Drupal\feature_toggle;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;

/**
 * Provides convenience methods for feature toggle services.
 */
trait FeatureUtilsTrait {

  /**
   * Drupal\Core\State\State definition.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The Immutable Config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $immutableConfig;

  /**
   * Constructs a new FeatureUtilsTrait object.
   */
  public function __construct(StateInterface $state, ConfigFactoryInterface $config_factory) {
    $this->state = $state;
    $this->config = $config_factory->getEditable('feature_toggle.features');
    $this->immutableConfig = $config_factory->get('feature_toggle.features');
  }

  /**
   * Returns the list of features.
   *
   * @return array
   *   The list of features in the system.
   */
  protected function loadFeatures() {
    // Tricky integration of overridden features in settings.php file.
    $mutable = is_array($this->config->get('features')) ? $this->config->get('features') : [];
    $immutable = is_array($this->immutableConfig->get('features')) ? $this->immutableConfig->get('features') : [];
    return array_merge($mutable, $immutable);
  }

  /**
   * Saves the features array.
   *
   * @param array $features
   *   The features array.
   */
  protected function saveFeatures(array $features) {
    $this->config->set('features', $features)->save();
  }

  /**
   * Returns the system status flags.
   *
   * @return array
   *   The status flags array.
   */
  protected function getStatusFlags() {
    $features = $this->state->get('feature_toggle_flags', []);
    return $features;
  }

  /**
   * Saves the status flags array.
   *
   * @param array $flags
   *   The status flags array.
   */
  protected function saveStatusFlags(array $flags) {
    $this->state->set('feature_toggle_flags', $flags);
  }

}
