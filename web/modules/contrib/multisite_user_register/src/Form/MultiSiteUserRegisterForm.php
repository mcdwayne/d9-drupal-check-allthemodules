<?php

namespace Drupal\multisite_user_register\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MultiSiteUserRegisterForm.
 */
class MultiSiteUserRegisterForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $multi_site_user_register = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $multi_site_user_register->label(),
      '#description' => $this->t("Label for the Multi Site User Register."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $multi_site_user_register->id(),
      '#machine_name' => [
        'exists' => '\Drupal\multisite_user_register\Entity\MultiSiteUserRegister::load',
      ],
      '#disabled' => !$multi_site_user_register->isNew(),
    ];

    $form['multisite_user_register'] = [
      '#type' => 'fieldset',
      '#title' => t('Site Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['multisite_user_register']['url'] = [
      '#title' => $this->t('URL'),
      '#type' => 'textfield',
      '#size' => 64,
      '#description' => $this->t("Enter site url."),
      '#required' => TRUE,
      '#default_value' => $multi_site_user_register->get_url(),
    ];

    $form['multisite_user_register']['username'] = [
      '#title' => $this->t('Username'),
      '#type' => 'textfield',
      '#description' => $this->t("Enter site username."),
      '#required' => TRUE,
      '#default_value' => $multi_site_user_register->get_username(),
    ];

    $form['multisite_user_register']['password'] = [
      '#title' => $this->t('password'),
      '#type' => 'password',
      '#description' => $this->t("Enter site password."),
      '#required' => TRUE,
      '#default_value' => $multi_site_user_register->get_password(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $multi_site_user_register = $this->entity;

    $status = $multi_site_user_register->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Multi Site User Register.', [
          '%label' => $multi_site_user_register->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Multi Site User Register.', [
          '%label' => $multi_site_user_register->label(),
        ]));
    }
    $form_state->setRedirectUrl($multi_site_user_register->toUrl('collection'));
  }

}
