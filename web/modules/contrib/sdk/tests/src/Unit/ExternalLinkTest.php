<?php

namespace Drupal\Tests\sdk\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Link;
use Drupal\Core\GeneratedLink;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;
use Drupal\sdk\ExternalLink;

/**
 * Test generating external links.
 *
 * @covers \Drupal\sdk\ExternalLink
 *
 * @group sdk-api
 */
class ExternalLinkTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $container = new ContainerBuilder();
    $container->set('url_generator', $this->getMock(UrlGeneratorInterface::class));
    $container->set('link_generator', $this->getMock(LinkGeneratorInterface::class));
    $container->set('unrouted_url_assembler', $this->getMock(UnroutedUrlAssemblerInterface::class));

    \Drupal::setContainer($container);
  }

  /**
   * Checks that external link properly generated.
   *
   * @param string $url
   *   Link URL.
   * @param string|null $text
   *   Link text. URL will be used if not specified.
   *
   * @dataProvider provider
   * @covers \Drupal\sdk\ExternalLink::externalLink
   */
  public function testExternalLink($url, $text = NULL) {
    $external_link = $this->getMockForTrait(ExternalLink::class);
    $generated_link = (new GeneratedLink())
      ->setGeneratedLink('<a href="' . $url . '" target="_blank">' . $text ?: $url . '</a>');

    \Drupal::linkGenerator()
      ->expects(static::once())
      ->method('generateFromLink')
      ->with(static::isInstanceOf(Link::class))
      ->willReturn($generated_link);

    $this->assertSame($generated_link, $external_link::externalLink($url, $text));
  }

  /**
   * Returns a set link properties: URL and title.
   *
   * @return array[]
   *   An array of arrays with two strings: URL and title of a link.
   */
  public function provider() {
    return [
      ['http://example.com', 'Example'],
      ['http://example.org'],
    ];
  }

}
