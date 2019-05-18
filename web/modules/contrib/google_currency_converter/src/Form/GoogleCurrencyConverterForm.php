<?php

namespace Drupal\google_currency_converter\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\google_currency_converter\GoogleCurrencyConverterManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Google Currency Converter form.
 */
class GoogleCurrencyConverterForm extends FormBase {

  /**
   * The Google currency Converter.
   *
   * @var \Drupal\google_currency_converter\GoogleCurrencyConverterManagerInterface
   */
  protected $googleCurrencyConveter;

  /**
   * Constructs a new GoogleCurrencyConverterForm.
   *
   * @param \Drupal\google_currency_converter\GoogleCurrencyConverterManagerInterface $google_currency_converter
   *   The Google Currency Converter Manager.
   */
  public function __construct(GoogleCurrencyConverterManagerInterface $google_currency_converter) {
    $this->googleCurrencyConveter = $google_currency_converter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('google_currency_converter.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_currency_converter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['google_currency_converter_from'] = array(
      '#type' => 'select',
      '#title' => t('Select Your Currency From'),
      '#options' => $this->googleCurrencyConveter->countries(),
      '#default_value' => '',
      '#attributes' => array('class' => array('gcc-select-list')),
      '#required' => TRUE,
    );
    $form['google_currency_converter_to'] = array(
      '#type' => 'select',
      '#title' => t('Select Your Currency To'),
      '#options' => $this->googleCurrencyConveter->countries(),
      '#default_value' => '',
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
    if ($form_state->getValue('google_currency_converter_from') === $form_state->getValue('google_currency_converter_to')) {
      $form_state->setErrorByName('google_currency_converter_to', $this->t('Please select different currency both currency are same.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $from = $form_state->getValue('google_currency_converter_from');
    $to = $form_state->getValue('google_currency_converter_to');
    $amount = $form_state->getValue('amount');
    $result = $this->googleCurrencyConveter->convertAmount($amount, $from, $to);

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
