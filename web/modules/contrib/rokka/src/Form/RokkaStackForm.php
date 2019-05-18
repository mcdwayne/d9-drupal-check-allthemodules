<?php

namespace Drupal\rokka\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class RokkaStackForm.
 */
class RokkaStackForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $rokka_stack = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $rokka_stack->label(),
      '#description' => $this->t("Label for the Rokka stack."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $rokka_stack->id(),
      '#machine_name' => [
        'exists' => '\Drupal\rokka\Entity\RokkaStack::load',
      ],
      '#disabled' => !$rokka_stack->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $rokka_stack = $this->entity;
    $status = $rokka_stack->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Rokka stack.', [
          '%label' => $rokka_stack->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Rokka stack.', [
          '%label' => $rokka_stack->label(),
        ]));
    }
    $form_state->setRedirectUrl($rokka_stack->toUrl('collection'));
  }

}
