<?php

namespace Drupal\sir_trevor\Tests\Unit\FieldWidget;

use Drupal\Component\DependencyInjection\Container;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\Annotation\FieldWidget;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormState;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\sir_trevor\Plugin\Field\FieldWidget\SirTrevor;
use Drupal\Tests\sir_trevor\Unit\AnnotationAsserter;
use Drupal\Tests\sir_trevor\Unit\TestDoubles\SirTrevorBlockMock;
use Drupal\Tests\sir_trevor\Unit\TestDoubles\SirTrevorMixinMock;
use Drupal\Tests\sir_trevor\Unit\TestDoubles\SirTrevorPluginManagerMock;
use Drupal\Tests\UnitTestCase;

/**
 * @group SirTrevor
 */
class SirTrevorTest extends UnitTestCase {

  /** @var Container */
  private $container;
  /** @var SirTrevorPluginManagerMock */
  private $pluginManagerMock;
  use AnnotationAsserter;
  use StringTranslationTrait;

  /** @var SirTrevor */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->pluginManagerMock = new SirTrevorPluginManagerMock();

    $blocks = [
      new SirTrevorBlockMock('text'),
      new SirTrevorBlockMock('list'),
      new SirTrevorBlockMock('ordered_list', 'st_extension')
    ];

    $instances = array_merge([new SirTrevorMixinMock('some_mixin')], $blocks);
    $this->pluginManagerMock->setInstances($instances);

    $this->pluginManagerMock->setBlocks($blocks);
    $this->pluginManagerMock->setEnabledBlocks($blocks);

    $this->container = new Container();
    \Drupal::setContainer($this->container);
    $this->container->set('string_translation', $this->getStringTranslationStub());
    $this->container->set('plugin.manager.sir_trevor', $this->pluginManagerMock);

    $this->createSut();
  }

  /**
   * {@inheritdoc}
   */
  protected function getAnnotationClassNames() {
    return [
      FieldWidget::class,
      Translation::class,
    ];
  }

  /**
   * @test
   */
  public function formElement() {
    $field_name = 'field_name';
    $items = $this->getFieldItemListMock($field_name);

    $expected = $this->getExpectedUnconfiguredFormElement($items);

    $actual = $this->call__FormElement($items);
    $this->assertEquals($expected, $actual);
  }

  /**
   * @test
   */
  public function formElement_LimitedByGloballyEnabledBlocks() {
    $blocks = $this->pluginManagerMock->getBlocks();
    array_pop($blocks);
    $this->pluginManagerMock->setEnabledBlocks($blocks);
    $field_name = 'field_name';
    $items = $this->getFieldItemListMock($field_name);

    $expected = $this->getExpectedUnconfiguredFormElement($items);
    $expected['json']['#attached']['drupalSettings']['sirTrevor'][$field_name]['blockTypes'] = ['Text', 'List'];

    $actual = $this->call__FormElement($items);
    $this->assertEquals($expected, $actual);
  }

  /**
   * @test
   */
  public function formElement_configured() {
    $settings = [
      'enabled_blocks' => [
        'Text' => 'Text',
        'List' => 0,
        'Ordered_list' => 'Ordered_list',
      ],
      'default_block' => 'ordered_list',
    ];
    $this->createSut($settings);
    $field_name = 'field_name';
    $items = $this->getFieldItemListMock($field_name);

    $expected = $this->getExpectedUnconfiguredFormElement($items);
    $expected['json']['#attached']['drupalSettings']['sirTrevor'][$field_name]['blockTypes'] = ['Text', 'OrderedList'];
    $expected['json']['#attached']['drupalSettings']['sirTrevor'][$field_name]['defaultType'] = 'ordered_list';

    $actual = $this->call__FormElement($items);
    $this->assertEquals($expected, $actual);
  }

  /**
   * @test
   */
  public function classAnnotation() {
    $expected = [
      new FieldWidget([
        'id' => 'sir_trevor',
        'label' => new Translation(['value' => 'Sir Trevor']),
        'multiple_values' => TRUE,
        'field_types' => [
          'sir_trevor',
        ],
      ]),
    ];

    $this->assertClassAnnotationsMatch($expected, SirTrevor::class);
  }

  /**
   * @test
   */
  public function settingsForm_unConfigured() {
    $expected = [
      'enabled_blocks' => [
        '#title' => $this->t('Enabled blocks'),
        '#type' => 'checkboxes',
        '#options' => [
          'text' => $this->t('Text'),
          'list' => $this->t('List'),
          'ordered_list' => $this->t('Ordered list'),
        ],
        '#default_value' => [],
      ],
      'default_block' => [
        '#title' => $this->t('Default block'),
        '#type' => 'select',
        '#options' => [
          'text' => $this->t('Text'),
          'list' => $this->t('List'),
          'ordered_list' => $this->t('Ordered list'),
        ],
        '#default_value' => 'Text',
      ],
    ];

    $this->assertEquals($expected, $this->sut->settingsForm([], new FormState()));

    return $expected;
  }

  /**
   * @test
   * @depends settingsForm_unConfigured
   */
  public function settingsForm_limitedByGloballyEnabledBlocks(array $expected) {
    $blocks = $this->pluginManagerMock->getBlocks();
    array_pop($blocks);
    $this->pluginManagerMock->setEnabledBlocks($blocks);

    $expectedOptions = [
      'text' => $this->t('Text'),
      'list' => $this->t('List'),
    ];
    $expected['enabled_blocks']['#options'] = $expectedOptions;
    $expected['default_block']['#options'] = $expectedOptions;

    $this->assertEquals($expected, $this->sut->settingsForm([], new FormState()));
  }

  /**
   * @test
   */
  public function settingsAreSetCorrectly() {
    $settings = [
      'enabled_blocks' => ['text', 'list'],
      'default_block' => 'list',
    ];
    $this->createSut($settings);

    $form = $this->sut->settingsForm([], new FormState());
    foreach ($settings as $key => $setting) {
      $this->assertEquals($setting, $form[$key]['#default_value']);
    }
  }

  /**
   * @test
   */
  public function settingsSummary_unConfigured() {
    $expected = [
      $this->t('Enabled blocks: @blocks', ['@blocks' => 'All']),
      $this->t('Default block: @block', ['@block' => 'Text']),
    ];
    $this->assertEquals($expected, $this->sut->settingsSummary());
  }

  /**
   * @test
   */
  public function settingsSummary_configured() {
    $settings = [
      'enabled_blocks' => [
        'Text' => 'Text',
        'List' => 'List',
        'Ordered list' => 0,
      ],
      'default_block' => 'List',
    ];
    $this->createSut($settings);

    $expected = [
      $this->t('Enabled blocks: @blocks', ['@blocks' => 'List, Text']),
      $this->t('Default block: @block', ['@block' => 'List']),
    ];

    $this->assertEquals($expected, $this->sut->settingsSummary());
  }

  /**
   * @param $settings
   * @covers SirTrevor::create()
   */
  protected function createSut($settings = []) {
    $pluginDefinition = [];
    $configuration = [
      'field_definition' => new BaseFieldDefinition(),
      'settings' => $settings,
      'third_party_settings' => [],
    ];
    $this->sut = SirTrevor::create($this->container, $configuration, 'plugin_id', $pluginDefinition);
  }

  /**
   * @param FieldItemList $items
   * @return array
   */
  private function getExpectedUnconfiguredFormElement(FieldItemList $items) {
    $expected = [
      'json' => [
        '#type' => 'textarea',
        '#default_value' => $items->getString(),
        '#attributes' => [
          'data-sir-trevor-field-name' => [$items->getName()],
        ],
        '#attached' => [
          'library' => [
            'sir_trevor/sir-trevor',
            'sir_trevor/mixin.some_mixin.editor',
            'sir_trevor/block.text.editor',
            'sir_trevor/block.list.editor',
            'st_extension/block.ordered_list.editor',
          ],
          'drupalSettings' => [
            'sirTrevor' => [
              $items->getName() => [
                'blockTypes' => [],
                'defaultType' => 'Text',
              ],
            ],
          ],
        ],
      ],
    ];
    return $expected;
  }

  /**
   * @param \Drupal\Core\Field\FieldItemList $items
   * @return array
   */
  private function call__FormElement(FieldItemList $items) {
    $delta = 1;
    $element = [];
    $form = [];
    $form_state = new FormState();
    return $this->sut->formElement($items, $delta, $element, $form, $form_state);
  }

  /**
   * @param $field_name
   * @return \Drupal\Core\Field\FieldItemList
   */
  private function getFieldItemListMock($field_name) {
    $items = new FieldItemList(new DataDefinition([]), 1);
    $items->setContext($field_name);
    return $items;
  }
}
