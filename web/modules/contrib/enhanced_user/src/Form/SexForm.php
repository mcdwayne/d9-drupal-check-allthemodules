<?php

namespace Drupal\enhanced_user\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SexForm.
 */
class SexForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $sex = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $sex->label(),
      '#description' => $this->t("Label for the Sex."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $sex->id(),
      '#machine_name' => [
        'exists' => '\Drupal\enhanced_user\Entity\Sex::load',
      ],
      '#disabled' => !$sex->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $sex = $this->entity;
    $status = $sex->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Sex.', [
          '%label' => $sex->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Sex.', [
          '%label' => $sex->label(),
        ]));
    }
    $form_state->setRedirectUrl($sex->toUrl('collection'));
  }

}
