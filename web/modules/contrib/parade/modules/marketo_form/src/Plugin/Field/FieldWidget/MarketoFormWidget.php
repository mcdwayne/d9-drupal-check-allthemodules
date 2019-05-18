<?php

namespace Drupal\marketo_form\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'marketo_form' widget.
 *
 * @FieldWidget(
 *   id = "marketo_form",
 *   module = "marketo_form",
 *   label = @Translation("Marketo form"),
 *   field_types = {
 *     "marketo_form"
 *   }
 * )
 */
class MarketoFormWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items[$delta];
    $element['subscription_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subscription domain'),
      '#description' => $this->t('E.g.: app-e.marketo.com'),
      '#default_value' => $item->subscription_url,
      '#maxlength' => 2048,
      '#required' => $element['#required'],
    ];
    $element['munchkin_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Munchkin ID'),
      '#description' => $this->t('E.g.: 517-ITT-285'),
      '#default_value' => $item->munchkin_id,
      '#maxlength' => 255,
      '#required' => $element['#required'],
    ];
    $element['form_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form ID'),
      '#description' => $this->t('E.g.: 5371'),
      '#default_value' => $item->form_id,
      '#maxlength' => 255,
      '#required' => $element['#required'],
    ];
    return $element;
  }

}
