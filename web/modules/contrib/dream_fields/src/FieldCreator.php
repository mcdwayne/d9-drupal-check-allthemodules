<?php

namespace Drupal\dream_fields;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Turn the field builder into an actual field.
 */
class FieldCreator implements FieldCreatorInterface {

  /**
   * The field config.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fieldConfig;

  /**
   * The field storage config.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fieldStorageConfig;

  /**
   * The entity form display.
   *
   * @var \Drupal\Core\Entity\Display\EntityDisplayInterface
   */
  protected $entityFormDisplay;

  /**
   * The entity view display.
   *
   * @var \Drupal\Core\Entity\Display\EntityDisplayInterface
   */
  protected $entityViewDisplay;

  /**
   * A service to assist in machine name generation.
   *
   * @var \Drupal\dream_fields\MachineNameGeneratorInterface
   */
  protected $machineNameGenerator;

  /**
   * The field UI config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $fieldUiSettings;

  /**
   * {@inheritdoc}
   */
  public function save(FieldBuilderInterface $field_builder) {
    $field_name = $this->machineNameGenerator->getMachineName($this->fieldUiSettings->get('field_prefix') . $field_builder->getLabel());

    $this->fieldStorageConfig->create([
      'type' => $field_builder->getFieldType(),
      'field_name' => $field_name,
      'entity_type' => $field_builder->getEntityType(),
      'cardinality' => $field_builder->getCardinality(),
      'bundle' => $field_builder->getBundle(),
      'settings' => $field_builder->getFieldStorageSettings(),
    ])->save();
    $this->fieldConfig->create([
      'label' => $field_builder->getLabel(),
      'type' => $field_builder->getFieldType(),
      'field_name' => $field_name,
      'entity_type' => $field_builder->getEntityType(),
      'bundle' => $field_builder->getBundle(),
      'settings' => $field_builder->getFieldSettings(),
    ])->save();

    if (!empty($field_builder->getWidget())) {
      $this->getEntityFormDisplay($field_builder->getEntityType(), $field_builder->getBundle())
        ->setComponent($field_name, [
          'type' => $field_builder->getWidget(),
          'settings' => $field_builder->getWidgetSettings(),
        ])
        ->save();
    }
    if (!empty($field_builder->getDisplayFormatter())) {
      $this->getEntityViewDisplay($field_builder->getEntityType(), $field_builder->getBundle())
        ->setComponent($field_name, [
          'type' => $field_builder->getDisplayFormatter(),
          'label' => $field_builder->getLabelDisplay(),
          'settings' => $field_builder->getDisplaySettings(),
        ])
        ->save();
    }
  }

  /**
   * Get an existing or create an entity form display.
   *
   * @see entity_get_form_display
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param string $display
   *   The display.
   *
   * @return \Drupal\Core\Entity\Entity\EntityFormDisplay
   *   The entity view display.
   */
  protected function getEntityFormDisplay($entity_type, $bundle, $display = 'default') {
    $entity_form_display = $this->entityFormDisplay->load($entity_type . '.' . $bundle . '.' . $display);
    if (!$entity_form_display) {
      $entity_form_display = $this->entityFormDisplay->create([
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => $display,
        'status' => TRUE,
      ]);
    }
    return $entity_form_display;
  }

  /**
   * Get an existing or create an entity view display.
   *
   * @see entity_get_display
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param string $display
   *   The display.
   *
   * @return \Drupal\Core\Entity\Entity\EntityViewDisplay
   *   The entity view display.
   */
  protected function getEntityViewDisplay($entity_type, $bundle, $view_mode = 'default') {
    $display = $this->entityViewDisplay->load($entity_type . '.' . $bundle . '.' . $view_mode);
    if (!$display) {
      $display = $this->entityViewDisplay->create([
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => $view_mode,
        'status' => TRUE,
      ]);
    }
    return $display;
  }

  /**
   * {@inheritdoc}
   */
  public static function createBuilder() {
    return new FieldBuilder();
  }

  /**
   * Create and instance of the FieldCreationManager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\dream_fields\MachineNameGeneratorInterface $machine_name_generator
   *   The machine name generator.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *  The field UI config.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MachineNameGeneratorInterface $machine_name_generator, ConfigFactoryInterface $config_factory) {
    $this->fieldConfig = $entity_type_manager->getStorage('field_config');
    $this->fieldStorageConfig = $entity_type_manager->getStorage('field_storage_config');
    $this->entityViewDisplay = $entity_type_manager->getStorage('entity_view_display');
    $this->entityFormDisplay = $entity_type_manager->getStorage('entity_form_display');
    $this->machineNameGenerator = $machine_name_generator;
    $this->fieldUiSettings = $config_factory->get('field_ui.settings');
  }

}
