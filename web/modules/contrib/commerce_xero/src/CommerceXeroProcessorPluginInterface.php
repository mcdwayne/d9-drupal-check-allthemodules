<?php

namespace Drupal\commerce_xero;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\ComplexDataInterface;

/**
 * Describes how a Commerce Xero plugin should be implemented.
 */
interface CommerceXeroProcessorPluginInterface extends ConfigurablePluginInterface, PluginInspectionInterface {

  /**
   * Provides a settings form to configure the processor plugin.
   *
   * If no configuration is needed, return an empty array.
   *
   * @param array $form
   *   The plugin settings form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form array.
   */
  public function settingsForm(array $form, FormStateInterface $form_state);

  /**
   * Modifies dataToProcess based on the provided paymentEntity.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment entity.
   * @param \Drupal\Core\TypedData\ComplexDataInterface $data
   *   The xero data type.
   *
   * @return bool
   *   Whether the process succeeded or not.
   */
  public function process(PaymentInterface $payment, ComplexDataInterface $data);

}
