<?php

namespace Drupal\enquirycart\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Enquiry cart config form.
 */
class EnquiryConfigForm extends ConfigFormBase {

  private $config;

  /**
   * Constructor to set the config.
   */
  public function __construct() {
    $this->config = $this->config('enquirycart.settings');

  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'enquiry_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'enquirycart.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $site_email = $this->config->get('enquirycart.email');
    if (empty($site_email)) {
      $system_site_config = $this->config('system.site');
      $site_email = $system_site_config->get('mail');
    }

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title of the page'),
      '#default_value' => $this->config->get('title'),
      '#description' => $this->t('Type in the page title that you want to display in the enquiry basket'),
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#default_value' => $site_email,
      '#description' => $this->t('Type in the email address that you need to send the enquiry to. By default it uses the site email configured in the website.'),
    ];

    $form['addtoenquirybtntitle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title of button "add to enquiry"'),
      '#default_value' => $this->config->get('buttonTitle'),
      '#description' => $this->t('Type in a title that you want to display in the button'),
    ];

    $form['sendbuttonTitle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title of button "Send Enquiry"'),
      '#default_value' => $this->config->get('sendbuttonTitle'),
      '#description' => $this->t('Type in a title that you want to display in the button to send the enquiry'),
    ];

    $form['basketfullmsg'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Basket full message'),
      '#default_value' => $this->config->get('instructions.basketfull'),
      '#format' => 'full_html',
    ];

    $form['basketemptymsg'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Basket empty message'),
      '#default_value' => $this->config->get('instructions.basketempty'),
      '#format' => 'full_html',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $basketfullvalue = $form_state->getValue('basketfullmsg');
    $basketemptyvalue = $form_state->getValue('basketemptymsg');
    // Retrieve the configuration.
    $this->config
        // Set the submitted configuration setting.
      ->set('title', $form_state->getValue('title'))
        // You can set multiple configurations at once by making
        // multiple calls to set()
      ->set('enquirycart.email', $form_state->getValue('email'))
      ->set('instructions.basketfull', $basketfullvalue['value'])
      ->set('instructions.basketempty', $basketemptyvalue['value'])
      ->set('buttonTitle', $form_state->getValue('addtoenquirybtntitle'))
      ->set('sendbuttonTitle', $form_state->getValue('sendbuttonTitle'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
