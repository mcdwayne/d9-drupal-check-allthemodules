<?php
/**
 * @file
 * Contains \Drupal\mm_fields\Plugin\Field\FieldFormatter\MMFieldsPageFullpath.
 */

namespace Drupal\mm_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * @FieldFormatter(
 *  id = "mm_fields_page_fullpath",
 *  label = @Translation("Full path to MM Page/Group"),
 *  field_types = {"mm_catlist", "mm_grouplist"}
 * )
 */
class MMFieldsPageFullpath extends FormatterBase {

  /**
   * @inheritDoc
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $mmtids = mm_content_get_parents_with_self($item->value);
      array_shift($mmtids);  // skip root

      $path = array();
      foreach ($mmtids as $mmtid) {
        if (!($tree = mm_content_get($mmtid))) {
          break;
        }
        $path[] = mm_content_get_name($tree);
      }

      $elements[$delta] = ['#markup' => implode('&nbsp;&raquo; ', $path)];
    }

    return $elements;
  }

}
