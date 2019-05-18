<?php

namespace Drupal\Tests\message\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests admin menus for the message module.
 *
 * @group message_thread
 */
class MenuTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['message_thread'];

  /**
   * Test that the menu links are working properly.
   */
  public function testMenuLinks() {
    $admin = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($admin);

    // Link should appear on main config page.
    $this->drupalGet(Url::fromRoute('system.admin_structure'));
    $this->assertSession()->linkExists(t('Message thread templates'));

    // Link should be on the message-specific overview page.
    $this->clickLink(t('Message thread templates'));
    $this->assertSession()->statusCodeEquals(200);
  }

}
