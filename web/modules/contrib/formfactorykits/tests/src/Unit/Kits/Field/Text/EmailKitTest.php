<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field\Text;

use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\Text\EmailKit
 * @group kit
 */
class EmailKitTest extends KitTestBase {
  public function testDefaults() {
    $email = $this->k->email();
    $this->assertArrayEquals([
      'email' => [
        '#type' => 'email',
      ],
    ], [
      $email->getID() => $email->getArray(),
    ]);
  }

  public function testCustomID() {
    $email = $this->k->email('foo');
    $this->assertEquals('foo', $email->getID());
  }

  public function testDefaultValue() {
    $email = $this->k->email()
      ->setDefaultValue('foo@example.com');
    $this->assertArrayEquals([
      'email' => [
        '#type' => 'email',
        '#default_value' => 'foo@example.com',
      ],
    ], [
      $email->getID() => $email->getArray(),
    ]);
  }

  public function testSize() {
    $email = $this->k->email()
      ->setSize(10);
    $this->assertArrayEquals([
      'email' => [
        '#type' => 'email',
        '#size' => 10,
      ],
    ], [
      $email->getID() => $email->getArray(),
    ]);
  }

  public function testPattern() {
    $email = $this->k->email()
      ->setPattern('pattern');
    $this->assertArrayEquals([
      'email' => [
        '#type' => 'email',
        '#pattern' => 'pattern',
      ],
    ], [
      $email->getID() => $email->getArray(),
    ]);
  }
}
