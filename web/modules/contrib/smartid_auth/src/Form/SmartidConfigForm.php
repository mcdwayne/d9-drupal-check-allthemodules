<?php

namespace Drupal\smartid_auth\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SmartidConfigForm.
 *
 * @package Drupal\smartid_auth\Form
 */
class SmartidConfigForm extends ConfigFormBase {

  /**
   * Path validator service.
   *
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   Path validator service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PathValidatorInterface $path_validator) {
    parent::__construct($config_factory);

    $this->pathValidator = $path_validator;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'smartid_auth.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smartid_auth_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('smartid_auth.settings');

    $form['login_redirect'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect path'),
      '#description' => $this->t('Path to redirect after successful authentication on smartid.ee.'),
      '#default_value' => $config->get('login_redirect'),
    ];

    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#description' => $this->t('Client ID value that you got when registering the smartid.ee website.'),
      '#default_value' => $config->get('client_id'),
    ];

    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#description' => $this->t('Client Secret value that you got when registering the smartid.ee website.'),
      '#default_value' => $config->get('client_secret'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $redirect_path = $form_state->getValue('login_redirect');

    if (!empty($redirect_path)) {
      $url = $this->pathValidator->getUrlIfValidWithoutAccessCheck($redirect_path);

      if (!$url) {
        $form_state->setErrorByName('login_redirect', 'Redirect path must exist!');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('smartid_auth.settings')
      // Redirect save.
      ->set('login_redirect', $form_state->getValue('login_redirect'))
      ->set('client_secret', $form_state->getValue('client_secret'))
      ->set('client_id', $form_state->getValue('client_id'))
      ->save();
  }

}
