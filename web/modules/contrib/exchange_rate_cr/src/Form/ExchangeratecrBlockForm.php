<?php

namespace Drupal\exchange_rate_cr\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Class ExchangeratecrBlockForm.
 *
 * @package Drupal\exchange_rate_cr\Form
 */
class ExchangeratecrBlockForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'exchange_rate_cr_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $startDate = date("j/n/Y");
    $endDate = date("j/n/Y");
    $name = "exchange_rate_cr";
    $sublevels = "N";

    $serviceDataBCCR = \Drupal::service('exchange_rate_cr.data_bccr_service');

    $dataBuyRate = $serviceDataBCCR->getDataFromBancoCentralCR('317', $startDate, $endDate, $name, $sublevels);
    $dataSellRate = $serviceDataBCCR->getDataFromBancoCentralCR('318', $startDate, $endDate, $name, $sublevels);

    if ($dataBuyRate['successful'] && $dataSellRate['successful']) {

      $buyRate = $dataBuyRate['value'];
      $sellRate = $dataSellRate['value'];
      $messageError = $dataBuyRate['message'];

      /*Just to know what is the dollar buy rate
      according to Banco Central de Costa RIca*/
      $form['buy_rate'] = [
        '#markup' => "<p >" . $this->t('Buy Rate:') . $buyRate . "</p>",
      ];

      /*Just to know what is the dollar sell rate
      according to Banco Central de Costa RIca*/
      $form['sell_rate'] = [
        '#markup' => "<p >" . $this->t('Sell Rate:') . $sellRate . "</p>",
      ];

      if (trim($messageError)) {

        // Error Message.
        $form['information_error_text'] = [
          '#markup' => "<p >" . $text = t('@message', ['@message' => $dataBuyRate['message']]) . "</p>",
        ];
      }

      // Information Text about the data.
      $form['information_text'] = [
        '#markup' => "<p >" . $this->t('Source: Banco Central de Costa Rica on:') . $dataBuyRate['date'] . "</p>",
      ];

      // Amount to Convert.
      $form['amount'] = [
        '#type' => 'number',
        '#title' => $this->t('Amount'),
        // No validation.
        '#limit_validation_errors' => [],
      ];

      // Options for select: Dollars, Colons.
      $options = [
        'CRC' => '₡ CRC',
        'USD' => '$ USD',
      ];

      // Currency I want to convert.
      $form['currency_from'] = [
        '#type' => 'select',
        '#title' => $this->t('From'),
        '#options' => $options,
        '#default_value' => 'USD',
        '#prefix' => "<div class ='wrapper_select_currency_from'>",
        '#suffix' => '</div>',
        '#ajax' => [
          'callback' => 'Drupal\exchange_rate_cr\Form\ExchangeratecrBlockForm::onSelectFromChange',
          'wrapper' => 'edit-currency',
        ],
      ];

      // Currency to I want to convert.
      $form['currency_to'] = [
        '#type' => 'select',
        '#title' => $this->t('To'),
        '#options' => $options,
        '#default_value' => 'CRC',
        '#prefix' => "<div class ='wrapper_select_currency_to'>",
        '#suffix' => '</div>',
        '#ajax' => [
          'callback' => 'Drupal\exchange_rate_cr\Form\ExchangeratecrBlockForm::onSelectToChange',
          'wrapper' => 'edit-currency',
        ],
      ];

      // Input to show the conversion result.
      $form['total'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Result'),
        '#placeholder' => $this->t('Total converted'),
        '#size' => '35',
        '#attributes' => ['readonly' => 'readonly'],
      ];

      $form['actions'] = [
        '#type' => 'submit',
        '#value' => $this->t('Convert'),
        '#ajax' => [
          'callback' => 'Drupal\exchange_rate_cr\Form\ExchangeratecrBlockForm::convertCurrecyCallback',
          'wrapper' => 'edit-currency',
        ],
      ];
    }
    else {
      $form['information_text'] = [
        '#markup' => "<p >" . t('@message', ['@message' => $dataBuyRate['message']]) . "</p>",
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * This method use the service to do the conversion.
   *
   * @param array $form
   *   Drupal form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax Response.
   */
  public function convertCurrecyCallback(array &$form, FormStateInterface $form_state) {

    // Amount to convert.
    $amount = $form_state->getValue('amount');

    // Ajax Response.
    $response = new AjaxResponse();

    // Placeholder text.
    $placeHolderText = t('Total converted');

    if ($amount > 0) {

      // Currency from I want to convert.
      $currency_from = $form_state->getValue('currency_from');

      // Service to use the function convertCurrency.
      $serviceDataBCCR = \Drupal::service('exchange_rate_cr.data_bccr_service');

      // Converting result.
      $result = number_format($serviceDataBCCR->convertCurrecy($currency_from, $amount), 2, '.', ' ');

      // Sign of the result.
      $sign = '';
      switch ($currency_from) {
        case 'CRC':
          $sign = '$ ';
          break;

        case 'USD':
          $sign = '₡ ';
          break;

        default;
          break;
      }
      // Set the values.
      $response->addCommand(new ReplaceCommand(
        '#edit-total',
        '<input data-drupal-selector="edit-total" disabled="disabled" type="text" id="edit-total" name="total" value="' . $sign . '' . $result . '" size="35" maxlength="128" placeholder="' . $placeHolderText . '" class="form-text">'));
      $response->addCommand(new ReplaceCommand(
        '#edit-total--description',
        '<div id="edit-total--description" class="description"></div>'));

    }
    else {
      // Set the values.
      $message = t('The Amount should be greater than 0');
      $response->addCommand(new ReplaceCommand(
        '#edit-total',
        '<input data-drupal-selector="edit-total" disabled="disabled" type="text" id="edit-total" name="total" value="' . $message . '" size="35" maxlength="128" placeholder="' . $placeHolderText . '" class="form-text">'));
    }

    return $response;
  }

  /**
   * Always option from currency should be different than option to currency.
   *
   * @param array $form
   *   Drupal Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax Response.
   */
  public function onSelectFromChange(array &$form, FormStateInterface $form_state) {

    // Ajax Response.
    $response = new AjaxResponse();

    // Placeholder text.
    $placeHolderText = t('Total converted');

    // Currency from.
    $currency_from = $form_state->getValue('currency_from');

    // Currency to.
    $currency_to = $form_state->getValue('currency_to');

    if ($currency_from == $currency_to) {

      switch ($currency_from) {
        case 'CRC':
          $form['currency_to']['#value'] = 'USD';
          break;

        case 'USD':
          $form['currency_to']['#value'] = 'CRC';
          break;

        default:
          break;
      }
      $response->addCommand(new ReplaceCommand(
        '#edit-total',
        '<input data-drupal-selector="edit-total" disabled="disabled" type="text" id="edit-total" name="total" value="" size="35" maxlength="128" placeholder="' . $placeHolderText . '" class="form-text">'));
      $response->addCommand(new ReplaceCommand(
        '.wrapper_select_currency_to',
        $form['currency_to']
      ));
    }
    return $response;
  }

  /**
   * Always option to currency should be different than option from currency.
   *
   * @param array $form
   *   Drupal Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax Response.
   */
  public function onSelectToChange(array &$form, FormStateInterface $form_state) {

    // Ajax Response.
    $response = new AjaxResponse();

    // Placeholder text.
    $placeHolderText = t('Total converted');

    // Currency to.
    $currency_to = $form_state->getValue('currency_to');

    // Currency from.
    $currency_from = $form_state->getValue('currency_from');

    if ($currency_to == $currency_from) {

      switch ($currency_to) {
        case 'CRC':
          $form['currency_from']['#value'] = 'USD';
          break;

        case 'USD':
          $form['currency_from']['#value'] = 'CRC';
          break;

        default:
          break;
      }
      $response->addCommand(new ReplaceCommand(
        '#edit-total',
        '<input data-drupal-selector="edit-total" disabled="disabled" type="text" id="edit-total" name="total" value="" size="35" maxlength="128" placeholder="' . $placeHolderText . '" class="form-text">'));
      $response->addCommand(new ReplaceCommand(
        '.wrapper_select_currency_from',
        $form['currency_from']
      ));
    }
    return $response;
  }

}
