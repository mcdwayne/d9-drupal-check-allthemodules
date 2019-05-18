<?php

namespace Drupal\Tests\collect\Kernel;

use Drupal\collect\Entity\Container;
use Drupal\collect\Entity\Model;
use Drupal\collect\Model\PropertyDefinition;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests models.
 *
 * @group collect
 */
class ModelTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'collect',
    'collect_test',
    'hal',
    'rest',
    'serialization',
    'user',
    'system',
    'views',
    'collect_common',
    'filter',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('collect_model');
    // Action storage is used in user_user_role_insert().
    $this->installEntitySchema('action');
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('collect_container');
    $this->installSchema('system', ['sequences']);
    $this->installConfig(['collect', 'collect_test']);
  }

  /**
   * Tests setters and getters.
   */
  public function testMutateAccess() {
    $empty = Model::create();

    $this->assertFalse($empty->isLocked());
    $this->assertFalse($empty->isContainerRevision());
    $this->assertEqual(array(), $empty->getProcessors());
    $this->assertEqual('', $empty->getPluginId());
    $this->assertEqual('', $empty->getUriPattern());

    $nonempty = Model::create([
      'id' => 'tomato',
      'label' => t('Tomato'),
      'uri_pattern' => 'http://example.com/model/tomato',
      'plugin_id' => 'vegetable',
      'locked' => TRUE,
      'container_revision' => TRUE,
      'processors' => [
        [
          'plugin_id' => 'peeler',
          'weight' => 3,
        ],
      ],
    ]);

    $this->assertTrue($nonempty->isLocked());
    $this->assertTrue($nonempty->isContainerRevision());
    $this->assertEqual('peeler', $nonempty->getProcessors()[0]['plugin_id']);
    $this->assertEqual('vegetable', $nonempty->getPluginId());
    $this->assertEqual('http://example.com/model/tomato', $nonempty->getUriPattern());
  }

  /**
   * Tests acccess control.
   */
  public function testAccess() {
    // Create users with different permissions.
    // The clerk role has administrative privileges.
    Role::create([
      'id' => 'clerk',
      'permissions' => ['administer collect'],
    ])->save();
    $user = User::create([
      // Skip ID 1, as it has magical consequences.
      'uid' => 2,
      'name' => 'User',
    ]);
    $clerk = User::create([
      'uid' => 3,
      'name' => 'Clerk',
      'roles' => ['clerk'],
    ]);

    // Verify user setup.
    $this->assertTrue($clerk->hasPermission('administer collect'));

    // Only clerk can edit and delete normal model.
    $empty = Model::create([
      'id' => 'empty',
    ]);
    $empty->save();
    $this->assertFalse($empty->access('update', $user));
    $this->assertFalse($empty->access('delete', $user));
    $this->assertTrue($empty->access('update', $clerk));
    $this->assertTrue($empty->access('delete', $clerk));

    // Locked model can not be deleted by anyone.
    $locked = Model::create([
      'id' => 'locked',
      'locked' => TRUE,
    ]);
    $locked->save();
    $this->assertFalse($locked->access('update', $user));
    $this->assertFalse($locked->access('delete', $user));
    $this->assertTrue($locked->access('update', $clerk));
    $this->assertFalse($locked->access('delete', $clerk));
  }

  /**
   * Tests the storing of properties on models.
   */
  public function testProperties() {
    // The 'test' config has a static property as well as stored property
    // definitions.
    /** @var \Drupal\collect\Model\ModelInterface $model */
    $model = Model::load('test');
    $model_plugin = collect_model_manager()->createInstanceFromConfig($model);

    $property_definitions = $model_plugin->getTypedData()->getPropertyDefinitions();

    // All stored properties should be present.
    $this->assertEqual(['hobbies', 'color', 'splonk', 'greeting'], array_keys($property_definitions));

    $greeting = $property_definitions['greeting']->getDataDefinition();
    $this->assertEqual($greeting->getLabel(), 'Greeting');
    $this->assertEqual($greeting->getDataType(), 'string');

    $hobbies = $property_definitions['hobbies']->getDataDefinition();
    $this->assertEqual($hobbies->getLabel(), 'Hobbies');
    $this->assertEqual($hobbies->getDescription(), '');
    /** @var \Drupal\Core\TypedData\ListDataDefinitionInterface $hobbies */
    $this->assertTrue($hobbies instanceof ListDataDefinitionInterface);
    $this->assertEqual($hobbies->getItemDefinition()->getDataType(), 'string');

    $color = $property_definitions['color']->getDataDefinition();
    $this->assertEqual($color->getLabel(), '');
    $this->assertEqual($color->getDescription(), 'Favorite color');
    $this->assertTrue($color instanceof ComplexDataDefinitionInterface);
    /** @var \Drupal\Core\TypedData\ComplexDataDefinitionInterface $color */
    $color_definitions = $color->getPropertyDefinitions();
    foreach (['red', 'blue', 'green'] as $primary_color) {
      $this->assertEqual($color_definitions[$primary_color]->getDataType(), 'integer');
    }

    $this->assertEqual($property_definitions['splonk']->getDataDefinition()->getDataType(), 'any');

    // Get typed data for an empty container.
    /** @var \Drupal\collect\TypedData\TypedDataProvider $typed_data_provider */
    $typed_data_provider = \Drupal::service('collect.typed_data_provider');
    $typed_data = $typed_data_provider->getTypedData(Container::create());

    // Accessing undefined property should throw exception.
    $exception_assert_message = 'Accessing an undefined property throws exception.';
    try {
      $this->assertNull($typed_data->get('carrot'));
      $this->fail($exception_assert_message);
    }
    catch (\Exception $e) {
      $this->pass($exception_assert_message);
      $this->assertTrue($e instanceof \InvalidArgumentException, 'Thrown exception is InvalidArgumentException');
    }
  }

  /**
   * Tests property queries.
   */
  public function testQueries() {
    // Create some complex JSON data.
    $container = Container::create([
      'schema_uri' => 'schema:json',
      'data' => Json::encode([
        'simple' => 'foo',
        'complex' => [
          'one' => 'eins',
          'two' => 'zwei',
        ],
      ]),
    ]);

    // Set up queries on a JSON model.
    Model::create([
      'id' => 'json',
      'label' => 'JSON',
      'uri_pattern' => 'schema:json',
      'plugin_id' => 'json',
    ])
      ->setTypedProperty('simple', new PropertyDefinition('simple', DataDefinition::create('string')))
      // Path resolution should be case-insensitive.
      ->setTypedProperty('complex_child', new PropertyDefinition('complex.Two', DataDefinition::create('string')))
      // Apply a Map definition to the complex element.
      ->setTypedProperty('complex', new PropertyDefinition('complex', MapDataDefinition::create()
          ->setPropertyDefinition('one', DataDefinition::create('string'))
          ->setPropertyDefinition('two', DataDefinition::create('string'))
      ))
      ->save();

    // Get the container data as typed data, using the model.
    /** @var \Drupal\collect\TypedData\TypedDataProvider $typed_data_provider */
    $typed_data_provider = \Drupal::service('collect.typed_data_provider');
    $typed_data = $typed_data_provider->getTypedData($container);

    $this->assertEqual('foo', $typed_data->get('simple')->getValue());
    $this->assertEqual('zwei', $typed_data->get('complex_child')->getValue());
    // The complex JSON element should be extracted as complex typed data.
    /** @var \Drupal\Core\TypedData\ComplexDataInterface $complex */
    $complex = $typed_data->get('complex');
    $this->assertTrue($complex instanceof ComplexDataInterface);
    $this->assertEqual('eins', $complex->get('one')->getValue());
    $this->assertEqual('zwei', $complex->get('two')->getValue());

    // Paths should be resolvable on any Traversable data.
    $data = new \SplQueue();
    $data->enqueue('foo');
    /** @var \Drupal\collect\Model\ModelPluginInterface $model_plugin */
    $model_plugin = collect_model_manager()->createInstance('default');
    $value = $model_plugin->getQueryEvaluator()->evaluate($data, '0');
    $this->assertEqual('foo', $value);
  }

  /**
   * Tests dependency calculation.
   */
  public function testDependencies() {
    // Dependency to model plugin provider.
    $model = Model::create([
      'id' => 'foo',
      'plugin_id' => 'test',
    ]);
    $model->save();
    $this->assertConfigEntityDependsOn($model, 'module', 'collect_test');

    // Dependency to processor provider.
    $model->setProcessors([
        'abc123' => [
          'plugin_id' => 'spicer',
        ],
      ]);
    $model->save();
    $this->assertConfigEntityDependsOn($model, 'module', 'collect_test');

    // Dependency to property datatype provider.
    $model->setTypedProperty('cinnamon', new PropertyDefinition('', DataDefinition::create('filter_format')));
    $model->save();
    $this->assertConfigEntityDependsOn($model, 'module', 'filter');

    // Dependency to field property fieldtype provider.
    $model->setTypedProperty('pumpkin', new PropertyDefinition('', BaseFieldDefinition::create('text')->setName('mytext')));
    $model->save();
    $this->assertConfigEntityDependsOn($model, 'module', 'text');

    // Create a new model.
    $person = Model::create([
      'id' => 'person',
      'label' => 'Person',
      'uri_pattern' => 'http://example.com/person',
      'plugin_id' => 'collectjson',
      'processors' => [
        [
          'plugin_id' => 'spicer',
          'weight' => 0,
          'spice' => 'pepper',
        ],
        [
          'plugin_id' => 'relation_creator_uri',
          'weight' => 1,
        ],
      ],
    ]);
    $person->save();

    $person->setTypedProperty('dummy', new PropertyDefinition('dummy', DataDefinition::create('dummy')))->save();
    $person->setTypedProperty('email', new PropertyDefinition('email', DataDefinition::create('email')))->save();

    $property_settings = [
      'dummy' => [
        'query' => 'dummy',
        'data_definition' => [
          'type' => 'dummy',
          'label' => NULL,
          'description' => NULL,
        ],
      ],
      'email' => [
        'query' => 'email',
        'data_definition' => [
          'type' => 'email',
          'label' => NULL,
          'description' => NULL,
        ],
      ],
      '_default_title' => [
        'query' => '_default_title',
        'data_definition' => [
          'type' => 'string',
          'label' => 'Default title',
          'description' => 'The default title of a container provided by applied model.',
        ],
      ],
    ];
    $this->assertEqual(\Drupal::config('collect.model.person')->get('properties'), $property_settings, 'Dummy property has been added.');

    // Remove collect_test module that adds a property and processor.
    \Drupal::service('module_installer')->uninstall(['collect_test']);
    unset($property_settings['dummy']);
    $this->assertEqual(\Drupal::config('collect.model.person')->get('properties'), $property_settings, 'Dummy property has been removed. Properties that do not depend on uninstalled module are untouched.');

    $processors_settings = [
      '1' => [
        'plugin_id' => 'relation_creator_uri',
        'weight' => 1,
      ],
    ];
    $this->assertEqual(\Drupal::config('collect.model.person')->get('processors'), $processors_settings, 'Spicer processor has been removed. Processors that do not depend on uninstalled module are untouched.');
  }

  /**
   * Asserts that a config entity depends on a given extension or entity.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The config entity that should have the dependency.
   * @param string $type
   *   The type of dependency (module, theme, content or config).
   * @param string $name
   *   The ID of the dependency.
   */
  protected function assertConfigEntityDependsOn(ConfigEntityInterface $entity, $type, $name) {
    $this->assertNotIdentical(FALSE, array_search($name, $entity->getDependencies()[$type]), t('The @label depends on the %name @type.', [
      '@label' => $entity->getEntityType()->getLowercaseLabel(),
      '%name' => $name,
      '@type' => $type,
    ]));
  }

}
