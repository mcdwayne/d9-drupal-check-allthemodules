<?php

namespace Drupal\dcom_odoo_entity_sync\Plugin\OdooEntitySync;

use Drupal\Core\Entity\EntityInterface;
use Drupal\odoo_api_entity_sync\Plugin\EntitySyncBase;

/**
 * Users sync.
 *
 * @OdooEntitySync(
 *   id = "odoo_api_entity_sync_example_user",
 *   entityType = "user",
 *   odooModel = "res.partner",
 *   exportType = "default"
 * )
 */
class User extends EntitySyncBase {

  /**
   * {@inheritdoc}
   */
  protected function shouldSync(EntityInterface $entity) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getOdooFields(EntityInterface $entity) {
    /** @var \Drupal\user\UserInterface $user */
    $user = $entity;

    $fields = [
      'email' => $user->getEmail(),
      'type' => 'contact',
      'name' => $user->getDisplayName(),
      // On Odoo the user should belong to his company.
      'parent_id' => $this->getReferencedEntityOdooId($user->getEntityTypeId(), 'res.partner', 'company', $user->id()),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  protected function shouldDelete(EntityInterface $entity) {
    return FALSE;
  }

}
