<?php

namespace Drupal\ipless\Asset;

/**
 * Interface AssetRendererInterface.
 *
 * @author Damien LAGUERRE
 */
interface AssetRendererInterface {

  /**
   * Render methode.
   *
   * @param bool $forced
   *   Force render if true.
   */
  public function render($forced = FALSE);

  /**
   * Return Less processor.
   *
   * @return Less_Parser
   */
  public function getLess();
}
