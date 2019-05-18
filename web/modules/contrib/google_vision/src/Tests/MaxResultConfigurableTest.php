<?php

namespace Drupal\google_vision\Tests;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests whether "Maximum results for Label Detection" is configurable.
 *
 * @group google_vision
 */
class MaxResultConfigurableTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['google_vision', 'google_vision_test'];

  /**
   * A user with permission to access the google settings page and check
   * for the values.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Creates administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer google vision',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test the default value for the "Maximum results for Label Detection".
   */
  public function testDefaultValue() {
    $this->drupalGet(Url::fromRoute('google_vision.settings'));
    $this->assertResponse(200);
    $this->assertFieldByName('max_results_labels', '5', 'The default value is set to 5');
  }

  /**
   * Test to verify that "Maximum results for Label Detection" is
   * configurable.
   */
  public function testNewValue() {
    $this->drupalGet(Url::fromRoute('google_vision.settings'));
    $this->assertResponse(200);
    $max_value = 2;
    $edit = ['max_results_labels' => $max_value];
    $this->drupalPostForm(Url::fromRoute('google_vision.settings'), $edit, t('Save configuration'));
    $this->assertFieldByName('max_results_labels', $max_value, 'The new value is set');
  }
}