<?php
/**
 * @file
 * Contains \Drupal\gdpr_tag_manager\Form\SettingsForm.
 */

namespace Drupal\gdpr_tag_manager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form builder for the gdpr_tag_manager basic settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gdpr_tag_manager_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['gdpr_tag_manager.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('gdpr_tag_manager.settings');
    $form['activate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activate EU lookup'),
      '#default_value' => $config->get('activate'),
      '#description' => $this->t('You can toggle if EU lookup will run here.'),
    ];
    $form['gtm_info_info'] = [
      '#type' => 'details',
      '#title' => $this->t('GTM Settings.'),
      '#open' => FALSE,
    ];
    $form['gtm_info_info']['gtm_container'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google tag manager container ID'),
      '#default_value' => $config->get('gtm_container'),
      '#description' => $this->t('Enter the google tag manager container ID.'),
    ];
    $form['gtm_info_info']['gtm_dl_event'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sets a dataLayer event value on pageload.'),
      '#default_value' => $config->get('gtm_dl_event'),
      '#description' => $this->t('Enter the name of the dataLayer event you want to set.'),
    ];
    $form['ip_service'] = [
      '#type' => 'radios',
      '#title' => $this->t('IP Lookup'),
      '#options' => [
        'IPAPI' => t('IPAPI'),
        'GEOIP' => t('GEOIP'),
      ],
      '#default_value' => $config->get('ip_service'),
      '#description' => $this->t('Select which IP lookup service to use.'),
      '#required' => TRUE,
    ];
    $form['ipapi_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('IPAPI API Key'),
      '#default_value' => $config->get('ipapi_key'),
      '#description' => $this->t('Enter your IPAPI Key.'),
    ];
    $form['cookie_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Cookie'),
      '#open' => FALSE,
    ];
    $form['cookie_info']['cookie_activate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set cookie'),
      '#default_value' => $config->get('cookie_activate'),
      '#description' => $this->t('Check to activate cookie.'),
    ];
    $form['cookie_info']['cookie_duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Cookie duration'),
      '#maxlength' => 3,
      '#min' => 1,
      '#default_value' => $config->get('cookie_duration'),
      '#description' => $this->t('Length of time in days until the cookie expires.'),
    ];
    $form['popup_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Pop-up'),
      '#open' => FALSE,
    ];
    $form['popup_info']['show_popup_us'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude pop-up from North American region.'),
      '#default_value' => $config->get('show_popup_us'),
      '#description' => $this->t('Check to disable cookie popup for North American users.'),
    ];
    $form['popup_info']['pop_up_msg'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Set message for cookie consent popup'),
      '#default_value' => $config->get('pop_up_msg'),
      '#description' => $this->t('Cookie consent pop up message can be set from here.'),
    ];
    $form['popup_info']['pop_up_scroll'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Set scroll length (pixels)'),
      '#default_value' => $config->get('pop_up_scroll'),
      '#description' => $this->t('Set after scrolling how many pixels the popup should disappear. Ex: 500'),
    ];
    $form['popup_info']['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link Text'),
      '#default_value' => $config->get('link_text'),
      '#description' => $this->t('Enter the link text.'),
    ];
    $form['popup_info']['privacy_policy_link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link to privacy policy'),
      '#default_value' => $config->get('privacy_policy_link'),
      '#description' => $this->t('Enter path to privacy page. Begin with "/"'),
    ];
    $form['popup_info']['background_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Background Color'),
      '#default_value' => $config->get('background_color'),
      '#description' => $this->t('Enter hex value with # to change popup background color'),
    ];
    $form['popup_info']['button_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button Color'),
      '#default_value' => $config->get('button_color'),
      '#description' => $this->t('Enter hex value with # to change button color.'),
    ];
    $form['popup_info']['pop_up_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Popup Position'),
      '#options' => [
        'top' => t('TOP'),
        'bottom' => t('BOTTOM'),
        'bottom-left' => t('BOTTOM LEFT'),
        'bottom-right' => t('BOTTOM RIGHT'),
      ],
      '#default_value' => $config->get('pop_up_position'),
      '#description' => $this->t('Select where you want the popup to display on the screen.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('gdpr_tag_manager.settings');
    $config->set('activate', $form_state->getValue('activate'));
    $config->set('pop_up_msg', $form_state->getValue('pop_up_msg'));
    $config->set('pop_up_scroll', $form_state->getValue('pop_up_scroll'));
    $config->set('link_text', $form_state->getValue('link_text'));
    $config->set('privacy_policy_link', $form_state->getValue('privacy_policy_link'));
    $config->set('gtm_container', $form_state->getValue('gtm_container'));
    $config->set('gtm_dl_event', $form_state->getValue('gtm_dl_event'));
    $config->set('ip_service', $form_state->getValue('ip_service'));
    $config->set('ipapi_key', $form_state->getValue('ipapi_key'));
    $config->set('cookie_duration', $form_state->getValue('cookie_duration'));
    $config->set('cookie_activate', $form_state->getValue('cookie_activate'));
    $config->set('show_popup_us', $form_state->getValue('show_popup_us'));
    $config->set('pop_up_position', $form_state->getValue('pop_up_position'));
    $config->set('button_color', $form_state->getValue('button_color'));
    $config->set('background_color', $form_state->getValue('background_color'));
    $config->save();
    parent::submitForm($form, $form_state);
  }
}
