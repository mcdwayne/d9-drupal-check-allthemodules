<?php

namespace Drupal\sir_trevor\Tests\Unit\FieldType;

use Drupal\Component\DependencyInjection\Container;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\Annotation\FieldType;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\entity_test\FieldStorageDefinition;
use Drupal\sir_trevor\Plugin\Field\FieldType\SirTrevor;
use Drupal\Tests\sir_trevor\Unit\AnnotationAsserter;
use Drupal\Tests\UnitTestCase;

/**
 * @group SirTrevor
 */
class SirTrevorTest extends UnitTestCase {
  use AnnotationAsserter;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    \Drupal::setContainer(new Container());
  }

  /**
   * {@inheritdoc}
   */
  protected function getAnnotationClassNames() {
    return [
      FieldType::class,
      Translation::class,
    ];
  }

  /**
   * @test
   */
  public function propertyDefinitions() {
    $expected = [
      'json' => DataDefinition::create('string'),
    ];
    $this->assertEquals($expected, SirTrevor::propertyDefinitions(new FieldStorageDefinition()));
  }

  /**
   * @test
   */
  public function schema() {
    $expected = [
      'columns' => [
        'json' => [
          'type' => 'text',
          'size' => 'big',
        ],
      ],
    ];

    $this->assertEquals($expected, SirTrevor::schema(new FieldStorageDefinition()));
  }

  /**
   * @test
   */
  public function classAnnotation() {
    $expected = [
      new FieldType([
        'id' => 'sir_trevor',
        'label' => new Translation(['value' => 'Sir Trevor']),
        'default_formatter' => 'sir_trevor',
        'default_widget' => 'sir_trevor',
      ])
    ];

    $this->assertClassAnnotationsMatch($expected, SirTrevor::class);
  }
}
