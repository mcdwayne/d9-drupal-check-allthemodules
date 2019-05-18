<?php

namespace Drupal\global_gateway;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Base class for language negotiation methods.
 */
abstract class RegionNegotiationTypeBase extends PluginBase implements RegionNegotiationTypeInterface {

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The label which is used for render of this tip.
   *
   * @var string
   */
  protected $label;

  /**
   * Allows tips to take more priority that others.
   *
   * @var string
   */
  protected $weight;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('plugin') ?: $this->getPluginDefinition()['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->getPluginDefinition()['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->getPluginDefinition()['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->get('weight') ?: $this->getPluginDefinition()['weight'];
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    if (!empty($this->configuration[$key])) {
      return $this->configuration[$key];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->configuration[$key] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrentUser(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function persist($region_code) {
    // Default implementation persists nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
    ] + $this->configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
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
  public function calculateDependencies() {
    $definition = $this->getPluginDefinition();
    $this->addDependency('module', $definition['provider']);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigRoute() {
    $definition = $this->getPluginDefinition();

    if (isset($definition['config_route_name'])) {
      return $definition['config_route_name'];
    }
    return FALSE;
  }

}
