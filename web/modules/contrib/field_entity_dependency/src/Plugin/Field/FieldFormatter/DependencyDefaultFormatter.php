<?php

namespace Drupal\field_entity_dependency\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\field_entity_dependency\Plugin\Field\FieldWidget\DependencyDefaultWidget;

/**
 * Plugin implementation of the 'DependencyDefaultFormatter' formatter.
 *
 * @FieldFormatter(
 *   id = "DependencyDefaultFormatter",
 *   label = @Translation("Dependency"),
 *   field_types = {
 *     "Dependency"
 *   }
 * )
 */
class DependencyDefaultFormatter extends FormatterBase {

  /**
   * Define how the field type is showed.
   *
   * Inside this method we can customize how the field is displayed inside
   * pages.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = [];
    foreach ($items as $delta => $item) {
      // get the current delta
      $delta = DependencyDefaultWidget::getMaxDelta();

      if ($delta < 1) {
        $list_items[] = [
          '#type' => 'label',
          '#title' => t('The Dependency field has not configured yet.'),
        ];
      }
      else {
        // build array
        $values = $item->getValue();
        array_pop($values);
        foreach ($values as $value) {
          if (!is_null($value) && $value != '') {
            $nodes_info[] = $value;
          }
        }
        // load the nodes
        $nodes = \Drupal\node\Entity\Node::loadMultiple($nodes_info);

        // create the items
        foreach ($values as $value) {
          $index = ((int)$value) ? (int)$value : '';
          if (!is_null($index) && $index != '' && $index != 0) {
            $list_items[] = [
              '#type' => 'link',
              '#title' => $nodes[$index]->title->value,
              '#url' => (!is_null($nodes[$index]->urlInfo())) ? $nodes[$index]->urlInfo() : '',
              '#attributes' => [
                'class' => ['fd_field_item'],
              ],
            ];
          }
        }
      }

      // add the element
      $elements[$delta] = [
        '#theme' => 'item_list',
        '#items' => $list_items,
        '#attributes' => [
          'class' => ['fd_fields_list'],
        ],
      ];

    }

    return $elements;
  }

}