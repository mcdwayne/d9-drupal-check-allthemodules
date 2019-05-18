<?php

namespace Drupal\formtips\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Form Tips settings for this site.
 */
class FormtipsSettingForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'formtips_setting_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('formtips.settings');
    $config_data = $config->getRawData();
    foreach ($config_data as $key => $value) {
      $config->set($key, $form_state->getValue($key));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['formtips.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $formtips_settings = $this->config('formtips.settings');
    $form['formtips_trigger_action'] = [
      '#type' => 'select',
      '#title' => $this->t('Trigger action'),
      '#description' => $this->t('Select the action that will trigger the display of tooltips.'),
      '#options' => [
        'hover' => $this->t('Hover'),
        'click' => $this->t('Click'),
      ],
      '#default_value' => $formtips_settings->get('formtips_trigger_action'),
    ];
    $form['formtips_selectors'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Selectors'),
      '#description' => $this->t("Enter some CSS/XPATH selectors (jQuery compatible) for which you don't want to tigger formtips (one per line)."),
      '#default_value' => $formtips_settings->get('formtips_selectors'),
    ];
    $form['formtips_max_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max-width'),
      '#description' => $this->t('Enter a value for the maximum width of the form description tooltip.'),
      '#default_value' => $formtips_settings->get('formtips_max_width'),
    ];
    $form['intent'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Hover intent settings'),
      '#description' => $this->t('Settings for controlling the hover intent plugin.'),
      '#states' => [
        'visible' => [
          ':input[name="formtips_trigger_action"]' => ['value' => 'hover'],
        ],
      ],
    ];
    $form['intent']['formtips_hoverintent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add hoverIntent plugin'),
      '#description' => $this->t('If the hoverIntent plugin is added by another module or in the theme you can switch this setting off.'),
      '#default_value' => $formtips_settings->get('formtips_hoverintent'),
    ];
    $form['intent']['formtips_interval'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Interval'),
      '#description' => $this->t('The number of milliseconds hoverIntent waits between reading/comparing mouse coordinates. When the user\'s mouse first enters the element its coordinates are recorded. The soonest the "over" function can be called is after a single polling interval. Setting the polling interval higher will increase the delay before the first possible "over" call, but also increases the time to the next point of comparison. Default interval: 100'),
      '#default_value' => $formtips_settings->get('formtips_interval'),
    ];
    $form['intent']['formtips_sensitivity'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sensitivity'),
      '#description' => $this->t('If the mouse travels fewer than this number of pixels between polling intervals, then the "over" function will be called. With the minimum sensitivity threshold of 1, the mouse must not move between polling intervals. With higher sensitivity thresholds you are more likely to receive a false positive. Default sensitivity: 7'),
      '#default_value' => $formtips_settings->get('formtips_sensitivity'),
    ];
    $form['intent']['formtips_timeout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Timeout'),
      '#description' => $this->t('A simple delay, in milliseconds, before the "out" function is called. If the user mouses back over the element before the timeout has expired the "out" function will not be called (nor will the "over" function be called). This is primarily to protect against sloppy/human mousing trajectories that temporarily (and unintentionally) take the user off of the target element... giving them time to return. Default timeout: 0'),
      '#default_value' => $formtips_settings->get('formtips_timeout'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
