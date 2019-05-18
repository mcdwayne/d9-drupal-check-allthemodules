<?php
/**
 * @file
 * Contains \Drupal\mm_fields\Plugin\Field\FieldFormatter\MMFieldsPageName.
 */

namespace Drupal\mm_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * @FieldFormatter(
 *  id = "mm_fields_page_name",
 *  label = @Translation("Name of MM Page/Group"),
 *  field_types = {"mm_catlist", "mm_grouplist"}
 * )
 */
class MMFieldsPageName extends FormatterBase {

  /**
   * @inheritDoc
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $tree = mm_content_get($item->value);
      if ($tree) {
        $elements[$delta] = ['#markup' => mm_content_get_name($tree)];
      }
    }

    return $elements;
  }

}
