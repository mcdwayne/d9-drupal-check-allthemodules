<?php

namespace Drupal\akismet\Tests;
use Drupal\akismet\Storage\ResponseDataStorage;

/**
 * Verify that Akismet data can be created, read, updated, and deleted.
 * @group akismet
 */
class DataCRUDTest extends AkismetTestBase {

  /**
   * Modules to enable.
   * @var array
   */
  public static $modules = ['dblog', 'akismet', 'node', 'comment', 'akismet_test_server'];

  protected $useLocal = TRUE;

  /**
   * Verify that Akismet data can be updated.
   *
   * Also verifies that the combined primary/unique database schema index is
   * properly accounted for; i.e., two entities having the same ID but different
   * types must not considered the same.
   */
  function testUpdate() {
    // Create a first data record.
    $data1 = (object) [
      'entity' => 'type1',
      'id' => 123,
      'form_id' => 'type1_form',
      'contentId' => 1,
    ];
    ResponseDataStorage::save($data1);
    $this->assertAkismetData($data1->entity, $data1->id, 'contentId', $data1->contentId);

    // Create a second data record; same ID, different entity type.
    $data2 = (object) [
      'entity' => 'type2',
      'id' => 123,
      'form_id' => 'type2_form',
      'contentId' => 2,
    ];
    ResponseDataStorage::save($data2);
    $this->assertAkismetData($data2->entity, $data2->id, 'contentId', $data2->contentId);

    // Update the first data record.
    $data1->contentId = 3;
    ResponseDataStorage::save($data1);

    // Verify that both records are correct.
    $this->assertAkismetData($data1->entity, $data1->id, 'contentId', $data1->contentId);
    $this->assertAkismetData($data2->entity, $data2->id, 'contentId', $data2->contentId);
  }

  /**
   * Verify that Akismet data can be deleted.
   */
  function testDelete() {
    // Create a data record.
    $data1 = (object) array(
      'entity' => 'type1',
      'id' => 123,
      'form_id' => 'type1_form',
      'contentId' => 1,
    );
    ResponseDataStorage::save($data1);

    // Create a second data record; same ID, different entity type.
    $data2 = (object) array(
      'entity' => 'type2',
      'id' => 123,
      'form_id' => 'type2_form',
      'contentId' => 2,
    );
    ResponseDataStorage::save($data2);

    // Verify that both records exist.
    $this->assertAkismetData($data1->entity, $data1->id, 'contentId', $data1->contentId);
    $this->assertAkismetData($data2->entity, $data2->id, 'contentId', $data2->contentId);

    // Delete the first data record.
    ResponseDataStorage::delete($data1->entity, $data1->id);

    // Verify that only the second record remained and was not changed.
    $this->assertNoAkismetData($data1->entity, $data1->id);
    $this->assertAkismetData($data2->entity, $data2->id, 'contentId', $data2->contentId);
  }
}
