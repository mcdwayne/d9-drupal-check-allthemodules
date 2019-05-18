<?php

namespace Drupal\rel_content;

use Drupal\Component\Plugin\PluginBase;

/**
 * A base class to help developers implement their own sandwich plugins.
 *
 * @see \Drupal\rel_content\Annotation\RelatedContent
 * @see \Drupal\rel_content\RelatedContentInterface
 */
abstract class RelatedContentBase extends PluginBase implements RelatedContentInterface {

  /**
   * {@inheritdoc}
   */
  public function description() {
    // Retrieve the @description property from the annotation and return it.
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  abstract public function getOptions();

}
