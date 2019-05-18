<?php
/**
 * @file
 * Contains Drupal\block_render\BlockBuilder.
 */

namespace Drupal\block_render;

use Drupal\block\BlockInterface;
use Drupal\block_render\Content\RenderedContent;
use Drupal\block_render\Utility\AssetUtilityInterface;
use Drupal\block_render\Response\BlockResponse;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Build a block from a given id.
 */
class BlockBuilder implements BlockBuilderInterface {

  /**
   * The asset utility.
   *
   * @var \Drupal\block_render\Utility\AssetUtilityInterface
   */
  protected $assetUtility;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterfac
   */
  protected $renderer;

  /**
   * Construct the object with the necessary dependencies.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Renderer.
   */
  public function __construct(
    AssetUtilityInterface $asset_utility,
    EntityManagerInterface $entity_manager,
    RendererInterface $renderer) {

    $this->assetUtility = $asset_utility;
    $this->entityManager = $entity_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function build(BlockInterface $block, array $loaded = array()) {
    return $this->buildMultiple([$block], $loaded, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function buildMultiple(array $blocks, array $loaded = array(), $single = FALSE) {
    $attached = array();
    $content = array();
    $count = count($blocks);
    $content = new RenderedContent(array(), $single);

    foreach ($blocks as $block) {

      // Build the block content.
      $build = $this->getEntityManager()->getViewBuilder('block')->view($block);

      // If a lazy_builder is returned, execute that first.
      if (isset($build['#lazy_builder'])) {
        $build = call_user_func_array($build['#lazy_builder'][0], $build['#lazy_builder'][1]);
      }

      // The query arguments should be added to the cache contexts.
      $contexts = isset($build['#cache']['contexts']) ? $build['#cache']['contexts'] : array();
      if ($count > 1) {
        $build['#cache']['contexts'] = Cache::mergeContexts(['url.query_args:' . $block->id()], $contexts);
      }
      else {
        $build['#cache']['contexts'] = Cache::mergeContexts(['url.query_args'], $contexts);
      }

      // Execute the pre_render hooks so the block will be built.
      $this->executePreRender($build);

      // Get the attached assets.
      if (!empty($build['content']['#attached'])) {
        $attached = array_merge_recursive($attached, $build['content']['#attached']);
        unset($build['content']['#attached']);
      }

      // Render the block. Render root is used to prevent the cachable metadata
      // from being added to the response, which throws a fatal error. The build
      // is typecasted as a string, because an object is returned.
      $content->addContent($block->id(), (string) $this->getRenderer()->renderRoot($build));
    }

    // Get all of the Assets.
    if ($attached) {
      $assets = AttachedAssets::createFromRenderArray(['#attached' => $attached]);
    }
    else {
      $assets = new AttachedAssets();
    }

    if ($loaded) {
      $assets->setAlreadyLoadedLibraries($loaded);
    }

    // Get the asset response.
    $asset_response = $this->getAssetUtility()->getAssetResponse($assets);

    return new BlockResponse($asset_response, $content);
  }

  /**
   * Executes the Pre Render Callbacks on a build array.
   *
   * @param array $build
   *   Build array with pre-render callbacks.
   */
  private function executePreRender(array &$build) {
    if (isset($build['#pre_render'])) {
      foreach ($build['#pre_render'] as $key => $callable) {
        if (is_string($callable) && strpos($callable, '::') === FALSE) {
          // @TODO controllerResolver is not a property on this class!
          // Since it is not, we'll continue for now.
          continue;
          // $callable =
          // $this->controllerResolver->getControllerFromDefinition($callable);
        }
        $build = call_user_func($callable, $build);
        unset($build['#pre_render'][$key]);
      }
    }
  }

  /**
   * Gets the Asset Utility object.
   *
   * @return \Drupal\block_render\Utility\AssetUtilityInterface
   *   Asset utility object.
   */
  public function getAssetUtility() {
    return $this->assetUtility;
  }

  /**
   * Gets the Entity Manager object.
   *
   * @return \Drupal\Core\Entity\EntityManagerInterface
   *   Entity Manager object.
   */
  public function getEntityManager() {
    return $this->entityManager;
  }

  /**
   * Gets the Renderer service.
   *
   * @return \Drupal\Core\Render\RendererInterface
   *   Renderer object.
   */
  public function getRenderer() {
    return $this->renderer;
  }

}
