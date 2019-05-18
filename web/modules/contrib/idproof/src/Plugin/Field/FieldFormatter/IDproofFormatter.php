<?php

namespace Drupal\idproof\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 *
 * @FieldFormatter(
 *   id = "idproof_formatter",
 *   label = @Translation("IDproof Formatter"),
 *   field_types = {
 *     "idproof"
 *   }
 * )
 */
 class IDproofFormatter extends FormatterBase {
   /**
    * Define how the field type is showed.
    *
    * Inside this method we can customize how the field is displayed inside
    * pages.
    */
   public function viewElements(FieldItemListInterface $items, $langcode) {
     $elements = array();
     foreach ($items as $delta => $item) {
       $elements[$delta] = array(
         '#type' => 'markup',
         '#markup' => "{$item->idproof}"
       );
     }
     return $elements;
   }

 }
