<?php

namespace Drupal\bibcite\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Processor plugins.
 */
interface BibCiteProcessorInterface extends PluginInspectionInterface {

  /**
   * Render citation string from CSL values array.
   *
   * @param array|\stdClass $data
   *   CSL values array or object.
   * @param string $csl
   *   Citation style (CSL) content.
   * @param string $lang
   *   Citation language.
   *
   * @return string
   *   Rendered citation.
   */
  public function render($data, $csl, $lang);

  /**
   * Get plugin description markup.
   *
   * @return mixed
   *   Description markup.
   */
  public function getDescription();

  /**
   * Get plugin label markup.
   *
   * @return mixed
   *   Label markup.
   */
  public function getPluginLabel();

}
