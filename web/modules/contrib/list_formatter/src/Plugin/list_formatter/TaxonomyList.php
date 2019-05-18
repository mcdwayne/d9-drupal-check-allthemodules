<?php
/**
 * @file
 */

namespace Drupal\list_formatter\Plugin\list_formatter;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\list_formatter\Plugin\ListFormatterListInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterInterface;

/**
 * Plugin implementation of the taxonomy module.
 *
 * @ListFormatter(
 *   id = "taxonomy",
 *   module = "taxonomy",
 *   field_types = {"taxonomy_term_reference"}
 * )
 */
class TaxonomyList implements ListFormatterListInterface {

  /**
   * @todo.
   */
  public function createList(FieldItemListInterface $items, FieldDefinitionInterface $field_definition, $langcode) {
    $settings = $display['settings'];
    $list_items = $tids = [];

    // Get an array of tids only.
    foreach ($items as $item) {
      $tids[] = $item['tid'];
    }

    $terms = Term::loadMultiple($tids);

    foreach ($items as $delta => $item) {
      // Check the term for this item has actually been loaded.
      // @see http://drupal.org/node/1281114
      if (empty($terms[$item['tid']])) {
        continue;
      }
      // Use the item name if autocreating, as there won't be a term object yet.
      $term_name = ($item['tid'] === 'autocreate') ? $item['name'] : $terms[$item['tid']]->label();
      // Check if we should display as term links or not.
      if ($settings['term_plain'] || ($item['tid'] === 'autocreate')) {
        $list_items[$delta] = [
          '#markup' => $term_name,
          '#allowed_tags' => FieldFilteredMarkup::allowedTags(),
        ];
      }
      else {
        $url = $terms[$item['tid']]->toUrl();
        $list_items[$delta] = [
          '#type' => 'link',
          '#title' => $term_name,
          '#url' => $url,
          '#options' => []
        ];
      }
    }

    return $list_items;
  }

  /**
   * @todo.
   */
  public function additionalSettings(&$elements, FieldDefinitionInterface $field_definition, FormatterInterface $formatter) {
    if ($field_definition->getType() === 'taxonomy_term_reference') {
      $elements['term_plain'] = [
        '#type' => 'checkbox',
        '#title' => t("Display taxonomy terms as plain text (Not term links)."),
        '#default_value' => $formatter->getSetting('term_plain'),
      ];
    }
  }

}
