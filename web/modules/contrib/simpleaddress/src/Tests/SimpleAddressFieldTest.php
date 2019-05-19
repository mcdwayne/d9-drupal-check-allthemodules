<?php

namespace Drupal\simpleaddress\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal;
use Drupal\Core\Locale\CountryManager;


/**
 * Tests the creation of telephone fields.
 */
class SimpleAddressFieldTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'field',
    'node',
    'simpleaddress'
  );

  protected $instance;
  protected $web_user;

  public static function getInfo() {
    return array(
      'name'  => 'Simple address field',
      'description'  => "Test the creation of simple address fields.",
      'group' => 'Field types'
    );
  }

  function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'article'));
    $this->article_creator = $this->drupalCreateUser(array('create article content', 'edit own article content'));
    $this->drupalLogin($this->article_creator);
  }

  // Test fields.

  /**
   * Helper function for testSimpleAddressField().
   */
  function testSimpleAddressField() {

    // Add the simple address field to the article content type.
    entity_create('field_storage_config', array(
      'name' => 'field_simple_address',
      'entity_type' => 'node',
      'type' => 'simpleaddress',
    ))->save();
    entity_create('field_instance_config', array(
      'field_name' => 'field_simple_address',
      'label' => 'Simple Address',
      'entity_type' => 'node',
      'bundle' => 'article',
    ))->save();

    entity_get_form_display('node', 'article', 'default')
      ->setComponent('field_simple_address', array(
        'type' => 'simpleaddress_default',
      ))
      ->save();

    entity_get_display('node', 'article', 'default')
      ->setComponent('field_simple_address', array(
        'type' => 'simpleaddress_default',
        'weight' => 1,
      ))
      ->save();

    // Display creation form.
    $this->drupalGet('node/add/article');

    $properties = array(
      'streetAddress' => '102, Olive Grove',
      'addressLocality' => 'Swindon',
      'addressRegion' => 'Wiltshire',
      'postalCode' => 'SN25 9RT',
      'postOfficeBoxNumber' => 'P.O. Box 12345',
      'addressCountry' => 'GB',
    );

    foreach ($properties as $key => $value) {
      $this->assertFieldByName("field_simple_address[0][$key]", '', "$key input found.");
    }

    // Test basic entery of telephone field.
    $edit = array(
      'title[0][value]' => $this->randomName(),
    );
    foreach ($properties as $key => $value) {
      $edit["field_simple_address[0][$key]"] = $value;
    }

    $this->drupalPostForm(NULL, $edit, t('Save'));

    $countries = \Drupal::service('country_manager')->getList();
    foreach ($properties as $key => $value) {
      if($key == 'addressCountry') {
        $value = $countries[$value];
      }
      $this->assertRaw("<span itemprop=\"$key\">$value</span>", "$key property is displayed on the page.");
    }
  }
}
