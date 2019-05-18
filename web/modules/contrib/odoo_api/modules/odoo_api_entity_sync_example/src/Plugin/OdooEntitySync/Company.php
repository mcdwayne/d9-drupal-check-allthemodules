<?php

namespace Drupal\dcom_odoo_entity_sync\Plugin\OdooEntitySync;

use Drupal\Core\Entity\EntityInterface;
use Drupal\odoo_api_entity_sync\Plugin\EntitySyncBase;

/**
 * Odoo companies sync.
 *
 * @OdooEntitySync(
 *   id = "odoo_api_entity_sync_example_company",
 *   entityType = "user",
 *   odooModel = "res.partner",
 *   exportType = "company"
 * )
 */
class Company extends EntitySyncBase {

  /**
   * {@inheritdoc}
   */
  protected function shouldSync(EntityInterface $entity) {
    /** @var \Drupal\user\UserInterface $user */
    $user = $entity;
    // Export only for UIDs 3 and 12 to Odoo, for instance.
    return in_array($user->id(), [3, 12]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getOdooFields(EntityInterface $entity) {
    /** @var \Drupal\user\UserInterface $user */
    $user = $entity;

    $fields = [
      'is_company' => TRUE,
      'company_type' => 'company',
      'name' => $user->getDisplayName(),
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
