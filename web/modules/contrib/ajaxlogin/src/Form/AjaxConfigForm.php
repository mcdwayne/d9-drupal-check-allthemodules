<?php

namespace Drupal\ajax_login\Form;

use Drupal\Core\Form\ConfigFormBase;

use Drupal\Core\Form\FormStateInterface;

use Drupal\Component\Utility\UrlHelper;

use Drupal\Core\Config\ConfigFactoryInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Path\PathValidator;

/**
 * Class AjaxConfigForm.
 *
 * @package Drupal\ajax_login\Form
 */
class AjaxConfigForm extends ConfigFormBase {

  const AJAX_MODAL_INPUT_SIZE = 5;

  /**
   * Path validator.
   *
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PathValidator $path_validator) {
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
  public function getFormId() {
    return 'ajax_config_form';
  }

  /**
   * Create configuration form for module.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form                                            = parent::buildForm($form, $form_state);
    $config                                          = $this->config('ajax_login.settings');
    $form['ajax_modal_settings']                     = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Modal window settings'),
    ];
    $form['ajax_modal_settings']['ajax_modal_width'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Please choose width of modal window.'),
      '#default_value' => $config->get('ajax_modal_width'),
      '#size'          => self::AJAX_MODAL_INPUT_SIZE,
      '#field_suffix'  => ' px',
    ];

    $form['ajax_modal_result']                      = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Result window settings'),
    ];
    $form['ajax_modal_result']['open_dialog_width'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Please choose width of result window.'),
      '#default_value' => $config->get('open_dialog_width'),
      '#size'          => self::AJAX_MODAL_INPUT_SIZE,
      '#field_suffix'  => ' px',
    ];

    $form['ajax_modal_result']['open_dialog_height'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Please choose height of result window.'),
      '#default_value' => $config->get('open_dialog_height'),
      '#size'          => self::AJAX_MODAL_INPUT_SIZE,
      '#field_suffix'  => ' px',
    ];
    $form['ajax_modal_links']                        = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Links settings'),
    ];
    $form['ajax_modal_links']['ajax_login_links']    = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Would you like to add links for modal window?'),
      '#default_value' => $config->get('ajax_login_links'),
    ];
    $form['ajax_redirect']                           = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Redirect settings'),
    ];
    $this->ajaxRedirectSettings('Login', $form['ajax_redirect']);
    $this->ajaxRedirectSettings('Register', $form['ajax_redirect']);
    $this->ajaxRedirectSettings('Password', $form['ajax_redirect']);
    return $form;
  }

  /**
   * Define redirect settings.
   */
  protected function ajaxRedirectSettings($type, &$form) {
    $config                         = $this->config('ajax_login.settings');
    $settings                       = [
      'default' => $this->t('Default'),
      'custom'  => $this->t('Custom'),
      'refresh' => $this->t('Refresh'),
      'none'    => $this->t('No redirect'),
    ];
    $form['ajax_redirect_' . $type] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('@type form redirect', ['@type' => $type]),
    ];
    if ('Login' == $type) {
      unset($settings['none']);
    }
    $form['ajax_redirect_' . $type]['ajax_redirect_' . strtolower($type) . '_settings'] = [
      '#type'          => 'radios',
      '#options'       => $settings,
      '#default_value' => $config->get('ajax_redirect_' . strtolower($type) . '_settings'),
      '#validated'     => TRUE,
    ];
    $form['ajax_redirect_' . $type]['ajax_redirect_' . strtolower($type) . '_url']      = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Custom redirect LINK'),
      '#description'   => $this->t('External and internal links. Examples: node/1, /node/1, http://example.com.'),
      '#default_value' => $config->get('ajax_redirect_' . strtolower($type) . '_url'),
      '#states'        => [
        'visible' => [
          ':input[name="ajax_redirect_' . strtolower($type) . '_settings"]' => ['value' => 'custom'],
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $fields = [
      'numbers' => [
        'ajax_modal_width',
        'open_dialog_width',
        'open_dialog_height',
      ],
      'type'    => [
        'login',
        'register',
        'password',
      ],
    ];
    foreach ($fields['numbers'] as $field) {
      if (empty($form_state->getValue($field))) {
        $form_state->setErrorByName($field, $this->t('This value should not be blank.'));
      }
      if (!preg_match('/^[\d]+$/', $form_state->getValue($field))) {
        $form_state->setErrorByName($field, $this->t('This value is not valid. A number is expected.'));
      }
    }
    foreach ($fields['type'] as $type) {
      if ('custom' == $form_state->getValue('ajax_redirect_' . $type . '_settings')) {
        // If redirect setting equals 'custom' url field can not be empty.
        if (empty($form_state->getValue('ajax_redirect_' . $type . '_url'))) {
          $form_state->setErrorByName('ajax_redirect_' . $type . '_url', $this->t('This value should not be blank.'));
        }
        else {
          $link = $form_state->getValue('ajax_redirect_' . $type . '_url');
          if (UrlHelper::isValid($link, TRUE)) {
            continue;
          }
          elseif ($this->pathValidator->getUrlIfValid($link) && $link === preg_replace('/^[\/]{2,}/', '', $link)) {
            continue;
          }
          else {
            $form_state->setErrorByName('ajax_redirect_' . $type . '_url', $this->t('The link @link is not a valid link.', ['@link' => $link]));
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('ajax_login.settings');
    foreach ($values as $var => $value) {
      $config->set($var, $value)
        ->save();
    }
  }

  /**
   * Return config settings.
   */
  protected function getEditableConfigNames() {
    return [
      'ajax_login.settings',
    ];
  }

}
