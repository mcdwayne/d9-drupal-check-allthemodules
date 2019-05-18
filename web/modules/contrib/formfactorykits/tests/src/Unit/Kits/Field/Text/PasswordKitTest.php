<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field\Text;

use Drupal\Tests\formfactorykits\Unit\Kits\Traits\StringTranslationTrait;
use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\Text\PasswordKit
 * @group kit
 */
class PasswordKitTest extends KitTestBase {
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
    $password = $this->k->password();
    $this->assertArrayEquals([
      'password' => [
        '#type' => 'password',
        '#title' => 'Password',
      ],
    ], [
      $password->getID() => $password->getArray(),
    ]);
  }

  public function testCustomID() {
    $password = $this->k->password('foo');
    $this->assertEquals('foo', $password->getID());
  }

  public function testDefaultValue() {
    $password = $this->k->password()
      ->setDefaultValue('foo');
    $this->assertArrayEquals([
      'password' => [
        '#type' => 'password',
        '#title' => 'Password',
        '#default_value' => 'foo',
      ],
    ], [
      $password->getID() => $password->getArray(),
    ]);
  }

  public function testSize() {
    $password = $this->k->password()
      ->setSize(10);
    $this->assertArrayEquals([
      'password' => [
        '#type' => 'password',
        '#title' => 'Password',
        '#size' => 10,
      ],
    ], [
      $password->getID() => $password->getArray(),
    ]);
  }

  public function testPattern() {
    $password = $this->k->password()
      ->setPattern('pattern');
    $this->assertArrayEquals([
      'password' => [
        '#type' => 'password',
        '#title' => 'Password',
        '#pattern' => 'pattern',
      ],
    ], [
      $password->getID() => $password->getArray(),
    ]);
  }
}
