<?php

namespace Drupal\contact_emails\Tests\Update;

use Drupal\system\Tests\Update\UpdatePathTestBase;
use Drupal\Core\Database\Database;

/**
 * Tests that the email database settings are properly converted to entities.
 *
 * @group ContactEmails
 */
class ContactEmailsTableToEntityUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../tests/fixtures/update/drupal-8.filled.standard.php.gz',
    ];
  }

  /**
   * Tests that the migration from the database table to the entity worked.
   *
   * @see contact_emails_update_8005()
   */
  public function testEntitiesExist() {
    // Get the number of old emails in the database.
    $old_emails = \Drupal::database()
      ->select('contact_message_email_settings', 'e')
      ->fields('e')
      ->execute()
      ->fetchAll();
    $count_old = count($old_emails);

    // Make sure we have at least one.
    $this->assertTrue($count_old ? TRUE : FALSE);

    // Run updates.
    $this->runUpdates();

    // Get the number of new entities.
    $new_email_ids = \Drupal::entityQuery('contact_email')->execute();
    $count_new = count($new_email_ids);

    // Ensure the number of entities matches the number of emails from the
    // table.
    $this->assertEqual($count_old, $count_new);
  }

}
