<?php

/**
 * @file
 * Contains \Drupal\field_example\Plugin\field\widget\TextWidget.
 */

namespace Drupal\casetracker\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'casetracker_status' widget.
 *
 * @FieldWidget(
 *   id = "casetracker_state_widget",
 *   module = "casetracker",
 *   label = @Translation("Case State Editor"),
 *   field_types = {
 *     "casetracker_state"
 *   }
 * )
 */
class CaseStatusWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->status) ? $items[$delta]->status : '';
    $element += array(
      '#type' => 'select',
      '#options' => array('1' => 'My Status'),
      '#default_value' => $value,
    );
    return array('case' => $element);
  }


}
