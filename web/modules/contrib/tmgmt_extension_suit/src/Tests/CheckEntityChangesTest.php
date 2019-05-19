<?php

namespace Drupal\tmgmt_extension_suit\Tests;

/**
 * Class CheckEntityChangesTest
 *
 * @group tmgmt_extension_suit
 */
class CheckEntityChangesTest extends TmgmtExtensionSuitTestBase {

  /**
   * Test "Track changes of the translatable entities" feature is turned on.
   */
  public function testTrackTranslatableEntityChanges() {
    $this->requestTranslation([1], 'fr', 1);

    // Submit node edit form without changes.
    // Expectations:
    // 1. Hash is not changed.
    // 2. Job is not added to upload queue.
    $oldHash = $this->getNodeHash(1, 1);
    $this->drupalPostForm("node/1/edit", [], t('Save'));
    $newHash = $this->getNodeHash(1, 1);
    $this->assertEqual($oldHash, $newHash);
    $this->assertEqual($this->isItemAddedToQueue('tmgmt_extension_suit_upload', 1), 0);

    // Submit node edit form with updated title.
    // Expectations:
    // 1. Hash is changed.
    // 2. Job is added to upload queue.
    $this->drupalPostForm("node/1/edit", [
      'title[0][value]' => 'New node test title',
    ], t('Save'));
    $newHash = $this->getNodeHash(1, 1);
    $this->assertNotEqual($oldHash, $newHash);
    $this->assertEqual($this->isItemAddedToQueue('tmgmt_extension_suit_upload', 1), 1);
  }

  /**
   * Test "Track changes of the translatable entities" feature is turned off.
   */
  public function testDoNotTrackTranslatableEntityChanges() {
    $this->requestTranslation([1], 'fr', 1);

    // Disable tracking feature.
    $this->drupalPostForm('admin/tmgmt/extension-settings', [
      'do_track_changes' => FALSE,
    ], t('Save configuration'));

    // Submit node edit form without changes.
    // Expectations:
    // 1. Hash is not changed.
    // 2. Job is not added to upload queue.
    $oldHash = $this->getNodeHash(1, 1);
    $this->drupalPostForm("node/1/edit", [], t('Save'));
    $newHash = $this->getNodeHash(1, 1);
    $this->assertEqual($oldHash, $newHash);
    $this->assertEqual($this->isItemAddedToQueue('tmgmt_extension_suit_upload', 1), 0);

    // Submit node edit form with updated title.
    // Expectations:
    // 1. Hash is not changed.
    // 2. Job is not added to upload queue.
    $this->drupalPostForm("node/1/edit", [
      'title[0][value]' => 'New node test title',
    ], t('Save'));
    $newHash = $this->getNodeHash(1, 1);
    $this->assertEqual($oldHash, $newHash);
    $this->assertEqual($this->isItemAddedToQueue('tmgmt_extension_suit_upload', 1), 0);
  }

}
