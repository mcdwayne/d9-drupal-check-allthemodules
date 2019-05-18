<?php

namespace Drupal\merci_line_item\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MerciLineItemTypeForm.
 *
 * @package Drupal\merci_line_item\Form
 */
class MerciLineItemTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $merci_line_item_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $merci_line_item_type->label(),
      '#description' => $this->t("Label for the Merci Line Item type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $merci_line_item_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\merci_line_item\Entity\MerciLineItemType::load',
      ],
      '#disabled' => !$merci_line_item_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $merci_line_item_type = $this->entity;
    $status = $merci_line_item_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Merci Line Item type.', [
          '%label' => $merci_line_item_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Merci Line Item type.', [
          '%label' => $merci_line_item_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($merci_line_item_type->toUrl('collection'));
  }

}
