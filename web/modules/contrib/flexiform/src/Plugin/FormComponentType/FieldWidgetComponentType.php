<?php

namespace Drupal\flexiform\Plugin\FormComponentType;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Entity\EntityDisplayBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\WidgetPluginManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\field_ui\Form\EntityDisplayFormBase;
use Drupal\flexiform\FormComponent\FormComponentTypeBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for field widget component types.
 *
 * @FormComponentType(
 *   id = "field_widget",
 *   label = @Translation("Field Widget"),
 *   component_class = "Drupal\flexiform\Plugin\FormComponentType\FieldWidgetComponent",
 * )
 */
class FieldWidgetComponentType extends FormComponentTypeBase implements ContainerFactoryPluginInterface {

  /**
   * The widget plugin manager.
   *
   * @var \Drupal\Core\Field\WidgetPluginManager
   */
  protected $pluginManager;

  /**
   * Field type definitions.
   *
   * @var array
   */
  protected $fieldTypes;

  /**
   * Field defintiions.
   *
   * @var array
   */
  protected $fieldDefinitions;

  /**
   * The entity field manager.
   *
   * @var array
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.field.widget'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Construct a new FieldWidgetComponentType object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\WidgetPluginManager $plugin_manager
   *   The plugin type manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WidgetPluginManager $plugin_manager, FieldTypePluginManagerInterface $field_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->fieldTypes = $field_type_manager->getDefinitions();
    $this->pluginManager = $plugin_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Get the field definitions.
   */
  protected function getFieldDefinitions() {
    if (empty($this->fieldDefinitions)) {
      foreach ($this->getFormEntityManager()->getFormEntities() as $namespace => $form_entity) {
        foreach ($this->entityFieldManager->getFieldDefinitions($form_entity->getEntityType(), $form_entity->getBundle()) as $field_name => $field_definition) {
          if (($this->getFormDisplay()->getMode() === EntityDisplayBase::CUSTOM_MODE) || ($field_definition->getDisplayOptions('form'))) {
            // Give field definitions a clone for form entities so that
            // overrides don't copy accross two different fields.
            $component_name = !empty($namespace) ? "{$namespace}:{$field_name}" : $field_name;
            $this->fieldDefinitions[$component_name] = clone $field_definition;

            // Apply overrides.
            $component_options = $this->getFormDisplay()->getComponent($component_name);
            if (!empty($component_options['third_party_settings']['flexiform']['field_definition'])) {
              $def_overrides = $component_options['third_party_settings']['flexiform']['field_definition'];
              if (!empty($def_overrides['label'])) {
                $this->fieldDefinitions[$component_name]->setLabel($def_overrides['label']);
              }
              if (!empty($def_overrides['settings'])) {
                $settings = $this->fieldDefinitions[$component_name]->getSettings();
                $settings = NestedArray::mergeDeep($settings, $def_overrides['settings']);
                $this->fieldDefinitions[$component_name]->setSettings($settings);
              }
            }
          }
        }
      }
    }

    return $this->fieldDefinitions;
  }

  /**
   * Get a field definition.
   */
  protected function getFieldDefinition($component_name) {
    $defs = $this->getFieldDefinitions();
    return !empty($defs[$component_name]) ? $defs[$component_name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getComponent($name, array $options = []) {
    $component = parent::getComponent($name, $options);
    if ($field_definition = $this->getFieldDefinition($name)) {
      $component->setFieldDefinition($this->getFieldDefinition($name));
    }
    return $component;
  }

  /**
   * {@inheritdoc}
   */
  public function componentRows(EntityDisplayFormBase $form_object, array $form, FormStateInterface $form_state) {
    $rows = [];
    foreach ($this->getFieldDefinitions() as $component_name => $field_definition) {
      if ($field_definition->isDisplayConfigurable('form')) {
        $rows[$component_name] = $this->buildComponentRow($form_object, $component_name, $form, $form_state);
      }
    }

    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  protected function getApplicableRendererPluginOptions($component_name) {
    $field_definition = $this->getFieldDefinition($component_name);
    if (!$field_definition) {
      print "NAME: " . $component_name;
    }
    $options = $this->pluginManager->getOptions($field_definition->getType());
    $applicable_options = [];

    foreach ($options as $option => $label) {
      $plugin_class = DefaultFactory::getPluginClass($option, $this->pluginManager->getDefinition($option));
      if ($plugin_class::isApplicable($field_definition)) {
        $applicable_options[$option] = $label;
      }
    }
    return $applicable_options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultRendererPlugin($component_name) {
    $type = $this->getFieldDefinition($component_name)->getType();
    return isset($this->fieldTypes[$type]['default_widget']) ? $this->fieldTypes[$type]['default_widget'] : NULL;
  }

}
