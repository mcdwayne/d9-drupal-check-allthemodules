<?php

namespace Drupal\paypal_donation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\Url;
use Drupal\facets\Exception\Exception;
use Drupal\paypal_donation\Configuration;
use Drupal\user\PrivateTempStoreFactory;
use PayPal\CoreComponentTypes\BasicAmountType;
use PayPal\EBLBaseComponents\BillingAgreementDetailsType;
use PayPal\EBLBaseComponents\PaymentDetailsItemType;
use PayPal\EBLBaseComponents\PaymentDetailsType;
use PayPal\EBLBaseComponents\SetExpressCheckoutRequestDetailsType;
use PayPal\PayPalAPI\SetExpressCheckoutReq;
use PayPal\PayPalAPI\SetExpressCheckoutRequestType;
use PayPal\Service\PayPalAPIInterfaceServiceService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DonateForm.
 *
 * @package Drupal\paypal_donation\Form
 */
class DonateForm extends FormBase {
  protected $tempStore;
  protected $sessionManager;
  protected $currentUser;

  /**
   * DonateForm constructor.
   *
   * @param \Drupal\paypal_donation\Form\PrivateTempStoreFactory $temp_store_factory
   *   User's temp store.
   * @param \Drupal\paypal_donation\Form\SessionManagerInterface $session_manager
   *   User's session manager.
   * @param \Drupal\paypal_donation\Form\AccountInterface $current_user
   *   Currently logged in user.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, SessionManagerInterface $session_manager, AccountInterface $current_user) {
    $this->tempStore = $temp_store_factory->get('paypal_donation');
    $this->sessionManager = $session_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('session_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'donate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('paypal_donation.settings');

    if ($config->get('welcome_text')) {
      $form['welcome_text'] = [
        '#type' => 'markup',
        '#markup' => $config->get('welcome_text'),
      ];
    }
    if ($amounts = $config->get('amounts')) {
      $amounts = array_map("trim", explode(",", $amounts));
      $amounts = array_combine($amounts, $amounts);
      $form['amount'] = [
        '#type' => 'radios',
        '#title' => $this->t('Donation amount') . ' (' . $config->get('currency_code') . ')',
        '#description' => $this->t('Select how much would you like to donate.'),
        '#options' => $amounts,

      ];
    }
    if ($config->get('allow_custom_amount')) {
      $form['amount']['#options']['other'] = $this->t('Other');
      $form['amount_custom'] = [
        '#type' => 'number',
        '#title' => $this->t('Custom amount') . ' (' . $config->get('currency_code') . ')',
        '#description' => $this->t('Write in the amount which you would like to donate.'),
        '#maxlength' => 64,
        '#size' => 64,
        '#step' => 0.01,
        '#states' => [
          'visible' => [
            ':input[name="amount"]' => ['value' => 'other'],
          ],
          'required' => [
            ':input[name="amount"]' => ['value' => 'other'],
          ],
        ],
      ];
    }

    if ($config->get('recurring')) {
      $form['recurring'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Recurring donation?'),
      ];
      $recurring_options = $config->get('recurring_options');
      $recurring_options_vals = $config->get('recurring_options_values');
      foreach ($recurring_options as $key => &$opt) {
        $opt = $recurring_options_vals[$key];
      }
      $form['recurring_options'] = [
        '#type' => 'select',
        '#title' => $this->t('Recurring options:'),
        '#description' => $this->t('Select how often would you like to donate.'),
        '#options' => $recurring_options,
        '#ajax' => [
          'callback' => '::recurringOptionsAjax',
          'method' => 'replace',
          'event' => 'change',
          'wrapper' => 'edit-cycles',
        ],
        '#states' => [
          'visible' => [
            ':input[name="recurring"]' => ['checked' => TRUE],
          ],
          'required' => [
            ':input[name="recurring"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['recurring_cycles'] = [
        '#type' => 'select',
        '#title' => $this->t('Recurring cycles:'),
        '#default_value' => 12,
        '#description' => $this->t('How many times should donation happen.'),
        '#options' => $this->generateCycles(),
        '#prefix' => '<div id="edit-cycles">',
        '#suffix' => '</div>',
        '#states' => [
          'visible' => [
            ':input[name="recurring"]' => ['checked' => TRUE],
          ],
          'required' => [
            ':input[name="recurring"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Donate'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values = $form_state->getValues();
    // Checking if user filled an amount or selected it when both options are
    // presented.
    if (array_key_exists('amount', $values) && array_key_exists('amount_custom', $values) && !$values['amount'] && !$values['amount_custom']) {
      $form_state->setErrorByName('amount_custom', $this->t('Please write the donated amount or select the amount from the list.'));
    }
    // Checking if user inputted an amount when only input is presented.
    elseif (array_key_exists('amount', $values) && !array_key_exists('amount_custom', $values) && !$values['amount']) {
      $form_state->setErrorByName('amount', $this->t('Please select the amount from the list.'));
    }
    // Checking if user selected an amount when only radios are presented.
    elseif (array_key_exists('amount_custom', $values) && !array_key_exists('amount', $values) && !$values['amount_custom']) {
      $form_state->setErrorByName('amount', $this->t('Please write the donated amount.'));
    }
  }

  /**
   * Generates number of cycles based on input.
   *
   * PayPal Docs: The combination of billing frequency and billing period must
   * be less than or equal to one year. For example, if the billing cycle is
   * Month, the maximum value for billing frequency is 12.
   * Similarly, if the billing cycle is Week, the maximum value for billing
   * frequency is 52. Note: If the billing period is SemiMonth, the billing
   * frequency must be 1.
   *
   * @param string $type
   *   Type of the cycle for which data is compiled.
   *
   * @return array
   *   Returns new array ready to use in form options.
   */
  private function generateCycles($type = '') {
    switch ($type) {
      case 'Week':
        $max = 52;
        break;

      case 'Year':
      case 'SemiMonth':
        $max = 1;
        break;

      case 'Month':
        $max = 12;
        break;

      default:
        $max = 365;
    }
    $cycles = range(1, $max);
    return array_combine($cycles, $cycles);
  }

  /**
   * Ajax callback when recurring options field/select is changed.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State Object.
   *
   * @return mixed
   *   Returns updated form element.
   */
  public function recurringOptionsAjax(array $form, FormStateInterface $form_state) {
    $form['recurring_cycles']['#options'] = $this->generateCycles($form_state->getValue('recurring_options'));
    $form['recurring_cycles']['#required'] = TRUE;
    return $form['recurring_cycles'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    global $base_url;
    $config = $this->config('paypal_donation.settings');
    $params = [];
    // Getting donation amount either from radios or from a custom text field.
    if ($amount_custom = $form_state->getValue('amount_custom')) {
      $params['amount'] = $amount_custom;
    }
    else {
      $params['amount'] = $form_state->getValue('amount');
    }
    $sandbox = "";
    if ($config->get('sandbox')) {
      $sandbox = 'sandbox.';
    }
    $params['cancel_return'] = $base_url . base_path() . Url::fromRoute('paypal_donation.return_page_controller_fail')->toString();
    if ($form_state->getValue('recurring')) {
      $params['return'] = $base_url . base_path() . Url::fromRoute('paypal_donation.recurring_controller_init')->toString();
      $paymentDetails = new PaymentDetailsType();
      $itemAmount = new BasicAmountType($config->get('currency_code'), $params['amount']);
      $itemDetails = new PaymentDetailsItemType();
      $itemDetails->Name = (string) $this->t("Donation");
      $itemDetails->Amount = $itemAmount;
      $itemDetails->Quantity = 1;
      $itemDetails->ItemCategory = "Digital";
      $paymentDetails->PaymentDetailsItem[0] = $itemDetails;

      $paymentDetails->ItemTotal = new BasicAmountType($config->get('currency_code'), $params['amount']);
      $paymentDetails->OrderTotal = new BasicAmountType($config->get('currency_code'), $params['amount']);

      $setECReqDetails = new SetExpressCheckoutRequestDetailsType();
      $setECReqDetails->PaymentDetails = $paymentDetails;
      $setECReqDetails->CancelURL = $params['cancel_return'];
      $setECReqDetails->ReturnURL = $params['return'];
      $setECReqDetails->SolutionType = 'Sole';
      $setECReqDetails->NoShipping = 1;
      $setECReqDetails->AddressOverride = 0;
      $setECReqDetails->ReqConfirmShipping = 0;

      $billingAgreementDetails = new BillingAgreementDetailsType("RecurringPayments");
      $billingAgreementDetails->BillingAgreementDescription = $config->get('billing_description');
      $setECReqDetails->BillingAgreementDetails = [$billingAgreementDetails];

      $setECReqType = new SetExpressCheckoutRequestType();
      $setECReqType->SetExpressCheckoutRequestDetails = $setECReqDetails;
      $setECReq = new SetExpressCheckoutReq();
      $setECReq->SetExpressCheckoutRequest = $setECReqType;

      $paypalService = new PayPalAPIInterfaceServiceService(Configuration::getConfig());

      try {
        $setECResponse = $paypalService->SetExpressCheckout($setECReq);
      }
      catch (Exception $ex) {
        throw new \Exception("PayPal SetExpressCheckout: " . $ex);
      }
      if (isset($setECResponse)) {
        if ($setECResponse->Ack == 'Success') {
          $token = $setECResponse->Token;
          // Setting extra parameters.
          $extra_params = [
            'recurring_options' => $form_state->getValue('recurring_options'),
            'recurring_cycles' => $form_state->getValue('recurring_cycles'),
            'amount' => $params['amount'],
          ];
          // If user is anonymous, session needs to be manually started.
          if ($this->currentUser->isAnonymous() && !isset($_SESSION['session_started'])) {
            $_SESSION['session_started'] = TRUE;
            $this->sessionManager->start();
          }
          // Saving extra parameters in user's tempstore. When user comes back
          // from Paypal's checkout to RecurringPaymentController, data from the
          // Drupal donation form is lost. Hence this data is saved here in user
          // private tempstore so it can be restored in
          // RecurringPaymentController.
          $this->tempStore->set('extra_params', $extra_params);
          $url = 'https://www.' . $sandbox . 'paypal.com/webscr?cmd=_express-checkout&token=' . $token;
        }
        else {
          throw new \Exception($setECResponse->Errors[0]['ShortMessage'] . ' Error Code: ' . $setECResponse->Errors[0]['ErrorCode']);
        }
      }
    }
    else {
      // Adding mandatory params for PayPal Donate to work.
      $params['return'] = $base_url . base_path() . Url::fromRoute('paypal_donation.return_page_controller_success')->toString();
      $params['cmd'] = '_donations';
      $params['business'] = $config->get('business');
      $params['lc'] = $config->get('lc');
      $params['item_name'] = $config->get('item_name');
      $params['item_number'] = $config->get('item_number');
      $params['currency_code'] = $config->get('currency_code');
      $params['no_note'] = 0;
      $params['cn'] = $config->get('cn');
      $params['no_shipping'] = $config->get('no_shipping');
      $params['rm'] = 1;
      $url = "https://www." . $sandbox . "paypal.com/cgi-bin/webscr" . '?' . http_build_query($params);
      $response = new TrustedRedirectResponse($url);
      $form_state->setResponse($response);
    }
    $response = new TrustedRedirectResponse($url);
    $form_state->setResponse($response);
  }

}
