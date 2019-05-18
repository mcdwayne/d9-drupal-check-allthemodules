<?php

namespace Drupal\cookiecuttr\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure CookieCuttr settings for this site.
 */
class CookieCuttrForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cookiecuttr_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cookiecuttr.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cookiecuttr.settings');

    $form['hide_parts'] = array(
      '#type' => 'textfield',
      '#title' => 'Hide elements',
      '#description' => t("if you'd like to actively hide parts of your website set this to true, for example say you use a comments system that inserts cookies, you can put the div name in below to replace it with a cookie warning message."),
      '#default_value' => $config->get('hide_parts'),
    );

    $form['analytics'] = array(
      '#type' => 'checkbox',
      '#title' => t('Analytics'),
      '#description' => t('if you are just using a simple analytics package you can set this to true, it displays a simple default message with no privacy policy link - this is set to true by default.'),
      '#default_value' => $config->get('analytics'),
    );

    $form['decline_button'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Decline Button'),
      '#description' => t('if you’d like a decline button to (ironically) write a cookie into the browser then set this to true.'),
      '#default_value' => $config->get('decline_button'),
    );

    $form['decline_button_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Decline Button Text'),
      '#description' => t('you can change the text of the red decline button.'),
      '#default_value' => $config->get('decline_button_text'),
      '#states' => array(
        // Hide the settings when the Decline button is disabled.
        'visible' => array(
          ':input[name="decline_button"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['accept_button'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Accept Button'),
      '#default_value' => $config->get('accept_button'),
    );

    $form['accept_button_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Accept Button Text'),
      '#description' => t('you can change the text of the green accept button.'),
      '#default_value' => $config->get('accept_button_text'),
      '#states' => array(
        // Hide the settings when the Accept button is disabled.
        'visible' => array(
          ':input[name="accept_button"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['reset_button'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show Reset Button'),
      '#description' => t('if you’d like a reset button to delete the accept or decline cookies then set this to true.'),
      '#default_value' => $config->get('reset_button'),
    );

    $form['reset_button_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Reset Button Text'),
      '#description' => t('you can change the text of the orange reset button.'),
      '#default_value' => $config->get('reset_button_text'),
      '#states' => array(
        // Hide the settings when the Reset button is disabled.
        'visible' => array(
          ':input[name="reset_button"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['overlay_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Overlay enabled'),
      '#description' => t("don't want a discreet toolbar? this makes the whole message into a 100% height"),
      '#default_value' => $config->get('overlay_enabled'),
    );

    $form['policy_link'] = array(
      '#type' => 'textfield',
      '#title' => t('Cookie Policy Link'),
      '#description' => t('if applicable, enter the link to your privacy policy in here - this is as soon as cookieAnalytics is set to false;'),
      '#default_value' => $config->get('policy_link'),
    );

    $form['message'] = array(
      '#type' => 'textfield',
      '#title' => t('Cookie Bar Message'),
      '#description' => t('edit the message you want to appear in the cookie bar, remember to keep the {{cookiePolicyLink}} variable in tact so it inserts your privacy policy link.'),
      '#default_value' => $config->get('message'),
    );

    $form['analytics_message'] = array(
      '#type' => 'textfield',
      '#title' => t('Analytics Message'),
      '#description' => t('edit the message you want to appear, this is the default message.'),
      '#default_value' => $config->get('analytics_message'),
    );

    $form['what_are_they_link'] = array(
      '#type' => 'textfield',
      '#title' => t('What are Cookies Link'),
      '#description' => t("edit the link for the 'What are Cookies' link."),
      '#default_value' => $config->get('what_are_they_link'),
    );

    $form['error_message'] = array(
      '#type' => 'textfield',
      '#title' => t('Error Message'),
      '#description' => t('edit the message you’d like to appear in place of the functionality'),
      '#default_value' => $config->get('error_message'),
    );

    $form['notification_location_bottom'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show cookie notification at bottom'),
      '#description' => t('Please note the cookie bar will remain at the top for mobile and iOS devices and Internet Explorer 6.'),
      '#default_value' => $config->get('notification_location_bottom'),
    );

    $form['CookieCutter'] = array(
      '#type' => 'checkbox',
      '#title' => t('Disable certain elements if cookies are not accepted'),
        '#default_value' => $config->get('CookieCutter'),
    );

    $form['disable'] = array(
      '#type' => 'textfield',
      '#title' => t('Elements to disable'),
      '#description' => t('list elements comma separated in here that you want to disable, this will only work if cookieCutter is set to true.'),
      '#default_value' => $config->get('disable'),
      '#states' => array(
        // Hide the settings when the CookieCutter checkbox is disabled.
        'visible' => array(
          ':input[name="CookieCutter"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['what_are_link_text'] = array(
      '#type' => 'textfield',
      '#title' => t('What Are Cookies Link Text'),
      '#description' => t('you can change the text of the "What are Cookies" link shown on Google Analytics message.'),
      '#default_value' => $config->get('what_are_link_text'),
    );

    $form['policy_page'] = array(
      '#type' => 'checkbox',
      '#title' => t('Cookie Policy Page'),
      '#description' => t('set this to true to display the message you want to appear on your privacy or cookie policy page.'),
      '#default_value' => $config->get('policy_page'),
    );

    $form['policy_page_message'] = array(
      '#type' => 'textfield',
      '#title' => t('Cookie Policy Page Message'),
      '#description' => t('edit the message you want to appear on your policy page.'),
      '#default_value' => $config->get('policy_page_message'),
    );

    $form['discreet_link'] = array(
      '#type' => 'checkbox',
      '#title' => t('Discreet Link'),
      '#description' => t('false by default, set to true to enable.'),
      '#default_value' => $config->get('discreet_link'),
    );

    $form['discreet_link_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Discreet Link Text'),
      '#description' => t('edit the text you want to appear on the discreet option.'),
      '#default_value' => $config->get('discreet_link_text'),
    );

    $form['discreet_position'] = array(
      '#type' => 'textfield',
      '#title' => t('Discreet Link Position'),
      '#description' => t('set to topleft by default, you can also set topright, bottomleft, bottomright.'),
      '#default_value' => $config->get('discreet_position'),
    );

    $form['domain'] = array(
      '#type' => 'textfield',
      '#title' => t('Cookie Domain'),
      '#description' => t('empty by default, add your domain name in here without www. or https:// or http:// to remove Google Analytics cookies on decline.'),
      '#default_value' => $config->get('domain'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('cookiecuttr.settings')
      ->set('hide_parts', $form_state->getValue('hide_parts'))
      ->set('analytics', $form_state->getValue('analytics'))
      ->set('decline_button', $form_state->getValue('decline_button'))
      ->set('accept_button', $form_state->getValue('accept_button'))
      ->set('reset_button', $form_state->getValue('reset_button'))
      ->set('overlay_enabled', $form_state->getValue('overlay_enabled'))
      ->set('policy_link', $form_state->getValue('policy_link'))
      ->set('message', $form_state->getValue('message'))
      ->set('analytics_message', $form_state->getValue('analytics_message'))
      ->set('what_are_they_link', $form_state->getValue('what_are_they_link'))
      ->set('error_message', $form_state->getValue('error_message'))
      ->set('notification_location_bottom', $form_state->getValue('notification_location_bottom'))
      ->set('disable', $form_state->getValue('disable'))
      ->set('accept_button_text', $form_state->getValue('accept_button_text'))
      ->set('decline_button_text', $form_state->getValue('decline_button_text'))
      ->set('reset_button_text', $form_state->getValue('reset_button_text'))
      ->set('what_are_link_text', $form_state->getValue('what_are_link_text'))
      ->set('policy_page', $form_state->getValue('policy_page'))
      ->set('policy_page_message', $form_state->getValue('policy_page_message'))
      ->set('discreet_link_text', $form_state->getValue('discreet_link_text'))
      ->set('discreet_position', $form_state->getValue('discreet_position'))
      ->set('domain', $form_state->getValue('domain'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}