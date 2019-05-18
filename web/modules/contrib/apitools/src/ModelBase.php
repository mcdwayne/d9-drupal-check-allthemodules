<?php

namespace Drupal\apitools;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Model plugins.
 */
abstract class ModelBase extends PluginBase implements ModelInterface, ContainerFactoryPluginInterface {

  use DependencySerializationTrait;

  use ExtensibleObjectTrait;

  /**
   * @var integer
   */
  public $id;

  /**
   * @var ModelControllerInterface
   */
  protected $controller;

  /**
   * @var ModelManagerInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModelManagerInterface $manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->manager = $manager;
    if (!empty($configuration['model_id'])) {
      $this->id = $configuration['model_id'];
    }
    if (!empty($configuration['data'])) {
      $this->values = $configuration['data'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.apitools_model')
    );
  }

  /**
   * Set the controller with the corresponding gateway and client.
   */
  public function setController($controller) {
    $this->controller = $controller;
    return $this;
  }

  public function getController() {
    return $this->controller;
  }

  public function getMachineName() {
    return $this->configuration['machine_name'];
  }

  public function __get($name) {
    // Call properties defined in plugin definition.
    if ($controller = $this->getPropertyControllerByMethod($name)) {
      return $controller;
    }
    if (isset($this->values[$name])) {
      return $this->values[$name];
    }
    // TODO: Add in contexts as properties.
    return FALSE;
  }

  public function set($name, $value) {
    $this->values[$name] = $value;
    return $this;
  }

  abstract public function save();

  protected function hasContext($context_name) {
    return !empty($this->configuration['contexts'][$context_name]);
  }

  protected function getContext($context_name) {
    return $this->hasContext($context_name)
      ? $this->configuration['contexts'][$context_name]
      : FALSE;
  }

  private function getPropertyControllerByMethod($name) {
    if (empty($this->configuration['model_properties'])) {
      return FALSE;
    }
    foreach ($this->configuration['model_properties'] as $model_id => $property_config) {
      $definition = $this->manager->getDefinition($model_id);
      if (in_array($name, $definition['client_properties']) || isset($definition['client_properties'][$name])) {
        $provider_name = $this->controller->getClient()->getProviderName();
        return $this->manager
          ->getModelController($definition['id'], $provider_name)
          ->setContext($this->configuration['id'], $this)
          ->setClient($this->controller->getClient());
      }
    }
    return FALSE;
  }

}
