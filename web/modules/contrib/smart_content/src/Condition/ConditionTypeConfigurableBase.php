<?php

namespace Drupal\smart_content\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\smart_content\ConditionType\ConditionTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class ConditionTypeConfigurableBase extends ConditionBase implements PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\smart_content\ConditionType\ConditionTypeInterface
   */
  protected $conditionType;

  /**
   * ConditionTypeConfigurableBase constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\smart_content\ConditionType\ConditionTypeManager $condition_type_manager
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConditionTypeManager $condition_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $configuration['conditions_type_settings'] = isset($configuration['conditions_type_settings']) ? $configuration['conditions_type_settings'] : [];
    $this->conditionType = $condition_type_manager->createInstance($plugin_definition['type'], $configuration['conditions_type_settings']);
    // Add instance of condition to conditionType for context.
    $this->conditionType->conditionInstance = $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.smart_content.condition_type')
    );
  }

  /**
   * @inheritdoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $this->conditionType->buildConfigurationForm($form, $form_state);
  }

  /**
   * @inheritdoc
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->conditionType->validateConfigurationForm($form, $form_state);
  }

  /**
   * @inheritdoc
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->conditionType->submitConfigurationForm($form, $form_state);

  }

  /**
   * @inheritdoc
   */
  public function writeChangesToConfiguration() {
    $configuration = $this->getConfiguration();
    $configuration['conditions_type_settings'] = $this->conditionType->getConfiguration();
    $this->setConfiguration($configuration);
  }

  /**
   * @inheritdoc
   */
  public function getLibraries() {
    return $this->getConditionType()->getLibraries();
  }

  /**
   * @inheritdoc
   */
  public function getAttachedSettings() {
    $settings = parent::getAttachedSettings();
    // Add the 'type' from the ConditionType Plugin.
    $settings['field']['type'] = $this->getConditionType()->getPluginDefinition()['id'];
    // Add the field 'settings' from the ConditionType Plugin.
    $settings['field']['settings'] = $this->getConditionType()->getFieldAttachedSettings();
    // Get the 'settings' from the ConditionType Plugin.
    $settings['settings'] = $this->getConditionType()->getAttachedSettings();
    return $settings;
  }

  /**
   * Helper function to return condition type.
   *
   * @return \Drupal\smart_content\ConditionType\ConditionTypeInterface|object
   */
  public function getConditionType() {
    return $this->conditionType;
  }

}
