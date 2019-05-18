<?php
/**
 * @file
 * Contains \Drupal\mm_fields\Plugin\Field\FieldFormatter\MMFieldsLinkPageFullpath.
 */

namespace Drupal\mm_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Link;

/**
 * @FieldFormatter(
 *  id = "mm_fields_link_page_fullpath",
 *  label = @Translation("Link to MM Page/Group with full path"),
 *  field_types = {"mm_catlist", "mm_grouplist"}
 * )
 */
class MMFieldsLinkPageFullpath extends FormatterBase {

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
        $name = mm_content_get_name($tree);
        $markup = Link::fromTextAndUrl($name, mm_content_get_mmtid_url($tree->mmtid))->toString();
        $path[] = $markup;
      }

      $elements[$delta] = ['#markup' => implode('&nbsp;&raquo; ', $path)];
    }

    return $elements;
  }

}
