<?php

namespace Drupal\commerce_partpay\PluginForm\OffSiteRedirect;

use Drupal\commerce_partpay\PartPay\PartPay;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PartPayOffSiteForm.
 *
 * @package Drupal\commerce_partpay\PluginForm\OffsiteRedirect
 */
class PartPayForm extends PaymentOffsiteForm implements ContainerInjectionInterface {

  /**
   * PartPay Service.
   *
   * @var \Drupal\commerce_partpay\PartPay\PartPay
   */
  protected $partPay;

  /**
   * PartPayOffSiteForm constructor.
   */
  public function __construct(PartPay $partPay) {
    $this->partPay = $partPay;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    $this->partPay->init();

    $transaction = $this->partPay->prepareTransaction($payment, $form);

    $response = $this->partPay->createOrder($transaction);

    if (!$this->partPay->isRedirectMethod($response)) {
      $this->partPay->logger->error('Error');
      return $form;
    }

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $payment->getOrder();

    $order->setData('partPay', $response);

    $order->save();

    $form = $this->buildRedirectForm($form, $form_state, $this->partPay->getRedirectUrl($response), []);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_partpay.partpay')
    );
  }

}
