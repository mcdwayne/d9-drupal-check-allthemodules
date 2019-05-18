<?php

namespace Drupal\Tests\entity_graph_usage\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\Tests\BrowserTestBase;

class EntityGraphUsageTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use EntityReferenceTestTrait;
  use StringTranslationTrait;

  /**
   * The admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_graph',
    'entity_graph_usage',
    'node',
  ];

  /**
   * Name of the reference field.
   *
   * @var string
   */
  protected $fieldName = 'reference_test';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access entity usage information',
      'access administration pages',
      'administer content types',
    ]);

    $this->drupalLogin($this->adminUser);

    $this->createContentType([
      'type' => 'article',
    ]);

    // Create a field.
    $this->createEntityReferenceField(
      'node',
      'article',
      $this->fieldName,
      'Field test',
      'node',
      'default',
      ['target_bundles' => ['article']],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );

    // Enable usage tab on nodes.
    $edit = [
      'node[article]' => 'article',
    ];
    $this->drupalPostForm('admin/config/content/entity_graph_usage', $edit, $this->t('Save configuration'));
    drupal_flush_all_caches();
  }

  /**
   * Test usage tab.
   */
  public function testAdminTitle() {
    // Add node that will be referenced.
    $node1 = $this->createNode([
      'type' => 'article',
      'title' => 'node 1',
    ]);

    // Add a node that references the one above.
    $node2 = $this->createNode([
      'type' => 'article',
      'title' => 'node 2',
      $this->fieldName => [
        ['target_id' => 1],
      ],
    ]);

    // Add node that references both nodes.
    $node3 = $this->createNode([
      'type' => 'article',
      'title' => 'node 3',
      $this->fieldName => [
        ['target_id' => 1],
        ['target_id' => 2],
      ],
    ]);

    $this->drupalGet('/entity_graph_usage/node/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('node 2');
    $this->assertSession()->responseContains('node 3');

    $this->drupalGet('/entity_graph_usage/node/2');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('node 3');

    $this->drupalGet('entity_graph_usage/node/3');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('This entity is not referenced by any other entity.');
  }

}
