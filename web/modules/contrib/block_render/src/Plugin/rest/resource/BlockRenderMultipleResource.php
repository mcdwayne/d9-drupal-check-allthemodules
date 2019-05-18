<?php
/**
 * @file
 * Contains Drupal\block_render\Plugin\rest\resource\BlockRenderResource.
 */

namespace Drupal\block_render\Plugin\rest\resource;

use Drupal\rest\ResourceResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * REST endpoint for multiple rendered Blocks.
 *
 * @RestResource(
 *   id = "block_render_multiple",
 *   label = @Translation("Block Render Multiple"),
 *   uri_paths = {
 *     "canonical" = "/block-render"
 *   }
 * )
 */
class BlockRenderMultipleResource extends BlockRenderResourceBase {


  /**
   * Multiple/List Block Routing.
   *
   * Drupal cannot handle Query String paramater routing, so routing happens
   * here.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the rendered block.
   */
  public function get() {
    $block_ids = $this->getRequest()->get('blocks', array());

    if ($block_ids) {
      $response = $this->getMultiple($block_ids);
    }
    else {
      $response = $this->getList();
    }

    // Cache a different version based on the Query Args.
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['url.query_args']);
    $response->addCacheableDependency($cache);

    return $response;
  }

  /**
   * Multiple Block Response.
   *
   * Returns a list of rendered block entry for the specified block.
   *
   * @param array $block_ids
   *   Reference to the blocks to render.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the rendered block.
   */
  public function getMultiple(array $block_ids) {
    $storage = $this->getEntityManager()->getStorage('block');

    if (!$block_ids) {
      throw new BadRequestHttpException($this->t('No Block IDs specified'));
    }

    // Deliever multiple rendered blocks.
    $blocks = $storage->loadMultiple($block_ids);

    if (!$blocks) {
      throw new NotFoundHttpException($this->t('No Blocks found'));
    }

    $loaded = $this->getRequest()->get('loaded', array());
    $config = $this->getRequest()->query->all();

    foreach ($blocks as $key => $block) {
      if (!$block->getPlugin()->access($this->getCurrentUser())) {
        unset($blocks[$key]);
        continue;
      }

      if (!isset($config[$block->id()])) {
        continue;
      }

      $block->getPlugin()->setConfiguration($config[$block->id()]);
    }

    $response = new ResourceResponse($this->getBuilder()->buildMultiple($blocks, $loaded));

    foreach ($blocks as $block) {
      $response->addCacheableDependency($block);
    }

    return $response;
  }

  /**
   * List Block Response.
   *
   * Returns a list blocks that can be rendered.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the list of blocks that can be rendered.
   */
  public function getList() {
    $blocks = $this->getEntityManager()->getStorage('block')->loadMultiple();

    $list = array();
    foreach ($blocks as $key => $block) {
      if (!$block->getPlugin()->access($this->getCurrentUser())) {
        unset($blocks[$key]);
        continue;
      }

      $list[] = [
        'id' => $block->id(),
        'label' => $block->label(),
        'theme' => $block->getTheme(),
      ];
    }

    $response = new ResourceResponse($list);

    foreach ($blocks as $block) {
      $response->addCacheableDependency($block);
    }

    return $response;
  }

}
