<?php /**
 * @file
 * Contains \Drupal\first_paragraph\Plugin\Field\FieldFormatter\TextFirstPara.
 */

namespace Drupal\first_paragraph\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Component\Utility\Html;


/**
 * @FieldFormatter(
 *  id = "text_first_para",
 *  label = @Translation("First Paragraph"),
 *  field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary"
 *  }
 * )
 */
class TextFirstPara extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $renderable_item = [
        '#type' => 'processed_text',
        '#text' => $item->value,
        '#format' => $item->format,
        '#langcode' => $item->getLangcode(),
      ];

      $rendered = \Drupal::service('renderer')->renderRoot($renderable_item);


      $first_para = Html::load($rendered)->getElementsByTagName('p');

      if ($first_para->length > 0) {
        $newdom = new \DOMDocument();
        $newdom->appendChild($newdom->importNode(
          $first_para->item(0)->cloneNode(TRUE), TRUE
        ));
        $text = $newdom->saveHTML();

        $elements[$delta] = [
          '#type' => 'processed_text',
          '#text' => $text,
          '#format' => $item->format,
          '#langcode' => $item->getLangcode(),
        ];
      }
      else {
        $elements[$delta] = '';
      }
    }

    return $elements;
  }

}
