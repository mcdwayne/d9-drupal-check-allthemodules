<?php

/**
 * @file
 * Contains \Drupal\hubspot_forms\Plugin\field\widget\HubspotFormSelectWidget.
 */

namespace Drupal\hubspot_forms\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hubspot_forms\HubspotFormsCore;

/**
 * Plugin implementation of the 'field_hubspot_select' widget.
 *
 * @FieldWidget(
 *   id = "field_hubspot_select",
 *   module = "hubspot_forms",
 *   label = @Translation("Hubspot Form"),
 *   field_types = {
 *     "field_hubspot_form"
 *   }
 * )
 */
class HubspotFormSelectWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $form_id = isset($items[$delta]->form_id) ? $items[$delta]->form_id : '';
    $HubspotFormsCore = new HubspotFormsCore();
    $element += [
      '#type'          => 'select',
      '#title'         => $this->t('Hubspot Form'),
      '#options'       => $HubspotFormsCore->getFormIds(),
      '#default_value' => $form_id,
      '#required'      => TRUE,
    ];
    return [
      'form_id' => $element
    ];
  }

}
