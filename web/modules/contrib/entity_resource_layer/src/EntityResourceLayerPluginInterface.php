<?php

namespace Drupal\entity_resource_layer;

use Drupal\Core\Entity\FieldableEntityInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Plugin interface for entity resource layer.
 *
 * @package Drupal\entity_resource_layer
 */
interface EntityResourceLayerPluginInterface {

  /**
   * Adapts outgoing entity data.
   *
   * @param array $data
   *   The outgoing data.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to be converted to resource.
   *
   * @return array
   *   The normalized data.
   */
  public function adaptOutgoing(array $data, FieldableEntityInterface $entity);

  /**
   * Adapts the incoming entity data.
   *
   * @param array $data
   *   The incoming entity data.
   *
   * @return array
   *   The de-normalized data.
   */
  public function adaptIncoming(array $data);

  /**
   * Gets the fields name mapping.
   *
   * @param array $fields
   *   The original field names.
   *
   * @return array
   *   The field name map.
   */
  public function getFieldsMapping(array $fields);

  /**
   * Maps outgoing data.
   *
   * @param array $data
   *   The outgoing data.
   * @param array $fieldMap
   *   (Optional) Field mapping, by default gets the default mapping.
   *
   * @return array
   *   The mapped data.
   */
  public function mapFieldsOutgoing(array $data, array $fieldMap = NULL);

  /**
   * Maps incoming data.
   *
   * @param array $data
   *   The incoming data.
   * @param string $bundle
   *   (Optional) The entity bundle.
   *
   * @return array
   *   The mapped data.
   */
  public function mapFieldsIncoming(array $data, $bundle = NULL);

  /**
   * Gets sensitive fields of the entity.
   *
   * @return array
   *   Sensitive field names of the entity.
   */
  public function getSensitiveFields();

  /**
   * Gets the list of fields to include in the resource.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The field names.
   */
  public function getVisibleFields(FieldableEntityInterface $entity);

  /**
   * Includes in the data array the referenced entities data.
   *
   * @param array $data
   *   The current state of the normalized entity data array.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   * @param string $format
   *   The format to serialize to.
   * @param array $context
   *   Context data for the serializer.
   *
   * @return array
   *   The new data with embedded entities.
   */
  public function embedReferences(array $data, FieldableEntityInterface $entity, $format, array $context);

  /**
   * Determines and gets the single field key.
   *
   * @return bool|string
   *   If enabled returns the field name, otherwise FALSE.
   */
  public function getFocus();

  /**
   * Validates the incoming entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @throws \Drupal\entity_resource_layer\Exception\EntityResourceException
   *   Resource exception.
   */
  public function validate(FieldableEntityInterface $entity);

  /**
   * Reacts to the entity before GET.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return \Symfony\Component\HttpFoundation\Response|void
   *   Optional response to return.
   */
  public function beforeGet(FieldableEntityInterface $entity);

  /**
   * Reacts to the entity before POST.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return \Symfony\Component\HttpFoundation\Response|void
   *   Optional response to return.
   */
  public function beforePost(FieldableEntityInterface $entity);

  /**
   * Reacts to the entity before PATCH.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $originalEntity
   *   The original entity.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $updatedEntity
   *   The updated entity.
   *
   * @return \Symfony\Component\HttpFoundation\Response|void
   *   Optional response to return.
   */
  public function beforePatch(FieldableEntityInterface $originalEntity, FieldableEntityInterface $updatedEntity = NULL);

  /**
   * Reacts to the entity before DELETE.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return \Symfony\Component\HttpFoundation\Response|void
   *   Optional response to return.
   */
  public function beforeDelete(FieldableEntityInterface $entity);

  /**
   * Reacts to the generated response for GET.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The generated response.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function reactGet(Response $response, FieldableEntityInterface $entity);

  /**
   * Reacts to the generated response for POST.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The generated response.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function reactPost(Response $response, FieldableEntityInterface $entity);

  /**
   * Reacts to the generated response for PATCH.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The generated response.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $originalEntity
   *   The entity.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entityFromResource
   *   The updated entity.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function reactPatch(Response $response, FieldableEntityInterface $originalEntity, FieldableEntityInterface $entityFromResource = NULL);

  /**
   * Reacts to the generated response for DELETE.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The generated response.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function reactDelete(Response $response, FieldableEntityInterface $entity);

  /**
   * Checks CRUD access to the entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   * @param string $operation
   *   The operation: GET, POST, PATCH or DELETE.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function checkAccess(FieldableEntityInterface $entity, $operation);

}
