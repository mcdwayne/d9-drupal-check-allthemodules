<?php

namespace Drupal\Tests\entity_autocomplete_extended\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Test the EntityReferenceAutocompleteExtendedWidget JS AJAX functionality.
 *
 * @group entity_autocomplete_extended
 */
class EntityAutocompleteExtendedJavascriptTest extends WebDriverTestBase {

  use EntityReferenceTestTrait;

  /**
   * The entity type used in this test.
   *
   * @var string
   */
  protected $entityType = 'entity_test';

  /**
   * The bundle used in this test.
   *
   * @var string
   */
  protected $bundle = 'entity_test';

  /**
   * The name of the field used in this test.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'config_test',
    'entity_test',
    'field_ui',
    'entity_autocomplete_extended',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a test user.
    $web_user = $this->drupalCreateUser(['administer entity_test content', 'administer entity_test fields', 'view test entity']);
    $this->drupalLogin($web_user);
  }

  /**
   * Tests that the autocomplete method returns the good number of results.
   */
  public function testAutocompleteCountResults() {
    $assert_session = $this->assertSession();
    $this->fieldName = 'field_test_autocomplete_count';

    // Create an entity reference field.
    $this->createEntityReferenceField($this->entityType, $this->bundle, $this->fieldName, $this->fieldName, 'entity_test', 'default', [], FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    if (!($form_display = EntityFormDisplay::load($this->entityType . '.' . $this->bundle . '.' . 'default'))) {
      $form_display = EntityFormDisplay::create([
        'targetEntityType' => $this->entityType,
        'bundle' => $this->bundle,
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }

    $this->getCountTestEntities();

    $types = [
      'entity_reference_autocomplete_extended',
      'entity_reference_autocomplete_extended_tags',
    ];
    foreach ($types as $type) {
      // First set widget with default settings (results limit of 10).
      $form_display->setComponent($this->fieldName, [
        'type' => $type,
      ])->save();

      $this->drupalGet($this->entityType . '/add');
      $name_element = '[target_id]"]';
      if ($type === 'entity_reference_autocomplete_extended') {
        $name_element = '[0]' . $name_element;
      }
      $result = $this->xpath('//input[@name="' . $this->fieldName . $name_element);
      $element = reset($result);

      $option_selector = 'ul.ui-autocomplete li.ui-menu-item';

      // Test that no matching term found.
      $element->setValue('zzz');
      $assert_session->waitOnAutocomplete();
      $options = $this->cssSelect($option_selector);
      $this->assertTrue(empty($options), 'Autocomplete returned no results');

      // Test that only one matching term found, when only one matches.
      $element->setValue('aaa 10');
      $assert_session->waitOnAutocomplete();
      $options = $this->cssSelect($option_selector);
      $this->assertEquals(1, count($options), 'Autocomplete returned 1 result');

      // Test the correct number of matches when multiple are partial matches.
      $element->setValue('aaa 1');
      // Wait first for a little bit so that the autocomplete results update.
      $this->getSession()->wait(3000);
      $assert_session->waitOnAutocomplete();
      $options = $this->cssSelect($option_selector);
      $this->assertEquals(3, count($options), 'Autocomplete returned 3 results');

      // Tests that only 10 results are returned, even if there are more than 10
      // matches.
      $element->setValue('aaa');
      // Wait first for a little bit so that the autocomplete results update.
      $this->getSession()->wait(3000);
      $assert_session->waitOnAutocomplete();
      $options = $this->cssSelect($option_selector);
      $this->assertEquals(10, count($options), 'Autocomplete returned only 10 results (for over 10 matches)');

      // Increase results limit to 15.
      $form_display->setComponent($this->fieldName, [
        'type' => $type,
        'settings' => [
          'results_limit' => 15,
        ],
      ])->save();

      $this->drupalGet($this->entityType . '/add');
      $result = $this->xpath('//input[@name="' . $this->fieldName . $name_element);
      $element = reset($result);

      // Tests that all 11 results are returned.
      $element->setValue('aaa');
      $assert_session->waitOnAutocomplete();
      $options = $this->cssSelect($option_selector);
      $this->assertEquals(11, count($options), 'Autocomplete returned 11 results');

      // Decrease results limit to 5.
      $form_display->setComponent($this->fieldName, [
        'type' => $type,
        'settings' => [
          'results_limit' => 5,
        ],
      ])->save();

      $this->drupalGet($this->entityType . '/add');
      $result = $this->xpath('//input[@name="' . $this->fieldName . $name_element);
      $element = reset($result);

      // Tests that only 5 results are returned, even if there are more than 5
      // matches.
      $element->setValue('aaa');
      $assert_session->waitOnAutocomplete();
      $options = $this->cssSelect($option_selector);
      $this->assertEquals(5, count($options), 'Autocomplete returned only 5 results (for over 5 matches)');
    }
  }

  /**
   * Creates 11 entities to be used in autocomplete count tests.
   *
   * @return array
   *   An array of entity objects.
   */
  protected function getCountTestEntities() {
    $names = [
      'aaa 20 bbb',
      'aaa 70 bbb',
      'aaa 10 bbb',
      'aaa 12 bbb',
      'aaa 40 bbb',
      'aaa 11 bbb',
      'aaa 30 bbb',
      'aaa 50 bbb',
      'aaa 80',
      'aaa 90',
      'bbb 60 aaa',
    ];

    $entities = [];
    foreach ($names as $name) {
      $entity = EntityTest::create(['name' => $name]);
      $entity->save();
      $entities[] = $entity;
    }

    return $entities;
  }

}
