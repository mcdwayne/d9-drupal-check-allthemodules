<?php

namespace Drupal\interface_string_stats\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Interface string stats settings.
 */
class StringStatsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'interface_string_stats_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'interface_string_stats.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('interface_string_stats.settings');

    // Capture statistics.
    $form['capture'] = [
      '#type' => 'radios',
      '#title' => $this->t('Capture statistics on interface strings'),
      '#description' => $this->t('Capturing statistics is very process intensive, so you must enable this functionality manually.'),
      '#required' => TRUE,
      '#default_value' => $config->get('capture'),
      '#options' => [
        '1' => $this->t('Yes'),
        '0' => $this->t('No'),
      ],
    ];

    // Uer role filter.
    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles to filter'),
      '#description' => $this->t('Select which roles will <b>not</b> be counted when capturing statistics. To capture only front end strings, select all admin roles here.'),
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', user_role_names()),
      '#default_value' => $config->get('roles'),
      '#weight' => 1,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $roles = user_role_names();
    $roles_selected = $form_state->getValue('roles');
    if (count($roles) == count(array_filter($roles_selected))) {
      $form_state->setErrorByName('roles', $this->t('You cannot select all roles, otherwise statistics will not be captured'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('interface_string_stats.settings')
      ->set('capture', $form_state->getValue('capture'))
      ->set('roles', $form_state->getValue('roles'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
