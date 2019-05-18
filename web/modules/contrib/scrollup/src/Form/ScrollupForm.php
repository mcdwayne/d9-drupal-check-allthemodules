<?php

namespace Drupal\scrollup\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure animated scroll to top settings for this site.
 */
class ScrollupForm extends ConfigFormBase {

  /**
   * Implements getEditableConfigNames().
   */
  protected function getEditableConfigNames() {
    return [
      'scrollup.settings',
    ];
  }

  /**
   * Implements getFormId().
   */
  public function getFormId() {
    return 'scrollup_form';
  }

  /**
   * Implements buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('scrollup.settings');

    $form['themename_fieldset'] = [
      '#title' => $this->t('Theme Visibility Configuration'),
      '#type' => 'fieldset',
    ];
    $form['themename_fieldset']['scrollup_themename'] = [
      '#title' => $this->t('Themes Name'),
      '#description' => $this->t('Scroll up button add multiple themes.'),
      '#type' => 'select',
      '#multiple' => true,
      '#options' => $this->getThemeName(),
      '#default_value' => $config->get('scrollup_themename'),
    ];
    $form['scrolling_fieldset'] = [
      '#title' => $this->t('Scrolling behaviour Configuration'),
      '#type' => 'fieldset',
    ];
    $form['scrolling_fieldset']['scrollup_title'] = [
      '#title' => $this->t( 'Scrollup Button Title' ),
      '#description' => $this->t('scrollup button title'),
      '#type' => 'textfield',
      '#default_value' => $config->get('scrollup_title'),
    ];
    $form['scrolling_fieldset']['scrollup_window_position'] = [
      '#title' => $this->t('Window scrollup fadeIn and fadeout position'),
      '#description' => $this->t('Enter the value of fadeIn & fadeout window scrollup in ms.'),
      '#type' => 'number',
      '#required' => true,
      '#default_value' => $config->get('scrollup_window_position'),
    ];
    $form['scrolling_fieldset']['scrollup_speed'] = [
      '#title' => $this->t('Scrollup speed'),
      '#description' => $this->t('Enter the value of Scrollup speed in ms.'),
      '#type' => 'number',
      '#required' => true,
      '#default_value' => $config->get('scrollup_speed'),
    ];
    $form['button_fieldset'] = [
      '#title' => $this->t('Scrollup Button Configuration'),
      '#type' => 'fieldset',
    ];
    $form['button_fieldset']['scrollup_position'] = [
      '#title' => $this->t('Button Position'),
      '#description' => $this->t('Scrollup button position.'),
      '#type' => 'select',
      '#options' => [
        1 => $this->t('right'),
        2 => $this->t('left'),
      ],
      '#default_value' => $config->get('scrollup_position'),
    ];
    $form['button_fieldset']['scrollup_button_bg_color'] = [
      '#title' => $this->t('Scrollup button background color'),
      '#description' => $this->t('Scrollup button background color.'),
      '#type' => 'color',
      '#default_value' => $config->get('scrollup_button_bg_color'),
    ];
    $form['button_fieldset']['scrollup_button_hover_bg_color'] = [
      '#title' => $this->t('Scrollup button hover background color'),
      '#description' => $this->t('Scrollup button hover background color.'),
      '#type' => 'color',
      '#default_value' => $config->get('scrollup_button_hover_bg_color'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implement getThemeName().
   */
  public function getThemeName(){
    $theme_handler = \Drupal::service('theme_handler');
    $themes = $theme_handler->listInfo();
    foreach($themes as $key=> $val){
      $theme_arr[$key]= $val->info['name'];
    }
    return $theme_arr;
  }

  /**
   * Implement submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $scrollup_position = $form_state->getValues('scrollup_position');
    $scrollup_button_bg_color = $form_state->getValues('scrollup_button_bg_color');
    $scrollup_button_hover_bg_color = $form_state->getValues('scrollup_button_hover_bg_color');	
    $scrollup_title = $form_state->getValues('scrollup_title');
    $scrollup_window_position = $form_state->getValues('scrollup_window_position');
    $scrollup_speed = $form_state->getValues('scrollup_speed');
    $scrollup_themename = $form_state->getValues('scrollup_themename');

    $config = $this->config('scrollup.settings')
      ->set('scrollup_position', $scrollup_position['scrollup_position'])
      ->set('scrollup_button_bg_color', $scrollup_position['scrollup_button_bg_color'])
      ->set('scrollup_button_hover_bg_color', $scrollup_position['scrollup_button_hover_bg_color'])
      ->set('scrollup_title', $scrollup_position['scrollup_title'])
      ->set('scrollup_window_position', $scrollup_position['scrollup_window_position'])
      ->set('scrollup_speed', $scrollup_position['scrollup_speed'])
      ->set('scrollup_themename', $scrollup_position['scrollup_themename'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
