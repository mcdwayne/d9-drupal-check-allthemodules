<?php

namespace Drupal\Tests\authorization_code_form\FunctionalJavascript;

use Drupal\authorization_code\Entity\LoginProcess;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Login form test.
 *
 * @group authorization_code
 */
class LoginBlockFormTest extends WebDriverTestBase {

  const VALID_CODE = '012345';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'authorization_code_form',
    'authorization_code_login_process_test',
  ];

  /**
   * Test user.
   *
   * @var \Drupal\user\UserInterface
   */
  private $testUser;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setUp() {
    parent::setUp();

    $this->config('system.site')->set('page.front', '/test-page')->save();

    ($this->testUser = $this->createUser([]))
      ->setEmail(sprintf('%s@%s', $this->randomMachineName(), $this->randomMachineName()))
      ->save();

    LoginProcess::create([
      'id' => 'test_login_process',
      'user_identifier' => ['plugin_id' => 'email', 'settings' => []],
      'code_generator' => ['plugin_id' => 'static_code', 'settings' => ['code' => static::VALID_CODE]],
      'code_sender' => ['plugin_id' => 'echo_code', 'settings' => []],
    ])->save();

    $this->drupalPlaceBlock('login_process_block', [
      'login_process' => 'test_login_process',
      'region' => 'content',
    ]);

    $this->drupalGet('<front>');
  }

  /**
   * Tests the login form when use is missing.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testUserMissing() {
    $session = $this->getSession();
    $page = $session->getPage();

    $page->findField('user_identifier')->setValue('missing_user@email');
    $page->findButton('edit-send-code')->press();

    // Wait for javascript on the page to prepare the form attributes.
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->pageTextNotContains('Code: ');
  }

  /**
   * Tests the login form when use is missing.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testValidationFails() {
    $session = $this->getSession();
    $page = $session->getPage();

    $page->findField('user_identifier')->setValue($this->testUser->getEmail());
    $page->findButton('edit-send-code')->press();

    // Wait for javascript on the page to prepare the form attributes.
    $this->assertSession()->assertWaitOnAjaxRequest();

    $page->findField('code')->setValue($this->randomMachineName());
    $page->find('css', '[data-drupal-selector="edit-login"]')->press();

    // Wait for javascript on the page to prepare the form attributes.
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->pageTextContains('This code is invalid');
    $this->assertTrue($page->findField('code')->hasAttribute('aria-invalid'));
  }

  /**
   * Tests the login form when use is missing.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testValidationSucceeds() {
    $session = $this->getSession();
    $page = $session->getPage();

    $page->findField('user_identifier')->setValue($this->testUser->getEmail());
    $page->findButton('edit-send-code')->press();

    // Wait for javascript on the page to prepare the form attributes.
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->pageTextContains(sprintf('Code: %s', static::VALID_CODE));

    $page->findField('code')->setValue(static::VALID_CODE);
    $page->find('css', '[data-drupal-selector="edit-login"]')->press();

    // Wait for javascript on the page to prepare the form attributes.
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->elementExists('css', '.user-logged-in');
  }

}
