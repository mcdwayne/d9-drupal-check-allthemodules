<?php

namespace Drupal\adstxt\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests functionality of configured ads.txt files.
 *
 * @group Ads.txt
 */
class AdsTxtTest extends WebTestBase {

  protected $profile = 'standard';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['adstxt', 'node'];

  /**
   * Checks that an administrator can view the configuration page.
   */
  public function testAdsTxtAdminAccess() {
    // Create user.
    $this->admin_user = $this->drupalCreateUser(['administer ads.txt']);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/config/system/adstxt');

    $this->assertFieldById('edit-adstxt-content', NULL, 'The textarea for configuring ads.txt is shown.');
  }

  /**
   * Checks that a non-administrative user cannot use the configuration page.
   */
  public function testAdsTxtUserNoAccess() {
    // Create user.
    $this->normal_user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($this->normal_user);
    $this->drupalGet('admin/config/system/adstxt');

    $this->assertResponse(403);
    $this->assertNoFieldById('edit-adstxt-content', NULL, 'The textarea for configuring ads.txt is not shown for users without appropriate permissions.');
  }

  /**
   * Test that the ads.txt path delivers content with an appropriate header.
   */
  public function testAdsTxtPath() {
    $this->drupalGet('ads.txt');
    $this->assertResponse(200, 'No local ads.txt file was detected, and an anonymous user is delivered content at the /ads.txt path.');
    $this->assertText('greenadexchange.com, 12345, DIRECT, AEC242');
    $this->assertText('blueadexchange.com, 4536, DIRECT');
    $this->assertText('silverssp.com, 9675, RESELLER');
    $this->assertHeader('Content-Type', 'text/plain; charset=UTF-8', 'The ads.txt file was served with header Content-Type: "text/plain; charset=UTF-8"');
  }

  /**
   * Checks that a configured ads.txt file is delivered as configured.
   */
  public function testAdsTxtConfigureAdsTxt() {
    // Create an admin user, log in and access settings form.
    $this->admin_user = $this->drupalCreateUser(['administer ads.txt']);
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/config/system/adstxt');

    $test_string = "# SimpleTest {$this->randomMachineName()}";
    $this->drupalPostForm(NULL, ['adstxt_content' => $test_string], t('Save configuration'));

    $this->drupalLogout();
    $this->drupalGet('ads.txt');
    $this->assertResponse(200, 'No local ads.txt file was detected, and an anonymous user is delivered content at the /ads.txt path.');
    $this->assertHeader('Content-Type', 'text/plain; charset=UTF-8', 'The ads.txt file was served with header Content-Type: "text/plain; charset=UTF-8"');
    $content = $this->getRawContent();
    $this->assertTrue($content == $test_string, sprintf('Test string [%s] is displayed in the configured ads.txt file [%s].', $test_string, $content));
  }

}
