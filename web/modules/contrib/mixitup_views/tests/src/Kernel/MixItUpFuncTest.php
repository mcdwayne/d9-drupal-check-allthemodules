<?php

namespace Drupal\Tests\mixitup_views\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\mixitup_views\MixitupFunc;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\mixitup_views\MixitupViewsDefaultOptionsService;
use Drupal\user\Entity\User;

/**
 * Class MixItUpFuncTest.
 *
 * @group MixItUp Views
 * @package Drupal\Tests\mixitup_views\Kernel
 */
class MixItUpFuncTest extends KernelTestBase {

  /**
   * Class which will be tested.
   *
   * @var \Drupal\mixitup_views\MixitupFunc
   */
  protected $unit;

  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Default options service.
   *
   * @var \Drupal\mixitup_views\MixitupViewsDefaultOptionsService
   */
  protected $defaultOptions;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'field',
    'text',
    'entity_reference',
    'user',
    'node',
    'taxonomy',
    'mixitup_views',
  ];

  /**
   * Before a test method is run, setUp() is invoked.
   *
   * Create new unit object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp() {
    parent::setUp();

    // Installing entities schema.
    $this->installSchema('system', ['sequences', 'key_value_expire']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('node');

    // Creating instance for class which will be tested.
    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->defaultOptions = new MixitupViewsDefaultOptionsService();
    $this->unit = new MixitupFunc($this->defaultOptions, $this->entityTypeManager);

    for ($i = 0; $i < 5; $i++) {
      // Creating the user.
      $user = User::create([
        'uid' => $i,
        'name' => 'user_name' . strval($i),
        'mail' => 'email' . strval($i) . '@email.com',
        'password' => 'password',
        'status' => '1',
        'role' => '1',
      ]);
      $user->save();

      // Creating taxonomy term.
      $taxonomy = Term::create(['vid' => 'tags']);
      $taxonomy->setName('test name' . strval($i));
      $taxonomy->save();

      // Creating node.
      $node = Node::create([
        'type' => 'article',
        'title' => 'Test node #' . strval($i),
        'uid' => $user->id(),
        'tags' => [
          0 => [
            'target_id' => $taxonomy->id(),
          ],
        ],
      ]);
      $node->save();
    }

    // Adding test data to taxonomy_index table.
    $taxonomy_index_query = db_insert('taxonomy_index')->fields([
      'nid',
      'tid',
      'status',
      'sticky',
      'created',
    ]);

    $taxonomy_index_query->values(['5', '2', '1', '0', time()])->execute();
    $taxonomy_index_query->values(['5', '3', '1', '0', time()])->execute();
    $taxonomy_index_query->values(['4', '1', '1', '0', time()])->execute();
    $taxonomy_index_query->values(['4', '4', '1', '0', time()])->execute();
    $taxonomy_index_query->values(['3', '1', '1', '0', time()])->execute();
    $taxonomy_index_query->values(['3', '5', '1', '0', time()])->execute();
    $taxonomy_index_query->values(['2', '3', '1', '0', time()])->execute();
    $taxonomy_index_query->values(['2', '4', '1', '0', time()])->execute();
    $taxonomy_index_query->values(['1', '2', '1', '0', time()])->execute();
    $taxonomy_index_query->values(['1', '4', '1', '0', time()])->execute();
  }

  /**
   * @covers \Drupal\mixitup_views\MixitupFunc::getRowClasses
   */
  public function testGetRowClasses() {
    // Check for equaling.
    self::assertEquals('tid_2 tid_3', $this->unit->getRowClasses(5));
    self::assertEquals('tid_1 tid_4', $this->unit->getRowClasses(4));
    self::assertEquals('tid_1 tid_5', $this->unit->getRowClasses(3));
    self::assertEquals('tid_3 tid_4', $this->unit->getRowClasses(2));
    self::assertEquals('tid_2 tid_4', $this->unit->getRowClasses(1));
  }

  /**
   * @covers \Drupal\mixitup_views\MixitupFunc::getNodeTids
   */
  public function testGetNodeTids() {
    // Check for equaling.
    self::assertEquals([0 => 2, 1 => 3], $this->unit->getNodeTids(5));
    self::assertEquals([0 => 1, 1 => 4], $this->unit->getNodeTids(4));
    self::assertEquals([0 => 1, 1 => 5], $this->unit->getNodeTids(3));
    self::assertEquals([0 => 3, 1 => 4], $this->unit->getNodeTids(2));
    self::assertEquals([0 => 2, 1 => 4], $this->unit->getNodeTids(1));
  }

  /**
   * If test has finished running, tearDown() will be invoked.
   *
   * Unset the $unit object.
   */
  public function tearDown() {
    unset($this->unit);
  }

}
