<?php

namespace Drupal\youtrack_ui\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class YouTrackSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['youtrack.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'youtrack_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('youtrack.settings');

    $form['youtrack_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#description' => $this->t('YouTrack instance URL'),
      '#default_value' => $config->get('youtrack_url'),
    );

    $form['youtrack_login'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('API User Login'),
      '#default_value' => $config->get('youtrack_login'),
    );

    $form['youtrack_password'] = array(
      '#type' => 'password',
      '#title' => $this->t('API User Password'),
      '#default_value' => $config->get('youtrack_password'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    try {
      $password = strlen($form_state->getValue('youtrack_password')) ? $form_state->getValue('youtrack_password') : $this->config('youtrack.settings')->get('youtrack_password');

      \Drupal::service('youtrack.connection')->connect(
        $form_state->getValue('youtrack_url'),
        $form_state->getValue('youtrack_login'),
        $password
      );
    }
    catch (\YouTrack\Exception $e) {
      $form_state->setError($form, $e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('youtrack.settings');
    $config
      ->set('youtrack_url', $form_state->getValue('youtrack_url'))
      ->set('youtrack_login', $form_state->getValue('youtrack_login'));
    if (strlen($form_state->getValue('youtrack_password'))) {
      $config->set('youtrack_password', $form_state->getValue('youtrack_password'));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }
}