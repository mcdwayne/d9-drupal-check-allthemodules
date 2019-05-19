<?php

/**
 * @file
 * Contains \Drupal\whiteboard\Plugin\field\formatter\WhiteboardWhiteboardFormatter.
 */

namespace Drupal\whiteboard\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\whiteboard\Whiteboard;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'whiteboard_whiteboard' formatter.
 *
 * @FieldFormatter(
 *   id = "whiteboard_whiteboard",
 *   label = @Translation("Whiteboard Whiteboard"),
 *   field_types = {
 *     "whiteboard"
 *   },
 *   settings = {
 *     "title" = ""
 *   }
 * )
 */
class WhiteboardWhiteboardFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();
    $user = \Drupal::currentUser();

    foreach ($items as $delta => $item) {
      $cid = $item->get('wbid')->getValue();

      $whiteboard = new Whiteboard($wbid);
      whiteboard_add_js($element, $whiteboard);

      $element[$delta] = [
        '#theme' => 'whiteboard_whiteboard',
        '#whiteboard' => $whiteboard,
      ];
    }

    return $element;
  }
}
