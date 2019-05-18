<?php

namespace Drupal\Tests\affiliates_connect\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Url;

/**
 * Check if our defined routes are working correctly or not.
 *
 * @group affiliates_connect
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class AmazonPluginTest extends BrowserTestBase {

  /**
   * An admin user used for this test.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * The permissions of the admin user.
   *
   * @var string[]
   */
  protected $adminUserPermissions = [
    'administer affiliates product entities',
    'add affiliates product entities',
    'delete affiliates product entities',
    'edit affiliates product entities',
    'view published affiliates product entities',
    'view unpublished affiliates product entities',
    'access administration pages',
  ];

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'affiliates_connect',
    'affiliates_connect_amazon'
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser($this->adminUserPermissions);
    // For admin
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test the availability of affiliates_connect_amazon on overview page.
   */
  public function testAmazonPlugin() {
    $this->drupalGet(URL::fromRoute('affiliates_connect.overview'));
    $this->assertResponse(200);
    $this->assertSession()->pageTextContains('Plugin provided by affiliates_connect_amazon.');
  }

  /**
   * Test the affiliates_connect_amazon config form.
   */
  public function testAmazonPluginConfigForm() {
    $this->drupalGet(URL::fromRoute('affiliates_connect_amazon.settings'));
    $this->assertResponse(200);
    // Test the form elements exist and have defaults.
    $config = $this->config('affiliates_connect_amazon.settings');
    $this->assertFieldByName(
      'amazon_associate_id',
      $config->get('amazon_associate_id'),
      'Affiliate Tracking ID has the defult value'
    );
    $this->assertFieldByName(
      'amazon_access_key',
      $config->get('amazon_access_key'),
      'Access Key has the defult value'
    );
    $this->assertFieldByName(
      'amazon_secret_key',
      $config->get('amazon_secret_key'),
      'Secret Key has the defult value'
    );

    $checkbox = $this->xpath('//input[@name="save_searched_products"]');
    $checked = $checkbox[0]->isChecked();
    $this->assertIdentical($checked, false, "Checkbox save_searched_products is unchecked");

    // Test form submission.
    $checkboxes = $this->xpath('//input[@type="checkbox"]');
    foreach ($checkboxes as $checkbox) {
      $checkbox->check();
    }

    $formdata = [
      'amazon_associate_id' => 'loremipsum_id',
      'amazon_access_key' => 'loremipsum_access_key',
      'amazon_secret_key' => 'loremipsum_secret_key',
      'locale' => 'IN',
    ];
    $this->submitForm($formdata, 'Save configuration');
    // Get new config
    $config = $this->config('affiliates_connect_amazon.settings');
    $this->assertFieldByName(
      'amazon_associate_id',
      $config->get('amazon_associate_id'),
      'Affiliate Tracking matched'
    );
    $this->assertFieldByName(
      'amazon_access_key',
      $config->get('amazon_access_key'),
      'Access Key matched'
    );
    $this->assertFieldByName(
      'amazon_secret_key',
      $config->get('amazon_secret_key'),
      'Secret Key matched'
    );
    $this->assertFieldByName(
      'locale',
      $config->get('locale')
    );
    // Get all checkboxes
    $checkboxes = $this->xpath('//input[@type="checkbox"]');
    $this->assertIdentical(count($checkboxes), 13, 'Correct number of checkboxes found.');
    foreach ($checkboxes as $checkbox) {
      $checked = $checkbox->isChecked();
      $name = (string) $checkbox->getAttribute('name');
      $this->assertIdentical($checked, $name == 'native_api' || $name == 'data_storage' || $name == 'full_content' || $name == 'price' || $name == 'available' || $name == 'size' || $name == 'color' || $name == 'offers' || $name == 'fallback_scraper' || $name == 'save_searched_products' || $name == 'cloaking' || $name == 'enable_hits_analysis' || $name == 'append_affiliate_id', format_string('Checkbox %name correctly checked', ['%name' => $name]));
    }
  }

  /**
   * Test the affiliates_connect_amazon search page.
   */
  public function testAmazonSearchPage() {
    $this->drupalGet(URL::fromRoute('affiliates_connect_amazon.search'));
    $this->assertResponse(200);
  }

}
