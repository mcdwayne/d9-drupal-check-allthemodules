<?php

namespace Drupal\Tests\workflow_participants\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the workflow_participants update path.
 *
 * @group workflow_participants
 */
class WorkflowParticipantsUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../fixtures/update/participant_dump.php.gz',
    ];
  }

  /**
   * Test that all duplicate participant entries are deleted.
   *
   * @see workflow_participants_update_8202()
   */
  public function testUpdate8202() {
    $database = \Drupal::database();
    $sub_query = $database->select('workflow_participants', 'wfp');
    $sub_query->fields('wfp', ['moderated_entity__target_id']);
    $sub_query->groupBy('wfp.moderated_entity__target_id');
    $sub_query->having("COUNT(wfp.moderated_entity__target_id) > 1", []);

    $participant_query = $database->select('workflow_participants', 'wp');
    $participant_query->fields('wp', ['moderated_entity__target_id', 'id']);
    $participant_query->innerJoin($sub_query, 'sub', 'wp.moderated_entity__target_id = sub.moderated_entity__target_id');
    $participant_query->orderBy('wp.moderated_entity__target_id', 'ASC');
    $participant_query->orderBy('wp.id', 'ASC');

    // Confirm there does exist duplicate entries in workflow_participants
    // table.
    $duplicates = $participant_query->execute()->fetchAllAssoc('id');
    $this->assertNotEmpty($duplicates, 'Expected duplicate entries to exist');

    // Gather ids of participant entities that will be deleted and those
    // that will be kept.
    $keep = [];
    $delete_ids = [];
    foreach ($duplicates as $id => $result) {
      if (!isset($keep[$result->moderated_entity__target_id])) {
        $keep[$result->moderated_entity__target_id] = $result->id;
        continue;
      }
      $delete_ids[] = $result->id;
    }

    // Confirm there are entries in the editor table referencing
    // workflow_participant entries that will be deleted.
    $editor_query = $database->select('workflow_participants__editors', 'wpe');
    $editor_query->addField('wpe', 'entity_id');
    $editor_query->condition('entity_id', $delete_ids, 'IN');
    $duplicate_editors = $editor_query->execute()->fetchAll();
    $this->assertNotEmpty($duplicate_editors);

    // Confirm there are entries in the reviewer table referencing
    // workflow_participant entries that will be deleted.
    $reviewer_query = $database->select('workflow_participants__reviewers', 'wpr');
    $reviewer_query->addField('wpr', 'entity_id');
    $reviewer_query->condition('entity_id', $delete_ids, 'IN');
    $duplicate_reviewers = $reviewer_query->execute()->fetchAll();
    $this->assertNotEmpty($duplicate_reviewers);

    $this->runUpdates();

    $duplicate_participants = $participant_query->execute()->fetchAll();
    $duplicate_reviewers = $reviewer_query->execute()->fetchAll();
    $duplicate_editors = $editor_query->execute()->fetchAll();

    // All duplicate entries should have been deleted.
    $this->assertEmpty($duplicate_participants);
    $this->assertEmpty($duplicate_reviewers);
    $this->assertEmpty($duplicate_editors);

    // All entries to keep should still be present.
    $participant_query = $database->select('workflow_participants', 'wp');
    $participant_query->addField('wp', 'id');
    $participant_query->condition('id', $keep, 'IN');
    $results = $participant_query->execute()->fetchAll();
    $this->assertNotEmpty($results);

    $reviewer_query = $database->select('workflow_participants__reviewers', 'wpr');
    $reviewer_query->addField('wpr', 'entity_id');
    $reviewer_query->condition('entity_id', $keep, 'IN');
    $results = $reviewer_query->execute()->fetchAll();
    $this->assertNotEmpty($results);

    $editor_query = $database->select('workflow_participants__editors', 'wpe');
    $editor_query->addField('wpe', 'entity_id');
    $editor_query->condition('entity_id', $keep, 'IN');
    $results = $editor_query->execute()->fetchAll();
    $this->assertNotEmpty($results);
  }

  /**
   * Confirm new workflow_participants_field__moderated_entity schema exist.
   *
   * @see workflow_participants_update_8202()
   */
  public function testUpdate8203() {
    $schema = \Drupal::database()->schema();
    $index_exist = $schema->indexExists('workflow_participants', 'workflow_participants_field__moderated_entity');
    $this->assertFalse($index_exist);

    $this->runUpdates();

    $index_exist = $schema->indexExists('workflow_participants', 'workflow_participants_field__moderated_entity');
    $this->assertTrue($index_exist);
  }

}
