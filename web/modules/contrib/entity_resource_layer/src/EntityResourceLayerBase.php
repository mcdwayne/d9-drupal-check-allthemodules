<?php

namespace Drupal\entity_resource_layer;

use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Plugin base functionality for the entity resource layer.
 *
 * @package Drupal\entity_resource_layer
 */
class EntityResourceLayerBase extends PluginBase implements EntityResourceLayerPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function adaptOutgoing(array $data, FieldableEntityInterface $entity) {
    // Default implementation does nothing special.
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function adaptIncoming(array $data) {
    // Default implementation does nothing special.
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getSensitiveFields() {
    return $this->pluginDefinition['sensitiveFields'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldsMapping(array $fields) {
    $map = array_combine($fields, $fields);

    // Add the field mapping from the annotation.
    if (!empty($this->pluginDefinition['fieldMap'])) {
      $this->mapFields($map);
    }

    // Trim 'field_' from the custom fields if enabled.
    if ($this->pluginDefinition['trimCustomFields']) {
      $this->trimCustomFields($map);
    }

    // Turn keys to camelcase if enabled.
    if ($this->pluginDefinition['camelFields']) {
      $this->camelFields($map);
    }

    return $map;
  }

  /**
   * {@inheritdoc}
   */
  public function mapFieldsOutgoing(array $data, array $fieldMap = NULL) {
    $mappedData = [];
    $fieldMap = $fieldMap ?: $this->getFieldsMapping(array_keys($data));
    foreach ($fieldMap as $original => $mapped) {
      $mappedData[$mapped] = $data[$original];
    }
    return $mappedData;
  }

  /**
   * {@inheritdoc}
   */
  public function mapFieldsIncoming(array $data, $bundle = NULL) {
    $fieldManager = \Drupal::service('entity_field.manager');
    $entityType = $this->pluginDefinition['entityType'];
    $fieldDefinitions = $fieldManager->getFieldDefinitions($entityType, $bundle ?: $entityType);

    $originalData = $data;
    $fieldMap = array_flip($this->getFieldsMapping(array_keys($fieldDefinitions)));

    foreach ($fieldMap as $mapped => $original) {
      if (array_key_exists($mapped, $data)) {
        unset($originalData[$mapped]);
        $originalData[$original] = $data[$mapped];
      }
    }

    return $originalData;
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibleFields(FieldableEntityInterface $entity) {
    $fields = [];

    // If only fields are specified then the answer is simple.
    if (($only = $this->pluginDefinition['fieldsOnly']) != NULL) {
      return $only;
    }

    // Collect all field names.
    foreach ($entity as $fieldName => $value) {
      $fields[] = $fieldName;
    }

    // If except is defined then the fields not present in except are needed.
    if (($except = $this->pluginDefinition['fieldsExcept']) != NULL) {
      return array_diff($fields, $except);
    }

    // By default show all fields.
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function embedReferences(array $data, FieldableEntityInterface $entity, $format, array $context) {
    if (!isset($this->pluginDefinition['embed'])) {
      return $data;
    }

    /** @var \Symfony\Component\Serializer\Normalizer\NormalizerInterface $normalizer */
    $normalizer = \Drupal::service('entity_resource_layer.normalizer.entity_adaptor');
    // Allow for single field setting.
    $embedReferencedFields = $this->pluginDefinition['embed'];
    if (is_string($embedReferencedFields)) {
      $embedReferencedFields = [$embedReferencedFields];
    }
    foreach ($embedReferencedFields as $field) {
      if (!isset($data[$field])) {
        continue;
      }
      // We have to use the field storage definition because this is where the
      // entity reference revision sets the target type.
      $definition = $entity->get($field)->getFieldDefinition()->getFieldStorageDefinition();
      $type = $definition->getSetting('target_type');

      if (!$type) {
        throw new \LogicException(sprintf('Tried to embed non reference field "%s" in resource "%s".', $field, $this->pluginDefinition['id']));
      }

      $loadedReferences = [];
      foreach ($this->loadEmbeddedReferences($data[$field], $type, $context) as $object) {
        $loadedReferences[] = $normalizer->normalize($object, $format, $context);
      }

      if (count($loadedReferences) == 1) {
        $data[$field] = $loadedReferences[0];
      }
      else {
        $data[$field] = $loadedReferences;
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getFocus() {
    return isset($this->pluginDefinition['fieldFocus']) ? $this->pluginDefinition['fieldFocus'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(FieldableEntityInterface $entity) {}

  /**
   * {@inheritdoc}
   */
  public function beforeGet(FieldableEntityInterface $entity) {}

  /**
   * {@inheritdoc}
   */
  public function beforePost(FieldableEntityInterface $entity) {}

  /**
   * {@inheritdoc}
   */
  public function beforePatch(FieldableEntityInterface $originalEntity, FieldableEntityInterface $updatedEntity = NULL) {}

  /**
   * {@inheritdoc}
   */
  public function beforeDelete(FieldableEntityInterface $entity) {}

  /**
   * {@inheritdoc}
   */
  public function reactGet(Response $response, FieldableEntityInterface $entity) {
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function reactPost(Response $response, FieldableEntityInterface $entity) {
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function reactPatch(Response $response, FieldableEntityInterface $originalEntity, FieldableEntityInterface $entityFromResource = NULL) {
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function reactDelete(Response $response, FieldableEntityInterface $entity) {
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(FieldableEntityInterface $entity, $operation) {
    return new AccessResultNeutral();
  }

  /**
   * Trims the field names removing the Drupal specific field prefix.
   *
   * @param array $map
   *   The map of fields to be parsed.
   */
  protected function trimCustomFields(array &$map) {
    foreach ($map as $original => $mapped) {
      if ($original == $mapped && strpos($mapped, 'field_') === 0) {
        $map[$original] = '$' . substr($mapped, strlen('field_'));
      }
    }
  }

  /**
   * Camel cases the field names.
   *
   * @param array $map
   *   The map of fields to be parsed.
   */
  protected function camelFields(array &$map) {
    foreach ($map as $original => $mapped) {
      $map[$original] = lcfirst(str_replace('_', '', ucwords($mapped, '_')));
    }
  }

  /**
   * Maps the field name to a REST friendly human defined name.
   *
   * @param array $map
   *   The map of fields to be parsed.
   */
  protected function mapFields(array &$map) {
    foreach ($this->pluginDefinition['fieldMap'] as $original => $mapped) {
      if (array_key_exists($original, $map)) {
        $map[$original] = $mapped;
      }
    }
  }

  /**
   * Loads the entities indicated by the ids parameter.
   *
   * @param array|int $ids
   *   The ids of the entities in form of an array or int.
   * @param string $type
   *   The entity type.
   * @param array $context
   *   Context data for the serializer.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]
   *   The loaded entities.
   */
  protected function loadEmbeddedReferences($ids, string $type, array $context) {
    $entityTypeManager = \Drupal::entityTypeManager();
    $ids = $this->buildIdsArray($ids);
    // Embed all referenced entities.
    $storage = $entityTypeManager->getStorage($type);

    if (!empty($ids)) {
      $entities = $storage->loadMultiple($ids);
      // Filter out the entities with access false.
      return array_filter($entities, function (EntityInterface $item) use ($context) {
        return $item->access('view', $context['account']);
      });
    }

    return [];
  }

  /**
   * Transforms the ids parameter into an entity load friendly array.
   *
   * @param array|int $ids
   *   The ids of the entities to be transformed.
   *
   * @return array|int
   *   The entity load friendly array if ids.
   */
  protected function buildIdsArray($ids) {
    // A field can be single or multiple.
    if (is_int($ids)) {
      $ids = [$ids];
    }
    elseif (is_array($ids)) {
      $ids = array_map(function ($item) {
        return isset($item['target_id']) ? $item['target_id'] : $item;
      }, $ids);
    }

    return $ids;
  }

}
