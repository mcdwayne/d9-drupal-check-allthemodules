<?php

namespace Drupal\Tests\address_algolia\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests for the basic usage of an Address field with our widget.
 *
 * @package Drupal\Tests\address_algolia\FunctionalJavascript
 *
 * @group address_algolia
 */
class BasicFieldTest extends AddressAlgoliaJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a test node bundle.
    $type = NodeType::create(['name' => 'aa_test_ct', 'type' => 'aa_test_ct']);
    $type->save();

    // Add the address field to the test content type.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_address',
      'entity_type' => 'node',
      'type' => 'address',
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'aa_test_ct',
      'label' => 'Address field',
      'settings' => [
        'available_countries' => [
          'FR' => 'FR',
        ],
        'fields' => [
          'administrativeArea' => 'administrativeArea',
          'locality' => 'locality',
          'dependentLocality' => 'dependentLocality',
          'postalCode' => 'postalCode',
          'sortingCode' => 'sortingCode',
          'addressLine1' => 'addressLine1',
          'addressLine2' => 'addressLine2',
          'givenName' => 'givenName',
          'additionalName' => 'additionalName',
          'familyName' => 'familyName',
          'organization' => '0',
        ],
        'langcode_override' => '',
      ],
    ]);
    $field->save();

    // Set article's form display.
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = EntityFormDisplay::load('node.aa_test_ct.default');

    if (!$form_display) {
      EntityFormDisplay::create([
        'targetEntityType' => 'node',
        'bundle' => 'aa_test_ct',
        'mode' => 'default',
        'status' => TRUE,
      ])->save();
      $form_display = EntityFormDisplay::load('node.aa_test_ct.default');
    }
    $form_display->setComponent('field_address', [
      'type' => 'address_algolia',
      'settings' => [
        'default_country' => 'FR',
        'use_algolia_autocomplete' => '1',
      ],
    ])->save();

  }

  /**
   * Tests the address algolia field widget.
   */
  public function testFieldWidget() {

    $this->assertTrue(TRUE);
    $page = $this->getSession()->getPage();

    // Go to the node creation page for our test content type and make sure
    // everything is in there.
    $this->drupalGet('/node/add/aa_test_ct');

    $this->assertSession()->pageTextContains('Create aa_test_ct');
    $this->assertSession()->fieldExists('field_address[0][address_line1]');
    $this->assertSession()->elementExists('css', '.algolia-places .ap-dropdown-menu');

    // @TODO The following is not functional, investigate why.
    // Add a known string into the autocomplete field.
    // $page->fillField('field_address[0][address_line1]', '23, rue du Tiv');
    // $this->waitUntilVisible('.ap-dropdown-menu.ap-with-places', '3000');
    // $this->saveHtmlOutput();

  }

}
