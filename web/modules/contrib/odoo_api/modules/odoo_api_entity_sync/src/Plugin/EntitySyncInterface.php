<?php

namespace Drupal\odoo_api_entity_sync\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for Odoo entity sync plugins.
 */
interface EntitySyncInterface extends PluginInspectionInterface {

  /**
   * Asserts that given entity matches sync plugin.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   *
   * @return self
   *   Self.
   */
  public function assertEntity(EntityInterface $entity);

  /**
   * Asserts that given entity should be synced.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   *
   * @return self
   *   Self.
   *
   * @throws \Drupal\odoo_api_entity_sync\Exception\SyncExcludedException
   *   Syncing excluded.
   * @throws \Drupal\odoo_api_entity_sync\Exception\RemovalRequestException
   *   Entity removal requested.
   */
  public function assertShouldSync(EntityInterface $entity);

  /**
   * Export given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   *
   * @return int
   *   Odoo object ID.
   *
   * @throws \Drupal\odoo_api_entity_sync\Plugin\Exception\MissingPluginException
   *   Missing sync plugin of referenced entity.
   * @throws \Drupal\odoo_api_entity_sync\Exception\ExportException
   *   Export exceptions.
   */
  public function export(EntityInterface $entity);

  /**
   * Delete given exported entity from Odoo.
   *
   * This method should be ONLY called if the entity exists in Drupal but should
   * be removed from Odoo. It's NOT for removing objects from Odoo due to Drupal
   * entity deletion.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   *
   * @throws \Drupal\odoo_api_entity_sync\Exception\ExportException
   *   Export exceptions.
   */
  public function deleteFromOdoo(EntityInterface $entity);

}
