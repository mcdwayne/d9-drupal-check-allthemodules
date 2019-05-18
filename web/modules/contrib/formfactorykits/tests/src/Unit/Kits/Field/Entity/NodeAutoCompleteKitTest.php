<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field\Entity;

use Drupal\Tests\formfactorykits\Unit\Kits\Traits\StringTranslationTrait;
use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\Entity\NodeAutoCompleteKit
 * @group kit
 */
class NodeAutoCompleteKitTest extends KitTestBase {
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
    $nodeAutoComplete = $this->k->nodeAutoComplete();
    $this->assertArrayEquals([
      'node_autocomplete' => [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'node',
        '#title' => $this->t('Node'),
      ],
    ], [
      $nodeAutoComplete->getID() => $nodeAutoComplete->getArray(),
    ]);
  }

  public function testCustomID() {
    $nodeAutoComplete = $this->k->nodeAutoComplete('foo');
    $this->assertEquals('foo', $nodeAutoComplete->getID());
  }

  public function testTitle() {
    $nodeAutoComplete = $this->k->nodeAutoComplete()
      ->setTitle($this->t('Foo'));
    $this->assertArrayEquals([
      'node_autocomplete' => [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'node',
        '#title' => $this->t('Foo'),
      ],
    ], [
      $nodeAutoComplete->getID() => $nodeAutoComplete->getArray(),
    ]);
  }

  public function testDescription() {
    $nodeAutoComplete = $this->k->nodeAutoComplete()
      ->setDescription($this->t('Foo'));
    $this->assertArrayEquals([
      'node_autocomplete' => [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'node',
        '#title' => $this->t('Node'),
        '#description' => $this->t('Foo'),
      ],
    ], [
      $nodeAutoComplete->getID() => $nodeAutoComplete->getArray(),
    ]);
  }

  public function testValue() {
    $nodeAutoComplete = $this->k->nodeAutoComplete()
      ->setValue('foo');
    $this->assertArrayEquals([
      'node_autocomplete' => [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'node',
        '#title' => $this->t('Node'),
        '#value' => 'foo',
      ],
    ], [
      $nodeAutoComplete->getID() => $nodeAutoComplete->getArray(),
    ]);
  }

  public function testDefaultValue() {
    $nodeAutoComplete = $this->k->nodeAutoComplete()
      ->setDefaultValue('foo');
    $this->assertArrayEquals([
      'node_autocomplete' => [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'node',
        '#title' => $this->t('Node'),
        '#default_value' => 'foo',
      ],
    ], [
      $nodeAutoComplete->getID() => $nodeAutoComplete->getArray(),
    ]);
  }

  public function testTargetBundle() {
    $nodeAutoComplete = $this->k->nodeAutoComplete()
      ->setTargetBundle('foo');
    $this->assertArrayEquals([
      'node_autocomplete' => [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'node',
        '#selection_settings' => [
          'target_bundles' => ['foo'],
        ],
        '#title' => $this->t('Node'),
      ],
    ], [
      $nodeAutoComplete->getID() => $nodeAutoComplete->getArray(),
    ]);
  }

  public function testTargetBundles() {
    $nodeAutoComplete = $this->k->nodeAutoComplete()
      ->setTargetBundles(['foo', 'bar']);
    $this->assertArrayEquals([
      'node_autocomplete' => [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'node',
        '#selection_settings' => [
          'target_bundles' => ['foo', 'bar'],
        ],
        '#title' => $this->t('Node'),
      ],
    ], [
      $nodeAutoComplete->getID() => $nodeAutoComplete->getArray(),
    ]);
  }
}
