<?php

namespace Drupal\atm\Form;

use Drupal\atm\AtmHttpClient;
use Drupal\atm\Helper\AtmApiHelper;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\BaseCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Extension\ThemeHandler;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\ClientException;

/**
 * Provides form for register new customer and genearte atm.js file.
 */
class AtmGeneralConfigForm extends AtmAbstractForm {

  /**
   * AtmAbstractForm constructor.
   *
   * @param \Drupal\atm\Helper\AtmApiHelper $atmApiHelper
   *   Provides helper for ATM.
   * @param \Drupal\atm\AtmHttpClient $atmHttpClient
   *   Client for API.
   * @param \Drupal\Core\Extension\ThemeHandler $themeHandler
   *   Default theme handler.
   */
  public function __construct(AtmApiHelper $atmApiHelper, AtmHttpClient $atmHttpClient, ThemeHandler $themeHandler) {
    $this->atmApiHelper = $atmApiHelper;
    $this->atmHttpClient = $atmHttpClient;
    $this->themeHandler = $themeHandler;
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'atm-general-config';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = [];

    if ($this->getHelper()->getApiKey()) {

      try {
        $countries = $this->getHelper()->getSupportedCountries();

        foreach ($countries as $country) {
          $options[$country['ISO']] = $country['Name'];
        }
      }
      catch (ClientException $exception) {
        drupal_set_message(
          $exception->getMessage(), 'error'
        );
      }
    }

    $form['country'] = [
      '#type' => 'select',
      '#title' => t('Country'),
      '#options' => $options,
      '#default_value' => $this->getHelper()->getApiCountry(),
      '#description' => $this->t('Choose the country of origin where revenue will be collected'),
      '#required' => TRUE,
      '#ajax' => [
        'event' => 'change',
        'callback' => [$this, 'selectCountryCallback'],
      ],
    ];

    $form['revenue_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Revenue model'),
      '#options' => [],
      '#default_value' => $this->getHelper()->get('revenue_method'),
      '#description' => $this->t('Choose the revenue model that will be used on this blog'),
    ];

    foreach ($this->getHelper()->getRevenueModelList() as $value => $name) {
      $form['revenue_method']['#options'][$value] = $name;
    }

    $form['save-config'] = [
      '#type' => 'button',
      '#value' => $this->t('Save'),
      '#ajax' => [
        'event' => 'click',
        'callback' => [$this, 'saveParams'],
      ],
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function saveParams(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $this->getHelper()->setApiCountry($form_state->getValue('country'));
    $this->getHelper()->set('revenue_method', $form_state->getValue('revenue_method'));

    $this->getAtmHttpClient()->propertyCreate();

    $errors = drupal_get_messages('error');
    if ($errors) {
      $errors = $errors['error'];
    }

    $errors = array_merge($form_state->getErrors(), $errors);

    if ($errors) {
      $response->addCommand(
        new BaseCommand('showNoty', [
          'options' => [
            'type' => 'error',
            'text' => implode("<br>", $errors),
            'maxVisible' => 1,
            'timeout' => 5000,
          ],
        ])
      );

      $form_state->clearErrors();
    }
    else {
      $response->addCommand(
        new BaseCommand('showNoty', [
          'options' => [
            'type' => 'information',
            'text' => $this->t('Form data saved successfully'),
            'maxVisible' => 1,
            'timeout' => 2000,
          ],
        ])
      );
    }

    return $response;
  }

  /**
   * Callback for country select.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   ajax response.
   */
  public function selectCountryCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $response->setAttachments($form['#attached']);

    $country = $form_state->getValue('country');
    if (empty($country)) {
      $response->addCommand(
        new OpenModalDialogCommand(
          '', $this->getErrorMessage($this->t('Please, select country')), $this->getModalDialogOptions()
        )
      );
      return $response;
    }

    $this->getHelper()->setApiCountry($country);

    $response->addCommand(
      new InvokeCommand('#edit-price-currency', 'setOptions', [$this->getHelper()->getCurrencyList()])
    );

    $response->addCommand(
      new InvokeCommand('#edit-revenue-method', 'setOptions', [$this->getHelper()->getRevenueModelList()])
    );

    return $response;
  }

}
