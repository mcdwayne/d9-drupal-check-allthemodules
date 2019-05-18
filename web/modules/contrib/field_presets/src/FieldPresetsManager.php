<?php
/**
 * @file
 * Contains FieldPresetsManager.
 */

namespace Drupal\field_presets;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Field presets manager.
 */
class FieldPresetsManager extends DefaultPluginManager {
  use StringTranslationTrait;

  /**
   * Entity manager.
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  protected $defaults = [
    'id' => '',
    // Optional prefix to add into field machine name,
    'machine_prefix' => '',
    'label' => '',
    'weight' => 0,
    'storage' => [
      'type' => '',
      'module' => '',
      'cardinality' => 1,
      'locked' => FALSE,
      'indexes' => [],
      'settings' => [],
    ],
    'instance' => [
      'required' => FALSE,
      'default_value' => [],
      'settings' => [],
    ],
    'widget' => [
      'weight' => 49,
      'settings' => [],
      'third_party_settings' => [],
      'type' => '',
    ],
    'formatter' => [
      'weight' => 0,
      'label' => 'above',
      'settings' => [],
      'third_party_settings' => [],
      'type' => '',
    ],
  ];

  /**
   * Constructs a FieldPresetsManager object.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend, TranslationInterface $string_translation, EntityManagerInterface $entity_manager) {
    $this->factory = new ContainerFactory($this);
    $this->moduleHandler = $module_handler;
    $this->setStringTranslation($string_translation);
    $this->entityManager = $entity_manager;
    $this->alterInfo('field_presets');
    $this->setCacheBackend($cache_backend, 'field_presets', ['field_presets']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('field_presets', $this->moduleHandler->getModuleDirectories());
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
  }

  /**
   * Get the machine name including the prefix.
   */
  private function getMachineName($plugin_id, $field_name) {
    $definition = $this->getDefinition($plugin_id);

    if (!empty($definition['machine_prefix'])) {
      $field_name = 'field_' . $definition['machine_prefix'] . '_' . $field_name;
    }
    else {
      $field_name = 'field_' . $field_name;
    }

    return $field_name;
  }

  /**
   * Check machine name validity.
   */
  private function machineNameIsValid($machine_name) {
    if (strlen($machine_name) > 32) {
      return "Too long.";
    }

    return TRUE;
  }

  /**
   * Create the field.
   */
  public function createField($plugin_id, $field_name, $bundle_entity_type, $bundle, $entity_type_id, $label, $description) {
    $definition = $this->getDefinition($plugin_id);

    $machine_name = $this->getMachineName($plugin_id, $field_name);
    $machine_name_valid = $this->machineNameIsValid($machine_name);
    if ($machine_name_valid !== TRUE) {
      return $this->t('Machine name is not valid: ' . $machine_name_valid);
    }

    $bundle_entity = $this->entityManager->getStorage($bundle_entity_type)->load($bundle);

    $field_storage_config = \Drupal\field\Entity\FieldStorageConfig::loadByName($entity_type_id, $machine_name);

    if ($field_storage_config !== NULL) {
      return $this->t("Field with this machine name already exists.");
    }

    // Field storage handling.
    $field_storage = $definition['storage'] + [
      'field_name' => $machine_name,
      'entity_type' => $entity_type_id,
    ];

    try {
      $field_storage_config = $this->entityManager->getStorage('field_storage_config')->create($field_storage);
      $field_storage_config->save();
    }
    catch (Exception $e) {
      return $this->t("Exception on field storage creation: " . $e->getMessage());
    }

    // Field instance handling.
    $field_instance = $definition['instance'] + [
      'field_name' => $machine_name,
      'entity_type' => $entity_type_id,
      'bundle' => $bundle,
      'label' => $label,
      'description' => $description,
    ];

    try {
      $field = $this->entityManager->getStorage('field_config')->create($field_instance);
      $field->save();
    }
    catch (Exception $e) {
      // Clean up from before.
      $field_storage_config->delete();
      return $this->t("Exception on field instance creation: " . $e->getMessage());
    }

    // Form displays (widgets).
    $this->setEntityDisplayComponent($entity_type_id, $bundle, $this->entityManager->getFormModes($entity_type_id), 'entity_form_display', $machine_name, $definition['widget']);

    // View displays (formatters).
    $this->setEntityDisplayComponent($entity_type_id, $bundle, $this->entityManager->getViewModes($entity_type_id), 'entity_view_display', $machine_name, $definition['formatter']);

    return TRUE;
  }

  /**
   * Set component on an entity display.
   */
  private function setEntityDisplayComponent($entity_type_id, $bundle, $all_form_modes, $storage, $field_name, $component_options) {
    $valid_modes = ['default'];
    foreach (array_keys($all_form_modes) as $form_mode) {
      $entity_display = $this->entityManager->getStorage($storage)->load($entity_type_id . '.' . $bundle . '.' . $form_mode);
      if ($entity_display !== NULL && $form_mode !== 'default' && $entity_display->get('id') !== NULL) {
        array_push($valid_modes, $form_mode);
      }
    }

    foreach ($valid_modes as $form_mode) {
      $entity_display = $this->entityManager->getStorage($storage)->load($entity_type_id . '.' . $bundle . '.' . $form_mode);
      if (!$entity_display) {
        $entity_display = $this->entityManager->getStorage($storage)->create([
          'targetEntityType' => $entity_type_id,
          'bundle' => $bundle,
          'mode' => $form_mode,
          'status' => TRUE,
        ]);
      }
      $entity_display->setComponent($field_name, $component_options);
      $entity_display->save();
    }
  }

}
