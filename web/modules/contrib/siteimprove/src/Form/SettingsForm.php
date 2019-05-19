<?php

namespace Drupal\siteimprove\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\siteimprove\SiteimproveUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\siteimprove\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * SiteimproveUtils var.
   *
   * @var \Drupal\siteimprove\SiteimproveUtils
   */
  protected $siteimprove;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, SiteimproveUtils $siteimprove) {
    parent::__construct($config_factory);

    $this->siteimprove = $siteimprove;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('config.factory'),
      $container->get('siteimprove.utils')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'siteimprove.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'siteimprove_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('siteimprove.settings');

    $form['container'] = [
      '#title' => $this->t('Token'),
      '#type' => 'fieldset',
    ];

    $form['container']['token'] = [
      '#default_value' => $config->get('token'),
      '#description' => $this->t('Configure Siteimprove Plugin token.'),
      '#maxlength' => 50,
      '#prefix' => '<div id="token-wrapper">',
      '#required' => TRUE,
      '#size' => 50,
      '#suffix' => '</div>',
      '#title' => $this->t('Token'),
      '#type' => 'textfield',
    ];

    $form['container']['request_new_token'] = [
      '#ajax' => [
        'callback' => '::requestToken',
        'wrapper' => 'token-wrapper',
      ],
      '#limit_validation_errors' => [],
      '#type' => 'button',
      '#value' => $this->t('Request new token'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements callback for Ajax event on token request.
   *
   * @param array $form
   *   From render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   *
   * @return array
   *   Token field with value filled.
   */
  public function requestToken(array &$form, FormStateInterface &$form_state) {

    // Request new token.
    if ($token = $this->siteimprove->requestToken()) {
      $form['container']['token']['#value'] = $token;
    }
    else {
      drupal_set_message($this->t('There was an error requesting a new token. Please try again in a few minutes.'), 'error');
    }

    $form_state->setRebuild(TRUE);
    return $form['container']['token'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('siteimprove.settings')
      ->set('token', $form_state->getValue('token'))
      ->save();
  }

}
