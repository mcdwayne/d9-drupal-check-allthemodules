<?php

namespace Drupal\gutenberg\Controller;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for our blocks routes.
 */
class ReusableBlocksController extends ControllerBase {

  /**
   * Returns JSON representing the loaded blocks.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $block_id
   *   The reusable block id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function load(Request $request, $block_id = NULL) {
    if ($block_id && $block_id > 0) {
      $block = BlockContent::load($block_id);

      return new JsonResponse([
        'id' => (int) $block->id(),
        'title' => ['raw' => $block->info->value],
        'content' => ['protected' => false, 'raw' => $block->body->value],
        'type' => 'block',
        'status' => 'publish',
        'slug' => 'reusable_block_' . $block->id(),
      ]);
    }

    $ids = \Drupal::entityQuery('block_content')
      ->condition('type', 'reusable_block')
      ->execute();

    $blocks = BlockContent::loadMultiple($ids);
    $result = [];

    foreach ($blocks as $key => $block) {
      $result[] = [
        'id' => (int) $block->id(),
        'title' => ['raw' => $block->info->value],
        'content' => ['protected' => false, 'raw' => $block->body->value],
        'type' => 'block',
        'status' => 'publish',
        'slug' => 'reusable_block_' . $block->id(),
      ];
    }

    return new JsonResponse($result);
  }

  /**
   * Saves reusable block.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $block_id
   *   The reusable block id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function save(Request $request, $block_id = NULL) {
    if ($block_id && $block_id > 0) {
      $data = json_decode($request->getContent(), TRUE);
      $block = BlockContent::load($block_id);
      $block->body->value = $data['content'];
      $block->info->value = $data['title'];
    }
    else {
      $params = $request->request->all();
      $block = BlockContent::create([
        'info' => $params['title'],
        'type' => 'reusable_block',
        'body' => [
          'value' => $params['content'],
          'format' => 'full_html',
        ],
      ]);
    }

    $block->save();

    return new JsonResponse([
      'id' => (int) $block->id(),
      'title' => ['raw' => $block->info->value],
      'content' => ['raw' => $block->body->value],
    ]);
  }

  /**
   * Delete reusable block.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $block_id
   *   The reusable block id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function delete(Request $request, $block_id = NULL) {
    $block = BlockContent::load($block_id);
    $block->delete();

    return new JsonResponse([
      'id' => (int) $block_id,
    ]);
  }

}
