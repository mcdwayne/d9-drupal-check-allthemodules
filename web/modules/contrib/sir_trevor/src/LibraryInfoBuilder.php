<?php

namespace Drupal\sir_trevor;

use Drupal\sir_trevor\Plugin\SirTrevorPlugin;
use Drupal\sir_trevor\Plugin\SirTrevorPluginManagerInterface;

class LibraryInfoBuilder {

  /** @var \Drupal\sir_trevor\Plugin\SirTrevorPluginManagerInterface */
  private $blockPluginManager;
  /** @var array */
  private $libraryInfo = [];

  /**
   * @param \Drupal\sir_trevor\Plugin\SirTrevorPluginManagerInterface $blockPluginManager
   */
  public function __construct(SirTrevorPluginManagerInterface $blockPluginManager) {
    $this->blockPluginManager = $blockPluginManager;
  }

  /**
   * @return array
   */
  public function getLibraryInfo() {
    // This method potentially gets called many times in the same request.
    // We therefore statically cache the results in a local variable.
    if (empty($this->libraryInfo)) {
      $this->libraryInfo = $this->fetchLibraryInfo();
    }

    return $this->libraryInfo;
  }

  /**
   * @return array
   */
  protected function fetchLibraryInfo() {
    $libraryInfo = [];

    foreach ($this->blockPluginManager->createInstances() as $instance) {
      foreach (['editor', 'display'] as $context) {
        $info = $this->getContextLibraryInfo($instance, $context);

        if (!empty($info)) {
          $infoKey = "{$instance::getType()}.{$instance->getMachineName()}.{$context}";
          $libraryInfo[$instance->getDefiningModule()][$infoKey] = $info;
        }
      }
    }

    return $libraryInfo;
  }

  /**
   * @param SirTrevorPlugin $instance
   * @param string $context
   * @return array
   */
  private function getContextLibraryInfo(SirTrevorPlugin $instance, $context) {
    $info = [];

    $info = $this->appendCss($info, $instance, $context);
    $info = $this->appendJs($info, $instance, $context);
    $info = $this->appendDependencies($info, $instance, $context);

    if (!empty($info)) {
      $info['dependencies'][] = 'sir_trevor/sir-trevor';
    }

    return $info;
  }

  /**
   * @param array $info
   * @param SirTrevorPlugin $instance
   * @param string $context
   * @return array
   */
  private function appendCss(array $info, SirTrevorPlugin $instance, $context) {
    $asset = 'css';


    if (!empty($this->getAssetValue($asset, $instance, $context))) {
      $assetValue = (array) $this->getAssetValue($asset, $instance, $context);

      foreach ($assetValue as $value) {
        $info[$asset]['theme'][$value] = [];
      }
    }

    return $info;
  }

  /**
   * @param string $asset
   * @param SirTrevorPlugin $instance
   * @param string $context
   * @return string|array
   */
  private function getAssetValue($asset, SirTrevorPlugin $instance, $context) {
    // Return early if we're getting the display assets for a mixin, because
    // mixins don't have display assets.
    if ($instance::getType() == SirTrevorPlugin::mixin && $context == 'display') {
      return '';
    }

    $context = ucfirst($context);
    $asset = ucfirst($asset);
    $methodName = "get{$context}{$asset}";
    return $instance->{$methodName}();
  }

  /**
   * @param array $info
   * @param SirTrevorPlugin $instance
   * @param string $context
   * @return array
   */
  private function appendJs(array $info, SirTrevorPlugin $instance, $context) {
    $asset = 'js';

    if (!empty($this->getAssetValue($asset, $instance, $context))) {
      $assetValue = (array) $this->getAssetValue($asset, $instance, $context);

      foreach ($assetValue as $value) {
        $info[$asset][$value] = [];
      }
    }

    return $info;
  }

  /**
   * @param array $info
   * @param  $instance
   * @param string $context
   * @return array
   */
  private function appendDependencies(array $info, SirTrevorPlugin $instance, $context) {
    $asset = 'dependencies';

    if (!empty($this->getAssetValue($asset, $instance, $context))) {
      $info[$asset] = $this->getAssetValue($asset, $instance, $context);
    }

    return $info;
  }
}
