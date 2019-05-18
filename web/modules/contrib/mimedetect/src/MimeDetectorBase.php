<?php

namespace Drupal\mimedetect;

use Drupal\Component\Plugin\PluginBase;

/**
 * A base class for mime detector plugins.
 *
 * @see \Drupal\mimedetect\Annotation\MimeDetector
 * @see \Drupal\mimedetect\MimeDetectorInterface
 */
abstract class MimeDetectorBase extends PluginBase implements MimeDetectorInterface {

  /**
   * {@inheritdoc}
   */
  public function description() {
    // Retrieve the @description property from the annotation and return it.
    return $this->pluginDefinition['description'];
  }

}
