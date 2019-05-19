<?php

namespace Drupal\site_map\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\taxonomy\Tests\TaxonomyTestBase;

/**
 * Base class for Site Map test cases.
 */
abstract class SiteMapTestBase extends TaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('site_map', 'node', 'menu_ui');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create filter format.
    $restricted_html_format = entity_create('filter_format', array(
      'format' => 'restricted_html',
      'name' => 'Restricted HTML',
      'filters' => array(
        'filter_html' => array(
          'status' => TRUE,
          'weight' => -10,
          'settings' => array(
            'allowed_html' => '<p> <br /> <strong> <a> <em> <h4>',
          ),
        ),
        'filter_autop' => array(
          'status' => TRUE,
          'weight' => 0,
        ),
        'filter_url' => array(
          'status' => TRUE,
          'weight' => 0,
        ),
        'filter_htmlcorrector' => array(
          'status' => TRUE,
          'weight' => 10,
        ),
      ),
    ));
    $restricted_html_format->save();

    // Create user then login.
    $this->user = $this->drupalCreateUser(array(
      'administer site configuration',
      'access site map',
      'administer menu',
      'administer nodes',
      'create article content',
      'administer taxonomy',
      $restricted_html_format->getPermissionName(),
    ));
    $this->drupalLogin($this->user);
  }

  /**
   * Get tags.
   *
   * @return array
   *   List of tags.
   */
  protected function getTags() {
    return array(
      $this->randomMachineName(),
      $this->randomMachineName(),
      $this->randomMachineName(),
    );
  }

  /**
   * Create taxonomy terms.
   *
   * @param object $vocabulary
   *   Taxonomy vocabulary.
   *
   * @return array
   *   List of tags.
   */
  protected function createTerms($vocabulary) {
    $terms = array(
      $this->createTerm($vocabulary),
      $this->createTerm($vocabulary),
      $this->createTerm($vocabulary),
    );

    // Make term 2 child of term 1, term 3 child of term 2.
    $edit = array(
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
    );
    $this->drupalPostForm('admin/structure/taxonomy/manage/' . $vocabulary->get('vid') . '/overview', $edit, t('Save'));

    return $terms;
  }

  /**
   * Create taxonomy term reference field for testing categories.
   *
   * @param object $vocabulary
   *   Taxonomy vocabulary.
   *
   * @return string
   *   Created field name.
   */
  protected function createTaxonomyTermReferenceField($vocabulary) {
    $field_tags_name = Unicode::strtolower($this->randomMachineName());
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => $field_tags_name,
      'entity_type' => 'node',
      'type' => 'taxonomy_term_reference',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => array(
        'allowed_values' => array(
          array(
            'vocabulary' => $vocabulary->id(),
            'parent' => '0',
          ),
        ),
      ),
    ));
    $field_storage->save();
    entity_create('field_config', array(
      'field_storage' => $field_storage,
      'bundle' => 'article',
    ))->save();
    entity_get_form_display('node', 'article', 'default')
      ->setComponent($field_tags_name, array(
        'type' => 'taxonomy_autocomplete',
      ))
      ->save();
    entity_get_display('node', 'article', 'full')
      ->setComponent($field_tags_name, array(
        'type' => 'taxonomy_term_reference_link',
      ))
      ->save();

    return $field_tags_name;
  }

}
