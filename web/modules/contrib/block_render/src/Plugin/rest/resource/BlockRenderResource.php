<?php
/**
 * @file
 * Contains Drupal\block_render\Plugin\rest\resource\BlockRenderResource.
 */

namespace Drupal\block_render\Plugin\rest\resource;

use Drupal\block\BlockInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * REST endpoint for rendered Block.
 *
 * @RestResource(
 *   id = "block_render",
 *   label = @Translation("Block Render"),
 *   uri_paths = {
 *     "canonical" = "/block-render/{block}"
 *   }
 * )
 */
class BlockRenderResource extends BlockRenderResourceBase {

  /**
   * Single Block Response.
   *
   * Returns a rendered block entry for the specified block.
   *
   * @param Drupal\block\BlockInterface $block
   *   Block to render.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the rendered block.
   */
  public function get(BlockInterface $block) {
    if (!$block->getPlugin()->access($this->getCurrentUser())) {
      throw new AccessDeniedHttpException($this->t('Access Denied to Block with ID @id', ['@id' => $block->id()]));
    }

    $loaded = $this->getRequest()->get('loaded', array());
    $config = $this->getRequest()->query->all();
    $block->getPlugin()->setConfiguration($config);

    $response = new ResourceResponse($this->getBuilder()->build($block, $loaded));
    $response->addCacheableDependency($block);

    // Cache a different version based on the Query Args.
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['url.query_args']);
    $response->addCacheableDependency($cache);

    return $response;
  }

  /**
   * {@inheritdoc}
   *
   * Set the 'block' paramater as being a block entity.
   */
  public function routes() {
    $collection = parent::routes();

    foreach ($this->getFormats() as $format) {
      $route = $collection->get($this->getPluginId() . '.GET.' . $format);
      $options = $route->getOptions();
      $options['parameters']['block']['type'] = 'entity:block';
      $route->setOptions($options);
    }

    return $collection;
  }

}
