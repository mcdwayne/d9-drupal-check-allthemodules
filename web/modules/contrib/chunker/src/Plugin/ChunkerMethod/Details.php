<?php

namespace Drupal\chunker\Plugin\ChunkerMethod;

use Drupal\chunker\ChunkerMethodBase;

/**
 * Performs chunking into details elements.
 *
 * @ChunkerMethod(
 *   id = "details_chunker_method",
 *   label = @Translation("Details"),
 *   description = @Translation("Use HTML5 details and summary to show and hide sections"),
 *   attached = {
 *     "library" = {
 *       "system/drupal.collapse",
 *     },
 *   },
 * )
 */
class Details extends ChunkerMethodBase {

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    $configuration = [
      'section_tag' => 'details',
      'content_class' => 'details-wrapper',
      'heading_tag' => 'summary',
    ] + parent::getConfiguration();
    return $configuration;
  }

}
