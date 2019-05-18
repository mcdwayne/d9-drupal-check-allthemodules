<?php

namespace Drupal\paypal_donation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\paypal_donation\Configuration;
use Drupal\user\PrivateTempStoreFactory;
use PayPal\CoreComponentTypes\BasicAmountType;
use PayPal\EBLBaseComponents\ActivationDetailsType;
use PayPal\EBLBaseComponents\BillingPeriodDetailsType;
use PayPal\EBLBaseComponents\CreateRecurringPaymentsProfileRequestDetailsType;
use PayPal\EBLBaseComponents\RecurringPaymentsProfileDetailsType;
use PayPal\EBLBaseComponents\ScheduleDetailsType;
use PayPal\PayPalAPI\CreateRecurringPaymentsProfileReq;
use PayPal\PayPalAPI\CreateRecurringPaymentsProfileRequestType;
use PayPal\Service\PayPalAPIInterfaceServiceService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class RecurringPaymentController.
 *
 * @package Drupal\paypal_donation\Controller
 */
class RecurringPaymentController extends ControllerBase {

  protected $tempStore;
  protected $request;

  /**
   * RecurringPayment constructor.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   User's temp store.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   RequestStack object.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, RequestStack $request) {
    $this->tempStore = $temp_store_factory->get('paypal_donation');
    $this->request = $request->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('request_stack')
    );
  }

  /**
   * Endpoint to which user is redirected after express checkout is finished.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects user to success or fail page.
   *
   * @throws \Exception
   */
  public function recurringReturn() {
    $config = $this->config('paypal_donation.settings');
    $extra_params = $this->tempStore->get('extra_params');
    if (!$token = trim($this->request->query->get('token'))) {
      throw new \Exception("No token found in URL!");
    }

    $rPProfileDetails = new RecurringPaymentsProfileDetailsType();
    $dateTime = new \DateTime();
    $rPProfileDetails->BillingStartDate = $dateTime->format(\DateTime::ISO8601);

    $activationDetails = new ActivationDetailsType();
    $activationDetails->InitialAmount = new BasicAmountType($config->get('currency_code'), $extra_params['amount']);
    $activationDetails->FailedInitialAmountAction = 'ContinueOnFailure';

    $paymentBillingPeriod = new BillingPeriodDetailsType();
    $paymentBillingPeriod->BillingFrequency = 1;
    $paymentBillingPeriod->BillingPeriod = $extra_params['recurring_options'];
    $paymentBillingPeriod->TotalBillingCycles = $extra_params['recurring_cycles'];
    $paymentBillingPeriod->Amount = new BasicAmountType($config->get('currency_code'), $extra_params['amount']);

    $scheduleDetails = new ScheduleDetailsType();
    $scheduleDetails->Description = $config->get('billing_description');
    $scheduleDetails->ActivationDetails = $activationDetails;

    $scheduleDetails->PaymentPeriod = $paymentBillingPeriod;

    $createRPProfileRequestDetail = new CreateRecurringPaymentsProfileRequestDetailsType();

    $createRPProfileRequestDetail->Token = $token;
    $createRPProfileRequestDetail->ScheduleDetails = $scheduleDetails;
    $createRPProfileRequestDetail->RecurringPaymentsProfileDetails = $rPProfileDetails;
    $createRPProfileRequest = new CreateRecurringPaymentsProfileRequestType();
    $createRPProfileRequest->CreateRecurringPaymentsProfileRequestDetails = $createRPProfileRequestDetail;
    $createRPProfileReq = new CreateRecurringPaymentsProfileReq();
    $createRPProfileReq->CreateRecurringPaymentsProfileRequest = $createRPProfileRequest;

    $paypalService = new PayPalAPIInterfaceServiceService(Configuration::getConfig());
    try {
      $paypalService->CreateRecurringPaymentsProfile($createRPProfileReq);
      return $this->redirect('paypal_donation.return_page_controller_success');
    }
    catch (Exception $ex) {
      return $this->redirect('paypal_donation.return_page_controller_fail');
    }

  }

}
