<?php

namespace Drupal\add_to_head\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class AddToHeadDeleteProfileForm extends ConfirmFormBase {
  private $profile;

  public function getFormId() {
    return 'add_to_head_delete_profile_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, array $profile = array()) {
    $this->profile = $profile;

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
    $settings = add_to_head_get_settings();

    unset($settings[$this->profile['name']]);

    add_to_head_set_settings($settings);

    $form_state->setRedirect('add_to_head.admin');
  }

  public function getCancelUrl() {
    return Url::fromRoute('add_to_head.admin');
  }

  public function getQuestion() {
    return $this->t('Are you sure you want to delete profile: %name?', array('%name' => $this->profile['name']));
  }
}