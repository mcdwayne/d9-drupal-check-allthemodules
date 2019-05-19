<?php
/**
 * @file
 * Contains \Drupal\resume\Form\ResumeForm.
 */
namespace Drupal\smooth_mouse_scrolling\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Form\ConfigFormBase;

class SmoothScroll extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smooth_scroll';
  }
  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'smooth_mouse_scrolling.settings',
    ];
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // kint($this);
    $config = $this->config('smooth_mouse_scrolling.settings');
    $form['step'] = [
      '#type' => 'textfield',
      '#attributes' => [
          'data-type' => 'number',
      ],
      '#title' => 'Step',
      '#width' => '30%',
      '#align' => 'center',
      '#required' => false,
      '#description' => t('Enter the scroll step.'),
      '#default_value' => isset($config) ? $config->get('step') : '', 
      '#maxlength' => 10
    ];
    $form['speed'] = [
      '#type' => 'textfield',
      '#attributes' => [
          'data-type' => 'number',
      ],
      '#title' => 'Speed',
      '#width' => '30%',
      '#align' => 'center',
      '#required' => false,
      '#description' => t('Enter the scroll speed.'),
      '#default_value' => isset($config)? $config->get('speed') : '',
      '#maxlength' => 10
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
    public function validateForm(array &$form, FormStateInterface $form_state) {
      if (!is_numeric($form_state->getValue('step'))) {
        $form_state->setErrorByName('step', $this->t('Step field must be in integer.'));
      }
      if (!is_numeric($form_state->getValue('speed'))) {
        $form_state->setErrorByName('speed', $this->t('Speed field must be in integer.'));
      }
    }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $step = (int)$form_state->getValue('step');
    $speed = (int)$form_state->getValue('speed');

    // Retrieve the configuration
    $this->configFactory->getEditable('smooth_mouse_scrolling.settings')
      // Set the submitted configuration setting
      ->set('step', $step)
      // You can set multiple configurations at once by making
      // multiple calls to set()
      ->set('speed', $speed)
      ->save();
    parent::submitForm($form, $form_state);
   }
}
