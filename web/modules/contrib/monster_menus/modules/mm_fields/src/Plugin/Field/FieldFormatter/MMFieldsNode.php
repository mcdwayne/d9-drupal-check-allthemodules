<?php
/**
 * @file
 * Contains \Drupal\mm_fields\Plugin\Field\FieldFormatter\MMFieldsNode.
 */

namespace Drupal\mm_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\node\Entity\Node;

/**
 * @FieldFormatter(
 *  id = "mm_fields_node",
 *  label = @Translation("Full node"),
 *  field_types = {"mm_nodelist"}
 * )
 */
class MMFieldsNode extends FormatterBase {

  /**
   * @inheritDoc
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      if ($node = Node::load($item->nid)) {
        $elements[$delta] = node_view($node, 'full', $langcode);
      }
    }

    return $elements;
  }

}
