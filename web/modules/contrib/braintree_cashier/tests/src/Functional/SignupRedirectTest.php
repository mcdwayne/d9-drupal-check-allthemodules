<?php

namespace Drupal\Tests\braintree_cashier\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests redirects from the /signup url.
 *
 * @group braintree_cashier
 */
class SignupRedirectTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['braintree_cashier'];

  protected $strictConfigSchema = FALSE;

  /**
   * Tests that anonymous users are redirected.
   *
   * They should be sent from /signup to the user registration page.
   */
  public function testRedirectAnonymous() {
    $this->drupalGet('signup');
    $this->assertSession()->pageTextContains('Create new account');
    $this->assertSession()->addressEquals('user/register');
  }

  /**
   * Tests that a logged in user is redirected.
   *
   * The redirect applies to users who have previously made a purchase. They
   * will be redirected to the My Subscription tab.
   */
  public function testRedirectExistingCustomer() {
    $account = $this->drupalCreateUser();
    $account->braintree_customer_id = '123';
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('signup');
    $this->assertSession()->pageTextContains('My subscription');
    $this->assertSession()->addressMatches('/user\/\d+\/subscription/');
  }

}
