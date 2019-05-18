<?php

namespace Drupal\entity_gallery\Tests;

/**
 * Tests user permissions for entity gallery revisions.
 *
 * @group entity_gallery
 */
class EntityGalleryRevisionPermissionsTest extends EntityGalleryTestBase {

  /**
   * The entity gallery revisions.
   *
   * @var array
   */
  protected $entityGalleryRevisions = [];

  /**
   * The accounts.
   *
   * @var array
   */
  protected $accounts = array();

  // Map revision permission names to entity gallery revision access ops.
  protected $map = array(
    'view' => 'view all revisions',
    'update' => 'revert all revisions',
    'delete' => 'delete all revisions',
  );

  // Map revision permission names to entity gallery type revision access ops.
  protected $typeMap = array(
    'view' => 'view page revisions',
    'update' => 'revert page revisions',
    'delete' => 'delete page revisions',
  );

  protected function setUp() {
    parent::setUp();

    $types = array('page', 'article');

    foreach ($types as $type) {
      // Create an entity gallery with several revisions.
      $entity_galleries[$type] = $this->drupalCreateEntityGallery(array('type' => $type));
      $this->entityGalleryRevisions[$type][] = $entity_galleries[$type];

      for ($i = 0; $i < 3; $i++) {
        // Create a revision for the same egid and settings with a random log.
        $revision = clone $entity_galleries[$type];
        $revision->setNewRevision();
        $revision->revision_log = $this->randomMachineName(32);
        $revision->save();
        $this->entityGalleryRevisions[$type][] = $revision;
      }
    }
  }

  /**
   * Tests general revision access permissions.
   */
  function testEntityGalleryRevisionAccessAnyType() {
    // Create three users, one with each revision permission.
    foreach ($this->map as $op => $permission) {
      // Create the user.
      $account = $this->drupalCreateUser(
        array(
          'access entity galleries',
          'edit any page entity galleries',
          'delete any page entity galleries',
          $permission,
        )
      );
      $account->op = $op;
      $this->accounts[] = $account;
    }

    // Create an admin account (returns TRUE for all revision permissions).
    $admin_account = $this->drupalCreateUser(array('access entity galleries', 'administer entity galleries'));
    $admin_account->is_admin = TRUE;
    $this->accounts['admin'] = $admin_account;
    $accounts['admin'] = $admin_account;

    // Create a normal account (returns FALSE for all revision permissions).
    $normal_account = $this->drupalCreateUser();
    $normal_account->op = FALSE;
    $this->accounts[] = $normal_account;
    $accounts[] = $normal_account;
    $revision = $this->entityGalleryRevisions['page'][1];

    $parameters = array(
      'op' => array_keys($this->map),
      'account' => $this->accounts,
    );

    $permutations = $this->generatePermutations($parameters);

    $entity_gallery_revision_access = \Drupal::service('access_check.entity_gallery.revision');
    foreach ($permutations as $case) {
      // Skip this test if there are no revisions for the entity gallery.
      if (!($revision->isDefaultRevision() && (db_query('SELECT COUNT(vid) FROM {entity_gallery_field_revision} WHERE egid = :egid', array(':egid' => $revision->id()))->fetchField() == 1 || $case['op'] == 'update' || $case['op'] == 'delete'))) {
        if (!empty($case['account']->is_admin) || $case['account']->hasPermission($this->map[$case['op']])) {
          $this->assertTrue($entity_gallery_revision_access->checkAccess($revision, $case['account'], $case['op']), "{$this->map[$case['op']]} granted.");
        }
        else {
          $this->assertFalse($entity_gallery_revision_access->checkAccess($revision, $case['account'], $case['op']), "{$this->map[$case['op']]} not granted.");
        }
      }
    }

    // Test that access is FALSE for a entity gallery administrator with an
    // invalid $entity_gallery or $op parameters.
    $admin_account = $accounts['admin'];
    $this->assertFalse($entity_gallery_revision_access->checkAccess($revision, $admin_account, 'invalid-op'), 'EntityGalleryRevisionAccessCheck() returns FALSE with an invalid op.');
  }

  /**
   * Tests revision access permissions for a specific entity gallery type.
   */
  function testEntityGalleryRevisionAccessPerType() {
    // Create three users, one with each revision permission.
    foreach ($this->typeMap as $op => $permission) {
      // Create the user.
      $account = $this->drupalCreateUser(
        array(
          'access entity galleries',
          'edit any page entity galleries',
          'delete any page entity galleries',
          $permission,
        )
      );
      $account->op = $op;
      $accounts[] = $account;
    }

    $parameters = array(
      'op' => array_keys($this->typeMap),
      'account' => $accounts,
    );

    // Test that the accounts have access to the corresponding page revision
    // permissions.
    $revision = $this->entityGalleryRevisions['page'][1];

    $permutations = $this->generatePermutations($parameters);
    $entity_gallery_revision_access = \Drupal::service('access_check.entity_gallery.revision');
    foreach ($permutations as $case) {
      // Skip this test if there are no revisions for the entity gallery.
      if (!($revision->isDefaultRevision() && (db_query('SELECT COUNT(vid) FROM {entity_gallery_field_revision} WHERE egid = :egid', array(':egid' => $revision->id()))->fetchField() == 1 || $case['op'] == 'update' || $case['op'] == 'delete'))) {
        if (!empty($case['account']->is_admin) || $case['account']->hasPermission($this->typeMap[$case['op']], $case['account'])) {
          $this->assertTrue($entity_gallery_revision_access->checkAccess($revision, $case['account'], $case['op']), "{$this->typeMap[$case['op']]} granted.");
        }
        else {
          $this->assertFalse($entity_gallery_revision_access->checkAccess($revision, $case['account'], $case['op']), "{$this->typeMap[$case['op']]} not granted.");
        }
      }
    }

    // Test that the accounts have no access to the article revisions.
    $revision = $this->entityGalleryRevisions['article'][1];

    foreach ($permutations as $case) {
      $this->assertFalse($entity_gallery_revision_access->checkAccess($revision, $case['account'], $case['op']), "{$this->typeMap[$case['op']]} did not grant revision permission for articles.");
    }
  }
}
