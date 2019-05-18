<?php

namespace Drupal\colorbox_zoom\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure Colorbox Zoom settings form
 */
class ColorboxZoomSettingsForm extends ConfigFormBase {

  /**
   * A state for the Colorbox Zoom settings
   */
  const STATE_ZOOM_SETTINGS = 0;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'colorbox_zoom_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['colorbox_zoom.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('colorbox_zoom.settings');


    $form['colorbox_zoom_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Colorbox Zoom Settings'),
      '#open' => TRUE,
    ];
    $form['colorbox_zoom_settings']['duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Duration'),
      '#default_value' => $config->get('zoom.duration'),
      '#min' => 0,
      '#size' => 4,
      '#description' => $this->t('The fadeIn/fadeOut speed of the large image. Default is 120.'),
      '#states' => $this->getState(static::STATE_ZOOM_SETTINGS),
    ];
    $colorbox_zoom_on_actions = [
      'mouseover' => $this->t('MouseOver'),
      'grab' => $this->t('Grab'),
      'click' => $this->t('Click'),
      'toggle' => $this->t('Toggle'),
    ];
    $form['colorbox_zoom_settings']['on_action'] = [
      '#type' => 'select',
      '#title' => $this->t('On-Action'),
      '#options' => $colorbox_zoom_on_actions,
      '#default_value' => $config->get('zoom.on_action'),
      '#description' => $this->t('The type of event that triggers zooming.'),
      '#states' => $this->getState(static::STATE_ZOOM_SETTINGS),
    ];
    $form['colorbox_zoom_settings']['touch'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Touch'),
      '#default_value' => $config->get('zoom.touch'),
      '#description' => $this->t('Enables interaction via touch events.'),
      '#states' => $this->getState(static::STATE_ZOOM_SETTINGS),
    ];
    $form['colorbox_zoom_settings']['magnify'] = [
      '#type' => 'number',
      '#title' => $this->t('Magnify'),
      '#default_value' => $config->get('zoom.magnify'),
      '#min' => 0,
      '#size' => 6,
      '#step' => .01,
      '#description' => $this->t('This value is multiplied against the full size of the zoomed image.
      The default value is .75, meaning the zoomed image should be at 75% of its natural width and height.'),
      '#states' => $this->getState(static::STATE_ZOOM_SETTINGS),
    ];
    $form['colorbox_zoom_settings']['zoom_target'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Target'),
      '#default_value' => $config->get('zoom.zoom_target'),
      '#size' => 30,
      '#description' => $this->t('A selector or DOM element that should be used as the parent container for the zoomed image. Leave blank for no target.'),
      '#states' => $this->getState(static::STATE_ZOOM_SETTINGS),
    ];
    $form['colorbox_zoom_settings']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $config->get('zoom.url'),
      '#size' => 50,
      '#description' => $this->t('The url of the large photo to be displayed. If no url is provided, zoom uses the src 
      of the first child IMG element inside the element it is assigned to. Leave blank for default value'),
      '#states' => $this->getState(static::STATE_ZOOM_SETTINGS),
    ];
    $form['colorbox_zoom_settings']['onZoomIn'] = [
      '#type' => 'textfield',
      '#title' => $this->t('onZoomIn'),
      '#default_value' => $config->get('zoom.onZoomIn'),
      '#size' => 50,
      '#description' => $this->t('A function to be called when the image has zoomed in. Inside the function, 
      `this` references the image element. Leave blank for default value.'),
      '#states' => $this->getState(static::STATE_ZOOM_SETTINGS),
    ];
    $form['colorbox_zoom_settings']['onZoomOut'] = [
      '#type' => 'textfield',
      '#title' => $this->t('onZoomOut'),
      '#default_value' => $config->get('zoom.onZoomOut'),
      '#size' => 50,
      '#description' => $this->t('A function to be called when the image has zoomed out. Inside the function, 
      `this` references the image element. Leave blank for default value.'),
      '#states' => $this->getState(static::STATE_ZOOM_SETTINGS),
    ];
    $form['colorbox_zoom_settings']['callback'] = [
      '#type' => 'textfield',
      '#title' => $this->t('callback'),
      '#default_value' => $config->get('zoom.callback'),
      '#size' => 50,
      '#description' => $this->t('A function to be called when the image has loaded. Inside the function,
       `this` references the image element. Leave blank for default value.'),
      '#states' => $this->getState(static::STATE_ZOOM_SETTINGS),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('colorbox_zoom.settings');

    $config
      ->set('zoom.zoom_target', $form_state->getValue('zoom_target'))
      ->set('zoom.duration', $form_state->getValue('duration'))
      ->set('zoom.on_action', $form_state->getValue('on_action'))
      ->set('zoom.touch', $form_state->getValue('touch'))
      ->set('zoom.magnify', $form_state->getValue('magnify'))
      ->set('zoom.url', $form_state->getValue('url'))
      ->set('zoom.onZoomIn', $form_state->getValue('onZoomIn'))
      ->set('zoom.onZoomOut', $form_state->getValue('onZoomOut'))
      ->set('zoom.callback', $form_state->getValue('callback'))
      ->save();

    return parent::submitForm($form, $form_state);
  }

  /**
   * Get one of the pre-defined states used in this form.
   *
   * @param string $state
   *   The state to get that matches one of the state class constants.
   *
   * @return array
   *   A corresponding form API state.
   */
  protected function getState($state) {
    $states = [
      static::STATE_ZOOM_SETTINGS => [
        'visible' => [
          ':input[name="colorbox_zoom_settings_activate"]' => ['value' => '1'],
        ],
      ],
    ];
    return $states[$state];
  }

}