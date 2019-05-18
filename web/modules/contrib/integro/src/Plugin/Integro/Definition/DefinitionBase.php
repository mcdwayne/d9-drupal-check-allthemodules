<?php

namespace Drupal\integro\Plugin\Integro\Definition;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\integro\ClientManagerInterface;
use Drupal\integro\DefinitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an integration definition.
 */
abstract class DefinitionBase extends PluginBase implements ContainerFactoryPluginInterface, DefinitionInterface {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * Constructs a new object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\integro\ClientManagerInterface $client_manager
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientManagerInterface $client_manager, TranslationInterface $string_translation) {
    $configuration += $this->defaultConfiguration();
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->clientManager = $client_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('integro_client.manager'),
      $container->get('string_translation')
    );
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
  public function defaultConfiguration() {
    return [
      'id' => '',
      'parent' => '',
      'provider' => '',
      'integration' => '',
      'definition' => '',
      'version' => '',
      'label' => '',
      'description' => '',
      'category' => '',
      'client' => '',
      'client_configuration' => [],
      'operations' => [],
    ];
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
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->getId();
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return isset($this->configuration['id']) ? $this->configuration['id'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->configuration['id'] = $id;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    return isset($this->configuration['version']) ? $this->configuration['version'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setVersion($version) {
    $this->configuration['version'] = $version;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIntegrationPluginId() {
    return isset($this->configuration['integration']) ? $this->configuration['integration'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setIntegrationPluginId($integration_plugin_id) {
    $this->configuration['integration'] = $integration_plugin_id;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getClientPlugin($configuration = []) {
    return isset($this->configuration['client']) ? $this->clientManager->createInstance($this->configuration['client'], $configuration) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getClientPluginId() {
    return isset($this->configuration['client']) ? $this->configuration['client'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setClientPluginId($client_plugin_id) {
    $this->configuration['client'] = $client_plugin_id;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return isset($this->configuration['label']) ? $this->configuration['label'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->configuration['label'] = $label;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return isset($this->configuration['description']) ? $this->configuration['description'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->configuration['description'] = $description;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCategory() {
    return isset($this->configuration['category']) ? $this->configuration['category'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setCategory($category) {
    $this->configuration['category'] = $category;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return isset($this->configuration['provider']) ? $this->configuration['provider'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setProvider($provider) {
    $this->configuration['provider'] = $provider;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getParent() {
    return isset($this->configuration['parent']) ? $this->configuration['parent'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setParent($parent) {
    $this->configuration['parent'] = $parent;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition() {
    return isset($this->configuration['definition']) ? $this->configuration['definition'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefinition($definition) {
    $this->configuration['definition'] = $definition;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations() {
    return isset($this->configuration['operations']) ? $this->configuration['operations'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setOperations($operations) {
    $this->configuration['operations'] = $operations;

    return $this;
  }

}
