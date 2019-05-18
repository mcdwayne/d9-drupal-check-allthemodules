<?php

namespace Drupal\entity_embed_extras\DialogEntityDisplay;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a base Dialog Display Review implementation.
 *
 * @see \Drupal\entity_embed_extras\DialogEntityDisplay\DialogEntityDisplayBase
 * @see \Drupal\entity_embed_extras\DialogEntityDisplay\DialogEntityDisplayBase
 * @see \Drupal\entity_embed_extras\DialogEntityDisplay\DialogEntityDisplayManager
 * @see \Drupal\entity_embed_extras\Annotation\DialogEntityDisplay
 * @see plugin_api
 *
 * @ingroup entity_embed_api
 */
abstract class DialogEntityDisplayBase extends PluginBase implements ContainerFactoryPluginInterface, DialogEntityDisplayInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The context for the plugin.
   *
   * @var array
   */
  public $context = [];

  /**
   * The attributes on the embedded entity.
   *
   * @var array
   */
  public $attributes = [];

  /**
   * Constructs an EntityEmbedDisplayBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * Whether this plugin should display a configuration form.
   *
   * @return bool
   *   A boolean value.
   */
  public function isConfigurable() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormElement(EntityInterface $entity, array &$original_form, FormStateInterface $form_state) {

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
    $this->configuration = NestedArray::mergeDeep(
      $this->defaultConfiguration(),
      $configuration
    );
    return $this;
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
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {
      $this->configuration = array_intersect_key($form_state->getValues(), $this->defaultConfiguration());
    }
  }

  /**
   * Gets a configuration value.
   *
   * @param string $name
   *   The name of the plugin configuration value.
   * @param mixed $default
   *   The default value to return if the configuration value does not exist.
   *
   * @return mixed
   *   The currently set configuration value, or the value of $default if the
   *   configuration value is not set.
   */
  public function getConfigurationValue($name, $default = NULL) {
    $configuration = $this->getConfiguration();
    return array_key_exists($name, $configuration) ? $configuration[$name] : $default;
  }

}
