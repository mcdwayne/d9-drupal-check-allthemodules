<?php

namespace Drupal\colours\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ColoursForm.
 */
class ColoursFormBase extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $colours = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $colours->label(),
      '#description' => $this->t("Label for the Colours."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $colours->id(),
      '#machine_name' => [
        'exists' => '\Drupal\colours\Entity\Colours::load',
      ],
      '#disabled' => !$colours->isNew(),
    ];


    

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $colours = $this->entity;
    $status = $colours->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Colours.', [
          '%label' => $colours->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Colours.', [
          '%label' => $colours->label(),
        ]));
    }
    $form_state->setRedirectUrl($colours->toUrl('collection'));
  }

}
