<?php
/**
 * @file
 * Contains \Drupal\mm_fields\Plugin\Field\FieldFormatter\MMFieldsLinkNodeTitle.
 */

namespace Drupal\mm_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * @FieldFormatter(
 *  id = "mm_fields_link_node_title",
 *  label = @Translation("Title with link"),
 *  field_types = {"mm_nodelist"}
 * )
 */
class MMFieldsLinkNodeTitle extends FormatterBase {

  /**
   * @inheritDoc
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      if ($node = Node::load($item->nid)) {
        $elements[$delta] = [
          '#type' => 'link',
          '#url' => Url::fromRoute('entity.node.canonical', ['node' => $node->id()]),
          '#title' => $node->label(),
        ];
      }
    }

    return $elements;
  }

}
