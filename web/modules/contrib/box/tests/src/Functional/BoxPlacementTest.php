<?php

namespace Drupal\Tests\box\Functional;

use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Test to ensure that authorized users can add and remove box types.
 *
 * @group box
 */
class BoxPlacementTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'box', 'language', 'content_translation'];

  /**
   * A user with permission to view published boxes.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The box storage.
   *
   * @var \Drupal\box\BoxStorageInterface
   */
  protected $boxStorage;

  /**
   * The nodestorage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * The text format we use.
   *
   * @var \Drupal\filter\Entity\FilterFormat
   */
  protected $textFormat;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create admin user.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    $this->user = $this->drupalCreateUser([
      'view published box entities',
      'administer languages',
    ]);
    $this->drupalLogin($this->user);

    // Get box and node storage.
    $this->boxStorage = $this->container->get('entity.manager')->getStorage('box');
    $this->nodeStorage = $this->container->get('entity.manager')->getStorage('node');

    // Add Slovak language.
    $edit = ['predefined_langcode' => 'sk'];
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));
    $this->container->get('language_manager')->reset();

    // Add text format and add 'Insert box' filter.
    $edit = [
      'format' => 'insert_box_format',
      'name' => 'Insert box format',
    ];
    $this->textFormat = FilterFormat::create($edit);
    $this->textFormat->setFilterConfig('filter_box', [
      'status' => 1,
      'weight' => 100,
    ]);
    $this->textFormat->save();
  }

  /**
   * Tests that authorized users can view inserted entities.
   */
  public function testBoxPlacements() {
    // Create box.
    $box_label = $this->randomMachineName();
    $box_machine_name = 'test';
    $box_text = $this->randomMachineName();
    /** @var \Drupal\box\Entity\BoxInterface $box */
    $box = $this->boxStorage->create([
      'type' => 'default',
      'title' => $box_label,
      'uid' => $this->user->id(),
      'machine_name' => $box_machine_name,
      'field_body' => $box_text,
    ]);
    $box->save();

    // Add box translation.
    $box_label_sk = $this->randomMachineName();
    $box_text_sk = $this->randomMachineName();
    $box->addTranslation('sk', [
      'title' => $box_label_sk,
      'field_body' => $box_text_sk,
    ])->save();

    // Create node where to embed box by ID.
    /** @var \Drupal\node\Entity\Node $node_by_id */
    $node_by_id = Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'body' => [
        'value' => "Test box by ID placement in EN [box:{$box->id()}] test",
        'format' => 'insert_box_format',
      ],
    ]);
    $node_by_id->save();
    $node_by_id->addTranslation('sk', [
      'title' => $this->randomMachineName(),
      'body' => [
        'value' => "Test box by ID placement in SK [box:{$box->id()}] test",
        'format' => 'insert_box_format',
      ],
    ])->save();

    // Create node where to embed box by machine name.
    /** @var \Drupal\node\Entity\Node $node_by_machine_name */
    $node_by_machine_name = Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'body' => [
        'value' => "Test box by machine name placement in EN [box-name:{$box_machine_name}] test",
        'format' => 'insert_box_format',
      ],
    ]);
    $node_by_machine_name->save();
    $node_by_machine_name->addTranslation('sk', [
      'title' => $this->randomMachineName(),
      'body' => [
        'value' => "Test box by machine name placement in SK [box-name:{$box_machine_name}] test",
        'format' => 'insert_box_format',
      ],
    ])->save();

    // Check that box is correctly embedded by ID in both languages.
    $this->drupalGet("node/{$node_by_id->id()}");
    $this->assertSession()->responseContains($box_label);
    $this->drupalGet("sk/node/{$node_by_id->id()}");
    $this->assertSession()->responseContains($box_label_sk);

    // Check that box is correctly embedded by machine name in both languages.
    $this->drupalGet("node/{$node_by_machine_name->id()}");
    $this->assertSession()->responseContains($box_label);
    $this->drupalGet("sk/node/{$node_by_machine_name->id()}");
    $this->assertSession()->responseContains($box_label_sk);
  }

}
