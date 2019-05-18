<?php

namespace Drupal\oauth2\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * OAuth2 Settings Form.
 */
class OAuth2SettingsForm extends ConfigFormBase {

  /**
   * {@inheritDoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'oauth2_settings_form';
  }

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames() {
    return ['oauth2.settings'];
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('oauth2.settings');

    $expires_in = [
      '+10 minutes' => $this->t('10 mintues'),
      '+20 minutes' => $this->t('20 mintues'),
      '+30 minutes' => $this->t('30 mintues'),
      '+40 minutes' => $this->t('40 mintues'),
      '+50 minutes' => $this->t('50 mintues'),
      '+1 hours'    => $this->t('1 hours'),
      '+2 hours'    => $this->t('2 hours'),
      '+4 hours'    => $this->t('4 hours'),
      '+8 hours'    => $this->t('8 hours'),
      '+16 hours'   => $this->t('16 hours'),
      '+1 days'     => $this->t('1 days'),
      '+2 days'     => $this->t('2 days'),
      '+4 days'     => $this->t('4 days'),
      '+1 weeks'    => $this->t('1 weeks'),
      '+2 weeks'    => $this->t('2 weeks'),
      '+1 months'   => $this->t('1 months'),
      '+2 months'   => $this->t('2 months'),
      '+4 months'   => $this->t('4 months'),
      '+8 months'   => $this->t('8 months'),
      '+1 years'    => $this->t('1 years'),
    ];
    $form['expires_in'] = [
      '#title' => $this->t('Expires In'),
      '#type' => 'fieldset',
      '#tree' => TRUE,
    ];
    $form['expires_in']['code'] = [
      '#title' => $this->t('Code'),
      '#type' => 'select',
      '#options'=> $expires_in,
      '#default_value' => $config->get('expires_in.code'),
    ];
    $form['expires_in']['access_token'] = [
      '#title' => $this->t('Access Token'),
      '#type' => 'select',
      '#options'=> $expires_in,
      '#default_value' => $config->get('expires_in.access_token'),
    ];
    $form['expires_in']['refresh_token'] = [
      '#title' => $this->t('Refresh Token'),
      '#type' => 'select',
      '#options'=> $expires_in,
      '#default_value' => $config->get('expires_in.refresh_token'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $form_values = $form_state->getValues();

    $this->configFactory->getEditable('oauth2.settings')
      ->set('expires_in.code', $form_values['expires_in']['code'])
      ->set('expires_in.access_token', $form_values['expires_in']['access_token'])
      ->set('expires_in.refresh_token', $form_values['expires_in']['refresh_token'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
