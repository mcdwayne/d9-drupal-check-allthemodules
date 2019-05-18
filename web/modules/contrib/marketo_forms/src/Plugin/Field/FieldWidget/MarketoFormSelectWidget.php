<?php

/**
 * @file
 * Contains \Drupal\marketo_forms\Plugin\field\widget\MarketoFormSelectWidget.
 */

namespace Drupal\marketo_forms\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\marketo_forms\MarketoFormsCore;

/**
 * Plugin implementation of the 'field_marketo_select' widget.
 *
 * @FieldWidget(
 *   id = "field_marketo_select",
 *   module = "marketo_forms",
 *   label = @Translation("Marketo Form"),
 *   field_types = {
 *     "field_marketo_form"
 *   }
 * )
 */
class MarketoFormSelectWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $form_id = isset($items[$delta]->form_id) ? $items[$delta]->form_id : '';
    $element += [
      '#type'          => 'textfield',
      '#title'         => $this->t('Marketo Form'),
      '#default_value' => $form_id,
      '#required'      => TRUE,
    ];
    return [
      'form_id' => $element
    ];
  }

}
