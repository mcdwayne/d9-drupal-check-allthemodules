<?php

/**
 * @file
 * Contains \Drupal\oauth\Form\OAuthSettingsForm.
 */

namespace Drupal\oauth\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a deletion confirmation form for the block instance deletion form.
 */
class OAuthSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Config\ConfigFactory $config */
    $config = $container->get('config.factory');
    return new static($config);
  }

  /**
   * Constructs an OAuthSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config service.
   */
  public function __construct(ConfigFactory $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'oauth.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'oauth_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->configFactory->get('oauth.settings');

    $form = array();

    $form['request_token_lifetime'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Request token lifetime (in seconds)'),
      '#default_value' => $config->get('request_token_lifetime'),
    );

    $form['login_path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Login page'),
      '#description' => $this->t('Specify an alternative login page. This is useful when, for example, you want to show a mobile-enhanced login page.'),
      '#default_value' => $config->get('login_path'),
    );

    $form['#submit'][] = array($this, 'submitForm');

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if (!intval($form_state->getValue('request_token_lifetime', 10))) {
      $form_state->setErrorByName('oauth_request_token_lifetime', $this->t('The request token lifetime must be a non-zero integer value.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    \Drupal::configFactory()->getEditable('oauth.settings')
      ->set('request_token_lifetime', $form_state->getValue('request_token_lifetime'))
      ->set('login_path', $form_state->getValue('login_path'))
      ->save();
  }

}
