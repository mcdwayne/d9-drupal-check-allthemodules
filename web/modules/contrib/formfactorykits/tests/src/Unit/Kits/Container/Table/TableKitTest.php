<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Container\Table;

use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Container\Table\TableKit
 * @group kit
 */
class TableKitTest extends KitTestBase {
  public function testDefaults() {
    $table = $this->k->table();
    $this->assertArrayEquals([
      'table' => [
        '#type' => 'table',
      ],
    ], [
      $table->getID() => $table->getArray(),
    ]);
  }

  public function testCustomID() {
    $table = $this->k->table('foo');
    $this->assertEquals('foo', $table->getID());
  }

  public function testHeader() {
    $table = $this->k->table()
      ->setHeader([
        'Foo',
        'Bar',
      ])
      ->appendHeaderColumn('Baz');
    $this->assertArrayEquals([
      'table' => [
        '#type' => 'table',
        '#header' => [
          'Foo',
          'Bar',
          'Baz',
        ],
      ],
    ], [
      $table->getID() => $table->getArray(),
    ]);
  }

  public function testRows() {
    $table = $this->k->table()
      ->setRows([
        ['foo'],
        ['bar'],
      ])
      ->appendRow(['baz', 'qux']);
    $this->assertArrayEquals([
      'table' => [
        '#type' => 'table',
        '#rows' => [
          ['foo'],
          ['bar'],
          ['baz', 'qux'],
        ],
      ],
    ], [
      $table->getID() => $table->getArray(),
    ]);
  }
}
