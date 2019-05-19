<?php

namespace Drupal\Tests\video_embed_vidyard\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\video_embed_vidyard\Plugin\video_embed_field\Provider\Vidyard;

/**
 * Ensure URL parsing is working for Vidyard URLs.
 *
 * @group video_embed_vidyard
 */
class VidyardProviderUrlParseTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $configFactory = $this->getConfigFactoryStub([
      'video_embed_vidyard.settings' => [
        'custom_domain' => 'demos.vidyard.com',
        'additional_pattern' => 'watch|view|stream',
      ],
    ]);

    $container = new ContainerBuilder();
    $container->set('config.factory', $configFactory);
    \Drupal::setContainer($container);
  }

  /**
   * Test URL parsing works as expected for Vidyard.
   *
   * @dataProvider urlsWithExpectedIds
   */
  public function testUrlParsing($url, $expected) {
    $this->assertEquals($expected, Vidyard::getIdFromInput($url));
  }

  /**
   * A data provider for URL parsing test cases.
   *
   * @return array
   *   An array of test cases.
   */
  public function urlsWithExpectedIds() {
    return [
      // Passing test cases.
      'Standard http URL' => [
        'http://play.vidyard.com/share/-Tv_ARjNb94wiI6G9FyUqw',
        '-Tv_ARjNb94wiI6G9FyUqw',
      ],
      'Standard https URL' => [
        'https://play.vidyard.com/share/-Tv_ARjNb94wiI6G9FyUqw',
        '-Tv_ARjNb94wiI6G9FyUqw',
      ],
      'Secure embed_select URL' => [
        'https://secure.vidyard.com/embed_select/A1B_C2D345E-fg-hijKL6M',
        'A1B_C2D345E-fg-hijKL6M',
      ],
      'Secure embed share URL' => [
        'http://embed.vidyard.com/share/A1B_C2D345E-fg-hijKL6M',
        'A1B_C2D345E-fg-hijKL6M',
      ],
      'Secure organizations embed_select URL' => [
        'https://secure.vidyard.com/organizations/12345/embed_select/A1B_C2D345E-fg-hijKL6M',
        'A1B_C2D345E-fg-hijKL6M',
      ],
      // Failing test cases.
      'Non Vidyard domain' => [
        'http://play.somedomain.com/share/-Tv_ARjNb94wiI6G9FyUqw',
        FALSE,
      ],
      'Non Vidyard domain (embed)' => [
        'http://embed.somedomain.com/share/A1B_C2D345E-fg-hijKL6M',
        FALSE,
      ],
      'Non Vidyard domain (organizations)' => [
        'https://secure.somedomain.com/organizations/12345/embed_select/A1B_C2D345E-fg-hijKL6M',
        FALSE,
      ],
      'Malformed URL' => [
        'https://play.vidyard.com/notvalid/-Tv_ARjNb94wiI6G9FyUqw',
        FALSE,
      ],
      // Pass Domain and Pattern.
      'Custom Domain and Watch Pattern' => [
        'http://demos.vidyard.com/watch/WtQbzSSTQvik776jvDidxP',
        FALSE,
      ],
      'Custom Domain and View Pattern' => [
        'http://demos.vidyard.com/view/WtQbzSSTQvik776jvDidxP',
        FALSE,
      ],
      'Custom Domain and Stream Pattern' => [
        'http://demos.vidyard.com/stream/WtQbzSSTQvik776jvDidxP',
        FALSE,
      ],
    ];
  }

}
