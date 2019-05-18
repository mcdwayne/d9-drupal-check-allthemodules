<?php

namespace Drupal\micro_path;

use Drupal\Core\Entity\EntityInterface;

/**
 * Provides and interface for MicroPathautoGenerator.
 */
interface MicroPathautoGeneratorInterface {

  /**
   * Apply patterns to create an alias for a micro site.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $site_id
   *   The micro site id.
   * @param string $op
   *   The op must be different of return or bulkupdate to allow notify user of
   *   alias change. Set by default to 'micro_path'.
   *
   * @return string
   *   The alias that was created.
   *
   */
  public function createEntitySiteAlias(EntityInterface $entity, $site_id, $op = 'micro_path');

  /**
   * Creates or updates an alias for the given entity.
   *
   * @param EntityInterface $entity
   *   Entity for which to update the alias.
   * @param integer $site_id
   *   The micro site id.
   * @param string $op
   *   The operation performed (insert, update)
   * @param array $options
   *   - force: will force updating the path
   *   - language: the language for which to create the alias
   *
   * @return array|null
   *   - An array with alias data in case the alias has been created or updated.
   *   - NULL if no operation performed.
   */
  public function updateEntitySiteAlias(EntityInterface $entity, $site_id, $op = 'micro_path', array $options = array());

}
