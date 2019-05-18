<?php

namespace Drupal\Tests\conflict\Kernel;

use Drupal;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\conflict;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * @group conflict
 */
class KernelLcaTest extends EntityKernelTestBase {

  protected $entityType;
  
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_test', 'conflict', 'system', 'user'];

  protected function setUp() {
    // First setup the needed entity types before installing the views.
    parent::setUp();
    $this->installEntitySchema('entity_test_rev');
  }

  /**
   * Creates an entity and it's revisions then performs a simple algorithm to
   * find common parent of two revisions.
   */
  public function testsimpleLcaResolver() {
    // Creates a new entity
    $entity = EntityTestRev::create(['name' => 'revision 1']);
    $entity->save();
    // Updates it multiple times to create new revisions.
    $entity->setName('revision 2');
    $entity->setNewRevision();
    $entity->save();
    $entity->setName('revision 3');
    $entity->setNewRevision();
    $entity->save();
    $entity->setName('revision 4');
    $entity->setNewRevision();
    $entity->save();
    // Load the revisions from database.
    $revision2 = Drupal::entityTypeManager()
      ->getStorage('entity_test_rev')
      ->loadRevision(2);
    $revision3 = Drupal::entityTypeManager()
      ->getStorage('entity_test_rev')
      ->loadRevision(3);
    $revision4 = Drupal::entityTypeManager()
      ->getStorage('entity_test_rev')
      ->loadRevision(4);

    $manager = Drupal::service('conflict.lca_manager');

    // Gets the parent id for revision 2 and 3.
    $parent_revision_id1 = $manager->resolveLowestCommonAncestor($revision2, $revision3);
    $revisionLca = Drupal::entityTypeManager()
      ->getStorage('entity_test_rev')
      ->loadRevision($parent_revision_id1);
    $this->assertEquals($revisionLca->label(), "revision 1");

    // Gets the parent id for revision 3 and 4.
    $parent_revision_id2 = $manager->resolveLowestCommonAncestor($revision3, $revision4);
    $revisionLca = Drupal::entityTypeManager()
      ->getStorage('entity_test_rev')
      ->loadRevision($parent_revision_id2);
    $this->assertEquals($revisionLca->label(), "revision 2");
  }

}
