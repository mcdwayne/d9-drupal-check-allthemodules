<?php

namespace Drupal\Tests\linky\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\linky\Entity\Linky;
use Drupal\linky\Url;

/**
 * Tests Linky URL.
 *
 * @group linky
 * @coversDefaultClass \Drupal\linky\Url
 */
class LinkyUrlTest extends LinkyKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_test',
  ];

  /**
   * Tests getting internal path from internal Linkys.
   */
  public function testInternal() {
    $this->installEntitySchema('entity_test');
    $entity = EntityTest::create();
    $entity->save();
    $entityId = $entity->id();

    $link = Linky::create([
      'link' => [
        'uri' => 'internal:/entity_test/' . $entityId,
      ],
    ]);
    $link->save();

    $url = $link->toUrl();
    $this->assertInstanceOf(Url::class, $url);
    $this->assertEquals('admin/content/linky/' . $link->id(), $url->getInternalPath());
    $this->assertEquals('/entity_test/' . $entityId, $url->toString());
  }

  /**
   * Tests getting internal path from external Linkys.
   *
   * Without the special Url class, an exception would be thrown:
   * UnexpectedValueException Unrouted URIs do not have internal
   * representations.
   */
  public function testExternal() {
    $link = Linky::create([
      'link' => [
        'uri' => 'http://hello.world/kapoww',
      ],
    ]);
    $link->save();

    $url = $link->toUrl();
    $this->assertInstanceOf(Url::class, $url);
    $this->assertEquals('admin/content/linky/' . $link->id(), $url->getInternalPath());
    $this->assertEquals('http://hello.world/kapoww', $url->toString());
  }

}
