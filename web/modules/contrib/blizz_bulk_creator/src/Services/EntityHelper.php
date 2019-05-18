<?php

namespace Drupal\blizz_bulk_creator\Services;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Class EntityHelper.
 *
 * Contains helper functions to handle entities in a consistent way.
 *
 * @package Drupal\blizz_bulk_creator\Services
 */
class EntityHelper implements EntityHelperInterface {

  /**
   * Drupal's entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal's entity type bundle information service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityBundleInfoService;

  /**
   * Drupal's entity field service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Drupal's entity type repository service.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface
   */
  protected $entityTypeRepository;

  /**
   * All available content entity types.
   *
   * @var \Drupal\Core\Entity\ContentEntityTypeInterface[]
   */
  protected $contentEntityTypeDefinitions;

  /**
   * The bundle information on entities.
   *
   * @var array
   */
  protected $entityBundleInformation;

  /**
   * An array holding the field informations of the different entity bundles.
   *
   * @var array
   */
  protected $bundleFieldDefinitions;

  /**
   * EntityHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Drupal's entity type manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_bundle_info_service
   *   Drupal's entity type bundle information service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Drupal's entity field service.
   * @param \Drupal\Core\Entity\EntityTypeRepositoryInterface $entity_type_repository
   *   Drupal's entity type repository service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_bundle_info_service,
    EntityFieldManagerInterface $entity_field_manager,
    EntityTypeRepositoryInterface $entity_type_repository
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityBundleInfoService = $entity_bundle_info_service;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeRepository = $entity_type_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeOptions($filterMedia = TRUE) {
    $options = array_map(
      function (ContentEntityTypeInterface $content_entity_type_definition) {
        return $content_entity_type_definition->getLabel();
      },
      $this->getContentEntityTypeDefinitions()
    );
    return !$filterMedia
      ? $options
      : array_filter(
          $options,
          function ($entity_type_id) {
            return $entity_type_id != 'media';
          },
          ARRAY_FILTER_USE_KEY
        );
  }

  /**
   * {@inheritdoc}
   */
  public function getContentEntityTypeDefinitions() {
    if (empty($this->contentEntityTypeDefinitions)) {
      $entity_type_ids = array_keys($this->entityTypeRepository->getEntityTypeLabels());
      $this->contentEntityTypeDefinitions = array_filter(
        array_combine(
          $entity_type_ids,
          array_map(
            function ($entity_type_id) {
              return $this->entityTypeManager->getDefinition($entity_type_id);
            },
            $entity_type_ids
          )
        ),
        function (EntityTypeInterface $entity_type_definition) {
          return $entity_type_definition instanceof ContentEntityTypeInterface;
        }
      );
    }
    return $this->contentEntityTypeDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityBundleOptions($entity_type_id) {
    return array_map(
      function ($bundle) {
        return $bundle['label'];
      },
      $this->getEntityBundleDefinitions($entity_type_id)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleFieldOptions($entity_type_id, $bundle, $include_base_fields = FALSE) {
    return array_map(
      function (FieldDefinitionInterface $field_definition) {
        return $field_definition->getLabel();
      },
      $this->getBundleFields($entity_type_id, $bundle, $include_base_fields)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityBundleDefinitions($entity_type_id) {
    return !empty($this->entityBundleInformation[$entity_type_id])
      ? $this->entityBundleInformation[$entity_type_id]
      : ($this->entityBundleInformation[$entity_type_id] = $this->entityBundleInfoService->getBundleInfo($entity_type_id));
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleFields($entity_type_id, $bundle, $include_base_fields = FALSE) {
    if (empty($this->bundleFieldDefinitions["{$entity_type_id}:{$bundle}"])) {
      $this->bundleFieldDefinitions["{$entity_type_id}:{$bundle}"] = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
    }
    return $include_base_fields
      ? $this->bundleFieldDefinitions["{$entity_type_id}:{$bundle}"]
      : array_filter(
          $this->bundleFieldDefinitions["{$entity_type_id}:{$bundle}"],
          function (FieldDefinitionInterface $field_definition) {
            return !($field_definition instanceof BaseFieldDefinition || $field_definition instanceof BaseFieldOverride);
          }
        );
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceFieldsForTargetBundle($reference_target_bundle, $entity_type_id, $bundle, $scanned = []) {

    // Which reference fields are contained in the given entity:bundle?
    $reference_fields = array_filter(
      $this->getBundleFields($entity_type_id, $bundle),
      function (FieldConfigInterface $field) {
        return in_array(
          $field->get('field_type'),
          ['entity_reference', 'entity_reference_revisions']
        );
      }
    );

    // Prepare an array to hold appropriate fields.
    $result = [];

    // Cycle over each reference field defined...
    foreach ($reference_fields as $field) {
      /* @var FieldConfigInterface $field */

      // Determine which entity type id is targeted and which bundles.
      $target_entity_type = explode(':', $field->getSetting('handler'));
      $target_entity_type = array_pop($target_entity_type);
      $handler_settings = $field->getSetting('handler_settings');
      $target_entity_bundles = isset($handler_settings['target_bundles']) ? $handler_settings['target_bundles'] : [];
      $fieldStorageDefinition = $field->getFieldStorageDefinition();

      // Remember this field and it's information.
      $current = (object) [
        'definition' => $field,
        'machine_name' => $field->getName(),
        'label' => $field->getLabel(),
        'host_entity_type' => $field->getTargetEntityTypeId(),
        'target_entity_type' => $target_entity_type,
        'target_entity_bundles' => $target_entity_bundles,
        'cardinality' => $fieldStorageDefinition->getCardinality(),
      ];

      // Cycle over each contained field in the configured target...
      foreach ($target_entity_bundles as $target_bundle) {

        if (!in_array("$target_entity_type:$target_bundle", $scanned)) {

          // Remember that this type/bundle combination has already been
          // scanned to prevent possible infinite recursions.
          $scanned[] = "$target_entity_type:$target_bundle";

          // Determine which subfields match our needs.
          $children = array_values($this->getReferenceFieldsForTargetBundle($reference_target_bundle, $target_entity_type, $target_bundle, $scanned));

          if (!empty($children)) {

            // Build an array containing information about these subfields.
            $current->children["$target_entity_type:$target_bundle"] = array_combine(
              array_map(
                function ($child) {
                  return $child->machine_name;
                },
                $children
              ),
              $children
            );

          }

        }

      }

      // Place the collected information into the result array.
      $result[] = $current;

    }

    // Filter out all fields, that do not directly reference to media
    // entities of the correct bundle or have no children referencing
    // to media entities of the appropriate bundle.
    $result = array_filter(
      $result,
      function ($item) use ($reference_target_bundle) {
        return
          (
            $item->target_entity_type == 'media' &&
            in_array($reference_target_bundle, $item->target_entity_bundles)
          )
          ||
          (
            isset($item->children) &&
            !empty(array_filter(
              $item->children,
              function ($child) {
                return !empty($child);
              }
            ))
          );
      }
    );

    // Set the field's machine name as the keys.
    $result = array_combine(
      array_map(
        function ($item) {
          return $item->machine_name;
        },
        $result
      ),
      $result
    );

    // Return the result.
    return $result;

  }

  /**
   * {@inheritdoc}
   */
  public function flattenReferenceFieldsToOptions(array $fields, $labelprefix = NULL, $nameprefix = NULL) {
    $result = [];

    foreach ($fields as $field_machine_name => $field) {

      // Build the machine name for the current field.
      $machine_name = empty($nameprefix)
        ? "{$field->machine_name}:{$field->cardinality}"
        : implode('/', [$nameprefix, "{$field->machine_name}:{$field->cardinality}"]);

      // Build a label for the current field.
      $label = empty($labelprefix)
        ? sprintf(
          '%s (%s)',
          $field->label,
          $this->getEntityTypeOptions(FALSE)[$field->target_entity_type]
        )
        : implode(
          ' > ',
          [
            $labelprefix,
            sprintf(
              '%s (%s)',
              $field->label,
              $this->getEntityTypeOptions(FALSE)[$field->target_entity_type]
            ),
          ]
        );

      // If this field can reference directly to media entities...
      if ($field->target_entity_type == 'media') {

        // ...build an option to use for this particular field...
        $option[$machine_name] = $label;

        // ...and merge it into the result set.
        $result = array_merge($result, $option);

      }

      // If there are fields referencing subentities...
      if (isset($field->children)) {

        foreach ($field->children as $entity_definition => $subfields) {

          foreach ($subfields as $subfield) {

            // Build options for these subfields, too.
            $result = array_merge(
              $result,
              $this->flattenReferenceFieldsToOptions([$subfield], $label, "$machine_name:$entity_definition")
            );

          }

        }

      }

    }

    return $result;

  }

}
