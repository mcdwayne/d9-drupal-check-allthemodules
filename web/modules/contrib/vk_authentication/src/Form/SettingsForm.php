<?php

namespace Drupal\vk_authentication\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\vk_authentication\Form
 */
class SettingsForm extends ConfigFormBase {

  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('messenger')
    );
  }

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Service "messenger".
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vk_authentication.admin_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vk_authentication_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vk_authentication.admin_settings');

    $form['vk_authentication_application_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your application ID'),
      '#description' => $this->t('Id of your vk application (client_id)'),
      '#default_value' => $config->get('vk_authentication_application_id'),
      '#required' => TRUE,
    ];

    $form['vk_authentication_secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your application secret key'),
      '#description' => $this->t('Your application secret key'),
      '#default_value' => $config->get('vk_authentication_secret_key'),
      '#required' => TRUE,
    ];

    $form['vk_authentication_redirect'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your redirect URI'),
      '#description' => $this->t('http://example.com/user/vk/login/response. Redirect to your web site URI after successful authentication'),
      '#required' => TRUE,
    ];

    $form['vk_authentication_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Select dialog display'),
      '#description' => $this->t('Please select one from: page, popup, mobile'),
      '#default_value' => $config->get('vk_authentication_display'),
      '#options' => [
        'page' => $this->t('Page'),
        'popup' => $this->t('Popup'),
        'mobile' => $this->t('Mobile'),
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Checking application ID value.
    if (preg_match('/^[0-9]+$/', $form_state->getValue('vk_authentication_application_id')) !== 1) {
      $form_state->setErrorByName('Application ID', $this->t("Please, provide correct application ID"));
    }
    // Checking secret key value.
    if ((string) $form_state->getValue('vk_authentication_secret_key') == '') {
      $form_state->setErrorByName('Secret key', $this->t("Please, provide secret key"));
    }
    // Checking redirect uri value.
    if (!UrlHelper::isValid($form_state->getValue('vk_authentication_redirect'), TRUE)) {
      $form_state->setErrorByName('Redirect URI', $this->t("Please, provide correct URI (http://example.com)"));
    }
    // Checking display value.
    if ($form_state->getValue('vk_authentication_display') !== 'page'
      && $form_state->getValue('vk_authentication_display') !== 'popup'
      && $form_state->getValue('vk_authentication_display') !== 'mobile') {
      $form_state->setErrorByName('Display parameter', $this->t("Please, provide correct display parameter"));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('vk_authentication.admin_settings')
      ->set('vk_authentication_application_id', $form_state->getValue('vk_authentication_application_id'))
      ->set('vk_authentication_secret_key', $form_state->getValue('vk_authentication_secret_key'))
      ->set('vk_authentication_redirect', $form_state->getValue('vk_authentication_redirect'))
      ->set('vk_authentication_display', $form_state->getValue('vk_authentication_display'))
      ->save();

    $this->messenger->addMessage($this->t('Settings saved successfully.'));
  }

}
