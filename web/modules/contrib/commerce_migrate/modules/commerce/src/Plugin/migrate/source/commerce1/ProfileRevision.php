<?php

namespace Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1;

/**
 * Drupal 7 commerce_customer_profile_revision source from database.
 *
 * @MigrateSource(
 *   id = "commerce1_profile_revision",
 *   source_module = "commerce_customer"
 * )
 */
class ProfileRevision extends Profile {

  /**
   * The join options between commerce_customer_profile and its revision table.
   */
  const JOIN = 'cp.profile_id = cpr.profile_id AND cp.revision_id <> cpr.revision_id';

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['revision_id']['type'] = 'integer';
    $ids['revision_id']['alias'] = 'cp';
    return $ids;
  }

}
