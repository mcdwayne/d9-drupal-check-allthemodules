<?php

namespace Drupal\asf\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'AsfFormatter' formatter.
 *
 * @FieldFormatter(
 *   id = "AsfFormatter",
 *   module = "asf",
 *   label = @Translation("Show Advanced Publication input"),
 *   field_types = {
 *     "asf"
 *   }
 * )
 */
class AsfFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $keys = array_keys($item->toArray());
      foreach ($keys as $key) {
        //var_dump($item->{$key});
        if(($key == 'startdate' || $key == 'enddate')) {
          //var_dump($item->{$key});
          if($item->{$key} == 0) {
            $val = '';
          }else{
            $val = date('Y-m-d H:i', $item->{$key});
          }

        }else{
          $val = $item->{$key};
        }
        if(!empty($val)) {
          $elements[$delta][] = array(
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#attributes' => array(
              //'style' => 'color: ' . $item->value,
            ),
            '#value' => $this->t('%field: %value', array(
              '%field' => $key,
              '%value' => $val,
            )),
          );
        }

      }
    }

    return $elements;
  }

}
