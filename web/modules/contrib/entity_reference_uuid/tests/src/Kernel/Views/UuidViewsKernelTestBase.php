<?php

namespace Drupal\Tests\entity_reference_uuid\Kernel\Views;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;
use Drupal\views\Tests\ViewResultAssertionTrait;
use Drupal\views\Tests\ViewTestData;

/**
 * A base class for Views kernel testing of UUID references.
 *
 * @see \Drupal\Tests\views\Kernel\ViewsKernelTestBase
 */
abstract class UuidViewsKernelTestBase extends KernelTestBase {

  use ViewResultAssertionTrait;

  /**
   * Views to be enabled.
   *
   * Test classes should override this property and provide the list of testing
   * views.
   *
   * @var array
   */
  public static $testViews = [];

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'views', 'user', 'node', 'field', 'text', 'filter', 'options', 'entity_reference_uuid', 'entity_reference_uuid_test'];

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var array
   */
  protected $entities = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['router', 'sequences', 'key_value_expire']);
    $this->installSchema('node', ['node_access']);

    $this->installEntitySchema('user');
    $this->installEntitySchema('test_entity_one');
    $this->installEntitySchema('test_entity_two');
    $this->installEntitySchema('node');

    $this->installConfig(static::$modules);

    $values = [
      'name' => 'test user',
      'mail' => 'foo@example.com',
      'status' => TRUE,
    ];
    $this->user = User::create($values);
    $this->user->save();
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->setUpFixtures();
    ViewTestData::createTestViews(get_class($this), ['entity_reference_uuid_test']);
  }

  /**
   * Sets up the content needed for tests.
   */
  protected function setUpFixtures() {

    // Create test_nodetype_one nodes first, since they are only reference
    // targets.
    $test_nodetype_one = [
      [
        'uuid' => 'f4924e8b-133b-4d37-b25b-542341850639',
        'title' => 'Dummy one one',
        'field_test_nodetype_one_text' => 'one',
      ],
      [
        'uuid' => 'cc505ae3-aeb8-4883-bc8d-4fd1906ce0f1',
        'title' => 'Dummy one two',
        'field_test_nodetype_one_text' => 'two',
      ],
      [
        'uuid' => 'dc2edcbe-8fca-4b26-9850-c5fc77c0c1e0',
        'title' => 'Dummy one three',
        'field_test_nodetype_one_text' => 'three',
      ],
      [
        'uuid' => '40894f20-922f-4564-ad68-19b67d4520f5',
        'title' => 'Dummy one four',
        'field_test_nodetype_one_text' => 'dog',
      ],
    ];
    $this->createNodes('test_nodetype_one', $test_nodetype_one);

    // Create test_entity_one entities, since they are also only reference
    // targets.
    $test_entity_one = [
      [
        'name' => 'Target one one',
        'uuid' => '799cbc6f-b819-47c5-abc6-8bfd430a6574',
      ],
      [
        'name' => 'Target one two',
        'uuid' => '65170b9b-2b3c-416b-8bce-3d843bff890c',
      ],
      [
        'name' => 'Target one three',
        'uuid' => '4ae62194-1fae-4c3d-b210-ba4b0ad71f7e',
        'status' => FALSE,
      ],
      [
        'name' => 'Target one four',
        'uuid' => '14b15f51-3519-45ec-941a-d004bd0c1d24',
      ],
      [
        'name' => 'Target one five',
        'uuid' => 'a6b05258-4381-4b15-83eb-f2b2edc3f1f3',
      ],
    ];
    $this->createEntities('test_entity_one', $test_entity_one);

    $test_nodetype_two = [
      [
        'uuid' => '8a4c2dbe-e87d-4edd-a58f-40cb00092ecb',
        'title' => 'Llama two one',
        'field_node_one_ref' => 'cc505ae3-aeb8-4883-bc8d-4fd1906ce0f1',
      ],
      [
        'uuid' => '3999aafc-8b3c-4005-9bf8-554cbfb0df22',
        'title' => 'Llama two two',
        'field_node_one_ref' => 'dc2edcbe-8fca-4b26-9850-c5fc77c0c1e0',
      ],
      [
        'uuid' => '1c73e274-7077-45dc-943a-655259d2ae6f',
        'title' => 'Llama two three',
        'field_node_one_ref' => '40894f20-922f-4564-ad68-19b67d4520f5',
        'field_entity_one_ref' => '14b15f51-3519-45ec-941a-d004bd0c1d24',
      ],
      [
        'uuid' => 'dffc353f-cde2-4d7b-98ad-82f157ffdd72',
        'title' => 'Llama two four',
      ],
      [
        'uuid' => '567a1ae4-e542-459c-aef1-976aa66b15b5',
        'title' => 'Llama two five',
        'field_entity_one_ref' => 'a6b05258-4381-4b15-83eb-f2b2edc3f1f3',
      ],
    ];
    $this->createNodes('test_nodetype_two', $test_nodetype_two);

    $test_entity_two = [
      [
        'name' => 'Mister two one',
        'uuid' => '208adf04-b0ea-4d8c-b744-e574ec97d1d2',
        'entity_one_ref' => '799cbc6f-b819-47c5-abc6-8bfd430a6574',
        // References a test_nodetype_two.
        'node_one_ref' => '8a4c2dbe-e87d-4edd-a58f-40cb00092ecb',
      ],
      [
        'name' => 'Mister two two',
        'uuid' => '83109432-2657-4217-bb1a-9bed7ef78599',
        'entity_one_ref' => '65170b9b-2b3c-416b-8bce-3d843bff890c',
        // References a test_nodetype_one.
        'node_one_ref' => 'f4924e8b-133b-4d37-b25b-542341850639',
      ],
      [
        'name' => 'Mister two three',
        'uuid' => '4ccccf2e-805c-421e-b029-bfa79dc7b006',
        'entity_one_ref' => '799cbc6f-b819-47c5-abc6-8bfd430a6574',
        // References a test_nodetype_one.
        'node_one_ref' => 'f4924e8b-133b-4d37-b25b-542341850639',
        'status' => FALSE,
      ],
      [
        'name' => 'Mister two four',
        'uuid' => '16b64581-e212-4a1e-a0c7-c471bf914eea',
        'entity_one_ref' => '4ae62194-1fae-4c3d-b210-ba4b0ad71f7e',
        // References a test_nodetype_one.
        'node_one_ref' => 'cc505ae3-aeb8-4883-bc8d-4fd1906ce0f1',
      ],
    ];
    $this->createEntities('test_entity_two', $test_entity_two);
  }

  protected function createNodes($type, array $list) {
    foreach ($list as $values) {
      $values += [
        'type' => $type,
        'status' => TRUE,
        'uid' => $this->user->id(),
      ];
      $n = $this->entityTypeManager->getStorage('node')->create($values);
      $errors = $n->validate();
      foreach ($errors as $e) {
        $this->verbose((string) $e);
      }
      $this->assertCount(0, $errors);
      $n->save();
      $this->entities[$n->uuid()] = $n;
    }
  }

  protected function createEntities($type, array $list) {
    foreach ($list as $values) {
      $values += [
        'status' => TRUE,
      ];
      $n = $this->entityTypeManager->getStorage($type)->create($values);
      $errors = $n->validate();
      foreach ($errors as $e) {
        $this->verbose((string) $e);
      }
      $this->assertCount(0, $errors);
      $n->save();
      $this->entities[$n->uuid()] = $n;
    }
  }

  /**
   * Orders a nested array containing a result set based on a given column.
   *
   * @param array $result_set
   *   An array of rows from a result set, with each row as an associative
   *   array keyed by column name.
   * @param string $column
   *   The column name by which to sort the result set.
   * @param bool $reverse
   *   (optional) Boolean indicating whether to sort the result set in reverse
   *   order. Defaults to FALSE.
   *
   * @return array
   *   The sorted result set.
   */
  protected function orderResultSet($result_set, $column, $reverse = FALSE) {
    $order = $reverse ? -1 : 1;
    usort($result_set, function ($a, $b) use ($column, $order) {
      if ($a[$column] == $b[$column]) {
        return 0;
      }
      return $order * (($a[$column] < $b[$column]) ? -1 : 1);
    });
    return $result_set;
  }

  /**
   * Executes a view with debugging.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view object.
   * @param array $args
   *   (optional) An array of the view arguments to use for the view.
   */
  protected function executeView($view, array $args = []) {
    $view->setDisplay();
    $view->preExecute($args);
    $view->execute();
    $verbose_message = '<pre>Executed view: ' . ((string) $view->build_info['query']) . '</pre>';
    if ($view->build_info['query'] instanceof SelectInterface) {
      $verbose_message .= '<pre>Arguments: ' . print_r($view->build_info['query']->getArguments(), TRUE) . '</pre>';
    }
    $this->verbose($verbose_message);
  }

  /**
   * Creates a field of an entity reference field storage on the specified
   * bundle.
   *
   * @param string $entity_type
   *   The type of entity the field will be attached to.
   * @param string $bundle
   *   The bundle name of the entity the field will be attached to.
   * @param string $field_name
   *   The name of the field; if it already exists, a new instance of the
   *   existing field will be created.
   * @param string $field_label
   *   The label of the field.
   * @param string $target_entity_type
   *   The type of the referenced entity.
   * @param string $selection_handler
   *   The selection handler used by this field.
   * @param array $selection_handler_settings
   *   An array of settings supported by the selection handler specified above.
   *   (e.g. 'target_bundles', 'sort', 'auto_create', etc).
   * @param int $cardinality
   *   The cardinality of the field.
   *
   * @see \Drupal\Core\Entity\Plugin\EntityReferenceSelection\SelectionBase::buildConfigurationForm()
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createEntityReferenceUuidField($entity_type, $bundle, $field_name, $field_label, $target_entity_type, $selection_handler = 'default', $selection_handler_settings = [], $cardinality = 1) {
    // Look for or add the specified field to the requested entity bundle.
    if (!FieldStorageConfig::loadByName($entity_type, $field_name)) {
      FieldStorageConfig::create([
        'field_name' => $field_name,
        'type' => 'entity_reference_uuid',
        'entity_type' => $entity_type,
        'cardinality' => $cardinality,
        'settings' => [
          'target_type' => $target_entity_type,
        ],
      ])->save();
    }
    if (!FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
      FieldConfig::create([
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'bundle' => $bundle,
        'label' => $field_label,
        'settings' => [
          'handler' => $selection_handler,
          'handler_settings' => $selection_handler_settings,
        ],
      ])->save();
    }
  }

}
