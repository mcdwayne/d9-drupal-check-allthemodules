<?php
/**
 * @file
 * Contains \Drupal\mm_fields\Plugin\Field\FieldFormatter\MMFieldsLinkPage.
 */

namespace Drupal\mm_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * @FieldFormatter(
 *  id = "mm_fields_link_page",
 *  label = @Translation("Link to MM Page/Group"),
 *  field_types = {"mm_catlist", "mm_grouplist"}
 * )
 */
class MMFieldsLinkPage extends FormatterBase {

  /**
   * @inheritDoc
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $tree = mm_content_get($item->value);
      if ($tree) {
        $name = mm_content_get_name($tree);
        $elements[$delta] = [
          '#type' => 'link',
          '#url' => mm_content_get_mmtid_url($tree->mmtid),
          '#title' => $name,
        ];
      }
    }

    return $elements;
  }

}
