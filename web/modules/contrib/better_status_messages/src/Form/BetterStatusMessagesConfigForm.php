<?php

namespace Drupal\better_status_messages\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BetterStatusMessagesConfigForm.
 *
 * @package Drupal\better_status_messages\Form
 */
class BetterStatusMessagesConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'better_status_messages_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $weight = 1;

    $form = parent::buildForm($form, $form_state);

    $config = $this->config('better_status_messages.settings');

    $textcolor = $config->get('color_status_text') ?? 'white';
    $bgcolor = $config->get('color_status_bg') ?? '#3D9970';
    $svgcolor = $config->get('color_close_button') ?? 'white';

    $form['color_status_bg'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Status message background color'),
      '#default_value' => $bgcolor,
      '#required' => TRUE,
      '#weight' => $weight,
      '#attributes' => ['style' => 'background-color: ' . $bgcolor . '; color: ' . $textcolor . ';'],
    ];

    $form['color_status_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Status message text color'),
      '#default_value' => $textcolor,
      '#required' => TRUE,
      '#weight' => $weight,
      '#attributes' => ['style' => 'background-color: ' . $bgcolor . '; color: ' . $textcolor . ';'],
    ];

    $form['color_close_button'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Close button svg fill color'),
      '#default_value' => $svgcolor,
      '#required' => TRUE,
      '#weight' => $weight,
      '#attributes' => ['style' => 'background-color: ' . $bgcolor . '; color: ' . $svgcolor . ';'],
    ];

    $textcolor = $config->get('color_error_text') ?? 'white';
    $bgcolor = $config->get('color_error_bg') ?? '#DD0C15';

    $form['color_error_bg'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Error message background color'),
      '#default_value' => $bgcolor,
      '#required' => TRUE,
      '#weight' => $weight,
      '#attributes' => ['style' => 'background-color: ' . $bgcolor . '; color: ' . $textcolor . ';'],
    ];

    $form['color_error_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Error message text color'),
      '#default_value' => $textcolor,
      '#required' => TRUE,
      '#weight' => $weight,
      '#attributes' => ['style' => 'background-color: ' . $bgcolor . '; color: ' . $textcolor . ';'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('better_status_messages.settings');
    $config->set('color_status_bg', $form_state->getValue('color_status_bg'));
    $config->set('color_status_text', $form_state->getValue('color_status_text'));
    $config->set('color_close_button', $form_state->getValue('color_close_button'));
    $config->set('color_error_bg', $form_state->getValue('color_error_bg'));
    $config->set('color_error_text', $form_state->getValue('color_error_text'));

    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'better_status_messages.settings',
    ];
  }

}
