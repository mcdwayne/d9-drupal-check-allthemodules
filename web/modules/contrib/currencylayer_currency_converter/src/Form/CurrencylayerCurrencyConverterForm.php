<?php

namespace Drupal\currencylayer_currency_converter\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\currencylayer_currency_converter\CurrencylayerCurrencyConverterManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Currencylayer currency converter form.
 */
class CurrencylayerCurrencyConverterForm extends FormBase {

  /**
   * The Currencylayer currency converter.
   *
   * @var \Drupal\currencylayer_currency_converter\CurrencylayerCurrencyConverterManagerInterface
   */
  protected $currencylayerCurrencyConverter;

  /**
   * Constructs a new CurrencylayerCurrencyConverterForm.
   *
   * @param \Drupal\currencylayer_currency_converter\CurrencylayerCurrencyConverterManagerInterface $currencylayer_currency_converter
   *   The Currencylayer currency converter Manager.
   */
  public function __construct(CurrencylayerCurrencyConverterManagerInterface $currencylayer_currency_converter) {
    $this->currencylayerCurrencyConverter = $currencylayer_currency_converter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('currencylayer_currency_converter.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'currencylayer_currency_converter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $block = \Drupal\block\Entity\Block::load('currencylayercurrencyconverterblock');
    $config = $block->get('settings');

    $form['currencylayer_currency_converter_from'] = array(
      '#type' => 'select',
      '#title' => t('Select Your Currency From'),
      '#options' => $this->currencylayerCurrencyConverter->countries(),
      '#default_value' => isset($config['currencylayer_currency_converter_from'])? $config['currencylayer_currency_converter_from'] : '',
      '#attributes' => array('class' => array('gcc-select-list')),
      '#required' => TRUE,
    );
    $form['currencylayer_currency_converter_to'] = array(
      '#type' => 'select',
      '#title' => t('Select Your Currency To'),
      '#options' => $this->currencylayerCurrencyConverter->countries(),
      '#default_value' => isset($config['currencylayer_currency_converter_to'])? $config['currencylayer_currency_converter_to'] : '',
      '#attributes' => array('class' => array('gcc-select-list')),
      '#required' => TRUE,
    );
    $form['amount'] = array(
      '#type' => 'number',
      '#title' => $this->t('Your Amount'),
      '#min' => 0,
      '#required' => TRUE,
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Convert'),
    );
    $form['#validate'][] = '::validateCurency';
    return $form;
  }

  /**
   * Checks from currency is not equal to converted currency.
   */
  public function validateCurency(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('currencylayer_currency_converter_from') === $form_state->getValue('currencylayer_currency_converter_to')) {
      $form_state->setErrorByName('currencylayer_currency_converter_to', $this->t('Please select different currency both currency are same.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $from = $form_state->getValue('currencylayer_currency_converter_from');
    $to = $form_state->getValue('currencylayer_currency_converter_to');
    $amount = $form_state->getValue('amount');
    $result = $this->currencylayerCurrencyConverter->convertAmount($amount, $from, $to);

    $arguments = array(
      '@value_from' => $from,
      '@value_to' => $to,
      '@value_amount' => $amount,
      '@result' => $result,
    );
    $output = $this->t('Your selected value is from @value_from to @value_to amount is @value_amount @value_from &amp; your converted value is @result @value_to', $arguments);
    drupal_set_message($output);
  }

}
