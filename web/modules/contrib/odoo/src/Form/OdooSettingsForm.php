<?php

namespace Drupal\odoo\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OdooSettingsForm
 *
 * @package Drupal\odoo\Form
 */
class OdooSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'odoo_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['odoo.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('odoo.settings');

    $form['endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('URL of the API endpoint'),
      '#default_value' => $config->get('endpoint') ?: '',
    ];

    $form['database'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Odoo database to use'),
      '#default_value' => $config->get('database') ?: '',
    ];

    $form['user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username of the API user'),
      '#default_value' => $config->get('user') ?: '',
    ];

    $form['pass'] = [
      '#type' => 'password',
      '#title' => $this->t('Password of the API user'),
      '#default_value' => '',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('odoo.settings');
    $values = $form_state->getValues();

    $config->set('endpoint', $values['endpoint']);
    $config->set('database', $values['database']);
    $config->set('user', $values['user']);
    if ($values['pass']) {
      $config->set('pass', $values['pass']);
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
