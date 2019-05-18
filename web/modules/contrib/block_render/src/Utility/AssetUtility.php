<?php
/**
 * @file
 * Contains Drupal\block_render\Utility\AssetUtility.
 */

namespace Drupal\block_render\Utility;

use Drupal\block_render\Response\AssetResponse;
use Drupal\block_render\Utility\LibraryUtilityInterface;
use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Asset\AssetCollectionRendererInterface;

/**
 * A utility to retrieve necessary assets.
 */
class AssetUtility implements AssetUtilityInterface {

  /**
   * The asset resolver.
   *
   * @var \Drupal\Core\Asset\AssetResolverInterface
   */
  protected $assetResolver;

  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Library Discovery.
   *
   * @var \Drupal\gc_api\Utility\LibraryUtilityInterface
   */
  protected $libraryUtility;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterfac
   */
  protected $renderer;

  /**
   * The CSS asset collection renderer service.
   *
   * @var \Drupal\Core\Asset\AssetCollectionRendererInterface
   */
  protected $cssRenderer;

  /**
   * The JS asset collection renderer service.
   *
   * @var \Drupal\Core\Asset\AssetCollectionRendererInterface
   */
  protected $jsRenderer;

  /**
   * Add the necessary dependencies.
   */
  public function __construct(
    AssetResolverInterface $asset_resolver,
    ConfigFactoryInterface $config,
    LibraryUtilityInterface $library_utility,
    AssetCollectionRendererInterface $css_renderer,
    AssetCollectionRendererInterface $js_renderer) {

    $this->assetResolver = $asset_resolver;
    $this->config = $config;
    $this->libraryUtility = $library_utility;
    $this->cssRenderer = $css_renderer;
    $this->jsRenderer = $js_renderer;
  }

  /**
   * Retrieves the Asset Response for a set of assets.
   *
   * @param \Drupal\Core\Asset\AttachedAssetsInterface $assets
   *   An attached assets object.
   *
   * @return \Drupal\block_render\Data\AssetReponse
   *   An asset response object.
   */
  public function getAssetResponse(AttachedAssetsInterface $assets) {
    // Get the Librarys.
    $libraries = $this->getLibraryUtility()->getLibraryResponse($assets);

    // Get the performance configuration.
    $performance = $this->getConfig()->get('system.performance');

    // Get the CSS & JS Assets.
    $css = $this->getAssetResolver()->getCssAssets($assets, $performance->get('css.preprocess'));
    $js = $this->getAssetResolver()->getJsAssets($assets, $performance->get('js.preprocess'));

    $header = $this->getCssRenderer()->render($css) + $this->getJsRenderer()->render($js[0]);
    $header = array_map([$this, 'cleanAssetProperties'], $header);

    $footer = $this->getJsRenderer()->render($js[1]);
    $footer = array_map([$this, 'cleanAssetProperties'], $footer);

    return new AssetResponse($libraries, $header, $footer);
  }

  /**
   * Cleans asset properties for easier consumption.
   *
   * @param array $asset
   *   Render array of assets.
   *
   * @return array
   *   An array with type and '#' removed.
   */
  protected function cleanAssetProperties(array $asset) {
    $new = array();
    unset($asset['#type']);

    foreach ($asset as $key => $value) {
      $new[ltrim($key, '#')] = $value;
    }

    return $new;
  }

  /**
   * Gets the Asset Resolver object.
   *
   * @return \Drupal\Core\Asset\AssetResolverInterface
   *   Asset Resolver object.
   */
  public function getAssetResolver() {
    return $this->assetResolver;
  }

  /**
   * Gets the Config Factory.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   Config Factory object.
   */
  public function getConfig() {
    return $this->config;
  }


  /**
   * Gets the Library Discovery.
   *
   * @return \Drupal\block_render\Utility\LibraryUtilityInterface
   *   Library Discovery object.
   */
  public function getLibraryUtility() {
    return $this->libraryUtility;
  }

  /**
   * Gets the Library Dependency Resolver.
   *
   * @return \Drupal\Core\Asset\LibraryDependencyResolverInterface
   *   Library Dependency Resolver object.
   */
  public function getLibraryDependencyResolver() {
    return $this->libraryDependencyResolver;
  }

  /**
   * Gets the CSS Renderer service.
   *
   * @return \Drupal\Core\Asset\AssetCollectionRendererInterface
   *   Renderer object.
   */
  public function getCssRenderer() {
    return $this->cssRenderer;
  }

  /**
   * Gets the Javascript Renderer service.
   *
   * @return \Drupal\Core\Asset\AssetCollectionRendererInterface
   *   Renderer object.
   */
  public function getJsRenderer() {
    return $this->jsRenderer;
  }

}
