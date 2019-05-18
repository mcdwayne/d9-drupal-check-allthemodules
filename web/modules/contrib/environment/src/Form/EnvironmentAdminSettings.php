<?php

/**
 * @file
 * Contains \Drupal\environment\Form\EnvironmentAdminSettings.
 */

namespace Drupal\environment\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class EnvironmentAdminSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'environment_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    environment_switch($form_state->getValue('environments'));

    $env_override = $form_state->getValue('environment_require_override');

    \Drupal::configFactory()
      ->getEditable('environment.settings')
      ->set('environment_require_override', $env_override)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['environment.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $env_req_override = \Drupal::config('environment.settings')->get('environment_require_override');
    $env_current = environment_current(FALSE);

    if (!$env_current) {
      drupal_set_message($this->t('Not in a valid environment. Saving this form will put it into a valid environment if one exists.'), 'warning');
    }

    $form['environment'] = [
      '#title' => t('Current Environment'),
      '#markup' => '<p>' . $this->t('Current Environment') . ': ' . $env_current . '</p>',
    ];

    if ($env_req_override) {
      $active = $this->t('Active');
    }
    else {
      $active = $this->t('Not Active');
    }

    $form['environment_override'] = [
      '#markup' => '<p>' . $this->t('Environment Override') . ': ' .  $active . '</p>',
    ];

    if (!$env_req_override) {

      $form['environments'] = [
        '#title' => $this->t('Select an environment'),
        '#description' => $this->t('This is the environment you want to switch to.'),
        '#type' => 'select',
        '#options' => _environment_options(),
        '#default_value' => $env_current,
      ];
    }
    $form['environment_require_override'] = [
      '#type' => 'checkbox',
      '#title' => t('Require environment override'),
      '#description' => t('Used to require that an environment is set in the settings.php file.'),
      '#default_value' => \Drupal::config('environment.settings')->get('environment_require_override'),
    ];
    return parent::buildForm($form, $form_state);
  }

}
