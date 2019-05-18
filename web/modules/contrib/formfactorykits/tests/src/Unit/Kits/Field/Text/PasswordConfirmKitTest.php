<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field\Text;

use Drupal\Tests\formfactorykits\Unit\Kits\Traits\StringTranslationTrait;
use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\Text\PasswordConfirmKit
 * @group kit
 */
class PasswordConfirmKitTest extends KitTestBase {
  public function testDefaults() {
    $passwordConfirm = $this->k->passwordConfirm();
    $this->assertArrayEquals([
      'password_confirm' => [
        '#type' => 'password_confirm',
      ],
    ], [
      $passwordConfirm->getID() => $passwordConfirm->getArray(),
    ]);
  }

  public function testCustomID() {
    $passwordConfirm = $this->k->passwordConfirm('foo');
    $this->assertEquals('foo', $passwordConfirm->getID());
  }

  public function testDefaultValue() {
    $passwordConfirm = $this->k->passwordConfirm()
      ->setDefaultValue('foo');
    $this->assertArrayEquals([
      'password_confirm' => [
        '#type' => 'password_confirm',
        '#default_value' => 'foo',
      ],
    ], [
      $passwordConfirm->getID() => $passwordConfirm->getArray(),
    ]);
  }

  public function testSize() {
    $passwordConfirm = $this->k->passwordConfirm()
      ->setSize(10);
    $this->assertArrayEquals([
      'password_confirm' => [
        '#type' => 'password_confirm',
        '#size' => 10,
      ],
    ], [
      $passwordConfirm->getID() => $passwordConfirm->getArray(),
    ]);
  }

  public function testPattern() {
    $passwordConfirm = $this->k->passwordConfirm()
      ->setPattern('pattern');
    $this->assertArrayEquals([
      'password_confirm' => [
        '#type' => 'password_confirm',
        '#pattern' => 'pattern',
      ],
    ], [
      $passwordConfirm->getID() => $passwordConfirm->getArray(),
    ]);
  }
}
