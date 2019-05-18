<?php

/**
 * @file
 * Contains Drupal\nicemessages\Form\ConfigForm.
 */

namespace Drupal\nicemessages\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConfigForm.
 *
 * @package Drupal\nicemessages\Form
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'nicemessages.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('nicemessages.config');
    $form['activation_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Activation method'),
      '#description' => $this->t('The settings <strong>On</strong> (1) and <strong>Off</strong> (2) are both global settings affecting all users including Anonoumus user, ignoring any user settings on user account profile forms.'),
      '#default_value' => $config->get('activation_method'),
      '#options' => [
        'on' => $this->t('On'),
        'off' => $this->t('Off'),
        // *todo: Add user setting.
      ],
    ];
    $form['position'] = [
      '#type' => 'select',
      '#title' => $this->t('Message popup screen position'),
      '#description' => $this->t('Choose where the popup messages should be displayed in the browser port view. The appending/prepending of multiple messages will automatically change regarding to your choosen position.'),
      '#default_value' => $config->get('position'),
      '#options' => [
        'top-left' => $this->t('top left'),
        'center' => $this->t('top center'),
        'top-right' => $this->t('top right'),
        'bottom-left' => $this->t('bottom left'),
        'bottom-right' => $this->t('bottom right'),
      ],
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
    parent::submitForm($form, $form_state);

    $this->config('nicemessages.config')
      ->set('activation_method', $form_state->getValue('activation_method'))
      ->set('position', $form_state->getValue('position'))
      ->save();
  }

}
