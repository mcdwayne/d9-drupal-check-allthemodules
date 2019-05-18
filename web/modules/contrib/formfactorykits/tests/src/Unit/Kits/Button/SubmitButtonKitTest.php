<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Button;

use Drupal\Tests\formfactorykits\Unit\Kits\Traits\StringTranslationTrait;
use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Button\SubmitButtonKit
 * @group kit
 */
class SubmitButtonKitTest extends KitTestBase {
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
    $submit = $this->k->submit();
    $this->assertArrayEquals([
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
      ],
    ], [
      $submit->getID() => $submit->getArray(),
    ]);
  }

  public function testCustomID() {
    $submit = $this->k->submit('foo');
    $this->assertEquals('foo', $submit->getID());
  }

  public function testValue() {
    $submit = $this->k->submit()
      ->setValue('Foo');
    $this->assertArrayEquals([
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Foo'),
      ],
    ], [
      $submit->getID() => $submit->getArray(),
    ]);
  }
}
