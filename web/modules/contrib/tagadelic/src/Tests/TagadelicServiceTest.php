<?php

namespace Drupal\tagadelic\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Language\LanguageInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests for tagadelic service.
 *
 * @group tagadelic
 */
class TagadelicServiceTest extends WebTestBase {

  /**
   * The vocabulary used for creating terms.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('tagadelic', 'taxonomy', 'node');

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    // Create an article content type
    $this->drupalCreateContentType(array(
      'type' => 'article',
    ));

    // Create the vocabulary for the tag field.
    $this->vocabulary = Vocabulary::create(array(
      'name' => 'Views testing tags',
      'vid' => 'views_testing_tags',
    ));
    $this->vocabulary->save();

    $field_name = 'field_' . $this->vocabulary->id();
    $handler_settings = array(
      'target_bundles' => array(
        $this->vocabulary->id() => $this->vocabulary->id(),
      ),
      'auto_create' => TRUE,
    );

    // Create the tag field
    if (!FieldStorageConfig::loadByName('node', $field_name)) {
      FieldStorageConfig::create(array(
        'field_name' => $field_name,
        'type' => 'entity_reference',
        'entity_type' => 'node',
        'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
        'settings' => array(
          'target_type' => 'taxonomy_term',
        ),
      ))->save();
    }

    if (!FieldConfig::loadByName('node', 'article', $field_name)) {
      $handler_settings = array(
        'target_bundles' => array(
          $this->vocabulary->id() => $this->vocabulary->id(),
        ),
        'auto_create' => TRUE,
      );
      FieldConfig::create(array(
        'field_name' => $field_name,
        'entity_type' => 'node',
        'bundle' => 'article',
        'label' => 'Tags',
        'settings' => array(
          'handler' => 'default',
          'handler_settings' => $handler_settings,
        ),
      ))->save();
    }

    entity_get_form_display('node', 'article', 'default')
      ->setComponent($field_name, array(
        'type' => 'entity_reference_autocomplete_tags',
        'weight' => -4,
      ))
      ->save();

    entity_get_display('node', 'article', 'default')
      ->setComponent($field_name, array(
        'type' => 'entity_reference_label',
        'weight' => 10,
      ))
      ->save();
    entity_get_display('node', 'article', 'teaser')
      ->setComponent($field_name, array(
        'type' => 'entity_reference_label',
        'weight' => 10,
      ))
      ->save();
  }

  /**
   * Test block placement.
   */
  function testTagadelicService() {
    $user = $this->drupalCreateUser(['administer taxonomy', 'bypass node access']);
    $this->drupalLogin($user);

    // Create 4 taxonomy terms and and 4 nodes
    // Add various combinations of ther terms' ids to the
    // entity reference field on the nodes
    $term1 = $this->createTerm($this->vocabulary);
    $term2 = $this->createTerm($this->vocabulary);
    $term3 = $this->createTerm($this->vocabulary);
    $term4 = $this->createTerm($this->vocabulary);

    $node = array();
    $node['type'] = 'article';
    $node['field_views_testing_tags'][]['target_id'] = $term1->id();
    $node['field_views_testing_tags'][]['target_id'] = $term2->id();
    $node['field_views_testing_tags'][]['target_id'] = $term3->id();
    $node['field_views_testing_tags'][]['target_id'] = $term4->id();
    $node1 = $this->drupalCreateNode($node);

    $node['field_views_testing_tags'][] = array();
    $node['field_views_testing_tags'][]['target_id'] = $term1->id();
    $node['field_views_testing_tags'][]['target_id'] = $term2->id();
    $node['field_views_testing_tags'][]['target_id'] = $term3->id();
    $node2 = $this->drupalCreateNode($node);

    $node['field_views_testing_tags'][] = array();
    $node['field_views_testing_tags'][]['target_id'] = $term1->id();
    $node['field_views_testing_tags'][]['target_id'] = $term2->id();
    $node['field_views_testing_tags'][]['target_id'] = $term3->id();
    $node3 = $this->drupalCreateNode($node);

    $node['field_views_testing_tags'][] = array();
    $node['field_views_testing_tags'][]['target_id'] = $term1->id();
    $node['field_views_testing_tags'][]['target_id'] = $term2->id();
    $node['field_views_testing_tags'][]['target_id'] = $term3->id();
    $node4 = $this->drupalCreateNode($node);

    // Create the tervice and check that it is returning tags
    $service = \Drupal::service('tagadelic.tagadelic_taxonomy');
    $tags = $service->getTags();
    $this->assertTrue(count($tags) > 0);
    $this->assertEqual(4, count($tags));
    $tag = array_pop($tags);
    $this->assertEqual('Drupal\tagadelic\TagadelicTag', get_class($tag));
  }

  /**
   * Creates and returns a taxonomy term.
   *
   * @param  object $vocabulary
   *   The vocabulary for the returned taxonomy term
   *
   * @return \Drupal\taxonomy\Entity\Term
   *   The created taxonomy term.
   */
  function createTerm($vocabulary) {
    $filter_formats = filter_formats();
    $format = array_pop($filter_formats);
    $term = Term::create(array(
      'name' => $this->randomMachineName(),
      'description' => array(
        'value' => $this->randomMachineName(),
        // Use the first available text format.
        'format' => $format->id(),
      ),
      'vid' => $vocabulary->id(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $term->save();
    return $term;
  }
}
