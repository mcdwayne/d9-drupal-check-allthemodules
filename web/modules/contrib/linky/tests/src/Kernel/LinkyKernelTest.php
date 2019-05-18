<?php

namespace Drupal\Tests\linky\Kernel;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\linky\Entity\Linky;

/**
 * Tests Linky entity functionality.
 *
 * @group linky
 * @coversDefaultClass \Drupal\linky\Entity\Linky
 */
class LinkyKernelTest extends LinkyKernelTestBase {

  /**
   * Tests basic entity functions.
   *
   * @covers ::toUrl
   * @covers ::label
   * @covers ::toLink
   */
  public function testLinkyEntity() {
    $link = Linky::create([
      'link' => [
        'uri' => 'http://example.com',
        'title' => 'Example.com',
      ],
    ]);
    $link->save();
    $this->assertEquals('Example.com (http://example.com)', $link->label());
    $this->assertEquals(Url::fromUri('http://example.com')->toString(), $link->toUrl()->toString());
    $edit_url = Url::fromRoute('entity.linky.edit_form', ['linky' => $link->id()]);
    $edit_url
      ->setOption('entity_type', 'linky')
      ->setOption('entity', $link)
      ->setOption('language', $link->language());
    $this->assertEquals($edit_url, $link->toUrl('edit-form'));
    $this->assertEquals((new Link('Example.com', Url::fromUri('http://example.com')))->toString(), $link->toLink()->toString());
    $this->assertEquals(new Link('Edit', $edit_url), $link->toLink('Edit', 'edit-form'));
  }

}
