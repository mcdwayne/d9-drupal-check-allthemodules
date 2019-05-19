<?php

namespace Drupal\tag1quo\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tag1quo\Adapter\Form\FormState;

/**
 * Class AdminSettingsForm8.
 */
class AdminSettingsForm8 extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tag1quo_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = AdminSettingsForm::create()->build($form, FormState::create($form_state));
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    AdminSettingsForm::create()->submit($form, FormState::create($form_state));
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    AdminSettingsForm::create()->validate($form, FormState::create($form_state));
  }

}
