<?php

namespace Drupal\integro\Plugin\Integro\Operation;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\integro\OperationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an integration operations.
 */
abstract class OperationBase extends PluginBase implements ContainerFactoryPluginInterface, OperationInterface {

  use StringTranslationTrait;

  /**
   * Constructs a new object.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TranslationInterface $string_translation) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      'definition' => [],
      'arguments' => [],
      'connector' => NULL,
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
  public function execute() {
    // To be implemented in descendants.
    return [];
  }

}
