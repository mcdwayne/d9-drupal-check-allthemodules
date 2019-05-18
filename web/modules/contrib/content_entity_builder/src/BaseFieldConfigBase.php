<?php

namespace Drupal\content_entity_builder;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\content_entity_builder\ContentTypeInterface;

/**
 * Provides a base class for tabs.
 *
 * @see \Drupal\content_entity_builder\Annotation\BaseFieldConfig
 * @see \Drupal\content_entity_builder\BaseFieldConfigInterface
 * @see \Drupal\content_entity_builder\BaseFieldConfigManager
 * @see plugin_api
 */
abstract class BaseFieldConfigBase extends ContextAwarePluginBase implements BaseFieldConfigInterface, ContainerFactoryPluginInterface {

  /**
   * The field name.
   *
   * @var string
   */
  protected $field_name;

  /**
   * The field label.
   *
   * @var string
   */
  protected $label;

  /**
   * The field label.
   *
   * @var string
   */
  protected $field_type;

  /**
   * The field description.
   *
   * @var string
   */
  protected $description = '';

  /**
   * Field-type specific settings.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * Default field value.
   *
   * @var string
   */
  protected $default_value;

  /**
   * Flag indicating whether the field is required.
   *
   * @var bool
   */
  protected $required = FALSE;

  /**
   * Flag indicating whether applied updates this field's schema to database.
   *
   * @var bool
   */
  protected $applied = FALSE;
  
  /**
   * Flag indicating whether add db index for this base field.
   *
   * @var bool
   */
  protected $index = FALSE;  

  /**
   * The weight of the tab.
   *
   * @var int|string
   */
  protected $weight = '';

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('content_entity_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeExtension($extension) {
    // Most tabs will not change the extension. This base
    // implementation represents this behavior. Override this method if your
    // tab does change the extension.
    return $extension;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldName() {
    return $this->field_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldType() {
    return $this->field_type;
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldType($field_type) {
    $this->field_type = $field_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label();
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    $this->settings = $settings + $this->settings;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key) {
    $settings = $this->settings;
    return isset($settings[$key]) ? $settings[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isRequired() {
    return $this->required;
  }

  /**
   * {@inheritdoc}
   */
  public function setRequired($required) {
    $this->required = $required;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isApplied() {
    return $this->applied;
  }

  /**
   * {@inheritdoc}
   */
  public function setApplied($applied) {
    $this->applied = $applied;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasIndex() {
    return $this->index;
  }

  /**
   * {@inheritdoc}
   */
  public function setIndex($index) {
    $this->index = $index;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultValue() {
    return $this->default_value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue($value) {
    $this->default_value = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'field_name' => $this->getFieldName(),
      'id' => $this->getPluginId(),
      'field_type' => $this->getFieldType(),
      'label' => $this->getLabel(),
      'description' => $this->getDescription(),
      'default_value' => $this->getDefaultValue(),
      'required' => $this->isRequired(),
      'applied' => $this->isApplied(),
      'index' => $this->hasIndex(),  
      'weight' => $this->getWeight(),
      'settings' => $this->configuration,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $configuration += [
      'settings' => [],
      'field_name' => '',
      'field_type' => '',
      'label' => '',
      'description' => '',
      'default_value' => '',
      'weight'  => '',
      'required' => FALSE,
      'applied' => FALSE,
      'index' => FALSE,	  
    ];
    $this->configuration = $configuration['settings'] + $this->defaultConfiguration();
    $this->field_name = $configuration['field_name'];
    $this->field_type = $configuration['field_type'];
    $this->label = $configuration['label'];
    $this->description = $configuration['description'];
    $this->default_value = $configuration['default_value'];
    $this->required = $configuration['required'];
    $this->applied = $configuration['applied'];
    $this->index = $configuration['index'];	
    $this->weight = $configuration['weight'];
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
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function addBaseField(ContentTypeInterface $content_type) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildBaseFieldDefinition() {
    $field_type = $this->getFieldType();
    $label = $this->getLabel();
    $base_field_definition = BaseFieldDefinition::create($field_type)
      ->setLabel($label)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $base_field_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function exportCode() {
    return '';
  }
  
}
