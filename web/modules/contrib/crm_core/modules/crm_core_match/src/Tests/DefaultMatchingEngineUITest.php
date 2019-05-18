<?php

namespace Drupal\crm_core_match\Tests;

use Drupal\crm_core_contact\Entity\IndividualType;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the UI for DefaultMatchingEngine.
 *
 * @group crm_core
 */
class DefaultMatchingEngineUITest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'crm_core_match',
    'crm_core_tests',
    'block',
    'options',
    'datetime',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    IndividualType::create([
      'name' => 'Customer',
      'type' => 'customer',
      'description' => 'A single customer.',
      'primary_fields' => [],
    ])->save();

    // Place local actions blocks.
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('system_breadcrumb_block');
  }

  /**
   * Test basic UI for DefaultMatchingEngine config form.
   */
  public function testDefaultMatchingEngine() {
    // Create and login user.
    $this->drupalLogin($this->drupalCreateUser([
      'administer matchers',
      'view matching engine rules settings',
    ]));

    // Test Organization matcher form.
    $this->drupalGet('admin/config/crm-core/match/organization');

    $this->assertText('Field Matching');
    $this->assertText('Unsupported Fields');
    $this->assertText('A given field is unsupported when there is no field match handler for the field type it belongs to. In order to make it supported a handler plugin should be created for it.');
    $this->assertNoFieldChecked('edit-configuration-rules-nametitle-status', 'There is checkbox for title property of the name field.');
    $this->assertFieldByName('configuration[rules][name:title][operator]', NULL, 'There is title property operator for the name field.');
    $this->assertText('ID', 'There is ID field in a form.');
    $this->assertNoFieldById('edit-configuration-rules-idvalue-status', '', 'There is no checkbox for ID field');
    $this->assertNoFieldById('edit-configuration-rules-idvalue-operator', '', 'There is no ID operator');

    $configuration = [
      'configuration[rules][name:given][status]' => TRUE,
      'configuration[rules][name:given][operator]' => 'CONTAINS',
      'configuration[rules][name:given][options]' => 'John',
      'configuration[rules][name:given][score]' => 1,
      'configuration[rules][name:family][status]' => TRUE,
      'configuration[rules][name:family][operator]' => 'CONTAINS',
      'configuration[rules][name:family][options]' => 'Smith',
      'configuration[rules][name:family][score]' => 1,
    ];
    $this->drupalPostForm(NULL, $configuration, 'Save');

    $this->drupalGet('admin/config/crm-core/match');
    $this->clickLink('Edit', 2);

    $this->assertFieldChecked('edit-configuration-rules-namegiven-status');
    $this->assertFieldById('edit-configuration-rules-namegiven-operator', 'CONTAINS');
    $this->assertFieldById('edit-configuration-rules-namegiven-options', 'John');
    $this->assertFieldById('edit-configuration-rules-namegiven-score', 1);

    $this->assertFieldChecked('edit-configuration-rules-namefamily-status');
    $this->assertFieldById('edit-configuration-rules-namefamily-operator', 'CONTAINS');
    $this->assertFieldById('edit-configuration-rules-namefamily-options', 'Smith');
    $this->assertFieldById('edit-configuration-rules-namefamily-score', 1);
  }

}
