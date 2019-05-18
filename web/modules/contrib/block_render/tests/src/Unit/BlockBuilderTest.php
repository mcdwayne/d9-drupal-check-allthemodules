<?php
/**
 * @file
 * Contains Drupal\block_render\BlockBuiler.
 */

namespace Drupal\Tests\block_render\Unit;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\block_render\BlockBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * Tests to Build a block from a given id.
 *
 * @group block_render
 */
class BlockBuilderTest extends UnitTestCase {

  /**
   * Test building multiple blocks.
   */
  public function testBuildMultiple() {
    $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
    $cache_contexts_manager = new CacheContextsManager($container, [
      'url.query_args',
    ]);
    $container->expects($this->once())
      ->method('get')
      ->with('cache_contexts_manager')
      ->will($this->returnValue($cache_contexts_manager));
    \Drupal::setContainer($container);

    $asset_response = $this->getMock('Drupal\block_render\Response\AssetResponseInterface');
    $asset_utility = $this->getMock('Drupal\block_render\Utility\AssetUtilityInterface');
    $asset_utility->expects($this->once())
      ->method('getAssetResponse')
      ->will($this->returnValue($asset_response));

    $machine_name = $this->randomMachineName();
    $block = $this->getBlockMockWithMachineName($machine_name);

    $entity_builder = $this->getMock('Drupal\Core\Entity\EntityViewBuilderInterface');
    $entity_builder->expects($this->once())
      ->method('view')
      ->with($block)
      ->will($this->returnValue([
          'content' => [
            '#attached' => [],
            '#markup' => $machine_name,
          ],
        ]));

    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $entity_manager->expects($this->once())
      ->method('getViewBuilder')
      ->with('block')
      ->will($this->returnValue($entity_builder));

    $markup = $this->getMock('Drupal\Component\Render\MarkupInterface');
    $renderer = $this->getMock('Drupal\Core\Render\RendererInterface');
    $renderer->expects($this->once())
      ->method('renderRoot')
      ->will($this->returnValue($markup));

    $block_builder = new BlockBuilder($asset_utility, $entity_manager, $renderer);

    $response = $block_builder->buildMultiple([$block]);

    $this->assertInstanceOf('Drupal\block_render\Response\BlockResponse', $response);
    $this->assertEquals($asset_response, $response->getAssets());
    $this->assertInstanceOf('Drupal\block_render\Content\RenderedContentInterface', $response->getContent());
  }

}
