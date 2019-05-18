<?php

namespace Drupal\currencylayer_currency_converter\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\currencylayer_currency_converter\CurrencylayerCurrencyConverterManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\currencylayer_currency_converter\Form\CurrencylayerCurrencyConverterForm;

/**
 * Provides a configurable block with Currencylayer currency converter Plugin.
 *
 * @Block(
 *  id = "currencylayer_currency_converter_block",
 *  admin_label = @Translation("Currencylayer currency converter Block"),
 * )
 */
class CurrencylayerCurrencyConverterBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Currencylayer currency converter.
   *
   * @var \Drupal\currencylayer_currency_converter\CurrencylayerCurrencyConverterManagerInterface
   */
  protected $currencylayerCurrencyConverter;


  /**
   * The Form Builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new CurrencylayerCurrencyConverterBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\currencylayer_currency_converter\CurrencylayerCurrencyConverterManagerInterface $currencylayer_currency_converter
   *   The Currencylayer currency converter Manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The Form Builder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrencylayerCurrencyConverterManagerInterface $currencylayer_currency_converter, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currencylayerCurrencyConverter = $currencylayer_currency_converter;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('currencylayer_currency_converter.manager'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $config = $this->getConfiguration();

    $form['currencylayer_currency_converter_from'] = array(
      '#type' => 'select',
      '#title' => $this->t('Select Default Currency From'),
      '#options' => $this->currencylayerCurrencyConverter->countries(),
      '#default_value' => $config['currencylayer_currency_converter_from'],
      '#required' => TRUE,
    );
    $form['currencylayer_currency_converter_to'] = array(
      '#type' => 'select',
      '#title' => $this->t('Select Default Currency To'),
      '#options' => $this->currencylayerCurrencyConverter->countries(),
      '#default_value' => $config['currencylayer_currency_converter_to'],
      '#required' => TRUE,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    if ($form_state->getValue('currencylayer_currency_converter_from') === $form_state->getValue('currencylayer_currency_converter_to')) {
      $form_state->setErrorByName('currencylayer_currency_converter_to', $this->t('Please select different currency both currency are same.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('currencylayer_currency_converter_from', $form_state->getValue('currencylayer_currency_converter_from'));
    $this->setConfigurationValue('currencylayer_currency_converter_to', $form_state->getValue('currencylayer_currency_converter_to'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $config = $this->getConfiguration();
    $form = $this->formBuilder->getForm(CurrencylayerCurrencyConverterForm::class);
    $form['currencylayer_currency_converter_from']['#default_value'] = $config['currencylayer_currency_converter_from'];
    $form['currencylayer_currency_converter_to']['#default_value'] = $config['currencylayer_currency_converter_to'];
    $render['block'] = $form;
    $render['block']['#attached']['library'] = ['currencylayer_currency_converter/drupal.currencylayer_currency_converter'];
    return $render;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'currencylayer_currency_converter_from' => '',
      'currencylayer_currency_converter_to' => '',
    ];
  }

}
