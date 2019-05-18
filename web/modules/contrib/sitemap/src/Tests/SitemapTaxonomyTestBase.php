<?php

namespace Drupal\sitemap\Tests;

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Tests\TaxonomyTestBase;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Base class for some Sitemap test cases.
 */
abstract class SitemapTaxonomyTestBase extends TaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['sitemap', 'node', 'taxonomy'];

  /**
   * A vocabulary entity.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  protected $vocabulary;

  /**
   * A string to identify the field name for testing terms.
   *
   * @var string
   */
  protected $fieldTagsName;

  /**
   * An array of taxonomy terms.
   *
   * @var array
   */
  protected $terms;

  /**
   * A user account to test with.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  public $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a vocabulary.
    $this->vocabulary = $this->createVocabulary();

    // Create user, then login.
    $this->user = $this->drupalCreateUser([
      'administer sitemap',
      'access sitemap',
      'administer nodes',
      'create article content',
      'administer taxonomy',
    ]);
    $this->drupalLogin($this->user);

    // Configure the sitemap to display the vocabulary.
    $vid = $this->vocabulary->id();
    $edit = [
      "show_vocabularies[$vid]" => $vid,
    ];
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));
  }

  /**
   * Create taxonomy terms.
   *
   * @param \Drupal\taxonomy\Entity\Vocabulary $vocabulary
   *   Taxonomy vocabulary.
   *
   * @return array
   *   List of tags.
   *
   * @throws \Exception
   */
  protected function createTerms(Vocabulary $vocabulary) {
    $terms = [
      $this->createTerm($vocabulary),
      $this->createTerm($vocabulary),
      $this->createTerm($vocabulary),
    ];
    $this->terms = $terms;

    // Make term 2 child of term 1, term 3 child of term 2.
    $edit = [
      // Term 1.
      'terms[tid:' . $terms[0]->id() . ':0][term][tid]' => $terms[0]->id(),
      'terms[tid:' . $terms[0]->id() . ':0][term][parent]' => 0,
      'terms[tid:' . $terms[0]->id() . ':0][term][depth]' => 0,
      'terms[tid:' . $terms[0]->id() . ':0][weight]' => 0,

      // Term 2.
      'terms[tid:' . $terms[1]->id() . ':0][term][tid]' => $terms[1]->id(),
      'terms[tid:' . $terms[1]->id() . ':0][term][parent]' => $terms[0]->id(),
      'terms[tid:' . $terms[1]->id() . ':0][term][depth]' => 1,
      'terms[tid:' . $terms[1]->id() . ':0][weight]' => 0,

      // Term 3.
      'terms[tid:' . $terms[2]->id() . ':0][term][tid]' => $terms[2]->id(),
      'terms[tid:' . $terms[2]->id() . ':0][term][parent]' => $terms[1]->id(),
      'terms[tid:' . $terms[2]->id() . ':0][term][depth]' => 2,
      'terms[tid:' . $terms[2]->id() . ':0][weight]' => 0,
    ];
    $this->drupalPostForm('admin/structure/taxonomy/manage/' . $vocabulary->get('vid') . '/overview', $edit, t('Save'));

    return $terms;
  }

  /**
   * Create node and assign tags to it.
   *
   * @param array $terms
   *   An array of taxonomy terms to apply to the node.
   *
   * @throws \Exception
   */
  protected function createNodeWithTerms(array $terms = []) {
    if (empty($terms)) {
      $this->terms = $this->createTerms($this->vocabulary);
    }

    // Add an entityreference field to a node bundle.
    $this->addEntityreferenceField();

    $values = [];
    foreach ($terms as $term) {
      $values[] = $term->getName();
    }
    $title = $this->randomString();
    $edit = [
      'title[0][value]' => $title,
      $this->fieldTagsName . '[target_id]' => implode(',', $values),
    ];
    $this->drupalPostForm('node/add/article', $edit, t('Save and publish'));
  }

  /**
   * Add an entityreference field to tag nodes.
   */
  protected function addEntityreferenceField() {
    $this->fieldTagsName = 'field_' . $this->vocabulary->id();

    $handler_settings = [
      'target_bundles' => [
        $this->vocabulary->id() => $this->vocabulary->id(),
      ],
      'auto_create' => TRUE,
    ];

    // Create the entity reference field for terms.
    $this->createEntityReferenceField('node', 'article', $this->fieldTagsName, 'Tags', 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    // Configure for autocomplete display.
    EntityFormDisplay::load('node.article.default')
      ->setComponent($this->fieldTagsName, [
        'type' => 'entity_reference_autocomplete_tags',
      ])
      ->save();
  }

}
