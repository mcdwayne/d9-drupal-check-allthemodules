<?php

namespace Drupal\region_renderer\Controller;

use Drupal\block\BlockRepositoryInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller to render particular region.
 */
class RegionRendererController extends ControllerBase {

  /**
   * The block repository.
   *
   * @var \Drupal\block\BlockRepositoryInterface
   */
  protected $blockRepository;

  /**
   * The block view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $blockViewBuilder;

  /**
   * The cache contexts manager service.
   *
   * @var \Drupal\Core\Cache\Context\CacheContextsManager
   */
  protected $cacheContextsManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new RegionTesterController object.
   *
   * @param \Drupal\block\BlockRepositoryInterface $blockRepository
   *   The block repository.
   * @param \Drupal\Core\Entity\EntityViewBuilderInterface $blockViewBuilder
   *   The block view builder.
   * @param \Drupal\Core\Cache\Context\CacheContextsManager $cacheContextsManager
   *   The cache contexts manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(BlockRepositoryInterface $blockRepository, EntityViewBuilderInterface $blockViewBuilder, CacheContextsManager $cacheContextsManager, RendererInterface $renderer) {
    $this->blockRepository = $blockRepository;
    $this->blockViewBuilder = $blockViewBuilder;
    $this->cacheContextsManager = $cacheContextsManager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('block.repository'),
      $container->get('entity.manager')->getViewBuilder('block'),
      $container->get('cache_contexts_manager'),
      $container->get('renderer')
    );
  }

  /**
   * Render region.
   *
   * @param string $region
   *   Region.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Rendered region.
   */
  public function renderRegion($region) {
    // Get regions with all cache tags/contexts.
    $regions = $this->blockRepository->getVisibleBlocksPerRegion();
    $content = [];
    if (isset($regions[$region])) {
      // Render blocks within the region and get cache metadata.
      foreach ($regions[$region] as $blockName => $block) {
        $content[$blockName] = $this->blockViewBuilder->view($block);
      }
      // Render the content without the surrounding blocks.
      $html = $this->renderer->renderRoot($content);
      $response = new CacheableResponse($html);
      $response->addCacheableDependency(CacheableMetadata::createFromRenderArray($content));
      return $response;
    }
    else {
      return new Response('', Response::HTTP_NOT_FOUND);
    }
  }

}
