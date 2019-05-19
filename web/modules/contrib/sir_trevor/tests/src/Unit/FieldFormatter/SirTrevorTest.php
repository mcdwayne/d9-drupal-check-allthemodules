<?php

namespace Drupal\sir_trevor\Tests\Unit\FieldFormatter;

use Drupal\Component\DependencyInjection\Container;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\Annotation\FieldFormatter;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\sir_trevor\Plugin\Field\FieldFormatter\SirTrevor;
use Drupal\Tests\sir_trevor\Unit\AnnotationAsserter;
use Drupal\Tests\sir_trevor\Unit\TestDoubles\EventDispatcherDummy;
use Drupal\Tests\sir_trevor\Unit\TestDoubles\FieldItemListMock;
use Drupal\Tests\UnitTestCase;

/**
 * @group SirTrevor
 */
class SirTrevorTest extends UnitTestCase {

  use AnnotationAsserter;

  /** @var SirTrevor */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    \Drupal::setContainer(new Container());
    \Drupal::getContainer()->set('event_dispatcher', new EventDispatcherDummy());
    $pluginId = 'plugin_id';
    $pluginDefinition = 'plugin definition';
    $baseFieldDefinition = new BaseFieldDefinition();
    $settings = [];
    $Label = 'Label';
    $viewMode = 'default';
    $thirdPartySettings = [];
    $this->sut = new SirTrevor($pluginId, $pluginDefinition, $baseFieldDefinition, $settings, $Label, $viewMode, $thirdPartySettings);

  }

  /**
   * Data provider for @see viewElements
   *
   * @return array
   */
  public function viewElementsTestDataProvider() {
    $testData = [];

    $testData['no value'] = [
      'expected' => [],
      'string' => NULL,
    ];

    $testData['empty string'] = [
      'expected' => [],
      'string' => '',
    ];

    $testData['invalid json'] = [
      'expected' => [],
      'string' => '{noClosingTag:""'
    ];

    $obj = (object) ['notTheDataAttribute' => ''];
    $testData['invalid datastructure'] = [
      'expected' => [],
      'string' => json_encode($obj),
    ];

    $obj = (object) [
      'data' => [
        (object) [
          'type' => 'some_type',
          'data' => (object) ['some-key' => 'some value']
        ]
      ]
    ];
    $testData['valid datastructure'] = [
      'expected' => [
        [
          '#theme' => "sir_trevor_{$obj->data[0]->type}",
          '#data' => $obj->data[0]->data,
          '#entity' => NULL,
        ]
      ],
      'string' => json_encode($obj),
    ];

    $container = new \stdClass();
    $container->data = [$obj->data[0], $obj->data[0]];
    $testData['container containing multiple valid datastructures'] = [
      'expected' => [
        [
          '#theme' => "sir_trevor_{$obj->data[0]->type}",
          '#data' => $obj->data[0]->data,
          '#entity' => NULL,
        ],
        [
          '#theme' => "sir_trevor_{$obj->data[0]->type}",
          '#data' => $obj->data[0]->data,
          '#entity' => NULL,
        ],
      ],
      'string' => json_encode($container),
    ];

    return $testData;
  }

  /**
   * @test
   * @dataProvider viewElementsTestDataProvider
   * @param array $expected
   * @param string $itemsString
   */
  public function viewElements(array $expected, $itemsString) {
    $items = new FieldItemListMock();
    $items->setString($itemsString);
    $this->assertEquals($expected, $this->sut->viewElements($items, 'en'));
  }

  /**
   * @test
   */
  public function classAnnotation() {
    $expected = [
      new FieldFormatter([
        'id' => 'sir_trevor',
        'label' => new Translation(['value' => 'Sir Trevor']),
        'field_types' => [
          'sir_trevor',
        ],
      ]),
    ];

    $this->assertClassAnnotationsMatch($expected, SirTrevor::class);
  }

  /**
   * {@inheritdoc}
   */
  protected function getAnnotationClassNames() {
    return [
      FieldFormatter::class,
      Translation::class,
    ];
  }
}
