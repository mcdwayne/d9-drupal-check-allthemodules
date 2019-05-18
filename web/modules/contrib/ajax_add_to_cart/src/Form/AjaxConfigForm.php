<?php

namespace Drupal\ajax_add_to_cart\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Path\PathValidator;

/**
 * Class AjaxConfigForm.
 */
class AjaxConfigForm extends ConfigFormBase {

  const AJAX_MODAL_INPUT_SIZE = 5;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;
  /**
   * Drupal\Core\Path\PathValidator definition.
   *
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;

  /**
   * Constructs a new AjaxConfigForm object.
   */
  public function __construct(
    ConfigFactory $config_factory,
    PathValidator $path_validator
    ) {
    parent::__construct($config_factory);
    $this->configFactory = $config_factory;
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
      'ajax_add_to_cart.ajaxconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ajax_add_to_cart.ajaxconfig');
    $form['ajax_modal_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Modal window settings'),
      '#description' => $this->t('Modal window settings'),
    ];
    $form['ajax_modal_settings']['time_ajax_modal'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter time after which modal window closes.'),
      '#default_value' => $config->get('time_ajax_modal'),
      '#description' => $this->t('Enter time in miliseconds like: 2000 stands for 2 seconds'),
    ];
    $form['ajax_modal_settings']['ajax_modal_width'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Please choose width of modal window.'),
      '#default_value' => $config->get('ajax_modal_width'),
      '#size'          => self::AJAX_MODAL_INPUT_SIZE,
      '#field_suffix'  => ' px',
    ];
    $form['ajax_modal_settings']['ajax_modal_height'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Please choose height of modal window.'),
      '#default_value' => $config->get('ajax_modal_height'),
      '#size'          => self::AJAX_MODAL_INPUT_SIZE,
      '#field_suffix'  => ' px',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('ajax_add_to_cart.ajaxconfig')
      ->set('time_ajax_modal', $form_state->getValue('time_ajax_modal'))
      ->set('ajax_modal_width', $form_state->getValue('ajax_modal_width'))
      ->set('ajax_modal_height', $form_state->getValue('ajax_modal_height'))
      ->save();
  }

}
