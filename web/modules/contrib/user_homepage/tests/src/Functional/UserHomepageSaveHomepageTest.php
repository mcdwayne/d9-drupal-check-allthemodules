<?php

namespace Drupal\Tests\user_homepage\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the saving (and redirect) functionality works correctly.
 *
 * @group user_homepage
 */
class UserHomepageSaveHomepageTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user_homepage', 'block'];

  /**
   * A user account with permissions to set his own homepage.
   *
   * @var \Drupal\user\Entity\User
   */
  private $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create user with permission to set a custom homepage.
    $this->user = $this->drupalCreateUser(['configure own homepage']);

    // Add the 'save' homepage button to a theme region.
    $this->placeBlock('user_homepage_save_button');
  }

  /**
   * Tests the user can save a homepage and is redirected to it upon login.
   */
  public function testSaveHomepageAndRedirectAfterLogin() {
    $this->drupalLogin($this->user);
    $this->drupalGet('<front>');
    $this->assertSession()->buttonExists('Save as homepage');
    $this->drupalPostForm('/node?earthworm=jim&notromeo=donquixote', [], 'Save as homepage');
    $this->drupalLogout();

    $this->drupalLogin($this->user);
    $this->assertEquals($this->baseUrl . '/' . urlencode(urlencode(urlencode('node?earthworm=jim&notromeo=donquixote'))), $this->getUrl());
  }

  /**
   * Tests user is not redirected if there is a 'destination' param the in URL.
   */
  public function testRedirectionNotTriggeredWhenDestinationParamPresent() {
    $this->drupalLogin($this->user);
    $this->drupalGet('<front>');
    $this->assertSession()->buttonExists('Save as homepage');
    $this->drupalPostForm('/node', [], 'Save as homepage');
    $this->drupalLogout();

    // Assert that final page is the one specified in the 'destination' param.
    $this->drupalLoginWithDestination($this->user, 'user/' . $this->user->id() . '/edit');
    $this->assertEquals($this->baseUrl . '/user/' . $this->user->id() . '/edit', $this->getUrl());
  }

  /**
   * Performs a user login with a 'destination' param being set on the URL.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to log in with.
   * @param string $destination
   *   The destination path to redirect the account to after login.
   *
   * @see BrowserTestBase::drupalLogin()
   */
  private function drupalLoginWithDestination(AccountInterface $account, $destination) {
    if ($this->loggedInUser) {
      $this->drupalLogout();
    }

    $this->drupalGet('user/login', ['query' => ['destination' => $destination]]);
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm([
      'name' => $account->getDisplayName(),
      'pass' => $account->passRaw,
    ], t('Log in'));

    // @see BrowserTestBase::drupalUserIsLoggedIn()
    $account->sessionId = $this->getSession()->getCookie($this->getSessionName());
    $this->assertTrue($this->drupalUserIsLoggedIn($account), new FormattableMarkup('User %name successfully logged in.', ['name' => $account->getAccountName()]));

    $this->loggedInUser = $account;
    $this->container->get('current_user')->setAccount($account);
  }

}
