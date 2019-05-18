<?php

namespace Drupal\Tests\machine\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Test login provided by module.
 *
 * @package Drupal\Tests\machine\Functional
 *
 * @group machine
 */
class AdminTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'machine'];

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
    $this->user = $this->drupalCreateUser([
      'administer site configuration',
      'administer machine configuration',
    ]);
  }

  /**
   * Testing admin form.
   *
   * @test
   */
  public function aUserConfiguresModule() {
    $this->drupalLogin($this->user);

    $this->drupalGet(Url::fromRoute('machine.admin_form'));
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->pageTextContains('Config entity types');
    $this->assertSession()->checkboxNotChecked('types[node]');

    $this->getSession()->getPage()->checkField('types[node]');
    $this->getSession()->getPage()->pressButton('op');

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Success');
    $this->assertSession()->checkboxChecked('types[node]');
  }

}
