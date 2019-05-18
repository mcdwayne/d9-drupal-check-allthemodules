<?php

namespace Drupal\blizz_bulk_creator\Services;

/**
 * Interface EntityHelperInterface.
 *
 * Defines the API for the entity helper service.
 *
 * @package Drupal\blizz_bulk_creator\Services
 */
interface EntityHelperInterface {

  /**
   * Returns ready-to-use entity type id options, keyed by machine name.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   The entity type options.
   */
  public function getEntityTypeOptions($filterMedia = TRUE);

  /**
   * Returns a list of available entity types, keyed by machine name.
   *
   * @return \Drupal\Core\Entity\ContentEntityTypeInterface[]
   *   An array of existing entity types.
   */
  public function getContentEntityTypeDefinitions();

  /**
   * Returns ready-to-use bundle options, keyed by the machine name.
   *
   * @param string $entity_type_id
   *   The entity type id the bundle information is desired for.
   *
   * @return array
   *   The bundle options for media entities.
   */
  public function getEntityBundleOptions($entity_type_id);

  /**
   * Returns ready-to-use field options, keyed by machine name.
   *
   * @param string $entity_type_id
   *   The entity type id the bundle information is desired for.
   * @param string $bundle
   *   The media bundle name the field information is desired for.
   * @param bool $include_base_fields
   *   Flag indicating if base field definitions should be included.
   *
   * @return array
   *   The field options of that bundle.
   */
  public function getBundleFieldOptions($entity_type_id, $bundle, $include_base_fields = FALSE);

  /**
   * Gets all available entity bundle informations.
   *
   * @param string $entity_type_id
   *   The entity type id the bundle information is desired for.
   *
   * @return array
   *   The bundle definitions.
   */
  public function getEntityBundleDefinitions($entity_type_id);

  /**
   * Gets the field information of a given entity bundle.
   *
   * @param string $entity_type_id
   *   The entity type id the bundle information is desired for.
   * @param string $bundle
   *   The name of the entity bundle the field information is desired for.
   * @param bool $include_base_fields
   *   Flag indicating if base field definitions should be included.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]|mixed
   *   The field information of that bundle.
   */
  public function getBundleFields($entity_type_id, $bundle, $include_base_fields = FALSE);

  /**
   * Get appropriate reference fields for a given type / bundle.
   *
   * Filters out reference fields from the given entity_type/bundle
   * combination, that either can reference the desired media bundle
   * themselves or reference to other subentities that handle the
   * media-referencing with own fields.
   *
   * @param string $reference_target_bundle
   *   The desired target entity bundle to reference.
   * @param string $entity_type_id
   *   The entity type id to examine.
   * @param string $bundle
   *   The bundle to examine.
   * @param array $scanned
   *   Internal use only.
   *
   * @return array
   *   A (recursive) list of potential target fields.
   */
  public function getReferenceFieldsForTargetBundle($reference_target_bundle, $entity_type_id, $bundle, $scanned = []);

  /**
   * Transforms the recursive field list into select-field-options.
   *
   * @param array $fields
   *   The array of reference fields.
   * @param string $labelprefix
   *   String to prefix the field label with (recursive use only).
   * @param string $nameprefix
   *   String to prefix the machine name with (recursive use only).
   *
   * @return string[]
   *   A ready-to-use set of select-field options.
   */
  public function flattenReferenceFieldsToOptions(array $fields, $labelprefix = NULL, $nameprefix = NULL);

}
