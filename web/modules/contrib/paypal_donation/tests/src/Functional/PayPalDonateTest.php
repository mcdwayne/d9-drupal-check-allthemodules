<?php

namespace Drupal\Tests\rules\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that PayPal donate module works correctly.
 *
 * @group rules_ui
 */
class PayPalDonateTest extends BrowserTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'paypal_donation'];
  private $amounts = [];
  private $welcomeText = "This is a welcome text";
  private $successText = "This is a success text";
  private $cancelText = "This is a cancel text";
  private $currency = "GBP";

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    foreach (range(3, 10) as $val) {
      $this->amounts[] = rand(1, 100);
    }
    $account = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($account);

    // Going to setting page and enter settings in for later tests.
    $this->drupalGet('admin/config/paypal_donation/settings');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Text which will be shown on the donation form page.');
    $this->getSession()->getPage()->fillField("Welcome text", $this->welcomeText);
    $this->getSession()->getPage()->fillField("Success text", $this->successText);
    $this->getSession()->getPage()->fillField("Cancel text", $this->cancelText);
    $this->getSession()->getPage()->selectFieldOption("Language", "en_US");
    $this->getSession()->getPage()->fillField("PayPal Business email", "mail@example.com");
    $this->getSession()->getPage()->fillField("Organization name/service", "Example Org");
    $this->getSession()->getPage()->fillField("Donation ID:", "Example ID");
    $this->getSession()->getPage()->fillField("Amounts:", implode(",", $this->amounts));
    $this->getSession()->getPage()->selectFieldOption("Currency:", $this->currency);
    $this->getSession()->getPage()->pressButton('Save configuration');
  }

  /**
   * Tests that Donate page works.
   */
  public function testDonatePage() {
    $account = $this->drupalCreateUser(["access content"]);
    $this->drupalLogin($account);

    $this->drupalGet('donate');
    $this->assertSession()->statusCodeEquals(200);
    // Test that there is an empty reaction rule listing.
    $this->assertSession()->pageTextContains($this->welcomeText);

    // Test that 'Donation amount (CURRENCY)' is seen.
    $this->assertSession()->pageTextContains('Donation amount (' . $this->currency . ')');

    // Test that number of offered amounts are the same as on the setting page.
    $this->assertSession()->elementsCount('css', '#edit-amount input', count($this->amounts));
  }

  /**
   * Tests that Success page works.
   */
  public function testSucessPage() {
    $account = $this->drupalCreateUser(["access content"]);
    $this->drupalLogin($account);
    $this->drupalGet('donate/success');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->successText);
  }

  /**
   * Tests that Fail page works.
   */
  public function testFailPage() {
    $account = $this->drupalCreateUser(["access content"]);
    $this->drupalLogin($account);
    $this->drupalGet('donate/fail');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->cancelText);
  }

}
