<?php

namespace Drupal\google_currency_converter\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\google_currency_converter\GoogleCurrencyConverterManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\google_currency_converter\Form\GoogleCurrencyConverterForm;

/**
 * Provides a configurable block with Google Currency Converter Plugin.
 *
 * @Block(
 *  id = "google_currency_converter_block",
 *  admin_label = @Translation("Google Currency Converter Block"),
 * )
 */
class GoogleCurrencyConverterBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Google currency Converter.
   *
   * @var \Drupal\google_currency_converter\GoogleCurrencyConverterManagerInterface
   */
  protected $googleCurrencyConveter;


  /**
   * The Form Builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new GoogleCurrencyConverterBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\google_currency_converter\GoogleCurrencyConverterManagerInterface $google_currency_converter
   *   The Google Currency Converter Manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The Form Builder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, GoogleCurrencyConverterManagerInterface $google_currency_converter, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->googleCurrencyConveter = $google_currency_converter;
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
      $container->get('google_currency_converter.manager'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $config = $this->getConfiguration();

    $form['google_currency_converter_from'] = array(
      '#type' => 'select',
      '#title' => $this->t('Select Default Currency From'),
      '#options' => $this->googleCurrencyConveter->countries(),
      '#default_value' => $config['google_currency_converter_from'],
      '#required' => TRUE,
    );
    $form['google_currency_converter_to'] = array(
      '#type' => 'select',
      '#title' => $this->t('Select Default Currency To'),
      '#options' => $this->googleCurrencyConveter->countries(),
      '#default_value' => $config['google_currency_converter_to'],
      '#required' => TRUE,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    if ($form_state->getValue('google_currency_converter_from') === $form_state->getValue('google_currency_converter_to')) {
      $form_state->setErrorByName('google_currency_converter_to', $this->t('Please select different currency both currency are same.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('google_currency_converter_from', $form_state->getValue('google_currency_converter_from'));
    $this->setConfigurationValue('google_currency_converter_to', $form_state->getValue('google_currency_converter_to'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $config = $this->getConfiguration();
    $form = $this->formBuilder->getForm(GoogleCurrencyConverterForm::class);
    $form['google_currency_converter_from']['#default_value'] = $config['google_currency_converter_from'];
    $form['google_currency_converter_to']['#default_value'] = $config['google_currency_converter_to'];
    $render['block'] = $form;
    $render['block']['#attached']['library'] = ['google_currency_converter/drupal.google_currency_converter'];
    return $render;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'google_currency_converter_from' => '',
      'google_currency_converter_to' => '',
    ];
  }

}
