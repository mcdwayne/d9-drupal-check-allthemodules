<?php

namespace Drupal\commerce_product_reservation\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the billing information pane.
 *
 * @CommerceCheckoutPane(
 *   id = "reservation_info",
 *   label = @Translation("Reservation information"),
 *   wrapper_element = "fieldset",
 * )
 */
class ReservationInformation extends CheckoutPaneBase {

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * ReservationInformation constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow, $entity_type_manager);
    $this->logger = $logger_factory->get('commerce_product_reservation');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $checkout_flow,
      $container->get('entity_type.manager'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form['reservation_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
    ];
    $pane_form['reservation_phone_number'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone number'),
    ];
    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $order = $this->order;
    try {
      $fields = [
        'reservation_name',
        'reservation_phone_number',
      ];
      $our_values = $form_state->getValue('reservation_info');
      foreach ($fields as $field) {
        if (empty($our_values[$field])) {
          continue;
        }
        if (!$order->hasField($field)) {
          // Well, then. Nothing to do. We are expecting the fields to be the
          // ones we install, and if this is used on a different order type,
          // then the user should make sure those fields.
          continue;
        }
        $order->set($field, $our_values[$field]);
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Caught exception saving the info from the reservation info. Message was: @msg and stack trace was: @trace0', [
        '@msg' => $e->getMessage(),
        '@trace' => $e->getTraceAsString(),
      ]);
    }
  }

}
