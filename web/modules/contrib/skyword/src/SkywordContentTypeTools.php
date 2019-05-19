<?php

namespace Drupal\skyword;

use Drupal\field\Entity\FieldConfig;

/**
 * Common Content Type Tools that Skyword uses
 */
class SkywordContentTypeTools {

  /**
   * Get a list of Content Types or a single one
   *
   * @param string $id
   *   The unique identifier of the content type
   * @param \Drupal\rest\ResourceResponse $response
   *   The response object to attach pager headers to, if needed
   *
   * @return array
   */
  public static function getTypes($id = NULL, &$response = NULL) {
    try {
      $data = [];

      $query = \Drupal::entityQuery('node_type');

      if (!empty($response)) {
        SkywordCommonTools::pager($response, $query);
      }

      if (!empty($id)) {
        $query->condition('type', $id);
      }

      $types = $query->execute();

      $node_types = \Drupal::service('entity_type.manager')
        ->getStorage('node_type')
        ->loadMultiple($types);

      /** @var \Drupal\node\Entity\NodeType $node_type */
      foreach ($node_types as $node_type) {

        $data[] = [
          'id'          => $node_type->id(),
          'name'        => $node_type->label(),
          'description' => $node_type->getHelp(),
          'fields'      => static::getTypeFields('node', $node_type->id(), true),
        ];
      }

      return $data;
    } catch (Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

  /**
   * Gets a list of Fields by entity and bundle
   *
   * @param string $entity_type_id
   *   The id of the entity
   *
   * @param string $bundle
   *   The id of the bundle
   *
   * @param bool $skyword
   *   Whether to get types for Skyword or for Drupal
   * @return array
   */
  public static function getTypeFields($entity_type_id, $bundle, $skyword = false) {
    $data = [];

    $field_definitions = \Drupal::service('entity_field.manager')
                                ->getFieldDefinitions($entity_type_id, $bundle);

    $field_storage_definitions = \Drupal::service('entity_field.manager')
                                        ->getFieldStorageDefinitions($entity_type_id);

    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
    foreach ($field_definitions as $field_definition) {
      if (!$field_definition instanceof FieldConfig) {
        continue;
      }
      $field = [
        'id'          => $field_definition->getUniqueIdentifier(),
        'name'        => $field_definition->getName(),
        'label'       => $field_definition->getLabel(),
        'description' => $field_definition->getDescription(),
        'required'    => $field_definition->isRequired(),
      ];

      if($skyword) {
        $field['type'] = static::getSkywordType($field_definition, $field_storage_definitions);
      } else {
        $field['type'] = $field_definition->getType();
      }

     $data[] = $field;
    }

    return $data;
  }

  /**
   * @param  \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *
   * @param array $field_storage_definitions
   *
   * @return string
   */
  public static function getSkywordType($field_definition, $field_storage_definitions) {
    $type = $field_definition->getType();

    switch ($type) {
      case 'text_with_summary':
        return 'TEXT-AREA';
      case 'boolean':
        return 'CHECKBOX';
      case 'datetime':
        return 'DATETIME';
      case 'email':
        return 'TEXT-FIELD';
      case 'image':
        return 'IMAGE';
      case 'link':
        return 'TEXT-FIELD';
      case 'text':
        return 'TEXT-FIELD';
      case 'text_long':
        return 'TEXT-AREA';
      case 'string':
        return 'TEXT-FIELD';
      case 'string_long':
        return 'TEXT-FIELD';
    }

    if ($type === 'entity_reference' && $field_definition->getSetting('handler') === 'default:taxonomy_term') {
      $field_storage_definition = $field_storage_definitions[$field_definition->getName()];

      $cardinality = $field_storage_definition->getCardinality();
      if ($cardinality === 1) {
        return 'TAXONOMY-SINGLE-SELECT';
      }

      return 'TAXONOMY-TEXT-FIELD';
    }

    return 'UNKNOWN';
  }
}
