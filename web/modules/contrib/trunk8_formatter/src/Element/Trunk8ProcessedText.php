<?php

namespace Drupal\trunk8_formatter\Element;

use Drupal\filter\Element\ProcessedText;

/**
 * Provides a processed text render element.
 *
 * @RenderElement("trunk8_processed_text")
 */
class Trunk8ProcessedText extends ProcessedText {

  /**
   * {@inheritdoc}
   */
  public static function preRenderText($element) {
    $element['#theme'] = 'trunk8_formatter';
    return parent::preRenderText($element);
  }

}
