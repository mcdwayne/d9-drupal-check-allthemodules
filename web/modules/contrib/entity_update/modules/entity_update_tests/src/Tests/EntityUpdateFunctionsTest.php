<?php

namespace Drupal\entity_update_tests\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\entity_update\EntityUpdate;
use Drupal\entity_update_tests\EntityUpdateTestHelper;
use Drupal\entity_update_tests\Entity\EntityUpdateTestsContentEntity;

/**
 * Test Entity Update functions.
 *
 * @group Entity Update
 */
class EntityUpdateFunctionsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_update', 'entity_update_tests'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Initialy, Disable the field 'name' => No need to update.
    EntityUpdateTestHelper::fieldDisable('name');
    EntityUpdateTestHelper::fieldDisable('description');
    EntityUpdateTestHelper::fieldSetType('type', NULL);
  }

  /**
   * Entity update function : basic.
   */
  public function testEntityUpdateBasic() {

    // Disable the field by default => No need to update.
    $list = EntityUpdate::getEntityTypesToUpdate();
    $this->assertEqual(count($list), 0, 'Every entities are up to date.');

    // Enable the field.
    EntityUpdateTestHelper::fieldEnable('name');

    // Get updates list.
    $list = EntityUpdate::getEntityTypesToUpdate();

    // Has one field to update.
    $this->assert(count($list) === 1, 'Has only one entity type to update.');

    // Analyse Entity to update.
    $first_item = reset($list);
    $first_key = key($list);
    $this->assertEqual($first_key, 'entity_update_tests_cnt', 'The first key is "entity_update_tests_cnt".');
    $this->assertEqual(count($first_item), 1, 'The "entity_update_tests_cnt" has one change.');
    // Get first change.
    $entity_change_summ = reset($first_item);
    $temp = strip_tags($entity_change_summ);
    $this->assertEqual($temp, 'The Name field needs to be installed.', 'Summary text is correct.');
    // Make Update.
    $res = EntityUpdate::basicUpdate();
    $this->assert($res, 'Entity schema has been updated (Field Add).');
    // Get updates list and check.
    $list = EntityUpdate::getEntityTypesToUpdate();
    $this->assertEqual(count($list), 0, 'Every entities are up to date.');
    // Check fields list on database.
    $fields = ['id', 'name'];
    $res = EntityUpdateTestHelper::checkFieldList('entity_update_tests_cnt', $fields);
    $this->assert($res === TRUE, 'Entity schema database has correct fields [' . (print_r($res, TRUE)) . ']');

    // Enable Type Field and set to 'string'.
    EntityUpdateTestHelper::fieldSetType('type', 'string');
    // Get updates list and check.
    $list = EntityUpdate::getEntityTypesToUpdate();
    $this->assertEqual(count($list), 1, 'Has one update.');
    // Make Update.
    $res = EntityUpdate::basicUpdate();
    $this->assert($res, 'Entity schema has been updated (Field Add).');
    // Get updates list and check.
    $list = EntityUpdate::getEntityTypesToUpdate();
    $this->assertEqual(count($list), 0, 'Every entities are up to date.');
    // Check fields list on database.
    $fields = ['id', 'name', 'type'];
    $res = EntityUpdateTestHelper::checkFieldList('entity_update_tests_cnt', $fields);
    $this->assert($res === TRUE, 'Entity schema database has correct fields [' . (print_r($res, TRUE)) . ']');

    // Disable the field 'name'.
    EntityUpdateTestHelper::fieldDisable('name');
    // Has one field to update.
    $list = EntityUpdate::getEntityTypesToUpdate();
    $this->assertEqual(count($list), 1, 'Has one entity type to update.');
    // Make Update.
    $res = EntityUpdate::basicUpdate();
    $this->assert($res, 'Entity schema has been updated (Field Remove).');
    // Has one field to update.
    $list = EntityUpdate::getEntityTypesToUpdate();
    $this->assertEqual(count($list), 0, 'Every entities are up to date.');

    // Enable Type Field and set to 'integer'.
    EntityUpdateTestHelper::fieldSetType('type', 'integer');
    // Has one field to update.
    $list = EntityUpdate::getEntityTypesToUpdate();
    $this->assertEqual(count($list), 1, 'Has one entity type to update.');
    // Make Update.
    $res = EntityUpdate::basicUpdate();
    $this->assert($res, 'Entity schema has been updated (Field Remove).');
    // Check fields list on database.
    $fields = ['id', 'type'];
    $res = EntityUpdateTestHelper::checkFieldList('entity_update_tests_cnt', $fields);
    $this->assert($res === TRUE, 'Entity schema database has correct fields [' . (print_r($res, TRUE)) . ']');
  }

  /**
   * Entity update function : all.
   */
  public function testEntityUpdateAll() {

    // Create an entity.
    $entity = EntityUpdateTestsContentEntity::create(['id' => 1]);
    $entity->save();

    $ids = \Drupal::entityQuery('entity_update_tests_cnt')->execute();
    $this->assertEqual(count($ids), 1, 'Has one entity.');
    // Enable the field.
    EntityUpdateTestHelper::fieldEnable('name');
    // Enable Type Field and set to 'string'.
    EntityUpdateTestHelper::fieldSetType('type', 'string');

    // Check update.
    $this->assertEqual(count(EntityUpdate::getEntityTypesToUpdate()), 1, 'Has one entity type to update.');
    // Make Update.
    $res = EntityUpdate::safeUpdateMain();
    $this->assert($res, 'Entity schema has been updated (Field Add).');
    // Check update.
    $this->assertEqual(count(EntityUpdate::getEntityTypesToUpdate()), 0, 'Entity type updated.');

    // Check fields list on database.
    $fields = ['id', 'name', 'type'];
    $res = EntityUpdateTestHelper::checkFieldList('entity_update_tests_cnt', $fields);
    $this->assert($res === TRUE, 'Entity schema database has correct fields [' . (print_r($res, TRUE)) . ']');

    // Load and update entity (after entity type update).
    $entity = EntityUpdateTestsContentEntity::load(1);
    $entity->set('name', 'value');
    $entity->save();

    // Disable the field 'name'.
    EntityUpdateTestHelper::fieldDisable('name');
    // Check update.
    $this->assertEqual(count(EntityUpdate::getEntityTypesToUpdate()), 1, 'Has one entity type to update.');
    // Make Update.
    $res = EntityUpdate::safeUpdateMain();
    $this->assert($res, 'Entity schema has been updated (Field Remove).');
    // Check update.
    $this->assertEqual(count(EntityUpdate::getEntityTypesToUpdate()), 0, 'Entity type updated.');

    // Check entity count.
    $ids = \Drupal::entityQuery('entity_update_tests_cnt')->execute();
    $this->assertEqual(count($ids), 1, 'Has one entity.');

    // Check fields list on database.
    $fields = ['id', 'type'];
    $res = EntityUpdateTestHelper::checkFieldList('entity_update_tests_cnt', $fields);
    $this->assert($res === TRUE, 'Entity schema database has correct fields [' . (print_r($res, TRUE)) . ']');
  }

  /**
   * Update a selected entity type.
   */
  public function testEntityUpdateSel() {

    // Create an entity.
    $entity = EntityUpdateTestsContentEntity::create(['id' => 1]);
    $entity->save();

    $ids = \Drupal::entityQuery('entity_update_tests_cnt')->execute();
    $this->assertEqual(count($ids), 1, 'Has one entity.');
    // Enable the field.
    EntityUpdateTestHelper::fieldEnable('name');
    EntityUpdateTestHelper::fieldEnable('city');
    // Check update.
    $this->assertEqual(count(EntityUpdate::getEntityTypesToUpdate()), 2, 'Has one entity type to update.');

    $entity_type = \Drupal::entityTypeManager()->getStorage('entity_update_tests_cnt');
    // Make Update of the type.
    $res = EntityUpdate::safeUpdateMain($entity_type->getEntityType());
    $this->assert($res, 'Entity schema has been updated (Field Add & Remove).');
    // Check update.
    $this->assertEqual(count(EntityUpdate::getEntityTypesToUpdate()), 1, 'Entity type updated.');

    // Check fields list on database.
    $fields = ['id', 'name'];
    $res = EntityUpdateTestHelper::checkFieldList('entity_update_tests_cnt', $fields);
    $this->assert($res === TRUE, 'Entity schema database has correct fields [' . (print_r($res, TRUE)) . ']');

    // Load and update entity (after entity type update).
    $entity = EntityUpdateTestsContentEntity::load(1);
    $entity->set('name', 'value');
    $entity->save();

    // Disable the field 'name'.
    EntityUpdateTestHelper::fieldDisable('name');
    // Check update.
    $this->assertEqual(count(EntityUpdate::getEntityTypesToUpdate()), 2, 'Has one entity type to update.');
    // Make Update of the type.
    $res = EntityUpdate::safeUpdateMain($entity_type->getEntityType());
    $this->assert($res, 'Entity schema has been updated (Field Remove).');
    // Check update.
    $this->assertEqual(count(EntityUpdate::getEntityTypesToUpdate()), 1, 'Entity type updated.');

    // Check entity count.
    $ids = \Drupal::entityQuery('entity_update_tests_cnt')->execute();
    $this->assertEqual(count($ids), 1, 'After updates, Hase one entity.');

    // Check entity count.
    $ids = \Drupal::entityQuery('entity_update_tests_cnt')->execute();
    $this->assertEqual(count($ids), 1, 'Has one entity.');

    // Check fields list on database.
    $fields = ['id'];
    $res = EntityUpdateTestHelper::checkFieldList('entity_update_tests_cnt', $fields);
    $this->assert($res === TRUE, 'Entity schema database has correct fields [' . (print_r($res, TRUE)) . ']');
  }

  /**
   * Entity update function : clean.
   */
  public function testEntityUpdateClean() {
    // Create an entity.
    $entity = EntityUpdateTestsContentEntity::create(['id' => 1]);
    $entity->save();

    $ids = \Drupal::entityQuery('entity_update_tests_cnt')->execute();
    $this->assertEqual(count($ids), 1, 'Has one entity.');
    // Enable the field.
    EntityUpdateTestHelper::fieldEnable('name');
    // Make Update.
    $res = EntityUpdate::safeUpdateMain();
    $this->assert($res, 'Entity schema has been updated (Field Add).');

    // Check fields list on database.
    $fields = ['id', 'name'];
    $res = EntityUpdateTestHelper::checkFieldList('entity_update_tests_cnt', $fields);
    $this->assert($res === TRUE, 'Entity schema database has correct fields [' . (print_r($res, TRUE)) . ']');

    // Disable the field.
    EntityUpdateTestHelper::fieldDisable('name');
    // Make Update.
    $res = EntityUpdate::safeUpdateMain();
    $this->assert($res, 'Entity schema has been updated (Field Remove).');
    // Cleanup function.
    $res = EntityUpdate::cleanupEntityBackup();
    $this->assert($res, 'Table cleanup END.');

    // Check entity count.
    $ids = \Drupal::entityQuery('entity_update_tests_cnt')->execute();
    $this->assertEqual(count($ids), 1, 'Has one entity.');

    // Check fields list on database.
    $fields = ['id'];
    $res = EntityUpdateTestHelper::checkFieldList('entity_update_tests_cnt', $fields);
    $this->assert($res === TRUE, 'Entity schema database has correct fields [' . (print_r($res, TRUE)) . ']');
  }

}
