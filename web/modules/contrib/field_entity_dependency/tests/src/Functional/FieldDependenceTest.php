<?php

namespace Drupal\Tests\field_entity_dependency\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\field_dependence\Plugin\Field\FieldWidget;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group field_entity_dependency
 */
class FieldDependenceTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['field_entity_dependency'];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests that the home page loads with a 200 response.
   */
  public function testLoad() {
    $this->drupalGet(Url::fromRoute('<front>'));
    $this->assertResponse(200);
  }

  /**
   * Tests the field dependence.
   */
  public function testFieldDependence() {
    // test the content type configuration page
    $this->drupalGet('admin/structure/types/manage/bill/form-display');
    // test the node addition
    $this->drupalGet('node/add/bill');
  }

}
