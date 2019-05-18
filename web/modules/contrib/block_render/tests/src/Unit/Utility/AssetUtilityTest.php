<?php
/**
 * @file
 * Contains Drupal\block_render\Utility\AssetUtility.
 */

namespace Drupal\Tests\block_render\Unit\Utility;

use Drupal\block_render\Utility\AssetUtility;
use Drupal\Tests\UnitTestCase;

/**
 * Tests a utility to retrieve necessary assets.
 *
 * @group block_render
 */
class AssetUtilityTest extends UnitTestCase {

  /**
   * Tests Retreiving the Asset Response for a set of assets.
   */
  public function testGetAssetResponse() {
    $asset_resolver = $this->getMockBuilder('Drupal\Core\Asset\AssetResolverInterface')
      ->getMock();
    $asset_resolver->expects($this->once())
      ->method('getCssAssets')
      ->will($this->returnValue([
          'asset' => 'value',
        ]));
    $asset_resolver->expects($this->once())
      ->method('getJsAssets')
      ->will($this->returnValue([
          [
            'header' => 'value',
          ],
          [
            'footer' => 'value',
          ],
        ]));

    $config = $this->getConfigFactoryStub([
      'system.performance' => [
        'css.preprocess' => FALSE,
        'js.preprocess' => FALSE,
      ],
    ]);

    $libraries = $this->getMockBuilder('Drupal\block_render\Libraries\LibrariesInterface')
      ->getMock();

    $library_utility = $this->getMockBuilder('Drupal\block_render\Utility\LibraryUtilityInterface')
      ->getMock();
    $library_utility->expects($this->once())
      ->method('getLibraryResponse')
      ->will($this->returnValue($libraries));

    $css_renderer = $this->getMockBuilder('Drupal\Core\Asset\AssetCollectionRendererInterface')
      ->getMock();
    $css_renderer->expects($this->once())
      ->method('render')
      ->will($this->returnValue([
        [
          '#type' => 'link',
          '#src' => 'http://example.com',
        ],
      ]));

    $js_renderer = $this->getMockBuilder('Drupal\Core\Asset\AssetCollectionRendererInterface')
      ->getMock();
    $js_renderer->expects($this->exactly(2))
      ->method('render')
      ->will($this->returnValue([
        [
          '#type' => 'script',
          '#src' => 'http://example.com',
        ],
      ]));

    $asset_utility = new AssetUtility($asset_resolver, $config, $library_utility, $css_renderer, $js_renderer);

    $assets = $this->getMockBuilder('Drupal\Core\Asset\AttachedAssetsInterface')
      ->getMock();

    $response = $asset_utility->getAssetResponse($assets);

    $this->assertInstanceOf('Drupal\block_render\Response\AssetResponseInterface', $response);

    $header = $response->getHeader();
    $this->assertInternalType('array', $header);
    $this->assertArrayHasKey(0, $header);
    $this->assertInternalType('array', $header[0]);
    $this->assertArrayHasKey('src', $header[0]);
    $this->assertInternalType('string', $header[0]['src']);
    $this->assertEquals('http://example.com', $header[0]['src']);

    $footer = $response->getFooter();
    $this->assertInternalType('array', $footer);
    $this->assertArrayHasKey(0, $footer);
    $this->assertInternalType('array', $footer[0]);
    $this->assertArrayHasKey('src', $footer[0]);
    $this->assertInternalType('string', $footer[0]['src']);
    $this->assertEquals('http://example.com', $footer[0]['src']);
  }

}
