<?php

namespace Drupal\Tests\linkback\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\linkback\Entity\Linkback;
use Drupal\linkback\Exception\LinkbackException;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Test linkback validation constraints.
 *
 * @group linkback
 */
class LinkbackValidationTest extends EntityKernelTestBase {

  protected $strictConfigSchema = FALSE;

  protected $node;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'linkback',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a node type for testing.
    $type = NodeType::create(['type' => 'page', 'name' => 'page']);
    $type->save();

    FieldStorageConfig::create([
      'field_name' => 'field_linkback',
      'entity_type' => 'node',
      'type' => 'linkback_handlers',
    ])->save();

    $field_config = FieldConfig::create([
      'field_name' => 'field_linkback',
      'entity_type' => 'node',
      'bundle' => 'page',
      'default_value' => [
        [
          'linkback_send' => 1,
          'linkback_receive' => 1,
        ],
      ],
      'field_type' => 'linkback_handlers',
    ]);

    $field_config->save();

    // Create a node for testing.
    $this->createUser();
    $this->node = Node::create(['type' => 'page', 'title' => 'test', 'uid' => 1]);
    $this->node->save();

    $this->installEntitySchema('linkback');
    $this->installSchema('node', ['node_access']);
  }

  /**
   * Tests the linkback field validation constraints.
   */
  public function testFieldValidation() {
    $linkback = Linkback::create([
      'url' => 'http://example.com',
      'title' => 'Test',
      'excerpt' => 'Test',
      'handler' => 'Test',
      'ref_content' => 1,
    ]);

    $violations = $linkback->validate();
    $this->assertEquals(count($violations), 0, 'No violations when validating a default linkback.');

    $linkback->set('url', NULL);
    $violations = $linkback->validate();
    $this->assertEquals(count($violations), 1, 'Violation found when URL is not set.');
    $this->assertEquals($violations[0]->getPropertyPath(), 'url');
    $this->assertEquals($violations[0]->getMessage(), 'This value should not be null.');

    $linkback->set('url', '');
    $violations = $linkback->validate();
    $this->assertEquals(count($violations), 1, 'Violation found when URL is set to an empty string.');
    $this->assertEquals($violations[0]->getPropertyPath(), 'url');

    $linkback->set('url', 'http://example.com');
    $linkback->set('title', $this->randomString(256));
    $violations = $linkback->validate();
    $this->assertEquals(count($violations), 1, 'Violation found when title is too long.');
    $this->assertEquals($violations[0]->getPropertyPath(), 'title.0.value');
    $this->assertEquals($violations[0]->getMessage(), '<em class="placeholder">Title</em>: may not be longer than 255 characters.');

    $linkback->set('title', NULL);
    $violations = $linkback->validate();
    $this->assertEquals(count($violations), 1, 'Violation found when title is not set.');
    $this->assertEquals($violations[0]->getPropertyPath(), 'title');
    $this->assertEquals($violations[0]->getMessage(), 'This value should not be null.');

    $linkback->set('title', '');
    $violations = $linkback->validate();
    $this->assertEquals(count($violations), 1, 'Violation found when title is set to an empty string.');
    $this->assertEquals($violations[0]->getPropertyPath(), 'title');

    $linkback->set('title', 'Test');
    $linkback->set('excerpt', NULL);
    $violations = $linkback->validate();
    $this->assertEquals(count($violations), 1, 'Violation found when excerpt is not set.');
    $this->assertEquals($violations[0]->getPropertyPath(), 'excerpt');
    $this->assertEquals($violations[0]->getMessage(), 'This value should not be null.');

    $linkback->set('excerpt', '');
    $violations = $linkback->validate();
    $this->assertEquals(count($violations), 1, 'Violation found when excerpt is set to an empty string.');
    $this->assertEquals($violations[0]->getPropertyPath(), 'excerpt');

    $linkback->set('excerpt', 'Test');
    $linkback->set('handler', $this->randomString(256));
    $violations = $linkback->validate();
    $this->assertEquals(count($violations), 1, 'Violation found when handler is too long.');
    $this->assertEquals($violations[0]->getPropertyPath(), 'handler.0.value');
    $this->assertEquals($violations[0]->getMessage(), '<em class="placeholder">Handler</em>: may not be longer than 255 characters.');

    $linkback->set('handler', NULL);
    $violations = $linkback->validate();
    $this->assertEquals(count($violations), 1, 'Violation found when handler is not set.');
    $this->assertEquals($violations[0]->getPropertyPath(), 'handler');
    $this->assertEquals($violations[0]->getMessage(), 'This value should not be null.');

    $linkback->set('handler', '');
    $violations = $linkback->validate();
    $this->assertEquals(count($violations), 1, 'Violation found when handler is set to an empty string.');
    $this->assertEquals($violations[0]->getPropertyPath(), 'handler');

    $linkback->set('handler', 'Test');

    // We get 2 errors here:
    //   - Content reference is not set.
    //   - Entity validation saying linkback is disabled.
    $linkback->set('ref_content', NULL);
    $violations = $linkback->validate();
    $this->assertEquals(count($violations), 2, 'Violation found when content reference is not set.');

    $linkback->set('ref_content', '');
    $violations = $linkback->validate();
    $this->assertEquals(count($violations), 2, 'Violation found when content reference is set to an empty string.');
  }

  /**
   * Test failed linkback entity creation.
   */
  public function testFailedLinkbackCreation() {
    $this->setExpectedException(LinkbackException::class, 'The refback-handler must be provided.');

    $linkback = Linkback::create([
      'url' => 'http://example.com',
      'title' => 'Test',
      'excerpt' => 'Test',
      'handler' => NULL,
      'ref_content' => 1,
    ]);

    $linkback->preSave(\Drupal::entityTypeManager()->getStorage('linkback'));
  }

  /**
   * Tests the linkback entity validation constraints.
   */
  public function testEntityValidation() {
    $linkback = Linkback::create([
      'url' => 'http://example.com',
      'title' => 'Test',
      'excerpt' => 'Test',
      'handler' => 'Test',
      'ref_content' => 1,
    ]);

    $violations = $linkback->validate();
    $this->assertEquals(count($violations), 0, 'No violations when validating a default linkback.');

    $this->node->field_linkback->linkback_receive = 0;
    $this->node->save();

    $violations = $linkback->validate();
    $this->assertEquals(count($violations), 1, 'Violation found when ref-back is not allowed.');
    $this->assertEquals($violations[0]->getMessage(), 'Content with id <em class="placeholder">1</em> has the receive linkbacks disabled.');

    $this->node->field_linkback->linkback_receive = 1;
    $this->node->save();

    $linkback->save();
    $linkback = Linkback::create([
      'url' => 'http://example.com',
      'title' => 'Test',
      'excerpt' => 'Test',
      'handler' => 'Test',
      'ref_content' => 1,
    ]);

    $violations = $linkback->validate();
    $this->assertEquals(count($violations), 1, 'Violation found when ref-back already exists.');
    $this->assertEquals($violations[0]->getMessage(), 'The <em class="placeholder">Test</em> linkback from url (<em class="placeholder">http://example.com</em>) to content with id <em class="placeholder">1</em> is already registered.');
  }

}
