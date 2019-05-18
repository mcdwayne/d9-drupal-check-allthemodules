<?php

namespace Drupal\applenews;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface ApplenewsTextStyleInterface.
 *
 * @code
 *  "fontName": "DINAlternate-Bold",
 *  "fontSize": 12,
 *  "lineHeight": 16,
 *  "textColor": "#53585F",
 *  "textAlignment": "right"
 *  "tracking": 0.12,
 * @endcode
 *
 * @see https://developer.apple.com/documentation/apple_news/apple_news_format/text_styles_and_effects/defining_and_using_text_styles
 * @see https://developer.apple.com/documentation/apple_news/text_style
 *
 * @package Drupal\applenews
 */
interface ApplenewsTextStyleInterface extends ConfigEntityInterface {

  /**
   * Generates the TextStyle object.
   *
   * @return \ChapterThree\AppleNewsAPI\Document\Styles\TextStyle
   *   Text style object with style data.
   */
  public function toObject();

}
