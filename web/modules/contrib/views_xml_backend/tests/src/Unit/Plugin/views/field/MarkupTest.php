<?php

/**
 * @file
 * Contains \Drupal\Tests\views_xml_backend\Unit\Plugin\views\field\MarkupTest.
 */

namespace Drupal\Tests\views_xml_backend\Unit\Plugin\views\field;

use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Tests\views_xml_backend\Unit\ViewsXmlBackendTestBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views_xml_backend\Plugin\views\field\Markup;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\views_xml_backend\Plugin\views\field\Markup
 * @group views_xml_backend
 */
class MarkupTest extends ViewsXmlBackendTestBase {

  /**
   * @covers ::render_item
   */
  public function testRenderItem() {
    $account = $this->prophesize(AccountProxyInterface::class);
    $renderer = $this->prophesize(RendererInterface::class);
    $renderer->renderPlain(Argument::type('array'))->will(function (array $args) {
      return $args[0]['#text'];
    });

    $plugin = new Markup([], '', [], $account->reveal(), $renderer->reveal());

    $options = ['format' => 'my_format'];

    $plugin->init($this->getMockedView(), $this->getMockedDisplay(), $options);

    $this->assertSame('foo', $plugin->render_item(0, ['value' => 'foo']));
  }

}
