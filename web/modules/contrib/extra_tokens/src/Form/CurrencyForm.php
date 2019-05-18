<?php

namespace Drupal\extra_tokens\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfigFormBaseTrait;

/**
 * Configure currency settings for this site.
 */
class CurrencyForm extends FormBase {

  use ConfigFormBaseTrait;

  /**
   * Stores the state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a CurrencyForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state, DateFormatterInterface $date_formatter, ModuleHandlerInterface $module_handler) {
    $this->state = $state;
    $this->dateFormatter = $date_formatter;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['extra_tokens.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state'),
      $container->get('date.formatter'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'extra_tokens_currency_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#markup' => '<p>' . t('Provides settings currency convert token.') . '</p>',
    ];
    $config = $this->config('extra_tokens.settings');
    $form['CURRENCIES'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Available currencies'),
      '#default_value' => implode( ',', array_keys($config->get('CURRENCIES'))),
      '#editable' => FALSE,
    ];
    $form['BASE_CURRENCY'] = [
      '#type' => 'select',
      '#title' => $this->t('Base currency'),
      '#default_value' => $config->get('BASE_CURRENCY'),
      '#options' => $config->get('CURRENCIES'),
    ];
    $form['EXCHANGE_RATE_UAH'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exchange rate UAH'),
      '#default_value' => $config->get('EXCHANGE_RATE_UAH'),
    ];
    $form['EXCHANGE_RATE_GBR'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exchange rate GBR'),
      '#default_value' => $config->get('EXCHANGE_RATE_GBR'),
    ];
    $form['EXCHANGE_RATE_EUR'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exchange rate EUR'),
      '#default_value' => $config->get('EXCHANGE_RATE_EUR'),
    ];
    $form['EXCHANGE_RATE_CKZ'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exchange rate CKZ'),
      '#default_value' => $config->get('EXCHANGE_RATE_CKZ'),
    ];
    $form['EXCHANGE_RATE_USD'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exchange rate USD'),
      '#default_value' => $config->get('EXCHANGE_RATE_USD'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save configuration'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('extra_tokens.settings')
         ->set('BASE_CURRENCY', $form_state->getValue('BASE_CURRENCY'))
         ->set('EXCHANGE_RATE_UAH', $form_state->getValue('EXCHANGE_RATE_UAH'))
         ->set('EXCHANGE_RATE_GBR', $form_state->getValue('EXCHANGE_RATE_GBR'))
         ->set('EXCHANGE_RATE_EUR', $form_state->getValue('EXCHANGE_RATE_EUR'))
         ->set('EXCHANGE_RATE_CKZ', $form_state->getValue('EXCHANGE_RATE_CKZ'))
         ->set('EXCHANGE_RATE_USD', $form_state->getValue('EXCHANGE_RATE_USD'))
         ->save();
    drupal_set_message(t('The configuration options have been saved.'));
  }

}
