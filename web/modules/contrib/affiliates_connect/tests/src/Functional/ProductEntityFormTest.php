<?php

namespace Drupal\Tests\affiliates_connect\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Check if our affiliate product entity working correctly or not.
 *
 * @group affiliates_connect
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ProductEntityFormTest extends BrowserTestBase {

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
  public static $modules = ['affiliates_connect'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser($this->adminUserPermissions);
  }

  /**
   * Test the affiliate product forms including add-form, edit-form and delete-form
   */
  public function testAffiliatesProductForm() {

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/structure/affiliates_connect/products');
    $this->assertSession()->pageTextContains('There are no affiliates product entities yet.');


    $this->drupalGet('/admin/structure/affiliates_connect/product/add');
    $this->assertResponse(200);
    $uid = $this->adminUser->name->value . ' (' . $this->adminUser->uid->value . ')';
    $formdata = [
      'uid[0][target_id]' => $uid,
      'name[0][value]' => 'iPhone',
      'plugin_id[0][value]' => 'affiliates_connect_amazon',
      'product_id[0][value]' => 'MOBDZ3Q7D8Q9HVCP',
      'product_description[0][value]' => 'Lorem Ipsam',
      'product_warranty[0][value]' => '1 year',
      'image_urls[0][value]' => 'https://rukminim1.flixcart.com/image/400/400/mobile/v/c/p/xolo-play-8x-original-imadz3eefsbg5wyy.jpeg?q=90',
      'product_family[0][value]' => 'Mobiles>Handsets',
      'currency[0][value]' => 'INR ',
      'maximum_retail_price[0][value]' => '19990.0',
      'vendor_selling_price[0][value]' => '18890.0',
      'vendor_special_price[0][value]' => '17890.0',
      'product_url[0][value]' => 'https://dl.flipkart.com/dl/xolo-hive-8x-1000-black-32-gb/p/itmdz3q7cs3kk5kc?pid=MOBDZ3Q7D8Q9HVCP',
      'product_brand[0][value]' => 'Apple',
      'in_stock[value]' => 1,
      'cod_available[value]' => 1,
      'discount_percentage[0][value]' => '0',
      'offers[0][value]' => '5% Instant Discount* with SBI Credit Cards,Extra 5% off* with Axis Bank Buzz Credit Card',
      'size[0][value]' => '',
      'color[0][value]' => '',
      'seller_name[0][value]' => 'xyz',
      'seller_average_rating[0][value]' => '4.9',
      'additional_data[0][value]' => '',
      'status[value]' => 1,
    ];
    $this->submitForm($formdata, 'Save');
    $this->assertSession()->pageTextContains('Created the ' . $formdata['name[0][value]'] . ' Affiliates Product.');

    $this->drupalGet('/admin/structure/affiliates_connect/products');
    $this->assertSession()->pageTextContains($formdata['name[0][value]']);

    foreach (['edit', 'delete'] as $tab) {
      $this->drupalGet('/admin/structure/affiliates_connect/product/1/' . $tab);
      $this->assertSession()->pageTextContains($formdata['name[0][value]']);
    }
    $this->drupalGet('/admin/structure/affiliates_connect/product/1/delete');
    $this->getSession()->getPage()->pressButton('Delete');
    $this->assertSession()->pageTextContains('The affiliates product ' . $formdata['name[0][value]'] . ' has been deleted.');
  }
}
