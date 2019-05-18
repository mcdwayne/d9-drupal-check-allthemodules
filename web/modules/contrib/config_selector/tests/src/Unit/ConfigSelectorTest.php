<?php

namespace Drupal\Tests\config_selector\Unit;

use Drupal\config_selector\ConfigSelector;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the ConfigSelector.
 *
 * @group config_selector
 *
 * @coversDefaultClass \Drupal\config_selector\ConfigSelector
 */
class ConfigSelectorTest extends UnitTestCase {

  /**
   * @covers ::getConfigEntityLink
   */
  public function testGetConfigEntityLinkToUrlEditForm() {
    $config_entity = $this->prophesize(ConfigEntityInterface::class);
    $config_entity->hasLinkTemplate('edit-form')->willReturn(TRUE);
    $url = $this->prophesize(Url::class);
    $url->toString()->willReturn('a/link/to/an/edit-form');
    $config_entity->toUrl('edit-form')->willReturn($url);
    $config_entity->toUrl()->shouldNotBeCalled();
    $this->assertEquals('a/link/to/an/edit-form', ConfigSelector::getConfigEntityLink($config_entity->reveal()));
  }

  /**
   * @covers ::getConfigEntityLink
   */
  public function testGetConfigEntityLinkToUrlCanonical() {
    $config_entity = $this->prophesize(ConfigEntityInterface::class);
    $config_entity->hasLinkTemplate('edit-form')->willReturn(FALSE);
    $url = $this->prophesize(Url::class);
    $url->toString()->willReturn('a/link/to/canonical');
    $config_entity->toUrl()->willReturn($url);
    $config_entity->toUrl('edit-form')->shouldNotBeCalled();
    $this->assertEquals('a/link/to/canonical', ConfigSelector::getConfigEntityLink($config_entity->reveal()));
  }

  /**
   * @covers ::getConfigEntityLink
   */
  public function testGetConfigEntityLinkToUrlException() {
    $config_entity = $this->prophesize(ConfigEntityInterface::class);
    $config_entity->hasLinkTemplate('edit-form')->willReturn(FALSE);
    $config_entity->toUrl()->willThrow(UndefinedLinkTemplateException::class);
    $this->assertEquals('', ConfigSelector::getConfigEntityLink($config_entity->reveal()));
  }

}
