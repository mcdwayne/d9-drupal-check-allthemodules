<?php

namespace Drupal\Tests\aws\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test behaviors when visiting the profile listing page.
 *
 * @group aws
 */
class ProfileListTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['aws'];

  /**
   * Tests the behavior when there are no actions to list in the admin page.
   */
  public function testEmptyProfileList() {
    // Create a user with permission to view the actions administration pages.
    $this->drupalLogin($this->drupalCreateUser(['administer aws']));

    // Ensure the empty text appears on the action list page.
    /** @var $storage \Drupal\Core\Entity\EntityStorageInterface */
    $storage = $this->container->get('entity.manager')->getStorage('aws_profile');
    $profiles = $storage->loadMultiple();
    $storage->delete($profiles);
    $this->drupalGet('/admin/config/services/aws/services');
    $this->assertRaw('There is no Action yet.');
  }

}
