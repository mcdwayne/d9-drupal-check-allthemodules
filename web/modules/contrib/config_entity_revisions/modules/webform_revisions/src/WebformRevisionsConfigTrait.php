<?php


namespace Drupal\webform_revisions;

use Drupal\config_entity_revisions\ConfigEntityRevisionsStorageTrait;


trait WebformRevisionsConfigTrait {
  use ConfigEntityRevisionsStorageTrait;

  private $constants = [
    'module_name' => 'webform_revisions',
    'config_entity_name' => 'webform',
    'revisions_entity_name' => 'WebformRevisions',
    'setting_name' => 'webform_revisions_id',
    'title' => 'Webform',
    'has_own_content' => TRUE,
    'content_entity_type' => 'webform_submissions',
    'content_entity_table' => 'webform_submissions',
    'content_parameter_name' => 'webform_submission',
    'content_parent_reference_field' => 'webform',
    'admin_permission' => 'administer webforms',
    'has_canonical_url' => TRUE,
  ];

  /**
   * Get the number of submissions related to a revision.
   *
   * @return integer
   *   The number of content entities using a particular revision.
   */
  public function contentEntityCount($rid) {
    return \Drupal::database()
      ->query("SELECT COUNT(sid) FROM {webform_submission} WHERE webform_revision = :rid",
        [':rid' => $rid])->fetchField();
  }

  /**
   * Delete submissions related to a revision.
   */
  public function deleteRelatedContentEntities($rid) {
    $sids = \Drupal::database()
      ->query("SELECT sid FROM {webform_submission} WHERE webform_revision = :rid",
        [ ':rid' => $rid ])->fetchCol();
    $storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
    $submissions = $storage->loadMultiple($sids);
    $storage->delete($submissions);
  }

  /**
   * Get the entity that actually has revisions.
   */
  public function revisioned_entity() {
    return $this;
  }

}