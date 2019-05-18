<?php

namespace Drupal\reference_map\Plugin;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Field\PluginSettingsBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Reference Map Type plugins.
 */
abstract class ReferenceMapTypeBase extends PluginSettingsBase implements ReferenceMapTypeInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The Reference Map Config entity.
   *
   * @var \Drupal\reference_map\Entity\ReferenceMapConfigInterface
   */
  protected $map = NULL;

  /**
   * The Entity Field Manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Entity Type Bundle Info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The Database Connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, LoggerInterface $logger, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->logger = $logger;
    $this->connection = $connection;

    if (!empty($configuration['config_entity'])) {
      $this->map = $configuration['config_entity'];
      $this->setSettings($this->map->settings);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('logger.factory')->get('reference_map'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function follow(ContentEntityInterface $entity, $exception_on_invalid = TRUE, $start = 0, $end = NULL) {
    // Return an empty array if the reference map config isn't valid.
    if (!$this->validate($exception_on_invalid)) {
      return [];
    }

    $map = $this->getMap($start, $end);

    // Return an empty array if the passed in entity isn't compatible with the
    // first step of the map.
    $first_step = current($map);
    $entity_type_id = $entity->getEntityTypeId();
    $entity_type_check = $entity_type_id === $first_step['entity_type'];
    $bundle_check = empty($first_step['bundles']) || in_array($entity->bundle(), $first_step['bundles']);
    $field_check = $entity->hasField($first_step['field_name']);
    if (!$entity_type_check || !$bundle_check || !$field_check) {
      if (!$entity_type_check) {
        $this->logger->error($this->t("The map %map doesn't apply to %entity_type %entity.", [
          '%map' => $this->map->id,
          '%entity_type' => $entity_type_id,
          '%entity' => $entity->label(),
        ]));
      }

      return [];
    }

    // Assemble the entity information from the passed in entity for the first
    // step.
    $entity_info = [
      'entity_type' => $entity_type_id,
      'entity_type_label' => $entity->getEntityType()->getLabel(),
      'bundles' => [$entity->bundle()],
      'field_definitions' => $this->entityFieldManager->getFieldStorageDefinitions($entity->getEntityTypeId()),
    ];

    $last_index = count($map) - 1;
    foreach ($map as $index => $step) {
      // Exit if we've reached the last step as there is nothing to do.
      if ($index === $last_index) {
        break;
      }

      // Assemble the entity information for the next step.
      $next_entity_type = $this->entityTypeManager
        ->getDefinition($entity_info['field_definitions'][$step['field_name']]->get('settings')['target_type']);
      $next_entity_info = [
        'entity_type' => $next_entity_type->id(),
        'entity_type_label' => $next_entity_type->getLabel(),
        'field_definitions' => $this->entityFieldManager->getFieldStorageDefinitions($next_entity_type->id()),
        'bundles' => array_keys($this->entityTypeBundleInfo->getBundleInfo($next_entity_type->id())),
      ];

      $next_step = $map[$index + 1];

      // If this is the first iteration, use the passed in entity to get values
      // from for the field as the entity may be new so no data would have been
      // saved to the database yet.
      if ($index === 0) {
        $next_entity_info['entity_ids'] = array_column($entity->{$step['field_name']}->getValue(), 'target_id');

        // Filter the entity_ids by bundle, if necessary.
        if (!empty($next_entity_info['entity_ids']) && !empty($next_step['bundles'])) {
          $id_column = $next_entity_type->getKey('id');
          $bundles = array_intersect($next_entity_info['bundles'], $next_step['bundles']);
          $next_entity_info['entity_ids'] = $this
            ->connection
            ->select($next_entity_type->getBaseTable(), 'bt')
            ->fields('bt', [$id_column])
            ->condition($id_column, $next_entity_info['entity_ids'], 'IN')
            ->condition($next_entity_type->getKey('bundle'), $bundles, 'IN')
            ->execute()
            ->fetchCol();
        }
      }
      else {
        $target_id_field = "{$step['field_name']}_target_id";

        $query = $this
          ->connection
          ->select("{$entity_info['entity_type']}__{$step['field_name']}", 'ef')
          ->distinct()
          ->fields('ef', [$target_id_field])
          ->condition('ef.entity_id', $entity_info['entity_ids'], 'IN');

        // If bundles have been specified, join on the next entity's data table
        // to filter by bundles.
        if (!empty($next_step['bundles'])) {
          $query->join($next_entity_type->getBaseTable(), 'bt', 'bt.%id = ef.%target_id', [
            '%id' => $next_entity_type->getKey('id'),
            '%target_id' => $target_id_field,
          ]);

          $query->condition('bt.' . $next_entity_type->getKey('bundle'), array_intersect($next_entity_info['bundles'], $next_step['bundles']), 'IN');
        }

        $next_entity_info['entity_ids'] = $query->execute()
          ->fetchCol();
      }

      // Exit if no referenced entities were found.
      if (empty($next_entity_info['entity_ids'])) {
        return [];
      }

      $entity_info = $next_entity_info;
    }

    return $entity_info['entity_ids'];
  }

  /**
   * {@inheritdoc}
   */
  public function followReverse(ContentEntityInterface $entity, $exception_on_invalid = TRUE, $end = NULL) {
    // Return an empty array if the reference map config isn't valid.
    if (!$this->validate($exception_on_invalid)) {
      return [];
    }

    // Get the next step in the map after the specified end so we can validate
    // the map for the entity.
    if ($end) {
      $end++;
    }
    $map = $this->getMap(0, $end);

    // Remove the previously added last step in the map.
    $last_step = array_pop($map);

    // Validate the map for this entity.
    if ($last_step) {
      $entity_type_id = $entity->getEntityTypeId();
      $entity_check = $last_step['entity_type'] == $entity_type_id;
      $bundle_check = empty($last_step['bundles']) || in_array($entity->bundle(), $last_step['bundles']);
      if (!$entity_check || !$bundle_check) {
        if (!$entity_check) {
          $this->logger->error($this->t("The map %map doesn't apply to %entity_type %entity at position %end.", [
            '%map' => $this->map->id,
            '%entity_type' => $entity_type_id,
            '%entity' => $entity->label(),
            '%end' => count($map),
          ]));
        }

        return [];
      }
    }

    // Initialize $entity_ids as this entity's id.
    $entity_ids = [$entity->id()];

    // Loop through the map backwards. At each step, collect the entities from
    // that stage that reference the entities from the previous step.
    foreach (array_reverse($map) as $index => $step) {
      $query = \Drupal::entityQuery($step['entity_type'])
        ->condition($step['field_name'], $entity_ids, 'IN');

      if (!empty($step['bundles'])) {
        $query->condition('type', $step['bundles'], 'IN');
      }

      $entity_ids = $query->execute();
    }

    // Get results.
    return $entity_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($exception_on_invalid = TRUE) {
    $violations = $this->map->validate();
    if (count($violations)) {
      $message = $this->t("The reference map %reference_map is invalid.\n\n<b>Violations:</b>\n%violations", [
        '%reference_map' => $this->map->id,
        '%violations' => $violations,
      ]);

      if ($exception_on_invalid) {
        throw new \Exception($message);
      }
      else {
        $this->logger->error($message);
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getMap($start = 0, $end = NULL) {
    if (empty($this->map)) {
      $this->logger->error($this->t('The %plugin Reference Map Type plugin is missing a Reference Map Config entity.', ['%plugin' => $this->pluginId]));

      return [];
    }

    // Calculate the length of the map to return.
    $length = $end ? $end - $start : NULL;

    // Return the map, trimmed if appropriate.
    return array_slice($this->map->map, $start, $length);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig() {
    return $this->map;
  }

  /**
   * {@inheritdoc}
   */
  public function configFormAlter(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function configFormValidate(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function configFormPreSave(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function configFormActions(array &$form, FormStateInterface $form_state, array &$actions) {
  }

}
