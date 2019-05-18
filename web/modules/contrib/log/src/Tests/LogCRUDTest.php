<?php

namespace Drupal\log\Tests;
use Drupal\log\Entity\Log;

/**
 * Tests the Log CRUD.
 *
 * @group log
 */
class LogCRUDTest extends LogTestBase {

  /**
   * Fields are displayed correctly.
   */
  public function testFieldsVisibility() {
    $this->drupalGet('log/add/default');
    $this->assertResponse('200');
    $this->assertFieldByName('name[0][value]', NULL, 'Name field is present');
    $this->assertFieldByName('timestamp[0][value][date]', NULL, 'Timestamp date field is present');
    $this->assertFieldByName('timestamp[0][value][time]', NULL, 'Timestamp time field is present');
    $this->assertFieldByName('done[value]', NULL, 'Done field is present');
    $this->assertFieldByName('revision', NULL, 'Revision field is present');
    $this->assertFieldByName('user_id[0][target_id]', NULL, 'User ID field is present');
    $this->assertFieldByName('created[0][value][time]', NULL, 'Created date field is present');
    $this->assertFieldByName('created[0][value][time]', NULL, 'Created time field is present');
    $this->assertFieldsByValue(t('Save'), NULL, 'Create button is present');
  }

  /**
   * Create Log entity.
   */
  public function testCreateLog() {
    $edit = [
      'name[0][value]' => $this->randomMachineName(),
    ];

    $this->drupalPostForm('log/add/default', $edit, t('Save'));

    $result = \Drupal::entityQuery('log')
      ->condition('name', $edit['name[0][value]'])
      ->range(0, 1)
      ->execute();
    $log_id = reset($result);
    $log = Log::load($log_id);
    $this->assertNotNull($log, 'Log has been created.');

    $this->assertRaw(\Drupal\Component\Utility\SafeMarkup::format('Created the %label Log.', ['%label' => $edit['name[0][value]']]));
    $this->assertText($edit['name[0][value]']);
    $this->assertText($this->loggedInUser->getDisplayName());
  }

  /**
   * Display log entity.
   */
  public function testViewLog() {
    $edit = [
      'name' => $this->randomMachineName(),
      'created' => REQUEST_TIME,
      'done' => TRUE,
    ];
    $log = $this->createLogEntity($edit);
    $log->save();

    $this->drupalGet($log->toUrl('canonical'));
    $this->assertResponse(200);

    $this->assertText($edit['name']);
    $this->assertRaw(format_date(REQUEST_TIME));
    $this->assertText($this->loggedInUser->getDisplayName());
  }

  /**
   * Edit log entity.
   */
  public function testEditLog() {
    $log = $this->createLogEntity();
    $log->save();

    $edit = [
      'name[0][value]' => $this->randomMachineName(),
    ];
    $this->drupalPostForm($log->toUrl('edit-form'), $edit, t('Save'));

    $this->assertRaw(\Drupal\Component\Utility\SafeMarkup::format('Saved the %label Log.', ['%label' => $edit['name[0][value]']]));
    $this->assertText($edit['name[0][value]']);
  }

  /**
   * Delete log entity.
   */
  public function testDeleteLog() {
    $log = $this->createLogEntity();
    $log->save();

    $type = $log->getTypeName();
    $label = $log->getName();
    $log_id = $log->id();

    $this->drupalPostForm($log->toUrl('delete-form'), [], t('Delete'));
    $this->assertRaw(\Drupal\Component\Utility\SafeMarkup::format('The @entity-type %label has been deleted.', array(
      '@entity-type' => $log->getEntityType()->getLowercaseLabel(),
      '%label' => $label,
    )));
    $this->assertNull(Log::load($log_id));
  }

}
