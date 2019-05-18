<?php
namespace Drupal\lightfoot;

use Drupal\Core\Asset\AssetResolver;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Asset\AttachedAssetsInterface;

//class AssetResolver implements AssetResolverInterface {
class LightfootAssetResolver extends AssetResolver {
  /**
   * {@inheritdoc}
   */
  public function getCssAssets(AttachedAssetsInterface $assets, $optimize) {
    //print_r($assets);
    //print_r($optimize);
    header('getCssAssets: Override');
    $ret = parent::getCssAssets($assets, $optimize);
    //print_r($ret);
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function getJsAssets(AttachedAssetsInterface $assets, $optimize) {
    //print_r($assets);
    //print_r($optimize);
    header('getJsAssets: Override');
    $ret = parent::getJsAssets($assets, $optimize);
    //print_r($ret);
    return $ret;
  }
}
