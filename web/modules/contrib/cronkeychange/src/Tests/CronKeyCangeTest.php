<?php

namespace Drupal\cronkeychange\Tests;

use Drupal\simpletest\WebTestBase;
use Masterminds\HTML5;

/**
 * Tests the change cron key.
 *
 * @group cronkeychange
 */
class CronKeyCangeTest extends WebTestBase {

  static public $modules = array('cronkeychange');

  protected $profile = 'minimal';

  public function setUp() {
    parent::setUp();
    // Create and log in our privileged user.
    $account = $this->drupalCreateUser(array(
      'administer site configuration',
    ));
    $this->drupalLogin($account);
  }

  /**
   * Tests the change cron key.
   */
  public function testCronKeyChange() {
    $original_cron_key = \Drupal::state()->get('system.cron_key');
    $this->drupalGet('/admin/config/system/cron');
    $visible_cron_key = trim($this->getXpathTextValue("id('edit-current')/text()[2]"));
    $this->assertEqual($original_cron_key, $visible_cron_key, 'Original value show correctly.');
    $this->drupalPostForm('admin/config/system/cron', array(), t('Generate new key'));
    $visible_cron_key = trim($this->getXpathTextValue("id('edit-current')/text()[2]"));
    $this->assertNotIdentical($visible_cron_key, '', 'Generated cron key is not null.');
    $this->assertNotEqual($original_cron_key, $visible_cron_key, 'Cron key is changed.');
  }

  /**
   * Get text value from document.
   */
  private function getXpathTextValue($path) {
    $element = $this->xpath($path)[0];
    return $element->__toString();
  }
}
