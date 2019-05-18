<?php

namespace Drupal\integro\Plugin\Integro\Integration;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\integro\DefinitionInterface;
use Drupal\integro\Entity\ConnectorInterface;
use Drupal\integro\IntegrationInterface;
use Drupal\integro\OperationManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an integration.
 */
abstract class IntegrationBase extends PluginBase implements ContainerFactoryPluginInterface, IntegrationInterface {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * Definition of integration.
   *
   * @var \Drupal\integro\DefinitionInterface
   */
  protected $definition;

  /**
   * Operation manager.
   *
   * @var \Drupal\integro\OperationManagerInterface
   */
  protected $operationManager;

  /**
   * Constructs a new instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\integro\OperationManagerInterface $operation_manager
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, OperationManagerInterface $operation_manager, TranslationInterface $string_translation) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->operationManager = $operation_manager;
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
      $container->get('integro_operation.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition() {
    return $this->definition;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefinition(DefinitionInterface $definition) {
    $this->definition = $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function operation(ConnectorInterface $connector, $operation, array $args = []) {
    $operation_definition = $this->definition->getConfiguration()['operations'][$operation];
    $operation_configuration = [
      'id' => $operation,
      'definition' => $operation_definition,
      'arguments' => $args,
      'connector' => $connector,
    ];
    $operation_plugin = $this->operationManager->createInstance($operation_definition['type'], $operation_configuration);
    return $operation_plugin;
  }

}
