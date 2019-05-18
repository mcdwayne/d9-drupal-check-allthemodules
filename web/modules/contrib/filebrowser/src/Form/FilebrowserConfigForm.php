<?php

namespace Drupal\filebrowser\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class FilebrowserConfigForm extends ConfigFormBase {

  /**
   * FilebrowserConfigForm constructor.
   * @param $config_factory
   */
  public function __construct( ConfigFactoryInterface $config_factory) {
    parent:: __construct($config_factory);
    $this->setConfigFactory($config_factory);
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['filebrowser.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = \Drupal::service('filebrowser.manager')->addFormExtraFields($form, $form_state, null, true);
    return parent::buildForm($form, $form_state);
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'filebrowser_admin_settings';
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

    parent::validateForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValue('filebrowser');
    \Drupal::configFactory()->getEditable('filebrowser.settings')
      ->set('filebrowser', $form_values)
      ->save();

    parent::submitForm($form, $form_state);
  }
}