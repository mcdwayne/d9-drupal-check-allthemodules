<?php

namespace Drupal\kashing\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\kashing\Entity\KashingAPI;
use Drupal\kashing\Entity\KashingValid;
use Drupal\kashing\misc\countries\KashingCountries;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Kashing Form class.
 */
class KashingForm extends FormBase {

  private $formID = 'kashing_form';
  private $kashingCountries;
  private $formAmount;
  private $formDescription;
  private $kashingApi;

  /**
   * Class constructor.
   */
  public function __construct($kashingCountries) {
    $this->kashingCountries = $kashingCountries;
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return $this->formID;
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $argument
   *   Array.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $argument = []) {

    $this->formAmount = $argument['kashing_form_amount'];

    $this->formDescription = $argument['kashing_form_description'];

    $form['first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
      '#required' => TRUE,
    ];

    $form['last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#required' => TRUE,
    ];

    $form['address1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Address 1'),
      '#required' => TRUE,
    ];

    if ($argument['kashing_form_checkboxes']['address2'] === 'address2') {
      $form['address2'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Address 2'),
      ];
    }

    $form['city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City'),
      '#required' => TRUE,
    ];

    $form['country'] = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#options' => $this->kashingCountries->getAll(),
      '#empty_option' => $this->t('--Select a country--'),
      '#required' => TRUE,
    ];

    $form['postcode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Post Code'),
      '#required' => TRUE,
    ];

    if ($argument['kashing_form_checkboxes']['phone'] === 'phone') {
      $form['phone'] = [
        '#type' => 'tel',
        '#title' => $this->t('Phone'),
      ];
    }

    if ($argument['kashing_form_checkboxes']['email'] === 'email') {
      $form['email'] = [
        '#type' => 'email',
        '#title' => $this->t('Email'),
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Pay with Kashing'),
      '#description' => $this->t('Submit, #type = submit'),
    ];

    return $form;
  }

  /**
   * Validate form function.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $phone = $this->getValidFieldValue('phone', $form_state);
    $regex = '/^[0-9\-\(\)\/\+\s]*$/';
    if ($phone != '' && !preg_match($regex, trim($phone))) {
      $form_state->setErrorByName('phone', $this->t('Please enter a valid phone number.'));
    }

    $email = $this->getValidFieldValue('email', $form_state);
    $regex = '/^[^\s@]+@[^\s@]+\.[^\s@]+$/';
    if ($email != '' && !preg_match($regex, trim($email))) {
      $form_state->setErrorByName('email', $this->t('Please enter a valid email address.'));
    }

    $kashing_validate = new KashingValid();
    if (!isset($this->kashingApi)) {
      $this->kashingApi = new KashingAPI();
    }

    // Check if kashing API is succesfully created
    // TODO show only to admins?
    if ($this->kashingApi->hasErrors()) {

      if ($kashing_validate->isAdmin()) {

        $errors = $this->kashingApi->getErrors();

        foreach ($errors as $error) {
          $form_state->setErrorByName($error['field'], $error['msg']);
        }

      }
      else {
        $form_state->setErrorByName($this->t('error'),
          $this->t('Something went wrong. Please contact the site administrator.'));
      }

      $this->kashingApi = NULL;

    }
    else {
      // Kashing API seems all right.
    }
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

    $first_name = Html::escape($this->getValidFieldValue('first_name', $form_state));
    $last_name = Html::escape($this->getValidFieldValue('last_name', $form_state));
    $address1 = Html::escape($this->getValidFieldValue('address1', $form_state));
    $city = Html::escape($this->getValidFieldValue('city', $form_state));
    $postcode = Html::escape($this->getValidFieldValue('postcode', $form_state));
    $country = Html::escape($this->getValidFieldValue('country', $form_state));

    $transaction_data = [
      'amount' => $this->formAmount,
      'description' => $this->formDescription,
      'firstname' => $first_name,
      'lastname' => $last_name,
      'address1' => $address1,
      'city' => $city,
      'postcode' => $postcode,
      'country' => $country,
    ];

    $email = Html::escape($this->getValidFieldValue('email', $form_state));
    if (isset($email)) {
      $transaction_data['email'] = $email;
    }

    $phone = Html::escape($this->getValidFieldValue('phone', $form_state));
    if (isset($phone)) {
      $transaction_data['phone'] = $phone;
    }

    $address2 = Html::escape($this->getValidFieldValue('address2', $form_state));
    if (isset($address2)) {
      $transaction_data['address2'] = $address2;
    }

    // If kashing api is successfully created.
    if ($this->kashingApi) {

      // Successful API call with redirection.
      if ($this->kashingApi->process($transaction_data)) {

        $redirect_url = $this->kashingApi->getRedirectUrl();
        $form_state->setResponse(new TrustedRedirectResponse($redirect_url, 302));

      }
      // No redirect URL or Kashing API call error.
      else {
        $response_message = $this->kashingApi->getResponseMessage();

        // Error.
        if (isset($response_message)) {

          $url_parameters = '?kError=' . $response_message['error']
                                    . '&kResponse=' . $response_message['response_code']
                                    . '&kReason=' . $response_message['reason_code'];

          $base_url = Url::fromUri('internal:/')->setAbsolute()->toString();

          $redirect_url = $base_url . '/payment-failure/';

          $redirect_url .= $url_parameters;

          $form_state->setResponse(new TrustedRedirectResponse($redirect_url, 302));

        }
        // No response redirection URL.
        else {
          // $errors = $this->kashingApi->getErrors();
        }

      }

    }
    else {
      // Cant send POST reqest to kashing due to configuration errors.
    }

  }

  /**
   * Valid field value function.
   */
  private function getValidFieldValue($field_name, FormStateInterface $form_state) {
    return Html::escape($form_state->getValue($field_name));;
  }

  /**
   * Create function.
   */
  public static function create(ContainerInterface $container) {
    return new static(
       new KashingCountries()
    );
  }

}
