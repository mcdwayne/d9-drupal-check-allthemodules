<?php

namespace Drupal\ips\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class IpsTypeForm.
 */
class IpsTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $ips_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $ips_type->label(),
      '#description' => $this->t("Label for the Ips type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $ips_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\ips\Entity\IpsType::load',
      ],
      '#disabled' => !$ips_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $ips_type = $this->entity;
    $status = $ips_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Ips type.', [
          '%label' => $ips_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Ips type.', [
          '%label' => $ips_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($ips_type->toUrl('collection'));
  }

}
