<?php

namespace Drupal\currencylayer_currency_converter\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests if Currencylayer currency converter block is available.
 *
 * @group currencylayer_currency_converter
 */
class CurrencylayerCurrencyConverterBlockTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system_test',
    'block',
    'currencylayer_currency_converter'
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create and login user.
    $admin_user = $this->drupalCreateUser(array(
      'administer blocks',
      'administer site configuration',
      'access administration pages',
    ));
    $this->drupalLogin($admin_user);
  }

  /**
   * Test that the Currencylayer currency converter block can be placed and works.
   */
  public function testCurrencylayerCurrencyConverterBlock() {
    // Test availability of the twitter block in the admin "Place blocks" list.
    \Drupal::service('theme_handler')->install(['bartik', 'seven', 'stark']);
    $theme_settings = $this->config('system.theme');
    foreach (['bartik', 'seven', 'stark'] as $theme) {
      $this->drupalGet('admin/structure/block/list/' . $theme);
      $this->assertResponse(200);
      // Configure and save the block.
      $this->drupalPlaceBlock('currencylayer_currency_converter_block', array(
        'currencylayer_currency_converter_from' => 'AED',
        'currencylayer_currency_converter_to' => 'AFN',
        'region' => 'content',
        'theme' => $theme,
      ));
      // Set the default theme and ensure the block is placed.
      $theme_settings->set('default', $theme)->save();
      $this->drupalGet('');
      $this->assertText('Select Your Currency From', 'Currencylayer currency converter block found');
      $edit = [];
      $edit['currencylayer_currency_converter_from'] = 'AED';
      $edit['currencylayer_currency_converter_to'] = 'AFN';
      $edit['amount'] = 100;
      $this->drupalPostForm('', $edit, t('Convert'));
      $result = \Drupal::service('currencylayer_currency_converter.manager')
        ->convertAmount(100, 'AED', 'AFN');
      $output = t('Your selected value is from AED to AFN amount is 100 AED &amp; your converted value is @result AFN', array('@result' => $result));
      $this->assertText($output);

      // Test error message same currency.
      $edit['currencylayer_currency_converter_from'] = 'AED';
      $edit['currencylayer_currency_converter_to'] = 'AED';
      $this->drupalPostForm('', $edit, t('Convert'));
      $this->assertText(t('Please select different currency both currency are same.'));
    }
  }

}
