<?php

namespace Drupal\Tests\search_api_hashtag\Unit;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\DataType\DataTypeInterface;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Utility\PluginHelperInterface;
use Drupal\search_api\Utility\Utility;
use Drupal\search_api_hashtag\Plugin\search_api\processor\Hashtag;
use Drupal\Tests\search_api\Unit\Processor\TestItemsTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the "Hashtag" processor.
 *
 * @group search_api
 *
 * @see \Drupal\search_api_hashtag\Plugin\search_api\processor\Hashtag
 */
class HashtagTest extends UnitTestCase {

  use TestItemsTrait;

  /**
   * The processor to be tested.
   *
   * @var \Drupal\search_api_hashtag\Plugin\search_api\processor\Hashtag
   */
  protected $processor;

  /**
   * A search index mock for the tests.
   *
   * @var \Drupal\search_api\IndexInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $index;

  /**
   * The field ID used in this test.
   *
   * @var string
   */
  protected $fieldId = 'search_api_hashtag';

  /**
   * The callback with which text values should be preprocessed.
   *
   * @var callable
   */
  protected $valueCallback;

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp() {
    parent::setUp();

    $datasource = $this->getMock(DatasourceInterface::class);
    $datasource->expects($this->any())
      ->method('getPropertyDefinitions')
      ->willReturn([]);
    $this->index = new Index([
      'datasourceInstances' => [
        'entity:test1' => $datasource,
        'entity:test2' => $datasource,
      ],
      'processorInstances' => [],
      'field_settings' => [
        'foo' => [
          'type' => 'string',
          'datasource_id' => 'entity:test1',
          'property_path' => 'foo',
        ],
        'bar' => [
          'type' => 'string',
          'datasource_id' => 'entity:test1',
          'property_path' => 'foo:bar',
        ],
        'bla' => [
          'type' => 'string',
          'datasource_id' => 'entity:test2',
          'property_path' => 'foobaz:bla',
        ],
        'search_api_hashtag' => [
          'type' => 'text',
          'property_path' => 'search_api_hashtag',
        ],
      ],
    ], 'search_api_index');
    $this->processor = new Hashtag(['#index' => $this->index], 'hashtag', []);
    $this->index->addProcessor($this->processor);
    $this->setUpMockContainer();

    $plugin_helper = $this->getMockBuilder(PluginHelperInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $plugin_helper->method('getProcessorsByStage')
      ->willReturn([]);
    $this->container->set('search_api.plugin_helper', $plugin_helper);

    // We want to check correct data type handling, so we need a somewhat more
    // complex mock-up for the datatype plugin handler.
    /** @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\search_api\DataType\DataTypePluginManager $data_type_manager */
    $data_type_manager = $this->container->get('plugin.manager.search_api.data_type');
    $data_type_manager->method('hasDefinition')
      ->willReturn(TRUE);
    $this->valueCallback = function ($value) {
      if (is_numeric($value)) {
        return $value + 1;
      }
      else {
        return '*' . $value;
      }
    };
    $data_type = $this->getMock(DataTypeInterface::class);
    $data_type->method('getValue')
      ->willReturnCallback($this->valueCallback);
    $data_type_manager->method('createInstance')
      ->willReturnMap([
        ['text', [], $data_type],
      ]);
  }

  /**
   * Tests hashtag fields of the given type.
   *
   * @param array $input
   *   The input to test.
   * @param array $expected
   *   The expected values for the two items.
   * @param bool $strtolower
   *   (optional) TRUE if the items' normal fields should contain integers,
   *   FALSE otherwise.
   *
   * @dataProvider hashtagTestsDataProvider
   */
  public function testHashtag($input, $expected, $strtolower = FALSE) {
    // Add the field configuration.
    $configuration = [
      'strtolower' => $strtolower,
      'fields' => [
        'entity:test1/foo',
        'entity:test1/foo:bar',
        'entity:test2/foobaz:bla',
      ],
    ];
    $this->index->getField($this->fieldId)->setConfiguration($configuration);

    $items = [];
    $i = 0;
    foreach (['entity:test1', 'entity:test2'] as $datasource_id) {
      $this->itemIds[$i++] = $item_id = Utility::createCombinedId($datasource_id, '1:en');
      $item = \Drupal::getContainer()
        ->get('search_api.fields_helper')
        ->createItem($this->index, $item_id);
      foreach ([NULL, $datasource_id] as $field_datasource_id) {
        foreach ($this->index->getFieldsByDatasource($field_datasource_id) as $field_id => $field) {
          $field = clone $field;
          if (!empty($input[$field_id])) {
            $field->setValues($input[$field_id]);
          }
          $item->setField($field_id, $field);
        }
      }
      $item->setFieldsExtracted(TRUE);
      $items[$item_id] = $item;
    }

    // Add the processor's field values to the items.
    foreach ($items as $item) {
      $this->processor->addFieldValues($item);
    }

    $this->assertEquals(array_map($this->valueCallback, $expected[0]), $items[$this->itemIds[0]]->getField($this->fieldId)->getValues(), 'Correct hashtag for item 1.');
    $this->assertEquals(array_map($this->valueCallback, $expected[1]), $items[$this->itemIds[1]]->getField($this->fieldId)->getValues(), 'Correct hashtag for item 2.');
  }

  /**
   * Provides test data for hashtag tests.
   *
   * @return array
   *   An array containing test data sets, with each being an array of
   *   arguments to pass to the test method.
   *
   * @see static::testHashtag()
   */
  public function hashtagTestsDataProvider() {
    return [
      'Basic natural' => [
        [
          'foo' => ['aasdf #Foo', 'bar'],
          'bar' => ['#baz'],
          'bla' => ['foobar'],
        ],
        [
          ['Foo', 'baz'],
          [],
        ],
      ],
      'Basic lowercase' => [
        [
          'foo' => ['aasdf #Foo', 'bar'],
          'bar' => ['#baz'],
          'bla' => ['foobar'],
        ],
        [
          ['foo', 'baz'],
          [],
        ],
        TRUE
      ],
    ];
  }

  /**
   * Tests whether the properties are correctly altered.
   *
   * @see \Drupal\search_api_hashtag\Plugin\search_api\processor\Hashtag::getPropertyDefinitions()
   */
  public function testGetPropertyDefinitions() {
    /** @var \Drupal\Core\StringTranslation\TranslationInterface $translation */
    $translation = $this->getStringTranslationStub();
    $this->processor->setStringTranslation($translation);

    // Check for added properties when no datasource is given.
    /** @var \Drupal\search_api\Processor\ProcessorPropertyInterface[] $properties */
    $properties = $this->processor->getPropertyDefinitions(NULL);

    $this->assertArrayHasKey('search_api_hashtag', $properties, 'The "search_api_hashtag" property was added to the properties.');
    $this->assertInstanceOf('Drupal\search_api_hashtag\Plugin\search_api\processor\Property\HashtagProperty', $properties['search_api_hashtag'], 'The "search_api_hashtag" property has the correct class.');
    $this->assertEquals('string', $properties['search_api_hashtag']->getDataType(), 'Correct data type set in the data definition.');
    $this->assertEquals($translation->translate('Hashtags'), $properties['search_api_hashtag']->getLabel(), 'Correct label set in the data definition.');
    $expected_description = $translation->translate('Hashtag items extracted from text.');
    $this->assertEquals($expected_description, $properties['search_api_hashtag']->getDescription(), 'Correct description set in the data definition.');

    // Verify that there are no properties if a datasource is given.
    $datasource = $this->getMock(DatasourceInterface::class);
    $properties = $this->processor->getPropertyDefinitions($datasource);
    $this->assertEmpty($properties, 'Datasource-specific properties did not get changed.');
  }

}
