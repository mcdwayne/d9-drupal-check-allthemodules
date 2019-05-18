<?php

namespace Drupal\ds_chains\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for deriving chained fields.
 */
class ChainsDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new ChainsDeriver object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   Bundle info.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository
   *   Entity display repository.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   */
  public function __construct(EntityFieldManagerInterface $entityFieldManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo, EntityTypeManagerInterface $entityTypeManager, EntityDisplayRepositoryInterface $entityDisplayRepository, LoggerInterface $logger) {
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityDisplayRepository = $entityDisplayRepository;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('logger.factory')->get('ds_chains')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityFieldManager->getFieldMapByFieldType('entity_reference') as $entity_type_id => $fields) {
      $view_modes = $this->entityDisplayRepository->getViewModes($entity_type_id);
      foreach ($fields as $field_name => $details) {
        $field_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
        if (!isset($field_definitions[$field_name])) {
          // Calculated/computed field.
          continue;
        }
        $field_definition = $field_definitions[$field_name];
        $target_type = $field_definition->getSetting('target_type');
        $target_class = $this->entityTypeManager->getDefinition($target_type);
        if (!$target_class->entityClassImplements(ContentEntityInterface::class) || !$target_class->hasViewBuilderClass()) {
          continue;
        }
        foreach ($details['bundles'] as $bundle) {
          $field_instances = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
          if (!isset($field_instances[$field_name])) {
            $this->logger->error('A non-existent config entity name returned by EntityFieldManagerInterface::getFieldMapByFieldType(): field name: %field, bundle: %bundle',
              ['%field' => $field_name, '%bundle' => $bundle]
            );
            continue;
          }
          $field = $field_instances[$field_name];
          $settings = $field->getSetting('handler_settings');
          $target_bundles = isset($settings['target_bundles']) ? $settings['target_bundles'] : array_keys($this->entityTypeBundleInfo->getBundleInfo($target_type));
          foreach ($target_bundles as $target_bundle) {
            $chained_fields = $this->entityFieldManager->getFieldDefinitions($target_type, $target_bundle);
            foreach ($chained_fields as $chained_field_name => $chained_field_definition) {
              if (!$chained_field_definition->isDisplayConfigurable('view')) {
                continue;
              }
              $id = sprintf('%s/%s/%s/%s', $entity_type_id, $bundle, $field_name, $chained_field_name);
              $this->derivatives[$id] = [
                'field_name' => $field_name,
                'field_cardinality' => $field->getFieldStorageDefinition()->getCardinality(),
                'chained_field_name' => $chained_field_name,
                'chained_field_title' => $chained_field_definition->getLabel(),
                'chained_field_type' => $chained_field_definition->getType(),
                'title' => sprintf('%s: %s', $field->getLabel(), $chained_field_definition->getLabel()),
                'bundle' => $bundle,
                'target_bundle' => $target_bundle,
                'target_entity_type' => $target_type,
                'entity_type' => $entity_type_id,
                'view_modes' => $this->getEnabledViewModes($entity_type_id, $bundle, array_merge(['default'], array_keys($view_modes)), $field_name),
              ] + $base_plugin_definition;
            }
          }
        }
      }
    }
    return $this->derivatives;
  }

  /**
   * Get enabled view modes.
   *
   * @param string $entity_type_id
   *   Entity type ID.
   * @param string $bundle_id
   *   Bundle ID.
   * @param array $view_modes
   *   Available view mode IDs for given entity type.
   * @param string $field_name
   *   Field name for the view mode.
   *
   * @return array
   *   Enabled view modes.
   */
  protected function getEnabledViewModes($entity_type_id, $bundle_id, array $view_modes, $field_name) {
    $display_ids = array_map(function ($view_mode) use ($entity_type_id, $bundle_id) {
      return "$entity_type_id.$bundle_id.$view_mode";
    }, $view_modes);
    $enabled_displays = array_filter($this->entityTypeManager->getStorage('entity_view_display')->loadMultiple($display_ids), function (EntityViewDisplayInterface $display) use ($field_name) {
      return $display->status() && in_array($field_name, $display->getThirdPartySetting('ds_chains', 'fields', []), TRUE);
    });
    return array_values(array_map(function (EntityViewDisplayInterface $display) {
      return $display->getMode();
    }, $enabled_displays));
  }

}
