<?php

namespace Drupal\Tests\uc_store\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\uc_store\Traits\AddressTestTrait;
use Drupal\uc_country\Entity\Country;

/**
 * Tests Ajax for the uc_address form element country and zone selection.
 *
 * @group file
 */
class AddressElementTest extends JavascriptTestBase {
  use AddressTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['uc_store'];

  /**
   * User with privileges to do everything.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Permissions for administrator user.
   *
   * @var string[]
   */
  public static $adminPermissions = [
    'access administration pages',
    'administer store',
  ];

  /**
   * Typical Address objects to test.
   *
   * Do not modify these in test functions! Test functions may run in any order
   * or simultaneously, leading to unpredictable results if these objects are
   * modified by a test. Instead, clone these objects and operate on the clone.
   *
   * @var \Drupal\uc_store\Address[]
   */
  protected $testAddresses = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Need to have some permissions to access the test page.
    $this->adminUser = $this->drupalCreateUser(static::$adminPermissions);
    $this->drupalLogin($this->adminUser);

    // Enable a random selection of 20 countries so we're not always
    // testing with the 1 site default.
    $countries = \Drupal::service('country_manager')->getAvailableList();
    $country_ids = array_rand($countries, 20);
    foreach ($country_ids as $country_id) {
      // Don't use the country UI, we're not testing that here...
      Country::load($country_id)->enable()->save();
    }
    // Last one of the 20 gets to be the store default country.
    $store_settings = \Drupal::configFactory()->getEditable('uc_store.settings');
    $store_settings->set('address.country', $country_id)->save();

    // Create a random address object for use in tests.
    $this->testAddresses[] = $this->createAddress();
  }

  /**
   * Tests population of zone select element via Ajax when country is changed.
   */
  public function testZoneAjax() {
    // Use testAddresses created in setUp().
    $address = clone($this->testAddresses[0]);

    // Go to the store settings page.
    $this->drupalGet('admin/store/config/store');
    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Go to the store address tab.
    $page->clickLink('Store address');
    $assert->assertWaitOnAjaxRequest();

    // Fill in the country. This should trigger Ajax to get the zones for that
    // country and populate the zone selection element if there are zones or
    // hide it if there are no zones.
    $page->findField('address[country]')->selectOption($address->getCountry());
    $assert->assertWaitOnAjaxRequest();
    $field = $page->findField('address[country]');
    $this->assertNotEmpty($field);
    $this->assertEquals($field->getValue(), $address->getCountry());

    // Don't try to set the zone unless the country has zones!
    if ($page->findField('address[zone]')) {
      $page->findField('address[zone]')->selectOption($address->getZone());
      $assert->assertWaitOnAjaxRequest();
      $field = $page->findField('address[zone]');
      $this->assertNotEmpty($field);
      $this->assertEquals($field->getValue(), $address->getZone());
    }
    else {
      // If there are no zones, the zone select is hidden.
      // Verify that this country really doesn't have zones.
      $zone_list = \Drupal::service('country_manager')->getZoneList($address->getCountry());
      $assert->assert(empty($zone_list), 'Zone field not shown and there are no zones.');
      $this->assertFalse($page->findField('address[zone]'));
    }

    // Fill in the remaining form fields.
    $edit = [
      'address[street1]' => $address->getStreet1(),
      'address[street2]' => $address->getStreet2(),
      'address[city]' => $address->getCity(),
      'address[postal_code]' => $address->getPostalCode(),
    ];
    $this->drupalPostForm(NULL, $edit, 'Save configuration');
    $page->clickLink('Store address');
    $assert->assertWaitOnAjaxRequest();

    // Compare values set in config against what we just submitted, to check
    // that the submission did the right thing.
    $store_settings = \Drupal::configFactory()->get('uc_store.settings');
    $this->assertEquals($store_settings->get('address.street1'), $address->getStreet1());
    $this->assertEquals($store_settings->get('address.street2'), $address->getStreet2());
    $this->assertEquals($store_settings->get('address.zone'), $address->getZone());
    $this->assertEquals($store_settings->get('address.country'), $address->getCountry());
    $this->assertEquals($store_settings->get('address.postal_code'), $address->getPostalCode());
    $this->assertEquals($store_settings->get('address.city'), $address->getCity());
  }

}
