<?php

namespace Drupal\block_sidekick\Controller;

use Drupal\block\Entity\Block;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller routines for Block Sidekick routes.
 */
class BlockSidekickController extends ControllerBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * Constructs a BlockSidekickController object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * Accepts block placement on a page.
   */
  public function placeBlock() {
    $blockId = $this->requestStack->getCurrentRequest()->request->get('block_id');
    $regionId = $this->requestStack->getCurrentRequest()->request->get('region_id');
    $themeId = $this->requestStack->getCurrentRequest()->request->get('theme_id');
    $id = $blockId . '_' . $regionId . '_' . strtotime("now");
    $settings = [
      'plugin' => $blockId,
      'region' => $regionId,
      'id' => $id,
      'theme' => $themeId,
      'label' => $id,
      'visibility' => [],
      'weight' => 0,
    ];
    $block = Block::create($settings);
    $block->save();
    $response = [
      'status' => 'OK',
      'blockId' => $blockId,
      'regionId' => $regionId,
    ];

    return new JsonResponse($response);
  }

  /**
   * Retrieves the region where the block currently resides (before placement).
   */
  public function getCurrentRegion() {
    $blockId = $this->requestStack->getCurrentRequest()->request->get('block_id');
    $themeId = $this->requestStack->getCurrentRequest()->request->get('theme_id');

    echo $blockId . '<br>';

    try {
      $block = Block::load('block.block.' . $blockId);
    } catch (\Exception $e) {
      print_r($e);
    }

    echo '<pre>' . print_r($block, TRUE) . '</pre>';
    die(0);

    $response = [
      'status' => 'OK',
      'regionId' => $block->getRegion(),
    ];

    return new JsonResponse($response);
  }

}
