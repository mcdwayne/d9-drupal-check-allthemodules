<?php

namespace Drupal\entity_content_export\Plugin\Field\FieldFormatter;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\Annotation\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Define list delimiter.
 *
 * @FieldFormatter(
 *   id = "entity_content_export_text_list_delimiter",
 *   label = @Translation("List delimiter"),
 *   field_types = {"text", "text_long", "text_with_summary", "string", "string_long"}
 * )
 */
class TextListDelimiter extends ListDelimiterFormatterBase {

  /**
   * {@inheritdoc}
   */
  protected function getFieldItemArrayList(FieldItemListInterface $items) {
    $list = [];

    /** @var \Drupal\Core\Field\FieldItemBase $item */
    foreach ($items as $item) {
      if ($item->isEmpty()) {
        continue;
      }
      $list[] = $item->getString();
    }

    return $list;
  }
}
