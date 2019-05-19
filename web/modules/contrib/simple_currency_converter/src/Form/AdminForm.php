<?php

/**
 * @file
 * Contains \Drupal\simple_currency_converter\Form\AdminForm.
 */

namespace Drupal\simple_currency_converter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AdminForm extends ConfigFormBase {

  var $configFactory;

  var $config;

  var $state;

  var $currency_converters;

  var $default_currency_converter;

  /**
   * Constructs a new form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state keyvalue collection to use.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state, $currency_converters, $default_currency_converter) {
    parent::__construct($config_factory);

    $this->configFactory = $config_factory;
    $this->config = $config_factory->getEditable('simple_currency_converter.settings');
    $this->state = $state;
    $this->currency_converters = $currency_converters;
    $this->default_currency_converter = $default_currency_converter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state'),
      $container->getParameter('simple_currency_converters'),
      $container->getParameter('simple_currency_converter_default')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_currency_converter_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['simple_currency_converter.settings'];
  }

  /**
   * @inheritdoc
   */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    module_load_include('inc', 'simple_currency_converter', 'simple_currency_converter.currency');

    $currency_converters = $this->currency_converters;
    $total = count($currency_converters);

    $form['currency_converters'] = [
      '#type' => 'fieldset',
      '#title' => t('Available Currency Converters'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $fieldset = &$form['currency_converters'];

    if (!$total) {
      $fieldset['#description'] = $this->t('Please enable at least one currency converter provider module.');
    }
    else {
      $options = [
        '' => $this->t('None'),
      ];

      foreach ($currency_converters as $key => $value) {
        $options[$key] = $value['title'];
      }

      $key = 'feed_primary';

      $default_value = $this->config->get($key);

      $fieldset[$key] = [
        '#type' => 'select',
        '#title' => $this->t('Choose the primary currency converter to use'),
        '#options' => $options,
        '#default_value' => $default_value,
      ];

      if ($total > 1) {
        $key = 'feed_secondary';

        $default_value = $this->config->get($key);

        $fieldset[$key] = [
          '#type' => 'select',
          '#title' => $this->t('Choose the secondary currency converter to use'),
          '#options' => $options,
          '#default_value' => $default_value,
        ];
      }

      $codes = simple_currency_converter_supply_country_info();

      foreach ($currency_converters as $key => $value) {
        $service = \Drupal::service($key);

        $fieldset[$key] = [
          '#type' => 'fieldset',
          '#title' => $value['title'],
          '#collapsible' => TRUE,
          '#collapsed' => TRUE,
        ];

        $currencies = $service->currencies();

        $markup = '';

        foreach ($currencies as $currency) {
          $markup .= '<li>' . $currency . ' => ' . $codes[$currency]['name'] . '</li>';
        }

        $markup = t('The following currencies are provided:') . '<ul>' . $markup . '</ul>';

        $fieldset[$key]['markup']['#markup'] = $markup;
      }
    }

    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => t('Configuration variables'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $fieldset = &$form['settings'];

    $options = simple_currency_converter_supply_country_codes_options();
    $options = array_merge(['none' => t('-- None --')], $options);

    $key = 'default_conversion_currency';

    $default_value = $this->config->get($key);

    $description = $this->t('Please choose a default currency to use for the conversion.  Note: This is the currency that is initially used on the page.');

    $fieldset[$key] = [
      '#type' => 'select',
      '#title' => $this->t('Default conversion currency'),
      '#options' => $options,
      '#default_value' => $default_value,
      '#description' => $description,
    ];

    $key = 'cookie_expiration';

    $default_value = $this->config->get($key);

    $description = $this->t('How many seconds should the currency conversion value last for, before refreshing?');

    $fieldset[$key] = [
      '#type' => 'textfield',
      '#title' => $this->t('Currency Rate Cookie Expiration'),
      '#default_value' => $default_value,
      '#description' => $description,
    ];

    $key = 'window_trigger';

    $default_value = $this->config->get($key);

    $description = $this->t('Please specify the CSS selector to use to make the converter modal window scroll down and up.');

    $fieldset[$key] = [
      '#type' => 'textfield',
      '#title' => $this->t('Modal Window Activation Selector'),
      '#default_value' => $default_value,
      '#description' => $description,
    ];

    $key = 'window_id';

    $default_value = $this->config->get($key);

    $description = $this->t('Please specify a CSS ID to use for placement of the converter dropdown, where users can choose a currency');

    $fieldset[$key] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form holder element ID'),
      '#default_value' => $default_value,
      '#description' => $description,
    ];

    $key = 'window_title';

    $default_value = $this->config->get($key);

    $description = $this->t('Clicking on element with this ID will close the form');

    $fieldset[$key] = [
      '#type' => 'textfield',
      '#title' => $this->t('Modal Window Title'),
      '#default_value' => $default_value,
      '#description' => $description,
    ];

    $key = 'conversion_selector';

    $default_value = $this->config->get($key);

    $description = $this->t('Please specify the CSS selector for the currency to convert.');

    $fieldset[$key] = [
      '#type' => 'textfield',
      '#title' => $this->t('Price elements to convert'),
      '#default_value' => $default_value,
      '#description' => $description,
    ];

    $key = 'disclaimer';

    $default_value = $this->config->get($key);

    $description = $this->t('Please specify the disclaimer text to display in the modal window.');

    $fieldset[$key] = [
      '#type' => 'textarea',
      '#title' => $this->t('Disclaimer'),
      '#default_value' => $default_value,
      '#description' => $description,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $keys = [
      'feed_primary',
      'feed_secondary',
      'window_id',
      'window_trigger',
      'window_title',
      'conversion_selector',
      'disclaimer',
      'default_conversion_currency',
      'cookie_expiration',
    ];

    foreach ($keys as $key) {
      $this->config->set($key, $form_state->getValue($key));
    }

    $this->config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

}
