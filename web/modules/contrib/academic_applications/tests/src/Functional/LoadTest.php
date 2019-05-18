<?php

namespace Drupal\Tests\academic_applications\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group academic_applications
 */
class LoadTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['academic_applications'];

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
    $this->user = $this->drupalCreateUser(['administer site configuration', 'administer academic applications']);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests that the entity list page loads with a 200 response.
   */
  public function testLoad() {
    $this->drupalGet(Url::fromRoute('entity.academic_applications_workflow.collection'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('There are no Academic applications workflows yet.');
  }

}
