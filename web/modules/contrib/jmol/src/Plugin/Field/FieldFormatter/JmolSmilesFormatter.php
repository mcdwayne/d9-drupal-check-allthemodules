<?php

namespace Drupal\jmol\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'jmol' formatter.
 *
 * @FieldFormatter(
 *   id = "jmolsmiles",
 *   label = @Translation("Jmol object"),
 *   field_types = {
 *     "string",
 *   }
 * )
 */
class JmolSmilesFormatter extends JmolFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $elements = FormatterBase::view($items, $langcode);
    return $elements;
  }


  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();
    foreach ($items as $delta => $item) {
      $smiles = $item->value;
      $id = md5($smiles);
      $info = $this->baseInfo();
      $info['script'] = 'load $' . $smiles;
      // Render each element as a jmol element.
      $element[] = array(
        '#attributes' => [
          'id' => $id,
        ],
        '#type' => 'jmol',
        '#info' => $info,
      );
    }

    return $element;
  }

  public function prepareView(array $entities_items){

  }

}
