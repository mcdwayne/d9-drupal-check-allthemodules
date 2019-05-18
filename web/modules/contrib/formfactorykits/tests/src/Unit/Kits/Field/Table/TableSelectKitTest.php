<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field\Select;

use Drupal\Tests\formfactorykits\Unit\Kits\Traits\StringTranslationTrait;
use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\Table\TableSelectKit
 * @group kit
 */
class TableSelectKitTest extends KitTestBase {
  use StringTranslationTrait;

  /**
   * @inheritdoc
   */
  public function getServices() {
    return [
      'string_translation' => $this->getTranslationManager(),
    ];
  }

  public function testDefaults() {
    $tableSelect = $this->k->tableSelect();
    $this->assertArrayEquals([
      'tableselect' => [
        '#type' => 'tableselect',
        '#options' => [],
      ],
    ], [
      $tableSelect->getID() => $tableSelect->getArray(),
    ]);
  }

  public function testCustomID() {
    $tableSelect = $this->k->tableSelect('foo');
    $this->assertEquals('foo', $tableSelect->getID());
  }

  public function testTitle() {
    $tableSelect = $this->k->tableSelect()
      ->setTitle('Foo');
    $this->assertArrayEquals([
      'tableselect' => [
        '#type' => 'tableselect',
        '#options' => [],
        '#title' => 'Foo',
      ],
    ], [
      $tableSelect->getID() => $tableSelect->getArray(),
    ]);
  }

  public function testDescription() {
    $tableSelect = $this->k->tableSelect()
      ->setDescription('Foo');
    $this->assertArrayEquals([
      'tableselect' => [
        '#type' => 'tableselect',
        '#options' => [],
        '#description' => 'Foo',
      ],
    ], [
      $tableSelect->getID() => $tableSelect->getArray(),
    ]);
  }

  public function testOptions() {
    $tableSelect = $this->k->tableSelect('names')
      ->setTitle($this->t('Names'))
      ->appendHeaderColumn('first', $this->t('First Name'))
      ->appendHeaderColumn('last', $this->t('Last Name'))
      ->appendHeaderColumn('suffix', $this->t('Suffix'))
      ->appendHeaderColumn('nickname', $this->t('Nickname'))
      ->setOptions([
        'henry' => [
          'first' => 'Henry',
          'last' => 'Jones',
        ],
        'anna' => [
          'first' => 'Anna',
          'last' => 'Jones',
        ],
      ])
      ->appendOption('jr', [
        'first' => 'Henry',
        'last' => 'Jones',
        'suffix' => 'Jr.',
        'nickname' => 'Indiana',
      ]);
    $this->assertArrayEquals([
      'names' => [
        '#type' => 'tableselect',
        '#title' => 'Names',
        '#header' => [
          'first' => 'First Name',
          'last' => 'Last Name',
          'suffix' => 'Suffix',
          'nickname' => 'Nickname',
        ],
        '#options' => [
          'henry' => [
            'first' => 'Henry',
            'last' => 'Jones',
          ],
          'anna' => [
            'first' => 'Anna',
            'last' => 'Jones',
          ],
          'jr' => [
            'first' => 'Henry',
            'last' => 'Jones',
            'suffix' => 'Jr.',
            'nickname' => 'Indiana',
          ],
        ],
      ],
    ], [
      $tableSelect->getID() => $tableSelect->getArray(),
    ]);
  }

  public function testValue() {
    $tableSelect = $this->k->tableSelect()
      ->setValue('foo');
    $this->assertArrayEquals([
      'tableselect' => [
        '#type' => 'tableselect',
        '#options' => [],
        '#value' => 'foo',
      ],
    ], [
      $tableSelect->getID() => $tableSelect->getArray(),
    ]);
  }
}
