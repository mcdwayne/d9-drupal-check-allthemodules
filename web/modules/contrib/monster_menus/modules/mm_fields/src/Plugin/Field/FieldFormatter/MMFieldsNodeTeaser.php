<?php
/**
 * @file
 * Contains \Drupal\mm_fields\Plugin\Field\FieldFormatter\MMFieldsNodeTeaser.
 */

namespace Drupal\mm_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\node\Entity\Node;

/**
 * @FieldFormatter(
 *  id = "mm_fields_node_teaser",
 *  label = @Translation("Teaser"),
 *  field_types = {"mm_nodelist"}
 * )
 */
class MMFieldsNodeTeaser extends FormatterBase {

  /**
   * @inheritDoc
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      if ($node = Node::load($item->nid)) {
        $elements[$delta] = node_view($node, 'teaser', $langcode);
      }
    }

    return $elements;
  }

}
