<?php

namespace Drupal\Tests\images_optimizer\Kernel\HookHandler;

use Drupal\Component\Render\MarkupInterface;
use Drupal\images_optimizer\HookHandler\HelpHookHandler;
use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel test class for the HelpHookHandler class.
 *
 * @package Drupal\Tests\images_optimizer\Kernel\HookHandler
 */
class HelpHookHandlerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter'];

  /**
   * The help hook handler to test.
   *
   * @var \Drupal\images_optimizer\HookHandler\HelpHookHandler
   */
  private $helpHookHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('filter');

    $this->helpHookHandler = new HelpHookHandler();
  }

  /**
   * Test process().
   */
  public function testProcess() {
    if (version_compare(\Drupal::VERSION, '8.6.0', '<')) {
      /** @var \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler */
      $moduleHandler = $this->container->get('module_handler');
      $moduleHandler->addModule('images_optimizer', sprintf('%s/../../../../', __DIR__));
    }
    else {
      /** @var \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList */
      $moduleExtensionList = $this->container->get('extension.list.module');
      $moduleExtensionList->setPathname('images_optimizer', sprintf('%s/../../../../../', __DIR__));
    }

    $this->assertInstanceOf(MarkupInterface::class, $this->helpHookHandler->process('help.page.images_optimizer'));
  }

}
