<?php

namespace Drupal\third_party_wrappers\Tests\Controller;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the third-party-wrappers page.
 *
 * @group third_party_wrappers
 *
 * @see \Drupal\third_party_wrappers\Controller\ThirdPartyWrappersController
 */
class ThirdPartyWrappersControllerTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['third_party_wrappers'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->config('third_party_wrappers.settings')->set('split_on', '<!-- third_party_wrappers -->');
    $this->config('third_party_wrappers.settings')->save();
  }

  /**
   * Test that the Third Party Wrappers page loads.
   */
  public function testPageStatus() {
    $this->drupalGet('third-party-wrappers');
    $this->assertResponse(200);
  }

  /**
   * Check that the top half of the page is displaying correctly.
   */
  public function testPageTop() {
    $this->drupalGet('third-party-wrappers/top');
    $this->assertRaw('<body');
    $this->assertNoRaw('</body>');
  }

  /**
   * Check that the bottom half of the page is displaying correctly.
   */
  public function testPageBottom() {
    $this->drupalGet('third-party-wrappers/bottom');
    $this->assertNoRaw('<body');
    $this->assertRaw('</body>');
  }

  /**
   * Test that invalid URLs will prompt an error.
   */
  public function testEmptyResponse() {
    // Query a URL that does not exist, in order to produce an invalid response.
    $this->drupalGet('third-party-wrappers/top', [
      'query' => [
        'url' => 'foo-bar',
      ],
    ]);
    $this->assertResponse(500);
  }

  /**
   * Ensures that an error is thrown if the split string is not defined.
   */
  public function testTopEmptySplitString() {
    $this->config('third_party_wrappers.settings')->set('split_on', '');
    $this->config('third_party_wrappers.settings')->save();
    $this->drupalGet('third-party-wrappers/top');
    $this->assertText('Please configure a content marker string for Third Party Wrappers.');
    $this->assertResponse(500);
  }

  /**
   * Ensures that an error is thrown if the split string is not defined.
   */
  public function testBottomEmptySplitString() {
    $this->config('third_party_wrappers.settings')->set('split_on', '');
    $this->config('third_party_wrappers.settings')->save();
    $this->drupalGet('third-party-wrappers/bottom');
    $this->assertText('Please configure a content marker string for Third Party Wrappers.');
    $this->assertResponse(500);
  }

  /**
   * Tests that only valid actions will work.
   */
  public function testNoValidAction() {
    $this->drupalGet('third-party-wrappers/foo');
    $this->assertText('No valid action found. Valid actions are top, bottom, or all.');
    $this->assertResponse(400);
  }

}
