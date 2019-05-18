<?php
/**
 * @file
 * Contains \Drupal\collect_demo\Tests\CollectDemoTest.
 */

namespace Drupal\collect_demo\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the demo module for collect.
 *
 * @group collect
 */
class CollectDemoTest extends WebTestBase {

  public static $modules = array('collect_demo');

  /**
   * Asserts the demo content on the web page.
   */
  public function testInstalled() {
    $user = $this->drupalCreateUser([
      'access administration pages',
      'administer collect',
      'view any crm_core_activity entity',
    ]);
    $this->drupalLogin($user);
    $this->drupalGet('admin/content/collect');
    $this->assertText('collect:url');
    $host = \Drupal::request()->getHost();
    $this->assertText('collect:collectjson/' . $host . '/entity/node/collect_demo');
    $this->assertText('collect:collectjson-definition/global/fields');
    $this->assertText('collect:collectjson/' . $host . '/entity/user');
    $this->assertText('collect:collectjson-definition/global/fields');
    $this->clickLink(t('View'));
    $this->drupalGet('<front>');
    $this->assertResponse(200);
    $this->assertText('Collect Demo');

    $this->drupalGet('admin/config/services/collect');
    $this->assertResponse(200);
    $this->assertText('Participants');
    $this->assertText('CRM Core Individual, CRM Core Organization');

    // Assert default title of created activities.
    $this->drupalGet('crm-core/activity');
    $this->assertLink('node collect_demo Collect Demo');
    $this->assertLink('user ' . $user->getAccountName());
  }

}
