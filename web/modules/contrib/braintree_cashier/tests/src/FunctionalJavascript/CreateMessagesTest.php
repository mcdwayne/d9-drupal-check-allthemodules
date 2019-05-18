<?php

namespace Drupal\Tests\braintree_cashier\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests that appropriate messages are created after Braintree Cashier activity.
 */
class CreateMessagesTest extends WebDriverTestBase {

  use BraintreeCashierTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['braintree_cashier'];

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * A user with permission to administer braintree cashier.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $cashierAdmin;

  /**
   * The monthly billing plan.
   *
   * @var \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface
   */
  protected $monthlyBillingPlan;

  /**
   * The processor declined billing plan.
   *
   * @var \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface
   */
  protected $processorDeclinedBillingPlan;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->container->get('config.factory')
      ->getEditable('user.settings')
      ->set('register', 'visitors')
      ->set('verify_mail', FALSE)
      ->save();
    $this->setupBraintreeApi();
    $this->monthlyBillingPlan = $this->createMonthlyBillingPlan();
    $this->processorDeclinedBillingPlan = $this->createProcessorDeclinedBillingPlan();
    $this->cashierAdmin = $this->drupalCreateUser([
      'administer braintree cashier',
      'access user profiles',
    ]);
  }

  /**
   * Tests that a message records new account creation after a plan is selected.
   */
  public function testNewAccountAfterPlanMessage() {
    $this->drupalGet('plans--sandbox');
    $page = $this->getSession()->getPage();
    $this->createScreenshot('/tmp/screenshot.jpg');
    $page->clickLink('Sign up now');

    $this->doAccountRegistration();

    $new_user = user_load_by_name('tester');
    $new_user_uid = $new_user->id();
    $this->drupalGet('user');
    $this->assertSession()->pageTextNotContains('Braintree Cashier Activity');
    $this->drupalLogout();

    $this->drupalLogin($this->cashierAdmin);
    $this->drupalGet('user/' . $new_user_uid);
    $this->getSession()->getPage()->clickLink('Braintree Cashier Activity');
    $this->assertSession()->pageTextContains('tester account created after selecting CI Monthly (' . $this->monthlyBillingPlan->id() . ') (CI Monthly for $12 / month)');
  }

  /**
   * Do new account registration.
   */
  public function doAccountRegistration() {
    $page = $this->getSession()->getPage();

    $page->fillField('Username', 'tester');
    $page->fillField('Email address', 'tester@example.com');
    $page->fillField('Password', '12345');
    $page->fillField('Confirm password', '12345');
    $page->pressButton('Create new account');

    $this->assertSession()->pageTextContains('Registration successful. You are now logged in.');
  }

  /**
   * A helper function to do a card signup.
   */
  public function doCardSignup() {
    $this->fillInCardForm($this, [
      'card_number' => '4242424242424242',
      'expiration' => '1123',
      'cvv' => '123',
      'postal_code' => '12345',
    ]);

    $this->getSession()->getPage()->find('css', '#submit-button')->click();
    $this->assertSession()->waitForElementVisible('css', '.messages--status', 20000);
    $this->assertSession()->pageTextContains('You have been signed up for the CI Monthly plan. Thank you, and enjoy your subscription!');
  }

  /**
   * Tests that a message is recorded after a new subscription is created.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testNewSubscriptionCreatedMessage() {
    $this->drupalGet('plans--sandbox');
    $page = $this->getSession()->getPage();
    $page->clickLink('Sign up now');

    $this->doAccountRegistration();
    // Get rid of the status message so that we can wait for the message status
    // element after card signup.
    $this->getSession()->reload();

    $this->doCardSignup();
    /** @var \Drupal\user\Entity\User $new_user */
    $new_user = user_load_by_name('tester');
    $this->drupalLogout();

    $this->drupalLogin($this->cashierAdmin);
    $this->drupalGet('user/' . $new_user->id());

    $this->getSession()->getPage()->clickLink('Braintree Cashier Activity');
    $text = 'The user ' . $new_user->getAccountName() . ' had a new subscription';
    $this->assertSession()->pageTextContains($text);
    $text = 'created from the CI Monthly billing plan';
    $this->assertSession()->pageTextContains($text);
  }

  /**
   * Tests that an error message is recorded for Braintree API errors.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testProcessorDeclinedMessage() {
    $this->drupalGet('plans--sandbox');
    $page = $this->getSession()->getPage();
    $page->clickLink('Sign up now');

    $this->doAccountRegistration();

    $this->getSession()->getPage()->selectFieldOption('Choose a plan', $this->processorDeclinedBillingPlan->id());
    $this->fillInCardForm($this, [
      'card_number' => '4242424242424242',
      'expiration' => '1123',
      'cvv' => '123',
      'postal_code' => '12345',
    ]);

    $this->getSession()->getPage()->find('css', '#submit-button')->click();

    $this->assertSession()->waitForElementVisible('css', '.messages--error', 25000);
    $this->createScreenshot('/tmp/screenshot.jpg');
    $this->assertSession()->pageTextContains('Card declined. Please either choose a different payment method or contact your bank');
    $this->assertSession()->pageTextContains('You have not been charged.');

    $this->drupalLogout();

    $this->drupalLogin($this->cashierAdmin);
    /** @var \Drupal\user\Entity\User $new_user */
    $new_user = user_load_by_name('tester');

    $this->drupalGet('user/' . $new_user->id());
    $this->getSession()->getPage()->clickLink('Braintree Cashier Activity');
    $this->assertSession()->pageTextContains($new_user->getAccountName() . ' had the following error with the Braintree API: ');
  }

  /**
   * Tests that a message is created when a user cancels their subscription.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function testSubscriptionCanceledByUserMessage() {
    $this->drupalGet('plans--sandbox');
    $page = $this->getSession()->getPage();
    $page->clickLink('Sign up now');

    $this->doAccountRegistration();
    // Get rid of the status message so that we can wait for the message status
    // element after card signup.
    $this->getSession()->reload();

    $this->doCardSignup();

    $this->drupalGet('user');
    $this->getSession()->getPage()->clickLink('Subscription');
    $this->getSession()->getPage()->clickLink('Cancel');
    $this->getSession()->getPage()->pressButton('Cancel my subscription');
    $this->getSession()->getPage()->pressButton('Yes, I wish to cancel.');
    $this->drupalLogout();

    $this->drupalLogin($this->cashierAdmin);
    /** @var \Drupal\user\Entity\User $new_user */
    $new_user = user_load_by_name('tester');

    $this->drupalGet('user/' . $new_user->id());
    $this->getSession()->getPage()->clickLink('Braintree Cashier Activity');
    $this->assertSession()->pageTextContains($new_user->toUrl()->toString() . ' canceled their subscription');

  }

  /**
   * Tests that a message records that a user updated their payment method.
   *
   * Also checks that a Braintree customer created message was made.
   */
  public function testPaymentMethodUpdatedMessage() {
    $this->drupalGet('user/register');
    $this->doAccountRegistration();
    $this->drupalGet('user');
    $this->getSession()->getPage()->clickLink('Subscription');
    $this->getSession()->getPage()->clickLink('Payment Method');
    // The first time creates a new Braintree Customer instead of updating
    // a payment method.
    $this->fillInCardForm($this, [
      'card_number' => '4242424242424242',
      'expiration' => '1123',
      'cvv' => '123',
      'postal_code' => '12345',
    ]);
    $this->getSession()->getPage()->find('css', '#submit-button')->click();
    $this->assertSession()->waitForElementVisible('css', '.messages--status', 20000);
    $this->assertSession()->pageTextContains('Your payment method has been updated successfully!');

    // Refresh the page to get rid of the status message.
    $this->getSession()->reload();

    $this->assertSession()->waitForElementVisible('css', '.braintree-loaded');
    $this->getSession()->getPage()->find('css', '.braintree-toggle')->click();

    $this->fillInCardForm($this, [
      'card_number' => '4111111111111111',
      'expiration' => '1123',
      'cvv' => '123',
      'postal_code' => '12345',
    ]);
    $this->getSession()->getPage()->find('css', '#submit-button')->click();
    $this->assertSession()->waitForElementVisible('css', '.messages--status', 20000);
    $this->assertSession()->pageTextContains('Your payment method has been updated successfully!');

    $this->drupalLogout();

    $this->drupalLogin($this->cashierAdmin);
    $new_user = user_load_by_name('tester');
    $this->drupalGet('user/' . $new_user->id());
    $this->getSession()->getPage()->clickLink('Braintree Cashier Activity');
    $this->createScreenshot('/tmp/screenshot.jpg');
    $this->assertSession()->pageTextContains('tester updated their payment method to the following type: Braintree\CreditCard');
    $this->assertSession()->pageTextContains('A new Braintree customer was created for tester');
  }

}
