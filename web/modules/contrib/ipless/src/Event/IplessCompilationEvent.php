<?php

namespace Drupal\ipless\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\ipless\Asset\AssetRenderer;

/**
 * Description of IplessCompilationEvent
 *
 * @author Damien LAGUERRE
 */
class IplessCompilationEvent extends Event {

  /**
   * Less AssetRenderer.
   *
   * @var \Drupal\ipless\Asset\AssetRenderer 
   */
  protected $assetRender;
  
  /**
   * The constructor.
   *
   * @param \Drupal\ipless\Asset\AssetRenderer $asset_renderer
   *   Less AssetRenderer.
   */
  public function __construct(AssetRenderer $asset_renderer) {
    $this->assetRender = $asset_renderer;
  }

  /**
   * Return Less Processor.
   *
   * @return Less_Parser
   */
  public function getLess() {
    return $this->assetRender->getLess();
  }


}
