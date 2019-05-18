<?php
/**
 * @file
 * Contains \Drupal\christmas_lights\Form\SettingsForm.
 */

namespace Drupal\christmas_lights\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures christmas_lights settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'ims_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
   return ['christmas_lights.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('christmas_lights.settings');

    $form['enabled'] = array(
      '#type' => 'checkbox',
      '#default_value' => $config->get('enabled'),
      '#title' => t('Enable christmas lights'),
    );
    $form['start'] = array(
      '#type' => 'date',
      '#title' => t('Start date'),
      '#default_value' => date('Y-m-d', $config->get('start')),
      '#description' => t('The date your enable christmas lights'),
      '#required' => TRUE,
    );
    $form['end'] = array(
      '#type' => 'date',
      '#title' => t('Finish date'),
      '#default_value' => date('Y-m-d', $config->get('end')),
      '#description' => t('The date your disable christmas lights'),
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $start = strtotime($values['start']);
    $end = strtotime($values['end']);
    if ($start >= $end) {
      $form_state->setErrorByName('start', t('You must select a valid start date.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config('christmas_lights.settings')
      ->set('enabled', $values['enabled'])
      ->set('start', strtotime($values['start']))
      ->set('end', strtotime($values['end']))
      ->save();
  }

}
