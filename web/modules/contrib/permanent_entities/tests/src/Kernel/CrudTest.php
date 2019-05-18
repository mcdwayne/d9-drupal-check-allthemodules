<?php

namespace Drupal\Tests\permanent_entities\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\permanent_entities\Entity\PermanentEntity;
use Drupal\permanent_entities\Entity\PermanentEntityType;

/**
 * Kernel Crud test.
 *
 * @group permanent_entities
 */
class CrudTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'permanent_entities',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('permanent_entity');

    PermanentEntityType::create([
      'label' => 'Planet',
      'id' => 'planet',
    ])->save();
  }

  /**
   * Test if is possible to create and delete Permanent Entities by code.
   */
  public function testCreate() {
    $planets = [
      'mercury' => 'Mercury',
      'venus' => 'Venus',
      'earth' => 'Earth',
    ];

    foreach ($planets as $id => $label) {
      PermanentEntity::create([
        'label' => $label,
        'id' => $id,
        'type' => 'planet',
      ])->save();
    }

    $this->assertCount(3, PermanentEntity::loadMultiple());

    $venus = PermanentEntity::load('venus');
    $venus->delete();
    $this->assertCount(2, PermanentEntity::loadMultiple());
  }

  /**
   * Test if is possible to edit Permanent Entities by code.
   */
  public function testEdit() {
    PermanentEntity::create([
      'label' => 'Benus',
      'id' => 'venus',
      'type' => 'planet',
    ])->save();
    $venus = PermanentEntity::load('venus');
    $this->assertEquals('Benus', $venus->label());

    $venus->label->value = 'Venus';
    $venus->save();
    $venus = PermanentEntity::load('venus');
    $this->assertEquals('Venus', $venus->label());
  }

}
