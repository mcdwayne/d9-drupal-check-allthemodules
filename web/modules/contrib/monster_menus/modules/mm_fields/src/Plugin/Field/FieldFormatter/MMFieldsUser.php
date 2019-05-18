<?php
/**
 * @file
 * Contains \Drupal\mm_fields\Plugin\Field\FieldFormatter\MMFieldsUser.
 */

namespace Drupal\mm_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * @FieldFormatter(
 *  id = "mm_fields_user",
 *  label = @Translation("User"),
 *  field_types = {"mm_userlist"}
 * )
 */
class MMFieldsUser extends FormatterBase {

  /**
   * @inheritDoc
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => mm_content_uid2name($item->value, 'fmlu')];
    }

    return $elements;
  }

}
