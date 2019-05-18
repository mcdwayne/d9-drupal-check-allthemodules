<?php

namespace Drupal\advance_currency_converter\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\AfterCommand;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\advance_currency_converter\Controller\CurrencyNameFetch;

/**
 * Class Front View.
 *
 * @package Drupal\advance_currency_converter\Form
 */
class FrontPanel extends FormBase implements ContainerInjectionInterface {

  protected $config = NULL;
  protected $currName = NULL;

  /**
   * It will provide you config and service class object.
   *
   * @param Drupal\Core\Config\ConfigFactoryInterface $config
   *
   *   This is the config factory object.
   * @param Drupal\advance_currency_converter\Controller\CurrencyNameFetch $currName
   *
   *   This will provide you an object of the service class.
   */
  public function __construct(ConfigFactoryInterface $config, CurrencyNameFetch $currName) {
    $this->config = $config;
    $this->currName = $currName;
  }

  /**
   * This will help you to achieve the Dependency Injection.
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   *   This will help you to create config and service class object.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('config.factory'), $container->get('currency.fetch_data')
    );
  }

  /**
   * Building a form.
   *
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // This will fetch only those country currency name
    // which are selected by you in /admin/config/system/currency.
    $options = $this->currName->getCheck();
    $default_options = $this->currName->getInfo();

    $form['amount'] = [
      '#type' => 'number',
      '#size' => 40,
      '#title' => $this->t('Enter Amount'),
      '#attributes' => ['id' => 'ConversionAmount'],
      '#required' => TRUE,
      '#default_value' => 0,
    ];
    $form['from'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Your Currency From'),
      '#options' => !empty($options) ? $options : $default_options,
    ];
    $form['to'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Your Currency To'),
      '#options' => !empty($options) ? $options : $default_options,
      '#attributes' => ['id' => 'currency_from'],
    ];
    $form['submission'] = [
      '#type' => 'submit',
      '#value' => 'Convert',
      '#ajax' => [
        'callback' => '::gettingData',
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
          'message' => 'Converting wait',
        ],
      ],
    ];
    $form['result'] = [
      '#type' => 'container',
      '#prefix' => '<div id="result"></div>',
    ];

    $form['graph'] = [
      '#type' => 'container',
      '#prefix' => '<div id="graphResult"></div>',
    ];
    $form['#attached']['library'][] = 'advance_currency_converter/currency-check';

    return $form;
  }

  /**
   * It will get the data.
   *
   * {@inheritDoc}
   */
  public function gettingData(array &$form, $form_state) {
    $response = new AjaxResponse();
    // It will fetch all the currency code and name.
    $options = $this->currName->getInfo();
    // Fetching the data from the block placed area.
    $amount = $form_state->getValue('amount');
    $to = $form_state->getValue('to');
    $from = $form_state->getValue('from');
    // Condition will check whether the number must be greater than 0
    // and response accordingly.
    if ($amount > 0) {
      if ($to != NULL) {
        // In this condition will check whether both currency are equals or not.
        if ($to != $from) {
          // It will compute the result and return and save it to $res variable.
          $res = $this->currName->currencyApi($from, $to, $amount);
          if ($this->config->get('currency.converter')->get('selection') != 'Select Currency API') {
            $result = $options[$to] . " is equals to " . $res . ".";
            $response->addCommand(new HtmlCommand('#result', $result));
            $response->addCommand(new RemoveCommand('#graphResult > div'));
            $response->addCommand(new RemoveCommand('#genrateGraph'));
            // If the selected API is equals to Data Offline Handling
            // so then the graph will appear else it
            // will not create.
            if ($this->config->get('currency.converter')->get('selection') == 'Data Offline Handling' && $this->config->get('currency.converter')->get('graph') == 1) {
              $response->addCommand(new AfterCommand('#result', '<svg id="genrateGraph" height=250px width=100%></svg>'));
              $response->addCommand(new AppendCommand('#graphResult', $this->currName->createGraph($from, $to)));
            }
          }
          else {
            $response->addCommand(new HtmlCommand('#result', $res));
          }
        }
        else {
          $response->addCommand(new RemoveCommand('#graphResult > div'));
          $response->addCommand(new RemoveCommand('#genrateGraph'));
          $response->addCommand(new HtmlCommand('#result', 'Please select different currency, both currency are same.'));
        }
      }
      else {
        $response->addCommand(new HtmlCommand('#result', 'Please select the currencies from /admin/config/system/currency'));
      }
    }
    else {
      $response->addCommand(new RemoveCommand('#graphResult > div'));
      $response->addCommand(new RemoveCommand('#genrateGraph'));
      $response->addCommand(new HtmlCommand('#result', 'The amount should be greater than zero.'));
    }

    return $response;
  }

  /**
   * Get form id.
   *
   * {@inheritDoc}
   */
  public function getFormId() {
    return "frontpanel";
  }

  /**
   * Submit Form.
   *
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
