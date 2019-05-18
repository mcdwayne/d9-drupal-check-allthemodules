<?php
/**
 * Author: Remigiusz Kornaga <remkor@o2.pl>
 */

namespace Drupal\regon\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'regon' widget.
 *
 * @FieldWidget(
 *   id = "regon",
 *   module = "regon",
 *   label = @Translation("REGON"),
 *   field_types = {
 *     "regon"
 *   }
 * )
 */
class RegonWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element += array(
      '#type' => 'textfield',
      '#size' => 14,
      '#maxlength' => 14,
      '#element_validate' => array(array($this, 'validate')),
      '#default_value' => (empty($items[$delta]->number)) ? '' : $items[$delta]->number,
    );
    return array('number' => $element);
  }

  /**
   * Validate the regon field.
   */
  public function validate($element, FormStateInterface $form_state) {
    if (!empty($element['#value'])) {
      $regon9 = preg_match('/^[0-9]{9}$/', $element['#value']);
      $regon14 = preg_match('/^[0-9]{14}$/', $element['#value']);
      if ($regon9 || $regon14) {
        $weights = NULL;
        if ($regon9) {
          $weights = array(8, 9, 2, 3, 4, 5, 6, 7);
        }
        else {
          $weights = array(2, 4, 8, 5, 0, 9, 7, 3, 6, 1, 2, 4, 8);
        }
        $sum = 0;
        for ($i = 0; $i < count($weights); $i++) {
          $sum += $element['#value'][$i] * $weights[$i];
        }
        $check = $sum % 11;
        $check = ($check == 10) ? 0 : $check;
        if ($check != $element['#value'][count($weights)]) {
          $form_state->setError($element, t('Incorrect REGON number, a checksum has failed.'));
        }
      }
      else {
        if (ctype_digit($element['#value'])) {
          $length = strlen($element['#value']);
          $form_state->setError($element, t('Incorrect length (@length digits) of REGON number, it should be 9 or 14 digits.', array('@length' => $length)));
        }
        else {
          $form_state->setError($element, t("Incorrect character in REGON number, only digits allowed."));
        }
      }
    }
  }

}
