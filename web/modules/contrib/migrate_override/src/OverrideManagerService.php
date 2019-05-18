<?php

namespace Drupal\migrate_override;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Class OverrideManagerService.
 */
class OverrideManagerService implements OverrideManagerServiceInterface {

  /**
   * The field name to use to store data.
   */
  const FIELD_NAME = 'migrate_override_data';

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Entity\EntityFieldManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Entity Display Repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a new OverrideManagerService object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository) {
    $this->configFactory = $config_factory;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function bundleEnabled($entity_type_id, $bundle) {
    $config = $this->getConfig();
    $enabled = $config->get("entities.$entity_type_id.$bundle.migrate_override_enabled");
    if (empty($enabled)) {
      $enabled = FALSE;
    }
    return $enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function entityBundleEnabled(ContentEntityInterface $entity) {
    return $this->bundleEnabled($entity->getEntityTypeId(), $entity->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function fieldInstanceSetting($entity_type, $bundle, $field) {
    $config = $this->getConfig();
    $status = $config->get("entities.$entity_type.$bundle.fields.$field");
    if (empty($status)) {
      $status = OverrideManagerServiceInterface::FIELD_IGNORED;
    }
    return $status;
  }

  /**
   * {@inheritdoc}
   */
  public function entityFieldInstanceSetting(ContentEntityInterface $entity, $field) {
    if ($field instanceof FieldDefinitionInterface) {
      $field = $field->getName();
    }
    return $this->fieldInstanceSetting($entity->getEntityTypeId(), $entity->bundle(), $field);
  }

  /**
   * {@inheritdoc}
   */
  public function entityBundleHasField($entity_type_id, $bundle) {
    if (!$this->entityHasFieldStorage($entity_type_id)) {
      return FALSE;
    }
    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
    return isset($fields[static::FIELD_NAME]);
  }

  /**
   * {@inheritdoc}
   */
  public function createBundleField($entity_type_id, $bundle) {
    if ($this->entityBundleHasField($entity_type_id, $bundle)) {
      return FieldConfig::loadByName($entity_type_id, $bundle, static::FIELD_NAME);
    }
    $field_storage = $this->createFieldStorage($entity_type_id);
    $field = $this->entityTypeManager->getStorage('field_config')->create([
      'field_storage' => $field_storage,
      'bundle' => $bundle,
      'label' => 'Select Fields to override:',
      'settings' => [],
    ]);
    $field->save();
    $form_modes = $this->entityDisplayRepository->getFormModeOptionsByBundle($entity_type_id, $bundle);
    foreach (array_keys($form_modes) as $mode) {
      /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_mode */
      $form_mode = $this->entityTypeManager->getStorage('entity_form_display')->load($entity_type_id . '.' . $bundle . '.' . $mode);
      $form_mode->setComponent(static::FIELD_NAME, [
        'type' => 'override_widget_default',
      ]);
      $form_mode->save();

    }
    $view_modes = $this->entityDisplayRepository->getViewModeOptionsByBundle($entity_type_id, $bundle);
    foreach (array_keys($view_modes) as $mode) {
      /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $view_mode */
      $view_mode = $this->entityTypeManager->getStorage('entity_view_display')->load($entity_type_id . '.' . $bundle . '.' . $mode);
      $view_mode->setComponent(static::FIELD_NAME, [
        'region' => 'hidden',
      ]);
      $view_mode->save();
    }
    return $field;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteBundleField($entity_type_id, $bundle) {
    if (!$this->entityBundleHasField($entity_type_id, $bundle)) {
      // Nothing to do.
      return;
    }
    $config = FieldConfig::loadByName($entity_type_id, $bundle, static::FIELD_NAME);
    $config->delete();

  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getEntityFieldStatus(ContentEntityInterface $entity, $field_name, $default = OverrideManagerServiceInterface::ENTITY_FIELD_LOCKED) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $field_data */
    $field_data = $entity->get(static::FIELD_NAME);
    if ($field_data->isEmpty()) {
      return $default;
    }
    $data = $field_data->value;
    if (!is_array($data)) {
      $data = unserialize($data);
    }
    if (!isset($data[$field_name])) {
      return $default;
    }
    return $data[$field_name];
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityFieldStatus(ContentEntityInterface $entity, $field_name, $status) {
    $field_data = $entity->get(static::FIELD_NAME);
    if ($field_data->isEmpty()) {
      $data = [];
    }
    else {
      $data = $field_data->value;
      if (!is_array($data)) {
        $data = unserialize($data);
      }
    }
    $data[$field_name] = $status;
    $entity->set(static::FIELD_NAME, [['value' => serialize($data)]]);
  }

  /**
   * {@inheritdoc}
   */
  public function getOverridableEntityFields(ContentEntityInterface $entity) {
    return $this->getOverridableFields($entity->getEntityTypeId(), $entity->bundle());
  }

  /**
   * Determines if the entity field storage exists.
   *
   * @param string $entity_type_id
   *   The entity type.
   *
   * @return bool
   *   True if storage exists.
   */
  protected function entityHasFieldStorage($entity_type_id) {
    $fields = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
    return isset($fields[static::FIELD_NAME]);
  }

  /**
   * Creates field storage if it doesn't exist.
   *
   * @param string $entity_type_id
   *   The entity type id.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\field\Entity\FieldStorageConfig
   *   The storage.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createFieldStorage($entity_type_id) {
    if ($this->entityHasFieldStorage($entity_type_id)) {
      $storage = FieldStorageConfig::loadByName($entity_type_id, static::FIELD_NAME);
      return $storage;
    }
    $storage = FieldStorageConfig::create([
      'field_name' => static::FIELD_NAME,
      'entity_type' => $entity_type_id,
      'type' => 'migrate_override_field_item',
    ]);
    $storage->save();
    return $storage;
  }

  /**
   * Returns a field options list for given bundle.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle id.
   *
   * @return array
   *   The options list.
   */
  protected function getOverridableFields($entity_type_id, $bundle) {
    if (!$this->bundleEnabled($entity_type_id, $bundle)) {
      return [];
    }
    $config = $this->getConfig();
    $fields = $config->get("entities.$entity_type_id.$bundle.fields");
    $options = [];
    foreach ($fields as $field_name => $setting) {
      if ((int) $setting === OverrideManagerServiceInterface::FIELD_OVERRIDEABLE) {
        $options[$field_name] = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id)[$field_name]->getLabel();
      }
    }
    return $options;
  }

  /**
   * Refreshes the config file.
   */
  protected function getConfig() {
    return $this->configFactory->get('migrate_override.migrateoverridesettings');
  }

}
