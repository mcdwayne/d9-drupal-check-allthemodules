<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field\Text;

use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\Text\SearchKit
 * @group kit
 */
class SearchKitTest extends KitTestBase {
  public function testDefaults() {
    $search = $this->k->search();
    $this->assertArrayEquals([
      'search' => [
        '#type' => 'search',
      ],
    ], [
      $search->getID() => $search->getArray(),
    ]);
  }

  public function testCustomID() {
    $search = $this->k->search('foo');
    $this->assertEquals('foo', $search->getID());
  }

  public function testTitle() {
    $search = $this->k->search()
      ->setTitle('Foo');
    $this->assertArrayEquals([
      'search' => [
        '#type' => 'search',
        '#title' => 'Foo',
      ],
    ], [
      $search->getID() => $search->getArray(),
    ]);
  }
}
