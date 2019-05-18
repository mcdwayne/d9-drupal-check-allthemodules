<?php

namespace Drupal\closeblock\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a module settings form.
 */
class CloseBlockSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'closeBlockSettingsForm';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['closeblock.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('closeblock.settings');

    $form['closeblock'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Closeblock selectors'),
      '#weight' => 0,
      '#attributes' => ['id' => 'close_block_form'],
    ];

    $form['closeblock']['close_block_button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button text'),
      '#default_value' => $config->get('close_block_button_text'),
      '#description' => $this->t('Button text for block'),
    ];

    $form['closeblock']['close_block_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Block close behavior'),
      '#options' => [
        'fadeOut' => $this->t('Fade Out'),
        'slideUp' => $this->t('Slide Up'),
        'none' => $this->t('None'),
      ],
      '#description' => $this->t('The animation type for hiding block.'),
      '#default_value' => $config->get('close_block_type'),
    ];

    $form['closeblock']['close_block_speed'] = [
      '#type' => 'number',
      '#title' => $this->t('Animation speed'),
      '#description' => $this->t('The animation speed in milliseconds.'),
      '#default_value' => $config->get('close_block_speed'),
    ];

    $form['closeblock']['reset_cookie_time'] = [
      '#type' => 'number',
      '#title' => $this->t('Reset Cookie Value'),
      '#description' => $this->t('How long cooke is valid (in days).'),
      '#default_value' => $config->get('reset_cookie_time'),
    ];

    $form['closeblock']['resetCookie'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset Cookie'),
      '#description' => $this->t('Reset cookie to visible block'),
      '#id' => 'closeblock-clear-cookie-button',
    ];

    $form['#attached']['library'][] = 'closeblock/closeblock';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('close_block_speed') < 0) {
      $form_state->setErrorByName('close_block_speed',
        $this->t('Animation speed can not be negative.'));
    }
    elseif ($form_state->getValue('close_block_speed') > 5000) {
      $form_state->setErrorByName('close_block_speed',
        $this->t('Animation speed can not be more that 5000'));
    }

    if ($form_state->getValue('reset_cookie_time') < 0) {
      $form_state->setErrorByName('reset_cookie_time',
        $this->t('Reset Cookie Value cannot be negative.'));
    }
    elseif ($form_state->getValue('reset_cookie_time') > 2030) {
      $form_state->setErrorByName('reset_cookie_time',
        $this->t('Reset Cookie Value cannot be more that 2030'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $factory = \Drupal::configFactory()->getEditable('closeblock.settings');
    $factory->set('close_block_type', $form_state->getValue('close_block_type'));
    $factory->set('close_block_speed', $form_state->getValue('close_block_speed'));
    $factory->set('reset_cookie_time', $form_state->getValue('reset_cookie_time'));
    $factory->set('close_block_button_text', $form_state->getValue('close_block_button_text'));
    $factory->save();
  }

}
