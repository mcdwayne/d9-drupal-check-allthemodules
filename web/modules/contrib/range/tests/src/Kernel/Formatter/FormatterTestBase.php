<?php

namespace Drupal\Tests\range\Kernel\Formatter;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\range\Traits\RangeTestTrait;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Base class for range functional integration tests.
 */
abstract class FormatterTestBase extends KernelTestBase {

  use RangeTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'field',
    'text',
    'entity_test',
    'user',
    'range',
  ];

  /**
   * Field type to test against.
   *
   * @var string
   */
  protected $fieldType;

  /**
   * Field name to test against.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * Display type to test.
   *
   * @var string
   */
  protected $displayType;

  /**
   * Entity, used for testing.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['system']);
    $this->installConfig(['field']);
    $this->installConfig(['text']);
    $this->installConfig(['range']);
    $this->installEntitySchema('entity_test');

    $this->fieldName = $this->getFieldName($this->fieldType);
    $this->createField($this->fieldType);
    $this->createViewDisplay();

    $this->entity = EntityTest::create([]);
  }

  /**
   * Tests formatter.
   *
   * @dataProvider formatterDataProvider
   */
  public function testFieldFormatter(array $display_settings, $from, $to, $expected_output) {
    $this->entity->{$this->fieldName} = [
      'from' => $from,
      'to' => $to,
    ];

    $this->setViewDisplayComponent($this->fieldType, $this->displayType, $display_settings);
    $this->renderEntityFields($this->entity);
    $this->assertText($expected_output);
  }

  /**
   * Data provider for testFieldFormatter().
   *
   * @return array
   *   Nested arrays of values to check:
   *     - $display_settings
   *     - $from
   *     - $to
   *     - $expected_output
   */
  abstract public function formatterDataProvider();

  /**
   * Renders fields of a given entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity object with attached fields to render.
   */
  protected function renderEntityFields(FieldableEntityInterface $entity) {
    $content = $this->viewDisplay->build($entity);
    $this->render($content);
  }

}
