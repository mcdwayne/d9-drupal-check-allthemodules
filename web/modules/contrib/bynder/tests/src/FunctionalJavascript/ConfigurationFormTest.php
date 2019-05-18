<?php

namespace Drupal\Tests\bynder\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests the Configuration form for bynder API.
 *
 * @group bynder
 */
class ConfigurationFormTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['bynder', 'help', 'bynder_test_module'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([
      'administer bynder configuration',
      'access administration pages',
    ]));

    // Set dummy initial values to prevent derivatives batch reload from
    // triggering on the initial save.
    $this->config('bynder.settings')
      ->set('consumer_key', 'consumer_key')
      ->set('consumer_secret', 'consumer_secret')
      ->set('token', 'token')
      ->set('token_secret', 'token_secret')
      ->set('account_domain', 'account_domain')
      ->save();
  }

  /**
   * Tests the configuration form.
   */
  public function testConfigurationForm() {
    $old_hash = \Drupal::state()->get('bynder_config_hash');
    $this->assertNotEmpty($old_hash, 'Config hash is calculated.');
    \Drupal::state()->set('bynder.bynder_test_brands', 'valid data');
    $this->drupalGet('admin/config/services');
    $this->assertSession()->pageTextContains('Bynder');
    $this->assertSession()->pageTextContains('Configure integration with Bynder');

    $this->clickLink('Bynder');
    $this->assertSession()->pageTextContains('Usage restriction metadata');
    $this->assertSession()->pageTextContains('To set usage restriction metaproperty provide valid credentials first.');

    $this->assertSession()->pageTextContains('Consumer key');
    $this->assertSession()->responseContains('Provide the consumer key. For more information check <a href="https://support.getbynder.com/hc/en-us/articles/208734785-Create-API-tokens-for-your-app">Bynder knowlage base</a>.');
    $this->getSession()->getPage()->fillField('credentials[consumer_key]', '11111111-1111-1111-1111111111111111');

    $this->assertSession()->pageTextContains('Consumer secret');
    $this->assertSession()->responseContains('Provide the consumer secret. For more information check <a href="https://support.getbynder.com/hc/en-us/articles/208734785-Create-API-tokens-for-your-app">Bynder knowlage base</a>.');
    $this->getSession()->getPage()->fillField('credentials[consumer_secret]', '22222222222222222222222222222222');

    $this->assertSession()->pageTextContains('Token');
    $this->assertSession()->responseContains('Provide the token. For more information check <a href="https://support.getbynder.com/hc/en-us/articles/208734785-Create-API-tokens-for-your-app">Bynder knowlage base</a>.');
    $this->getSession()->getPage()->fillField('credentials[token]', '33333333-3333-3333-3333333333333333');

    $this->assertSession()->pageTextContains('Token secret');
    $this->assertSession()->responseContains('Provide the token secret. For more information check <a href="https://support.getbynder.com/hc/en-us/articles/208734785-Create-API-tokens-for-your-app">Bynder knowlage base</a>.');
    $this->getSession()->getPage()->fillField('credentials[token_secret]', '44444444444444444444444444444444');

    $this->assertSession()->pageTextContains('Account domain');
    $this->assertSession()->responseContains('Provide your Bynder account domain. It should be in the format "https://bynder-domain.extension". Change "bynder-domain.extension" with the domain provided by Bynder. For more information check <a href="http://docs.bynder.apiary.io/#reference/">Bynder docs</a>.');
    $this->getSession()->getPage()->fillField('credentials[account_domain]', 'https://test.getbynder.com');

    $this->assertSession()->pageTextContains('Debug');
    $this->assertSession()->pageTextContains('Check this setting if you want to have more verbose log messages.');
    $this->getSession()->getPage()->fillField('debug', TRUE);

    $this->assertSession()->pageTextContains('Test connection before saving');
    $this->assertSession()->pageTextContains("Uncheck to allow saving credentials even if connection to Bynder can't be established.");
    $this->assertSession()->checkboxChecked('test_connection');

    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->assertNotEquals($old_hash, \Drupal::state()->get('bynder_config_hash'), 'Config hash was updated after the configuration was changed.');

    $metaproperties = [
      'property1' => [
        'label' => 'Property 1',
        'options' => [
          0 => [
            'label' => 'Option 1',
            'id' => 'option1',
          ],
          1 => [
            'label' => 'Option 2',
            'id' => 'option2',
          ],
          2 => [
            'label' => 'Option 3',
            'id' => 'option3',
          ],
        ],
      ],
      'property2' => [
        'label' => 'Property 2',
        'options' => [],
      ],
    ];
    \Drupal::state()->set('bynder.bynder_test_metaproperties', $metaproperties);

    $this->drupalGet('admin/config/services/bynder');
    $this->assertSession()->fieldValueEquals('credentials[consumer_key]', '11111111-1111-1111-1111111111111111');
    $this->assertSession()->fieldValueEquals('credentials[consumer_secret]', '22222222222222222222222222222222');
    $this->assertSession()->fieldValueEquals('credentials[token]', '33333333-3333-3333-3333333333333333');
    $this->assertSession()->fieldValueEquals('credentials[token_secret]', '44444444444444444444444444444444');
    $this->assertSession()->fieldValueEquals('credentials[account_domain]', 'https://test.getbynder.com');
    $this->assertSession()->fieldValueEquals('debug', TRUE);
    $this->assertSession()->selectExists('usage_metaproperty');
    $this->assertSession()->optionExists('usage_metaproperty', 'property1');
    $this->assertSession()->optionNotExists('usage_metaproperty', 'property2');
    $this->assertSession()->pageTextNotContains('Royalty free restriction level');
    $this->assertSession()->pageTextNotContains('Web license restriction level');
    $this->assertSession()->pageTextNotContains('Print license restriction level');

    $this->getSession()->getPage()->selectFieldOption('usage_metaproperty', 'property1');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->selectFieldOption('restrictions[royalty_free]', 'option1');
    $this->getSession()->getPage()->selectFieldOption('restrictions[web_license]', 'option2');
    $this->getSession()->getPage()->selectFieldOption('restrictions[print_license]', 'option3');

    // Check form validation.
    $this->getSession()->getPage()->fillField('credentials[consumer_key]', '11111111-1111-1111-111111111111111');
    $this->getSession()->getPage()->fillField('credentials[consumer_secret]', '????????????????????????????????');
    $this->getSession()->getPage()->fillField('credentials[token]', '33333333-3333-333!-3333333333333333');
    $this->getSession()->getPage()->fillField('credentials[token_secret]', '!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
    $this->getSession()->getPage()->fillField('credentials[account_domain]', 'http://test.getbynder.comm');
    $this->getSession()->getPage()->pressButton('Save configuration');

    $this->assertSession()->pageTextContains('Select metaproperty which is responsible for usage restriction. This is used to limit what assets can be used. If the information is not provided we assume royalty free.');
    $this->assertSession()->pageTextContains('Select metaproperty option for assets that can be used everywhere.');
    $this->assertSession()->pageTextContains('Select metaproperty option for the assets that may be used only online.');
    $this->assertSession()->pageTextContains('Select metaproperty option for the assets that may be used only for print.');

    $this->assertTrue($this->xpath('//select[@name="restrictions[royalty_free]"]//option[@selected="selected" and @value="option1"]'));
    $this->assertTrue($this->xpath('//select[@name="restrictions[web_license]"]//option[@selected="selected" and @value="option2"]'));
    $this->assertTrue($this->xpath('//select[@name="restrictions[print_license]"]//option[@selected="selected" and @value="option3"]'));

    $this->getSession()->getPage()->selectFieldOption('usage_metaproperty', 'none');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->pageTextNotContains('Royalty free restriction level');
    $this->assertSession()->pageTextNotContains('Web license restriction level');
    $this->assertSession()->pageTextNotContains('Print license restriction level');

    $this->assertSession()->pageTextContains('Consumer key needs to use the pattern 8-4-4-16.');
    $this->assertSession()->pageTextContains('Consumer secret needs to contain only letters and numbers.');
    $this->assertSession()->pageTextContains('Token needs to use only numbers and letters separated with "-".');
    $this->assertSession()->pageTextContains('Token secret needs to contain only letters and numbers.');
    $this->assertSession()->pageTextContains('Account domain expect a valid secure url format, as provided to you by Bynder: "https://bynder-domain.extension/".');

    \Drupal::state()->set('bynder.bynder_test_brands', NULL);
    $this->drupalGet('admin/config/services/bynder');
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->assertSession()->pageTextContains('Could not establish connection with Bynder. Check your credentials or contact support.');
    $this->drupalGet('admin/config/services/bynder');
    $this->getSession()->getPage()->fillField('test_connection', FALSE);
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->assertSession()->pageTextNotContains('Could not establish connection with Bynder. Check your credentials or contact support.');

    $this->drupalGet('admin/config/services/bynder');
    $this->getSession()->getPage()->pressButton('Test connection');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Could not establish connection with Bynder. Check your credentials or contact support.');
    \Drupal::state()->set('bynder.bynder_test_brands', 'valid data');
    $this->getSession()->getPage()->pressButton('Test connection');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('The API connection was established successfully.');
  }

}
