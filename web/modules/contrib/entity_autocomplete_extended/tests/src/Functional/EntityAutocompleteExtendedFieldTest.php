<?php

namespace Drupal\Tests\entity_autocomplete_extended\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\field\Functional\EntityReference\EntityReferenceIntegrationTest;

/**
 * Test the EntityReferenceAutocompleteExtendedWidget.
 *
 * @group entity_autocomplete_extended
 */
class EntityAutocompleteExtendedFieldTest extends EntityReferenceIntegrationTest {

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
   * Tests that the autocomplete method returns the good number of results.
   */
  public function testAutocompleteCountResults() {
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
      $url = $this->getAbsoluteUrl($result[0]->getAttribute('data-autocomplete-path'));

      // Test that no matching term found.
      $data = $this->drupalGetJson(
        $url,
        ['query' => ['q' => 'zzz']]
      );
      $this->assertTrue(empty($data), 'Autocomplete returned no results');

      // Test that only one matching term found, when only one matches.
      $data = $this->drupalGetJson(
        $url,
        ['query' => ['q' => 'aaa 10']]
      );
      $this->assertEquals(1, count($data), 'Autocomplete returned 1 result');

      // Test the correct number of matches when multiple are partial matches.
      $data = $this->drupalGetJson(
        $url,
        ['query' => ['q' => 'aaa 1']]
      );
      $this->assertEquals(3, count($data), 'Autocomplete returned 3 results');

      // Tests that only 10 results are returned, even if there are more than 10
      // matches.
      $data = $this->drupalGetJson(
        $url,
        ['query' => ['q' => 'aaa']]
      );
      $this->assertEquals(10, count($data), 'Autocomplete returned only 10 results (for over 10 matches)');

      // Increase results limit to 15.
      $form_display->setComponent($this->fieldName, [
        'type' => $type,
        'settings' => [
          'results_limit' => 15,
        ],
      ])->save();

      $this->drupalGet($this->entityType . '/add');
      $result = $this->xpath('//input[@name="' . $this->fieldName . $name_element);
      $url = $this->getAbsoluteUrl($result[0]->getAttribute('data-autocomplete-path'));

      // Tests that all 11 results are returned.
      $data = $this->drupalGetJson(
        $url,
        ['query' => ['q' => 'aaa']]
      );
      $this->assertEquals(11, count($data), 'Autocomplete returned 11 results');

      // Decrease results limit to 5.
      $form_display->setComponent($this->fieldName, [
        'type' => $type,
        'settings' => [
          'results_limit' => 5,
        ],
      ])->save();

      $this->drupalGet($this->entityType . '/add');
      $result = $this->xpath('//input[@name="' . $this->fieldName . $name_element);
      $url = $this->getAbsoluteUrl($result[0]->getAttribute('data-autocomplete-path'));

      // Tests that only 5 results are returned, even if there are more than 5
      // matches.
      $data = $this->drupalGetJson(
        $url,
        ['query' => ['q' => 'aaa']]
      );
      $this->assertEquals(5, count($data), 'Autocomplete returned only 5 results (for over 5 matches)');
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

  /**
   * Retrieves a Drupal path or an absolute path and JSON decodes the result.
   *
   * @param \Drupal\Core\Url|string $path
   *   Drupal path or URL to request AJAX from.
   * @param array $options
   *   Array of URL options.
   * @param array $headers
   *   Array of headers. Eg array('Accept: application/vnd.drupal-ajax').
   *
   * @return array
   *   Decoded json.
   */
  protected function drupalGetJson($path, array $options = [], array $headers = []) {
    return Json::decode($this->drupalGetWithFormat($path, 'json', $options, $headers));
  }

  /**
   * Retrieves a Drupal path or an absolute path for a given format.
   *
   * @param \Drupal\Core\Url|string $path
   *   Drupal path or URL to request given format from.
   * @param string $format
   *   The wanted request format.
   * @param array $options
   *   Array of URL options.
   * @param array $headers
   *   Array of headers.
   *
   * @return mixed
   *   The result of the request.
   */
  protected function drupalGetWithFormat($path, $format, array $options = [], array $headers = []) {
    $options = array_merge_recursive(['query' => ['_format' => $format]], $options);
    return $this->drupalGet($path, $options, $headers);
  }

}
