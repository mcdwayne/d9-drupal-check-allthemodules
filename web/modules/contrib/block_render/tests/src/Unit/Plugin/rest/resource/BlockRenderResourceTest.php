<?php
/**
 * @file
 * Drupal\Tests\block_render\Unit\Plugin\rest\resource\BlockRenderResource.
 */

namespace Drupal\Tests\block_render\Unit\Plugin\rest\resource;

use Drupal\block_render\Plugin\rest\resource\BlockRenderResource;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests REST endpoint for rendered Blocks.
 *
 * @group block_render
 *
 * @covers Drupal\block_render\Plugin\rest\resource\BlockRenderResourceBase
 */
class BlockRenderResourceTest extends BlockRenderResourceBase {


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
    $entity_manager = $this->getEntityManager();

    $stack = new RequestStack();
    $stack->push(new Request());

    $plugin = $this->getPlugin();
    $plugin->expects($this->once())
      ->method('access')
      ->will($this->returnValue(TRUE));

    $block = $this->getMockBuilder('Drupal\block\BlockInterface')
      ->getMock();
    $block->expects($this->exactly(2))
      ->method('getPlugin')
      ->will($this->returnValue($plugin));

    $block_id = $this->randomMachineName();

    $builder = $this->getBuilder();
    $builder->expects($this->once())
      ->method('build')
      ->will($this->returnValue($block_id));

    $resource = new BlockRenderResource(
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

    $response = $resource->get($block);
    $content = $response->getResponseData();

    $this->assertInternalType('string', $content);
    $this->assertEquals($block_id, $content);
  }

  /**
   * Test Response to GET request failure.
   */
  public function testGetFailure() {
    $block_id = $this->randomMachineName();

    $this->setExpectedException(
      'Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException',
      'Access Denied to Block with ID ' . $block_id
    );

    $configuration = array();
    $plugin_id = $this->randomMachineName();
    $plugin_definition = array();
    $serializer_formats = ['test'];
    $logger = $this->getLogger();
    $current_user = $this->getCurrentUser();
    $translator = $this->getStringTranslationStub();
    $entity_manager = $this->getEntityManager();
    $builder = $this->getBuilder();

    $stack = new RequestStack();
    $stack->push(new Request());

    $plugin = $this->getPlugin();
    $plugin->expects($this->once())
      ->method('access')
      ->will($this->returnValue(FALSE));

    $block = $this->getMockBuilder('Drupal\block\BlockInterface')
      ->getMock();
    $block->expects($this->once())
      ->method('id')
      ->will($this->returnValue($block_id));
    $block->expects($this->once())
      ->method('getPlugin')
      ->will($this->returnValue($plugin));

    $resource = new BlockRenderResource(
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

    $resource->get($block);
  }

  /**
   * Tests altering the routes.
   */
  public function testRoutes() {
    $configuration = array();
    $plugin_id = $this->randomMachineName();
    $plugin_definition = array();
    $serializer_formats = ['test'];
    $logger = $this->getLogger();
    $current_user = $this->getCurrentUser();
    $translator = $this->getStringTranslationStub();
    $entity_manager = $this->getEntityManager();
    $builder = $this->getBuilder();

    $stack = new RequestStack();
    $stack->push(new Request());

    $resource = new BlockRenderResource(
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

    $collection = $resource->routes();
    $this->assertInstanceOf('Symfony\Component\Routing\RouteCollection', $collection);

    $route = $collection->get($plugin_id . '.GET.test');
    $this->assertInstanceOf('Symfony\Component\Routing\Route', $route);

    $options = $route->getOptions();
    $this->assertInternalType('array', $options);
    $this->assertInternalType('array', $options['parameters']);
    $this->assertInternalType('array', $options['parameters']['block']);
    $this->assertInternalType('string', $options['parameters']['block']['type']);
    $this->assertEquals('entity:block', $options['parameters']['block']['type']);
  }

}
