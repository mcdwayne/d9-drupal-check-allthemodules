<?php

namespace Drupal\mailcamp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\mailcamp\MailcampService;

/**
 * Class SettingsForm.
 *
 * @package Drupal\mailcamp\Form
 */
class SettingsForm extends ConfigFormBase {

  protected $mailcamp;

  /**
   * Constructor, initializes the MailcampService.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->mailcamp = new MailcampService();
    parent::__construct($config_factory);

  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'mailcamp.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailcamp_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mailcamp.settings');
    $form['mailcamp_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mailcamp url'),
      '#description' => $this->t('Enter the xml url of your mailcamp installation.'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('mailcamp_url'),
    ];
    $form['mailcamp_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mailcamp username'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('mailcamp_username'),
    ];
    $form['mailcamp_usertoken'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mailcamp usertoken'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('mailcamp_usertoken'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!filter_var($form_state->getValue('mailcamp_url'), FILTER_VALIDATE_URL)) {
      $form_state->setErrorByName('mailcamp_url', $this->t('Please enter a valid url.'));
    }
    if (!strlen($form_state->getValue('mailcamp_username'))) {
      $form_state->setErrorByName('mailcamp_username', $this->t('Please enter a username.'));
    }
    if (!strlen($form_state->getValue('mailcamp_usertoken'))) {
      $form_state->setErrorByName('mailcamp_usertoken', $this->t('Please enter a usertoken.'));
    }
    $this->mailcamp->url = $form_state->getValue('mailcamp_url');
    $this->mailcamp->username = $form_state->getValue('mailcamp_username');
    $this->mailcamp->usertoken = $form_state->getValue('mailcamp_usertoken');
    $response = $this->mailcamp->getMailingLists();

    if (!is_array($response)) {
      $form_state->setErrorByName('mailcamp', $this->t('The API call was unsuccessful. Make sure that you can connect to the API and your credentials are valid.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('mailcamp.settings');
    $config->set('mailcamp_url', $form_state->getValue('mailcamp_url'));
    $config->set('mailcamp_username', $form_state->getValue('mailcamp_username'));
    $config->set('mailcamp_usertoken', $form_state->getValue('mailcamp_usertoken'));
    $config->save();
  }

}
