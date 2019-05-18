<?php

namespace Drupal\collmex\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Collmex settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'collmex_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['collmex.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['customer'] = [
      '#type' => 'number',
      '#min' => 1,
      '#step' => 1,
      '#title' => $this->t('Collmex customer ID'),
      '#default_value' => $this->config('collmex.settings')->get('customer'),
      '#size' => 20,
    ];
    $form['user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Collmex user'),
      '#default_value' => $this->config('collmex.settings')->get('user'),
      '#size' => 20,
    ];
    $form['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Collmex password'),
      '#default_value' => $this->config('collmex.settings')->get('password'),
      '#size' => 20,
    ];
    $form['system_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('System name'),
      '#description' => $this->t('Collmex keeps track of queried items with this.'),
      '#default_value' => $this->config('collmex.settings')->get('system_name'),
      '#size' => 20,
    ];
    $form['dryrun'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Dry run'),
      '#default_value' => $this->config('collmex.settings')->get('dryrun'),
    ];
    $form['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug'),
      '#default_value' => $this->config('collmex.settings')->get('debug'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('collmex.settings')
      ->set('customer', $form_state->getValue('customer'))
      ->set('user', $form_state->getValue('user'))
      ->set('password', $form_state->getValue('password'))
      ->set('system_name', $form_state->getValue('system_name'))
      ->set('dryrun', $form_state->getValue('dryrun'))
      ->set('debug', $form_state->getValue('debug'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
