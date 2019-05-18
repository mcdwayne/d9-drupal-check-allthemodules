<?php

namespace Drupal\private_entity;

use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Interface PrivateEntityInterface.
 */
interface PrivateEntityInterface {

  const PRIVATE_ENTITY_REALM = 'private_entity_access';

  const ACCESS_PUBLIC = 0;

  const ACCESS_PRIVATE = 1;

  /**
   * Initializes the value of existing entities to public.
   *
   * @param string $entity_type_id
   *   Entity type id.
   * @param string $entity_bundle
   *   Entity bundle name.
   * @param string $field_name
   *   Field name.
   *
   * @return int
   *   Amount of entries that were updated.
   */
  public function initExistingEntities($entity_type_id, $entity_bundle, $field_name);

  /**
   * Get a field name from an entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   Fieldable entity.
   * @param string $field_type
   *   Field type.
   *
   * @return null|string
   *   Field name.
   */
  public function getFieldNameFromType(FieldableEntityInterface $entity, $field_type);

}
