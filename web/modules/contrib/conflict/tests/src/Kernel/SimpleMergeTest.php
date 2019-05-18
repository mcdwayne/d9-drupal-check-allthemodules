<?php

namespace Drupal\Tests\conflict\Kernel;

use Drupal;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\conflict;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * @group conflict
 */
class SimpleMergeTest extends EntityKernelTestBase {

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
  public function testsimpleMergeResolver() {
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
    $entity->setName('revision 5');
    $entity->setNewRevision();
    $entity->save();
    $entity->setName('revision 6');
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
    $revision5 = Drupal::entityTypeManager()
        ->getStorage('entity_test_rev')
        ->loadRevision(5);
    $revision6 = Drupal::entityTypeManager()
        ->getStorage('entity_test_rev')
        ->loadRevision(6);

    $manager = Drupal::service('conflict.merge_manager');
    $newest_revision1 = $manager->resolveSimpleMerge($revision2, $revision3, $revision4);
    $revisionLca = Drupal::entityTypeManager()
        ->getStorage('entity_test_rev')
        ->loadRevision($newest_revision1);
    $this->assertEquals($revisionLca->label(), "revision 4");

    //This test will pass as it returns "revision 6"
    $newest_revision2 = $manager->resolveSimpleMerge($revision2, $revision3, $revision6);
    $revisionLca = Drupal::entityTypeManager()
        ->getStorage('entity_test_rev')
        ->loadRevision($newest_revision2);
    $this->assertEquals($revisionLca->label(), "revision 6");
  }

}
