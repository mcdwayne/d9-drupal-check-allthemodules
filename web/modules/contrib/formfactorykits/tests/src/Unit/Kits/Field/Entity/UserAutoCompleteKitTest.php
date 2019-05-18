<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field\Entity;

use Drupal\Tests\formfactorykits\Unit\Kits\Traits\StringTranslationTrait;
use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\Entity\UserAutoCompleteKit
 * @group kit
 */
class UserAutoCompleteKitTest extends KitTestBase {
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
    $userAutoComplete = $this->k->userAutoComplete();
    $this->assertArrayEquals([
      'user_autocomplete' => [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'user',
        '#title' => $this->t('User'),
      ],
    ], [
      $userAutoComplete->getID() => $userAutoComplete->getArray(),
    ]);
  }

  public function testCustomID() {
    $userAutoComplete = $this->k->userAutoComplete('foo');
    $this->assertEquals('foo', $userAutoComplete->getID());
  }

  public function testTitle() {
    $userAutoComplete = $this->k->userAutoComplete()
      ->setTitle($this->t('Foo'));
    $this->assertArrayEquals([
      'user_autocomplete' => [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'user',
        '#title' => $this->t('Foo'),
      ],
    ], [
      $userAutoComplete->getID() => $userAutoComplete->getArray(),
    ]);
  }

  public function testDescription() {
    $userAutoComplete = $this->k->userAutoComplete()
      ->setDescription($this->t('Foo'));
    $this->assertArrayEquals([
      'user_autocomplete' => [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'user',
        '#title' => $this->t('User'),
        '#description' => $this->t('Foo'),
      ],
    ], [
      $userAutoComplete->getID() => $userAutoComplete->getArray(),
    ]);
  }

  public function testValue() {
    $userAutoComplete = $this->k->userAutoComplete()
      ->setValue('foo');
    $this->assertArrayEquals([
      'user_autocomplete' => [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'user',
        '#title' => $this->t('User'),
        '#value' => 'foo',
      ],
    ], [
      $userAutoComplete->getID() => $userAutoComplete->getArray(),
    ]);
  }

  public function testDefaultValue() {
    $userAutoComplete = $this->k->userAutoComplete()
      ->setDefaultValue('foo');
    $this->assertArrayEquals([
      'user_autocomplete' => [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'user',
        '#title' => $this->t('User'),
        '#default_value' => 'foo',
      ],
    ], [
      $userAutoComplete->getID() => $userAutoComplete->getArray(),
    ]);
  }
}
