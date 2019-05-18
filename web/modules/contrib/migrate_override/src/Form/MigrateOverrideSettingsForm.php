<?php

namespace Drupal\migrate_override\Form;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\migrate_override\OverrideManagerServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;

/**
 * Class MigrateOverrideSettingsForm.
 */
class MigrateOverrideSettingsForm extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Drupal\Core\Entity\EntityFieldManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The override manager service.
   *
   * @var \Drupal\migrate_override\OverrideManagerServiceInterface
   */
  protected $overrideManager;

  /**
   * Constructs a new MigrateOverrideSettingsForm object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
      EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    EntityFieldManagerInterface $entity_field_manager,
    OverrideManagerServiceInterface $override_manager
    ) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
    $this->overrideManager = $override_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
            $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager'),
      $container->get('migrate_override.override_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'migrate_override.migrateoverridesettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_override_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('migrate_override.migrateoverridesettings');

    $entity_types = $this->getContentEntityTypes();

    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    foreach ($entity_types as $entity_type) {
      $type_id = $entity_type->id();
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($type_id);
      $form['#tree'] = TRUE;
      if ($bundles) {
        $form[$type_id] = [
          '#type' => 'fieldset',
          '#title' => $entity_type->getLabel() . " Entity Type",
        ];
        foreach ($bundles as $bundle_id => $bundle_info) {
          $fields = $this->entityFieldManager->getFieldDefinitions($type_id, $bundle_id);
          if ($fields) {
            $form[$type_id][$bundle_id]['migrate_override_enabled'] = [
              '#type' => 'checkbox',
              '#title' => "Enable for " . $bundle_info['label'],
              '#default_value' => $config->get("entities.$type_id.$bundle_id.migrate_override_enabled"),
            ];
            $form[$type_id][$bundle_id]['fields'] = [
              '#type' => 'details',
              '#title' => $bundle_info['label'],
              '#open' => $config->get("entities.$type_id.$bundle_id.migrate_override_enabled"),
            ];
            foreach ($fields as $field_name => $field_definition) {
              $form[$type_id][$bundle_id]['fields'][$field_name] = [
                '#type' => 'select',
                '#options' => [
                  OverrideManagerServiceInterface::FIELD_IGNORED => 'Ignore this field',
                  OverrideManagerServiceInterface::FIELD_LOCKED => 'Prevent this field from being overwritten',
                  OverrideManagerServiceInterface::FIELD_OVERRIDEABLE => 'Allow this field to be overridden',
                ],
                '#default_value' => $config->get("entities.$type_id.$bundle_id.fields.$field_name"),
                '#title' => $field_definition->getLabel(),
              ];
            }
          }
        }
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('migrate_override.migrateoverridesettings');
    $entity_types = $this->getContentEntityTypes();

    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    foreach ($entity_types as $entity_type) {
      $type_id = $entity_type->id();
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($type_id);
      if ($bundles) {
        foreach ($bundles as $bundle_id => $bundle_info) {
          $enabled = $form_state->getValue([
            $type_id,
            $bundle_id,
            'migrate_override_enabled',
          ]);
          $config->set("entities.$type_id.$bundle_id.migrate_override_enabled", $enabled);
          if ($enabled) {
            if (!$this->overrideManager->entityBundleHasField($type_id, $bundle_id)) {
              $this->overrideManager->createBundleField($type_id, $bundle_id);
            }
            $fields = $this->entityFieldManager->getFieldDefinitions($type_id, $bundle_id);
            if ($fields) {
              foreach ($fields as $field_name => $field_definition) {
                $config->set("entities.$type_id.$bundle_id.fields.$field_name", $form_state->getValue([
                  $type_id,
                  $bundle_id,
                  'fields',
                  $field_name,
                ]));
              }
            }
          }
          else {
            if ($this->overrideManager->entityBundleHasField($type_id, $bundle_id)) {
              $this->overrideManager->deleteBundleField($type_id, $bundle_id);
            }
          }
        }
      }
    }

    $config->save();
  }

  /**
   * Returns all content entity type definitions.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   An array of content entity types.
   */
  protected function getContentEntityTypes() {
    $all_entity_types = $this->entityTypeManager->getDefinitions();

    $content_entity_types = array_filter(
      $all_entity_types,
      function ($entity_type) {
        return $entity_type instanceof ContentEntityType;
      }
    );
    unset($content_entity_types['migration_data']);

    return $content_entity_types;
  }

}
