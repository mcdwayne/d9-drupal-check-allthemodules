<?php

namespace Drupal\block_ajax\Controller;

use Drupal\Core\Block\BlockManager;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class BlockAjaxController extends ControllerBase {

  /**
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $block_manager;

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  public function __construct(BlockManager $block_manager, RendererInterface $renderer) {
    $this->block_manager = $block_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('renderer')
    );
  }

  /**
   * Implements ajax block update request handler.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function ajaxBlock(Request $request) {
    $plugin_id = $request->get('plugin_id', '');
    $configuration = $request->get('config', []);

    // Construct and render the block.
    $block = $this->block_manager->createInstance($plugin_id, $configuration)->build();
    $block = $this->renderer->render($block);

    // Render the ajax response.
    return new JsonResponse([
      'content' => $block
    ]);
  }

}
