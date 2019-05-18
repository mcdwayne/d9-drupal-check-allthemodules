<?php
/**
 * @file
 * Contains \Drupal\Tests\block_render\Unit\Controller\BlockControllerTest.
 */

namespace Drupal\Tests\block_render\Unit\Controller;

use Drupal\block_render\Controller\BlockController;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests Block Controllers.
 *
 * @group block_render
 */
class BlockControllerTest extends UnitTestCase {

  /**
   * Tests the render controller.
   */
  public function testRender() {
    $stack = new RequestStack();
    $stack->push(new Request());

    $entity_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityManagerInterface')
      ->getMock();

    $view_builder = $this->getMockBuilder('Drupal\Core\Entity\EntityViewBuilderInterface')
      ->getMock();

    $view_builder->expects($this->once())
      ->method('view')
      ->will($this->returnValue(['#theme' => 'block']));

    $entity_manager->expects($this->once())
      ->method('getViewBuilder')
      ->will($this->returnValue($view_builder));

    $current_user = $this->getMockBuilder('Drupal\Core\Session\AccountInterface')
      ->getMock();

    $string_translation = $this->getMockBuilder('Drupal\Core\StringTranslation\TranslationInterface')
      ->getMock();

    $controller = new BlockController($entity_manager, $stack, $current_user, $string_translation);

    $block = $this->getBlockMockWithMachineName($this->randomMachineName());

    $block->getPlugin()->expects($this->once())
      ->method('access')
      ->will($this->returnValue(TRUE));

    $build = $controller->render($block);

    $this->assertArrayHasKey('#theme', $build);
    $this->assertEquals('block', $build['#theme']);

    $this->assertArrayHasKey('#cache', $build);
    $this->assertArrayHasKey('contexts', $build['#cache']);
    $this->assertArrayHasKey(0, $build['#cache']['contexts']);
    $this->assertEquals('url.query_args', $build['#cache']['contexts'][0]);
  }

  /**
   * Tests the render controller failure.
   */
  public function testRenderFailure() {
    $this->setExpectedException('\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException', 'Access Denied to Block');

    $stack = new RequestStack();
    $stack->push(new Request());

    $entity_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityManagerInterface')
      ->getMock();

    $current_user = $this->getMockBuilder('Drupal\Core\Session\AccountInterface')
      ->getMock();

    $string_translation = $this->getMockBuilder('Drupal\Core\StringTranslation\TranslationInterface')
      ->getMock();

    $string_translation->expects($this->once())
      ->method('translate')
      ->will($this->returnValue('Access Denied to Block'));

    $controller = new BlockController($entity_manager, $stack, $current_user, $string_translation);

    $block = $this->getBlockMockWithMachineName($this->randomMachineName());

    $block->getPlugin()->expects($this->once())
      ->method('access')
      ->will($this->returnValue(FALSE));

    $controller->render($block);
  }

  /**
   * Tests rending the title.
   */
  public function testRenderTitle() {
    $controller = $this->createBlockController();

    $block = $this->getBlockMockWithMachineName($this->randomMachineName());
    $block->expects($this->once())
      ->method('label')
      ->will($this->returnValue('Block Label'));

    $label = $controller->renderTitle($block);

    $this->assertInternalType('string', $label);
    $this->assertEquals('Block Label', $label);
  }

  /**
   * Tests getting the Request.
   */
  public function testGetRequest() {
    $controller = $this->createBlockController();

    $this->assertInstanceOf('\Symfony\Component\HttpFoundation\Request', $controller->getRequest());
  }

  /**
   * Create BlockController stub.
   *
   * @return \Drupal\block_render\Controller\BlockController
   *   New BlockController instance.
   */
  public function createBlockController() {
    $stack = new RequestStack();
    $stack->push(new Request());
    $entity_manager = $this->getMockBuilder('Drupal\Core\Entity\EntityManagerInterface')
      ->getMock();
    $current_user = $this->getMockBuilder('Drupal\Core\Session\AccountInterface')
      ->getMock();
    $string_translation = $this->getMockBuilder('Drupal\Core\StringTranslation\TranslationInterface')
      ->getMock();
    return new BlockController($entity_manager, $stack, $current_user, $string_translation);
  }

}
