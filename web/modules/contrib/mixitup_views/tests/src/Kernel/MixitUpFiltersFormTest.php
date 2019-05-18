<?php

namespace Drupal\Tests\mixitup_views\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\mixitup_views\Form\MixitupFiltersForm;
use Drupal\mixitup_views\MixitupFunc;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\mixitup_views\MixitupViewsDefaultOptionsService;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\User;

/**
 * Class MixitUpFiltersFormTest.
 *
 * @group MixItUp Views
 * @package Drupal\Tests\mixitup_views\Kernel
 */
class MixitUpFiltersFormTest extends KernelTestBase {

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
   * MixItUpFunc service.
   *
   * @var \Drupal\mixitup_views\MixitupFunc
   */
  protected $mixitupFunc;

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
   */
  protected function setUp() {
    parent::setUp();

    // Installing entities schema.
    $this->installSchema('system', ['sequences', 'key_value_expire']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installEntitySchema('node');

    // Creating instance for class which will be tested.
    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->defaultOptions = new MixitupViewsDefaultOptionsService();
    $this->mixitupFunc = new MixitupFunc($this->defaultOptions, $this->entityTypeManager);
    $this->unit = new MixitupFiltersForm($this->mixitupFunc);

    // Creating vocabulary.
    Vocabulary::create([
      'vid' => 'tags',
      'name' => 'Tags',
      'description' => 'Tags',
    ])->save();

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
   * Covers buildForm method in MixitupFiltersForm class.
   *
   * @covers \Drupal\mixitup_views\Form\MixitupFiltersForm::buildForm
   */
  public function testBuildForm() {

    // Creating MixItUpFunc Service instance with one of nodes.
    $this->mixitupFunc->getRowClasses(5);

    // Defining options array.
    $options = [
      'grouping' => [],
      'row_class' => '',
      'default_row_class' => TRUE,
      'uses_fields' => FALSE,
      'selectors_target' => '.mix',
      'selectors_filter' => '.filter',
      'selectors_sort' => '.sort',
      'load_filter' => 'all',
      'load_sort' => 'default:asc',
      'animation_enable' => 1,
      'animation_effects' => 'fade scale',
      'animation_duration' => '600',
      'animation_easing' => 'ease',
      'animation_perspectiveDistance' => '3000px',
      'animation_perspectiveOrigin' => '50% 50%',
      'animation_queue' => 1,
      'animation_queueLimit' => '1',
      'restrict_vocab' => 0,
      'restrict_vocab_ids' => [],
      'filter_type' => 'checkboxes',
      'agregation_type' => 'or',
      'use_sort' => 0,
      'sorts' =>
        [
          'node_field_data_created' => 'Created',
        ],
      'hide_unchecked_chekboxes' => 0,
    ];

    // Tests build form.
    $form = [];
    $form_state = new FormState();
    $form = $this->unit->buildForm($form, $form_state, $options);

    self::assertEquals('<a href="#reset" id="reset">Reset filters</a>', $form['reset']['#markup']);
    self::assertEquals('checkboxes', $form['filter_tags']['#type']);
    $filter_options = [
      '.tid_2' => 'test name1',
      '.tid_3' => 'test name2',
    ];
    self::assertEquals($filter_options, $form['filter_tags']['#options']);
    self::assertEquals('tags', $form['filter_tags']['#attributes']['vid']);

    // Creating MixItUpFunc Service instance with one of nodes.
    $this->mixitupFunc->getRowClasses(4);
    // Tests build form.
    $form = [];
    $form_state = new FormState();
    $form = $this->unit->buildForm($form, $form_state, $options);
    $filter_options = [
      '.tid_1' => 'test name0',
      '.tid_4' => 'test name3',
      '.tid_2' => 'test name1',
      '.tid_3' => 'test name2',
    ];
    self::assertEquals($filter_options, $form['filter_tags']['#options']);

    // Creating MixItUpFunc Service instance with one of nodes.
    $this->mixitupFunc->getRowClasses(3);
    // Tests build form.
    $form = [];
    $form_state = new FormState();
    $form = $this->unit->buildForm($form, $form_state, $options);
    unset($filter_options);
    $filter_options = [
      '.tid_1' => 'test name0',
      '.tid_5' => 'test name4',
      '.tid_4' => 'test name3',
      '.tid_2' => 'test name1',
      '.tid_3' => 'test name2',
    ];
    self::assertEquals($filter_options, $form['filter_tags']['#options']);

    // Creating MixItUpFunc Service instance with one of nodes.
    $this->mixitupFunc->getRowClasses(2);
    // Tests build form.
    $form = [];
    $form_state = new FormState();
    $form = $this->unit->buildForm($form, $form_state, $options);
    unset($filter_options);
    $filter_options = [
      '.tid_3' => 'test name2',
      '.tid_4' => 'test name3',
      '.tid_1' => 'test name0',
      '.tid_5' => 'test name4',
      '.tid_2' => 'test name1',
    ];
    self::assertEquals($filter_options, $form['filter_tags']['#options']);

    // Creating MixItUpFunc Service instance with one of nodes.
    $this->mixitupFunc->getRowClasses(1);
    // Tests build form.
    $form = [];
    $form_state = new FormState();
    $form = $this->unit->buildForm($form, $form_state, $options);
    unset($filter_options);
    $filter_options = [
      '.tid_2' => 'test name1',
      '.tid_4' => 'test name3',
      '.tid_3' => 'test name2',
      '.tid_1' => 'test name0',
      '.tid_5' => 'test name4',
    ];
    self::assertEquals($filter_options, $form['filter_tags']['#options']);

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
