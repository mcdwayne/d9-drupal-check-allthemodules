<?php

namespace Drupal\Tests\custom_tokens\Kernel;

use Drupal\custom_tokens\Entity\TokenEntity;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test the custom entity test.
 */
class CustomTokenEntityTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'custom_tokens',
  ];

  /**
   * Test the custom token entity.
   */
  public function testCustomTokenEntity() {
    $entity = TokenEntity::create([
      'id' => 'foo',
      'tokenValue' => 'bar',
      'tokenName' => 'baz:biz',
    ]);
    $entity->save();

    $this->assertEquals($entity->id(), 'foo');
    $this->assertEquals($entity->getTokenValue(), 'bar');
    $this->assertEquals($entity->getTokenName(), 'baz:biz');
  }

}
