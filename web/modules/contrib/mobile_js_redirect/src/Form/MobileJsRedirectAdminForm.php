<?php

namespace Drupal\mobile_js_redirect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements an MobileJsRedirectAdminForm form.
 */
class MobileJsRedirectAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mobile_js_redirect_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mobile_js_redirect.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mobile_js_redirect.settings');
    $form['site_urls'] = array(
      '#type' => 'fieldset',
      '#title' => t('Site URLs'),
    );

    $form['site_urls']['mobile_js_redirect_instructions'] = array(
      '#markup' => t('Mobile and Desktop sites URLs - Please read the instructions below the textarea.'),
    );

    $form['site_urls']['mobile_js_redirect_urls'] = array(
      '#type' => 'textarea',
      '#description' => t('- Put each group of urls on a line separated by | character (pipe) without spaces like: TARGET URL|DESKTOP URL|MOBILE URL.<br>- You can use the * character (asterisk) as a wildcard on target url.<br>- If you wont redirect one of these (desktop or mobile) leave blank.<br>- The redirect occurrs on the first "true" match.'),
      '#default_value' => $config->get('mobile_js_redirect_urls'),
      '#attributes' => array('placeholder' => array('Target URL|Desktop URL|Mobile URL')),
      '#size' => 10,
      '#resizable' => FALSE,
    );

    $form['devices_list'] = array(
      '#type' => 'fieldset',
      '#title' => t('Devices List'),
      '#description' => t('Enter list of User Agent device names which should be recognized as mobile devices.<br>The default list Devices List is: iphone|ipad|ipod|android|blackberry|mini|windowssce|iemobile|palm'),
    );

    $form['devices_list']['mobile_js_redirect_regexp_devices_list'] = array(
      '#type' => 'textfield',
      '#description' => t('Use | for separation.'),
      '#default_value' => (!empty($config->get('mobile_js_redirect_regexp_devices_list'))) ? $config->get('mobile_js_redirect_regexp_devices_list') : MOBILE_JS_REDIRECT_REGEXP_DEVICES_LIST,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('mobile_js_redirect_regexp_devices_list'))) {
      $form_state->setErrorByName('mobile_js_redirect_regexp_devices_list', $this->t('The device list is empty. Please fill the Device list to continue.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('mobile_js_redirect.settings');
    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, 'mobile_js_redirect_') !== FALSE) {
        $config->set($key, $value);
      }
    }

    $config->save();

    drupal_set_message($this->t('Mobile Js Redirect configuration has been saved.'));
  }

}
