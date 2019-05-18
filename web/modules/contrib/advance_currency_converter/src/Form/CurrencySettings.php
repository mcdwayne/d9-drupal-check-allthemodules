<?php

namespace Drupal\advance_currency_converter\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\advance_currency_converter\Controller\CurrencyNameFetch;
use Drupal\Core\Form\drupal_set_message;
use Drupal\Core\Cache\CacheTagsInvalidator;

/**
 * Class Currency Settings.
 *
 * @category class
 */
class CurrencySettings extends ConfigFormBase implements ContainerInjectionInterface {

  protected $currName = NULL;
  protected $cacheTag = NULL;

  /**
   * Constructor with setting the currency name class object.
   *
   * @param Drupal\advance_currency_converter\Controller\CurrencyNameFetch $currName
   *   It will provide you a Service class Object.
   * @param Drupal\Core\Cache\CacheTagsInvalidator $cacheTag
   *   It will provide you to clear a cache.
   */
  public function __construct(CurrencyNameFetch $currName, CacheTagsInvalidator $cacheTag) {
    $this->currName = $currName;
    $this->cacheTag = $cacheTag;
  }

  /**
   * It will help us achieve the Dependency Injection.
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   *   It will provide you a Service class object.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('currency.fetch_data'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * Get Editable Config name.
   *
   * {@inheritDoc}
   */
  protected function getEditableConfigNames() {
    return ['currency.converter'];
  }

  /**
   * Get form id.
   *
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'currency_converter';
  }

  /**
   * Building a form.
   *
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $options = $this->currName->getInfo();
    $form = parent::buildForm($form, $form_state);
    $form['Currency_Converter_API'] = [
      '#type' => 'details',
      '#title' => $this->t('Currency Converter API'),
      '#open' => TRUE,
    ];
    $form['Currency_Converter_API']['status'] = [
      '#type' => 'label',
      '#title' => $this->t('If you have selected the "Data Offline Handling" then <a href=":cron">cron maintenance task</a> is required.', [':cron' => '/admin/config/system/cron']),
    ];
    $form['Currency_Converter_API']['selection'] = [
      '#type' => 'select',
      '#options' => [
        'Google Currency Converter API' => 'Google Currency Converter API',
        'Data Offline Handling' => 'Data Offline Handling',
      ],
      '#default_value' => $this->config('currency.converter')->get('selection') ? $this->config('currency.converter')->get('selection') : 'Google Currency Converter API',
      '#weight' => -30,
    ];
    $form['Currency_Converter_API']['graph'] = [
      '#type' => 'checkbox',
      '#title' => 'Do you want to show the graph?' . "<br>" . 'The graph will render'
      . ' when the Data Offline Handling mode selected.',
      '#default_value' => $this->config('currency.converter')->get('graph') ? $this->config('currency.converter')->get('graph') : '',
    ];
    $form['Currency_Converter_API']['days'] = [
      '#type' => 'number',
      '#title' => $this->t('Please enter the number of days, you want to save currencies data into table.'),
      '#default_value' => !empty($this->config('currency.converter')->get('days')) ? $this->config('currency.converter')->get('days') : 5,
    ];
    $form['Selection'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select Currency you want to display:'),
      '#options' => $options,
      '#default_value' => $this->config('currency.converter')->get('selecti') ? $this->config('currency.converter')->get('selecti') : [],
    ];
    $form['#attached']['library'][] = 'advance_currency_converter/currency-check';

    return $form;
  }

  /**
   * Submitting a form.
   *
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Saving the data into the config table.
    $this->cacheTag->invalidateTags(['advance_currency_converter:currency']);
    $select_currency = $form_state->getValue('Selection');
    $count = 0;
    foreach ($select_currency as $value) {
      if ($value === 0) {
        break;
      }
      else {
        $count += 1;
      }
    }
    if ($count > 1 || $count == 0) {
      $days = (int) $form_state->getValue('days');
      $this->config('currency.converter')->set('graph', $form_state->getValue('graph'))->save();
      $this->config('currency.converter')->set('selection', $form_state->getValue('selection'))->save();
      $this->config('currency.converter')->set('selecti', $form_state->getValue('Selection'))->save();
      if ($days > 0) {
        $this->config('currency.converter')->set('days', $days)->save();
      }
      else {
        drupal_set_message($this->t("Number of days should be greater than zero"), 'error');
      }
      parent::submitForm($form, $form_state);
    }
    else {
      drupal_set_message($this->t("Please select minimum two currencies."), 'error');
    }

  }

}
