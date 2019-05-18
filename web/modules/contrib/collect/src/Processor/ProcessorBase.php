<?php
/**
 * @file
 * Contains \Drupal\collect\Processor\ProcessorBase.
 */

namespace Drupal\collect\Processor;

use Drupal\collect\Model\PropertyDefinition;
use Drupal\collect\Model\ModelPluginInterface;
use Drupal\collect\TypedData\TypedDataProvider;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract base class for post-processor plugins.
 *
 * To enable form values to be saved to the parent model entity, plugin
 * implementations should copy values to configuration in
 * ::submitConfigurationForm().
 */
abstract class ProcessorBase extends PluginBase implements ProcessorInterface, ContainerFactoryPluginInterface {

  /**
   * The injected logger.
   *
   * @var \Psr\Log\LoggerInterface;
   */
  protected $logger;

  /**
   * The injected Collect Typed Data provider service.
   *
   * @var \Drupal\collect\TypedData\TypedDataProvider
   */
  protected $typedDataProvider;

  /**
   * Processor settings.
   *
   * @var array
   */
  protected $configuration = array();

  /**
   * Parent model plugin.
   *
   * @var \Drupal\collect\Model\ModelPluginInterface
   */
  protected $modelPlugin;

  /**
   * Constructs a new processor plugin instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, TypedDataProvider $typed_data_provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->typedDataProvider = $typed_data_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('logger.factory')->get('collect'),
      $container->get('collect.typed_data_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return (string) $this->getPluginDefinition()['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return (string) $this->getPluginDefinition()['description'];
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
  public function getConfigurationItem($key) {
    $configuration = $this->getConfiguration();
    return NestedArray::getValue($configuration, (array) $key);
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = [
      'plugin_id' => $this->getPluginId(),
      'weight' => $this->getWeight(),
    ] + $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array();
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
  public function setWeight($weight) {
    $this->configuration['weight'] = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getModelPlugin() {
    if (!isset($this->modelPlugin)) {
      throw new \LogicException('Processor must have its model plugin set when created.');
    }
    return $this->modelPlugin;
  }

  /**
   * {@inheritdoc}
   */
  public function setModelPlugin(ModelPluginInterface $model_plugin) {
    $this->modelPlugin = $model_plugin;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // No validation by default.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // By default, assume that the form structure matches the settings
    // structure.
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * Returns the properties defined for the model.
   *
   * @param string $type
   *   (optional) A data type to filter by. Defaults to NULL, including
   *   definitions of all types.
   *
   * @return array
   *   An associative array of property definitions keyed by name.
   *
   * @todo Access child properties of complex model properties, https://www.drupal.org/node/2454619
   */
  protected function getPropertyDefinitions($type = NULL) {
    if (!$this->getModelPlugin()) {
      return array();
    }

    // @todo Restore static caching of propertyDefinitions in https://www.drupal.org/node/2495039
    $property_definitions = $this->typedDataProvider->createDataDefinition($this->getModelPlugin())->getPropertyDefinitions();

    $filter = function (DataDefinitionInterface $property_definition) use ($type) {
      // Field definitions as properties is a common use case. It returns
      // the unhelpful 'list' for getDataType() regardless of field type, so
      // use the dedicated getType() instead.
      $property_type = $property_definition instanceof FieldDefinitionInterface
        ? $property_definition->getType()
        : $property_definition->getDataType();
      return $property_type == $type;
    };

    return empty($type) ? $property_definitions : array_filter($property_definitions, $filter);
  }

  /**
   * Returns the properties defined for the model, suitable as form options.
   *
   * @param string $type
   *   (optional) A data type to filter by. Defaults to NULL, including
   *   definitions of all types.
   *
   * @return array
   *   An associative array with property names as keys and labels as values.
   */
  protected function getPropertyDefinitionOptions($type = NULL) {
    return array_map(function (DataDefinitionInterface $property_definition) {
      return SafeMarkup::checkPlain($property_definition->getLabel());
    }, $this->getPropertyDefinitions($type));
  }

}
