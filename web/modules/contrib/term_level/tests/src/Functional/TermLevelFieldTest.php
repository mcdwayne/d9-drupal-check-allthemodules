<?php

namespace Drupal\Tests\term_level\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\taxonomy\Functional\TaxonomyTestTrait;

/**
 * Tests the functionality of Term level field type in BrowserTestBase tests.
 *
 * @group term_level
 */
class TermLevelFieldTest extends BrowserTestBase {

  use TaxonomyTestTrait;

  /**
   * The vocabulary object used in the test.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  protected $vocabulary;

  /**
   * The taxonomy term objects used in the test.
   *
   * @var \Drupal\taxonomy\TermInterface[]
   */
  protected $terms = [];

  /**
   * The taxonomy term names used in the test.
   *
   * @var array
   */
  protected $termNames = [
    'Nginx',
    'Drupal 8',
    'Javascript',
  ];

  /**
   * The term level field levels used in the test.
   *
   * @var array
   */
  protected $levels = [
    0 => 'n/a',
    3 => 'Basic',
    6 => 'Intermediate',
    9 => 'Expert',
  ];

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field_ui', 'term_level'];

  /**
   * {@inheritdoc}
   *
   * Prepare test user and test vocabulary with test terms.
   */
  protected function setUp() {
    parent::setUp();

    // Create test CV content type.
    $this->createContentType(['type' => 'curriculum_vitae']);

    // Create random test vocabulary.
    $this->vocabulary = $this->createVocabulary();

    // Create test terms.
    foreach ($this->termNames as $term_name) {
      $this->terms[$term_name] = $this->createTerm($this->vocabulary, ['name' => $term_name]);
    }
  }

  /**
   * Helper function to build levels value for the field settings.
   *
   * @return string
   *   Key value pairs in format "key|value", one per line.
   */
  protected function buildLevelsValue() {
    $value = [];
    foreach ($this->levels as $level_key => $level_value) {
      $value[] = $level_key . '|' . $level_value;
    }
    return implode("\n", $value);
  }

  /**
   * Tests the term level field.
   */
  public function testTermLevelField() {
    $this->drupalLogin($this->rootUser);
    // Add test term level field to CV node type.
    $values = [
      'new_storage_type' => 'term_level',
      'label' => 'Test term level',
      'field_name' => 'test_term_level',
    ];
    $this->drupalPostForm('admin/structure/types/manage/curriculum_vitae/fields/add-field', $values, 'Save and continue');
    // Configure levels and cardinality.
    $values = [
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings[levels]' => $this->buildLevelsValue(),
    ];
    $this->drupalPostForm('admin/structure/types/manage/curriculum_vitae/fields/node.curriculum_vitae.field_test_term_level/storage', $values, 'Save field settings');
    // Configure referenced vocabulary.
    $this->drupalGet('admin/structure/types/manage/curriculum_vitae/fields/node.curriculum_vitae.field_term_level');
    $vocabulary_id = $this->vocabulary->id();
    $values = [
      'label' => 'Term level',
      'settings[handler]' => 'default:taxonomy_term',
      'settings[handler_settings][target_bundles][' . $vocabulary_id . ']' => $vocabulary_id,
    ];
    $this->drupalPostForm('admin/structure/types/manage/curriculum_vitae/fields/node.curriculum_vitae.field_test_term_level', $values, 'Save settings');
    // Display the term level field on the node view.
    $values = [
      'fields[field_test_term_level][weight]' => '0',
      'fields[field_test_term_level][parent]' => '',
      'fields[field_test_term_level][region]' => 'content',
      'fields[field_test_term_level][label]' => 'above',
      'fields[field_test_term_level][type]' => 'term_level_formatter',
    ];
    $this->drupalPostForm('admin/structure/types/manage/curriculum_vitae/display', $values, 'Save');
    // Test term level form element to contain expected options.
    $this->drupalGet('node/add/curriculum_vitae');
    $assert_session = $this->assertSession();
    foreach ($this->levels as $level_key => $level_value) {
      $option_element = $assert_session->optionExists('field_test_term_level[0][level]', $level_value);
      // Test the level select does exist with expected key value pairs.
      $this->assertTrue($option_element);
      $this->assertEquals($level_key, $option_element->getValue());
    }
    // Create the CV test node with term level field.
    $this->drupalGet('node/add/curriculum_vitae');
    // Add two more term level field items.
    $this->drupalPostForm(NULL, [], 'Add another item');
    $this->drupalPostForm(NULL, [], 'Add another item');
    // Create test test CV node.
    $values = [
      'title[0][value]' => 'Test CV',
      'field_test_term_level[0][target_id]' => 'Nginx (' . $this->terms['Nginx']->id() . ')',
      'field_test_term_level[0][level]' => '0',
      'field_test_term_level[0][_weight]' => '0',
      'field_test_term_level[1][target_id]' => 'Drupal 8 (' . $this->terms['Drupal 8']->id() . ')',
      'field_test_term_level[1][level]' => '9',
      'field_test_term_level[1][_weight]' => '1',
      'field_test_term_level[2][target_id]' => 'Javascript (' . $this->terms['Javascript']->id() . ')',
      'field_test_term_level[2][level]' => '6',
      'field_test_term_level[2][_weight]' => '2',
    ];
    $this->drupalPostForm(NULL, $values, 'Save');
    // Test the term level is properly shown with expected values.
    $term_level_element = $assert_session->elementExists('css', '.field--name-field-test-term-level');
    $term_level_element_text = $term_level_element->getText();
    $this->assertContains('Nginx : n/a', $term_level_element_text);
    $this->assertContains('Drupal 8 : Expert', $term_level_element_text);
    $this->assertContains('Javascript : Intermediate', $term_level_element_text);
  }

}
