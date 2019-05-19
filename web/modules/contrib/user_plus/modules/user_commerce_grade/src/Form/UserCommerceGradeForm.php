<?php

namespace Drupal\user_commerce_grade\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class UserCommerceGradeForm.
 */
class UserCommerceGradeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $user_commerce_grade = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $user_commerce_grade->label(),
      '#description' => $this->t("Label for the User commerce grade."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $user_commerce_grade->id(),
      '#machine_name' => [
        'exists' => '\Drupal\user_commerce_grade\Entity\UserCommerceGrade::load',
      ],
      '#disabled' => !$user_commerce_grade->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $user_commerce_grade = $this->entity;
    $status = $user_commerce_grade->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label User commerce grade.', [
          '%label' => $user_commerce_grade->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label User commerce grade.', [
          '%label' => $user_commerce_grade->label(),
        ]));
    }
    $form_state->setRedirectUrl($user_commerce_grade->toUrl('collection'));
  }

}
