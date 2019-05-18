<?php
/**
 * @file
 * Drupal\Tests\block_render\Unit\Plugin\rest\resource\BlockRenderResource.
 */

namespace Drupal\Tests\block_render\Unit\Plugin\rest\resource;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\block_render\Plugin\rest\resource\BlockRenderMultipleResource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests REST endpoint for rendered Blocks.
 *
 * @group block_render
 *
 * @covers Drupal\block_render\Plugin\rest\resource\BlockRenderResourceBase
 */
class BlockRenderMultipleResourceTest extends BlockRenderResourceBase {

  /**
   * Test Response to GET requests.
   */
  public function testGet() {
    $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
    $cache_contexts_manager = new CacheContextsManager($container, [
      'url.query_args',
    ]);
    $container->expects($this->once())
      ->method('get')
      ->with('cache_contexts_manager')
      ->will($this->returnValue($cache_contexts_manager));
    \Drupal::setContainer($container);

    $configuration = array();
    $plugin_id = $this->randomMachineName();
    $plugin_definition = array();
    $serializer_formats = ['test'];
    $logger = $this->getLogger();
    $current_user = $this->getCurrentUser();
    $translator = $this->getStringTranslationStub();
    $builder = $this->getBuilder();

    $storage = $this->getStorage();
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->will($this->returnValue(array()));

    $entity_manager = $this->getEntityManager();
    $entity_manager->expects($this->once())
      ->method('getStorage')
      ->with('block')
      ->will($this->returnValue($storage));

    $stack = new RequestStack();
    $stack->push(new Request());

    $resource = new BlockRenderMultipleResource(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $serializer_formats,
      $logger,
      $current_user,
      $entity_manager,
      $builder,
      $translator,
      $stack
    );

    $response = $resource->get();
    $content = $response->getResponseData();

    $this->assertInternalType('array', $content);
    $this->assertEmpty($content);
  }

  /**
   * Tests getting multiple blocks.
   */
  public function testGetMultiple() {
    $configuration = array();
    $plugin_id = $this->randomMachineName();
    $plugin_definition = array();
    $serializer_formats = ['test'];
    $logger = $this->getLogger();
    $current_user = $this->getCurrentUser();
    $translator = $this->getStringTranslationStub();
    $builder = $this->getBuilder();

    $plugin = $this->getPlugin();
    $plugin->expects($this->once())
      ->method('access')
      ->will($this->returnValue(TRUE));

    $block_id = $this->randomMachineName();
    $block = $this->getMockBuilder('Drupal\block\BlockInterface')
      ->getMock();
    $block->expects($this->once())
      ->method('getPlugin')
      ->will($this->returnValue($plugin));
    $storage = $this->getStorage();
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->with([$block_id])
      ->will($this->returnValue([$block_id => $block]));

    $entity_manager = $this->getEntityManager();
    $entity_manager->expects($this->once())
      ->method('getStorage')
      ->with('block')
      ->will($this->returnValue($storage));

    $stack = new RequestStack();
    $stack->push(new Request());

    $resource = new BlockRenderMultipleResource(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $serializer_formats,
      $logger,
      $current_user,
      $entity_manager,
      $builder,
      $translator,
      $stack
    );

    $response = $resource->getMultiple([$block_id]);
    $content = $response->getResponseData();

    $this->assertEmpty($content);
  }

  /**
   * Tests the Bad Request exception when getting multiple blocks.
   */
  public function testGetMultipleRequestFauilure() {
    $this->setExpectedException(
      'Symfony\Component\HttpKernel\Exception\BadRequestHttpException',
      'No Block IDs specified'
    );

    $configuration = array();
    $plugin_id = $this->randomMachineName();
    $plugin_definition = array();
    $serializer_formats = ['test'];
    $logger = $this->getLogger();
    $current_user = $this->getCurrentUser();
    $translator = $this->getStringTranslationStub();
    $builder = $this->getBuilder();
    $storage = $this->getStorage();

    $entity_manager = $this->getEntityManager();
    $entity_manager->expects($this->once())
      ->method('getStorage')
      ->with('block')
      ->will($this->returnValue($storage));

    $stack = new RequestStack();
    $stack->push(new Request());

    $resource = new BlockRenderMultipleResource(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $serializer_formats,
      $logger,
      $current_user,
      $entity_manager,
      $builder,
      $translator,
      $stack
    );

    $resource->getMultiple(array());
  }

  /**
   * Tests the Not Found exception when getting multiple blocks.
   */
  public function testGetMultipleNotFoundFauilure() {
    $this->setExpectedException(
      'Symfony\Component\HttpKernel\Exception\NotFoundHttpException',
      'No Blocks found'
    );

    $configuration = array();
    $plugin_id = $this->randomMachineName();
    $plugin_definition = array();
    $serializer_formats = ['test'];
    $logger = $this->getLogger();
    $current_user = $this->getCurrentUser();
    $translator = $this->getStringTranslationStub();
    $builder = $this->getBuilder();
    $storage = $this->getStorage();
    $block_id = $this->randomMachineName();

    $storage->expects($this->once())
      ->method('loadMultiple')
      ->with([$block_id])
      ->will($this->returnValue(array()));

    $entity_manager = $this->getEntityManager();
    $entity_manager->expects($this->once())
      ->method('getStorage')
      ->with('block')
      ->will($this->returnValue($storage));

    $stack = new RequestStack();
    $stack->push(new Request());

    $resource = new BlockRenderMultipleResource(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $serializer_formats,
      $logger,
      $current_user,
      $entity_manager,
      $builder,
      $translator,
      $stack
    );

    $resource->getMultiple([$block_id]);
  }

  /**
   * Tests the list method.
   */
  public function testGetList() {
    $configuration = array();
    $plugin_id = $this->randomMachineName();
    $plugin_definition = array();
    $serializer_formats = ['test'];
    $logger = $this->getLogger();
    $current_user = $this->getCurrentUser();
    $translator = $this->getStringTranslationStub();
    $builder = $this->getBuilder();
    $storage = $this->getStorage();
    $block_id = $this->randomMachineName();

    $plugin = $this->getPlugin();
    $plugin->expects($this->once())
      ->method('access')
      ->will($this->returnValue(TRUE));

    $block_id = $this->randomMachineName();
    $block = $this->getMockBuilder('Drupal\block\BlockInterface')
      ->getMock();
    $block->expects($this->once())
      ->method('getPlugin')
      ->will($this->returnValue($plugin));
    $block->expects($this->once())
      ->method('id')
      ->will($this->returnValue($block_id));
    $block->expects($this->once())
      ->method('label')
      ->will($this->returnValue('Test'));
    $block->expects($this->once())
      ->method('getTheme')
      ->will($this->returnValue('test'));

    $storage = $this->getStorage();
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->will($this->returnValue([$block_id => $block]));

    $entity_manager = $this->getEntityManager();
    $entity_manager->expects($this->once())
      ->method('getStorage')
      ->with('block')
      ->will($this->returnValue($storage));

    $stack = new RequestStack();
    $stack->push(new Request());

    $resource = new BlockRenderMultipleResource(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $serializer_formats,
      $logger,
      $current_user,
      $entity_manager,
      $builder,
      $translator,
      $stack
    );

    $response = $resource->getList();
    $content = $response->getResponseData();

    $this->assertInternalType('array', $content);
    $this->assertArrayHasKey(0, $content);
    $this->assertInternalType('array', $content[0]);
    $this->assertArrayHasKey('id', $content[0]);
    $this->assertInternalType('string', $content[0]['id']);
    $this->assertEquals($block_id, $content[0]['id']);
    $this->assertArrayHasKey('label', $content[0]);
    $this->assertInternalType('string', $content[0]['label']);
    $this->assertEquals('Test', $content[0]['label']);
    $this->assertArrayHasKey('theme', $content[0]);
    $this->assertInternalType('string', $content[0]['theme']);
    $this->assertEquals('test', $content[0]['theme']);
  }

}
