<?php

namespace Drupal\Tests\freelinking_prepopulate\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\freelinking\Functional\FreelinkingBrowserTestBase;
use Drupal\Tests\taxonomy\Functional\TaxonomyTestTrait;

/**
 * Tests embedded prepopulate links.
 *
 * @group freelinking_prepopulate
 */
class FreelinkingPrepopulateTest extends FreelinkingBrowserTestBase {

  use TaxonomyTestTrait;

  public static $modules = [
    'node',
    'user',
    'field',
    'text',
    'taxonomy',
    'entity_reference',
    'prepopulate',
    'freelinking',
    'freelinking_prepopulate',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Creates a content type to act as default prepopulate content type.
    $this->createContentType(['name' => 'Person', 'type' => 'person']);

    $vocabulary = $this->createVocabulary();

    // Creates a basic text field.
    FieldStorageConfig::create([
      'field_name' => 'field_basic',
      'entity_type' => 'node',
      'type' => 'text',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_basic',
      'entity_type' => 'node',
      'bundle' => 'person',
      'label' => 'Basic',
    ])->save();

    // Creates a taxonomy_term entity_reference field.
    FieldStorageConfig::create([
      'field_name' => 'field_tags',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_tags',
      'entity_type' => 'node',
      'bundle' => 'person',
      'label' => $vocabulary->label(),
      'settings' => [
        'handler' => 'default:taxonomy_term',
        'handler_settings' => [
          'target_bundles' => [$vocabulary->id() => $vocabulary->id()],
          'sort' => ['field' => '_none'],
        ],
        'auto_create' => TRUE,
      ],
    ])->save();

    $formDisplay = EntityFormDisplay::load('node.person.default');
    $formDisplay
      ->setComponent('field_basic', ['type' => 'string_textfield']);
    $formDisplay
      ->setComponent('field_tags', [
        'type' => 'entity_reference_autocomplete_tags',
      ]);
    $formDisplay->save();

    $prefix = 'filters[freelinking][settings][plugins][freelinking_prepopulate]';

    $filter_settings = [
      'filters[freelinking][status]' => 1,
      'filters[freelinking][weight]' => 0,
      $prefix . '[enabled]' => 1,
      $prefix . '[settings][failover]' => 'error',
      $prefix . '[settings][default_node_type]' => 'person',
      'filters[filter_url][weight]' => 1,
      'filters[filter_html][weight]' => 2,
      'filters[filter_autop][weight]' => 3,
      'filters[filter_htmlcorrector][weight]' => 4,
    ];

    $this->updateFilterSettings('plain_text', $filter_settings);
  }

  /**
   * Asserts that prepopulate links are displayed correctly.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testFreelinkingPrepopulate() {
    // Creates a node for the assertions.
    $edit = [];
    $edit['title[0][value]'] = t('Testing all freelinking plugins');
    $edit['body[0][value]'] = <<< EOF
      <ul>
        <li>Default with body: [[create:Alex|Alex|Add Alex|body="A page about Alex."]]</li>
        <li>Basic page: [[create:History of Programming|History of Programming|Create New Page|type=page]]</li>
        <li>With fields: [[create:Sam|Sam|Add Sam|body="A page about Sam."|field_basic="test"|field_tags="tag1,tag2,tag3"]]</li>
      </ul>
EOF;
    $this->drupalPostForm('node/add/page', $edit, t('Save'));

    // This is the most ridiculous developer-unfriendly way to test hrefs ever.
    // DrupalWTF. MinkWTF.
    $defaultLinkHref = '/node/add/person?' .
      rawurlencode('edit[title][widget][0][value]') . '=' .
      rawurlencode('Alex') . '&' .
      rawurlencode('edit[body][widget][0][value]') . '=' .
      rawurlencode('&quot;A page about Alex.&quot;');
    $pageLinkHref = '/node/add/page?' . rawurlencode('edit[title][widget][0][value]') . '=History%20of%20Programming';
    $fieldsLinkHref = '/node/add/person?' .
      rawurlencode('edit[title][widget][0][value]') . '=Sam&' .
      rawurlencode('edit[body][widget][0][value]') . '=' .
      rawurlencode('&quot;A page about Sam.&quot;') . '&' .
      rawurlencode('edit[field_basic][widget][0][value]') . '=' .
      rawurlencode('&quot;test&quot;') . '&' .
      rawurlencode('edit[field_tags][widget][target_id]') . '=' .
      rawurlencode('&quot;tag1,tag2,tag3&quot;');

    $this->assertSession()
      ->linkByHrefExists($defaultLinkHref);
    $this->assertSession()
      ->linkByHrefExists($pageLinkHref);
    $this->assertSession()
      ->linkByHrefExists($fieldsLinkHref);

    // Logout and visit the page.
    $this->drupalLogout();
    $this->drupalGet('/node/3');
    $this->assertSession()
      ->pageTextContains('Freelinking: Access denied to create missing content.');
    $this->assertSession()
      ->linkByHrefNotExists($defaultLinkHref, 0, 'Link to create default content type not found.');
    $this->assertSession()
      ->linkByHrefNotExists($pageLinkHref, 0, 'Link to create specified content type not found.');
    $this->assertSession()
      ->linkByHrefNotExists($fieldsLinkHref, 0, 'Link to create content with fields not found.');
  }

}
