<?php

namespace Drupal\smart_content\Reaction;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * Base class for Smart reaction plugins.
 */
abstract class ReactionBase extends PluginBase implements ReactionInterface, ConfigurablePluginInterface {

  use DependencySerializationTrait;
  /**
   * Sets ID of reaction.
   *
   * @return string|null
   */
  public function id() {
    return isset($this->configuration['id']) ? $this->configuration['id'] : NULL;
  }

  /**
   * Gets ID of reaction.
   *
   * @param $id
   */
  public function setId($id) {
    $configuration = $this->getConfiguration();
    $configuration['id'] = $id;
    $this->setConfiguration($configuration);
  }

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    return [
      'id' => $this->id(),
      'plugin_id' => $this->getPluginId(),
      'weight' => $this->getWeight(),
    ];
  }

  /**
   * @inheritdoc
   */
  public function calculateDependencies() {
    // TODO: Implement calculateDependencies() method.
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
  public function setWeight($weight) {
    $configuration = $this->getConfiguration();
    $configuration['weight'] = $weight;
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return isset($this->configuration['weight']) ? $this->configuration['weight'] : 0;
  }

  /**
   * {@inheritdoc}
   */
  function buildResponse(AjaxResponse $response) {
  }
}
