<?php

namespace Drupal\commerce_amazon_lpa\Plugin\Commerce\CheckoutFlow;

use Drupal\commerce\Response\NeedsRedirectException;
use Drupal\commerce_amazon_lpa\AmazonPay as AmazonPayApi;
use Drupal\commerce_amazon_lpa\Exception\AmazonPayPaymentGatewayFailureException;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowBase;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Render\Element;

/**
 * Provides the default multistep checkout flow.
 *
 * @CommerceCheckoutFlow(
 *   id = "amazon_pay",
 *   label = "Amazon Pay",
 * )
 */
class AmazonPay extends CheckoutFlowBase {

  /**
   * The Amazon Pay API wrapper.
   *
   * @var \Drupal\commerce_amazon_lpa\AmazonPay
   */
  protected $amazonPay;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Creates a new AmazonPay object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\commerce_amazon_lpa\AmazonPay $amazon_pay
   *   The Amazon Pay service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, RouteMatchInterface $route_match, AmazonPayApi $amazon_pay, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $event_dispatcher, $route_match);
    $this->amazonPay = $amazon_pay;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('current_route_match'),
      $container->get('commerce_amazon_lpa.amazon_pay'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSteps() {
    // Note that previous_label and next_label are not the labels
    // shown on the step itself. Instead, they are the labels shown
    // when going back to the step, or proceeding to the step.
    return [
      'order_information' => [
        'label' => $this->t('Order information'),
        'has_sidebar' => TRUE,
        'previous_label' => $this->t('Go back'),
      ],
      'review' => [
        'label' => $this->t('Review'),
        'next_label' => $this->t('Continue to review'),
        'previous_label' => $this->t('Go back'),
        'has_sidebar' => TRUE,
      ],
    ] + parent::getSteps();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'coupons' => 1,
      'require_shipping_profile' => TRUE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['coupons'] = [
      '#type' => 'select',
      '#title' => $this->t('Allow coupons to be redeemed'),
      '#options' => [
        '0' => $this->t('Do not allow coupons'),
        '1' => $this->t('Only allow one coupon to be redeemed.'),
        '-1' => $this->t('Allow multiple coupons to be redeemed.'),
      ],
      '#default_value' => $this->configuration['coupons'],
    ];
    $form['require_shipping_profile'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide shipping costs until an address is entered'),
      '#default_value' => $this->configuration['require_shipping_profile'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration = [];
      $this->configuration['coupons'] = $values['coupons'];
      $this->configuration['require_shipping_profile'] = !empty($values['require_shipping_profile']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $step_id = NULL) {
    $form = parent::buildForm($form, $form_state, $step_id);
    $form['#attached']['library'][] = 'commerce_amazon_lpa/amazon_pay';

    $order_reference_id = $this->order->get('amazon_order_reference')->value;
    // Remove temporary value.
    // @todo find better fix for this.
    if (strlen($order_reference_id) == 1) {
      $order_reference_id = '';
    }

    $form['#attached']['drupalSettings']['amazonPay'] = [
      'orderReferenceId' => $order_reference_id,
      'isShippable' => $this->isOrderShippable(),
    ];

    switch ($step_id) {
      case 'order_information':
        $form['sidebar']['coupons'] = [
          '#type' => 'commerce_coupon_redemption_form',
          '#order_id' => $this->order->id(),
          '#access' => $this->configuration['coupons'] != 0,
          '#cardinality' => $this->configuration['coupons'] == -1 ? NULL : $this->configuration['coupons'],
          '#element_ajax' => [
            [get_class($this), 'ajaxRefreshSummary'],
          ],
        ];
        $form['amazon_order_reference_id'] = [
          '#type' => 'hidden',
          '#default_value' => $order_reference_id,
        ];
        $form['addressbook'] = [
          '#type' => 'amazon_addressbook',
          '#access' => $this->isOrderShippable(),
        ];
        $form['wallet'] = [
          '#type' => 'amazon_wallet',
          '#weight' => 100,
        ];

        // Shipments.
        $this->buildShipmentSelector($form, $form_state);
        break;

      case 'review':
        $form['addressbook'] = [
          '#type' => 'amazon_addressbook',
          '#display_mode' => 'read',
          '#access' => $this->isOrderShippable(),
        ];
        $form['wallet'] = [
          '#type' => 'amazon_wallet',
          '#display_mode' => 'read',
        ];
        break;

      case 'payment':
        try {
          $order_reference = $this->amazonPay->getOrderReference($this->order);
          $amazon_order_state = $order_reference['OrderReferenceStatus']['State'];
          if ($amazon_order_state == 'Draft') {
            $this->amazonPay->setOrderReference($this->order);
          }
          $this->amazonPay->confirmOrderReference($this->order);
        }
        catch (\Exception $e) {
          drupal_set_message($e->getMessage(), 'error');
          $this->redirectToStep('order_information');
        }

        try {
          $this->doProcessPayment();
          $this->redirectToStep('complete');
        }
        catch (DeclineException $e) {
          drupal_set_message($e->getMessage(), 'error');
          $this->redirectToStep('order_information');
        }
        catch (AmazonPayPaymentGatewayFailureException $e) {
          \Drupal::messenger()->addError($e->getMessage());
          setcookie('amazon_Login_accessToken', '', REQUEST_TIME - 3600, '/');
          setcookie('amazon_Login_state_cache', '', REQUEST_TIME - 3600, '/');
          $this->order->get('checkout_flow')->setValue(NULL);
          $this->order->get('checkout_step')->setValue(NULL);
          $this->order->get('amazon_order_reference')->setValue(NULL);
          $this->order->unlock();
          $this->order->save();
          throw new NeedsRedirectException(Url::fromRoute('commerce_cart.page')->toString());
        }
        break;

      case 'complete':
        // @todo Process shippable orders, sync shipping profile.
        // @todo Process and sync billing profile if available.
        $form['completion_message']['#theme'] = 'commerce_checkout_completion_message';
        $form['completion_message']['#order_entity'] = $this->order;
        break;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    switch ($form['#step_id']) {
      case 'order_information':
        if (!$this->moduleHandler->moduleExists('commerce_shipping') || !$this->order->shipments) {
          break;
        }
        $shipment_indexes = Element::children($form['shipping']['shipments']);
        $triggering_element = $form_state->getTriggeringElement();
        $recalculate = !empty($triggering_element['#recalculate']);
        $button_type = isset($triggering_element['#button_type']) ? $triggering_element['#button_type'] : '';
        if (!$recalculate && $button_type == 'primary' && empty($shipment_indexes)) {
          // The checkout step was submitted without shipping being calculated.
          // Force the recalculation now and reload the page.
          $recalculate = TRUE;
          drupal_set_message($this->t('Please select a shipping method.'), 'error');
          $form_state->setRebuild(TRUE);
        }

        if ($recalculate) {
          $form_state->set('recalculate_shipping', TRUE);
        }

        foreach ($shipment_indexes as $index) {
          $shipment = clone $form['shipping']['shipments'][$index]['#shipment'];
          $form_display = EntityFormDisplay::collectRenderDisplay($shipment, 'default');
          $form_display->removeComponent('shipping_profile');
          $form_display->removeComponent('title');
          $form_display->extractFormValues($shipment, $form['shipping']['shipments'][$index], $form_state);
          $form_display->validateFormValues($shipment, $form['shipping']['shipments'][$index], $form_state);
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    switch ($form['#step_id']) {
      case 'order_information':
        $order_reference_id = $form_state->getValue('amazon_order_reference_id');
        $this->order->get('amazon_order_reference')->setValue($order_reference_id);
        if (!empty($form['code']['#coupon_id'])) {
          $this->order->get('coupons')->appendItem($form['code']['#coupon_id']);
        }

        if (!$this->moduleHandler->moduleExists('commerce_shipping') || !$this->order->hasField('shipments')) {
          break;
        }
        // Save the modified shipments.
        $shipments = [];
        foreach (Element::children($form['shipping']['shipments']) as $index) {
          /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
          $shipment = clone $form['shipping']['shipments'][$index]['#shipment'];
          $form_display = EntityFormDisplay::collectRenderDisplay($shipment, 'default');
          $form_display->removeComponent('shipping_profile');
          $form_display->removeComponent('title');
          $form_display->extractFormValues($shipment, $form['shipping']['shipments'][$index], $form_state);
          $shipment->save();
          $shipments[] = $shipment;
        }
        $this->order->shipments = $shipments;

        // Delete shipments that are no longer in use.
        $removed_shipment_ids = $form['shipping']['removed_shipments']['#value'];
        if (!empty($removed_shipment_ids)) {
          $shipment_storage = $this->entityTypeManager->getStorage('commerce_shipment');
          $removed_shipments = $shipment_storage->loadMultiple($removed_shipment_ids);
          $shipment_storage->delete($removed_shipments);
        }
        break;
    }
    parent::submitForm($form, $form_state);
  }

  /**
   * Helper function to build shipment selector.
   */
  protected function buildShipmentSelector(array &$form, FormStateInterface $form_state) {
    if (!$this->moduleHandler->moduleExists('commerce_shipping') || !$this->order->hasField('shipments')) {
      return;
    }
    if (!$this->isOrderShippable()) {
      return;
    }
    $shipping_profile = $this->getShippingProfile($form, $form_state);

    // Prepare the form for ajax.
    // Not using Html::getUniqueId() on the wrapper ID to avoid #2675688.
    $form['shipping']['#wrapper_id'] = 'shipping-information-wrapper';
    $form['shipping']['#prefix'] = '<div id="' . $form['shipping']['#wrapper_id'] . '">';
    $form['shipping']['#suffix'] = '</div>';

    $form['shipping']['recalculate_shipping'] = [
      '#type' => 'button',
      '#value' => $this->t('Recalculate shipping'),
      '#recalculate' => TRUE,
      '#ajax' => [
        'callback' => [get_class($this), 'ajaxRefresh'],
        'wrapper' => $form['shipping']['#wrapper_id'],
      ],
    ];
    $form['shipping']['removed_shipments'] = [
      '#type' => 'value',
      '#value' => [],
    ];
    $form['shipping']['shipments'] = [
      '#type' => 'container',
    ];

    $shipments = $this->order->shipments->referencedEntities();
    $recalculate_shipping = $form_state->get('recalculate_shipping');
    // @todo Inject this conditionally (if commerce_shipping is enabled).
    $packer_manager = \Drupal::service('commerce_shipping.packer_manager');
    if ($recalculate_shipping) {
      list($shipments, $removed_shipments) = $packer_manager->packToShipments($this->order, $shipping_profile, $shipments);

      // Store the IDs of removed shipments for submitPaneForm().
      $form['shipping']['removed_shipments']['#value'] = array_map(function ($shipment) {
        /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
        return $shipment->id();
      }, $removed_shipments);
    }

    $single_shipment = count($shipments) === 1;
    foreach ($shipments as $index => $shipment) {
      /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
      $form['shipping']['shipments'][$index] = [
        '#type' => $single_shipment ? 'container' : 'fieldset',
        '#title' => $shipment->getTitle(),
      ];
      $form_display = EntityFormDisplay::collectRenderDisplay($shipment, 'default');
      $form_display->removeComponent('shipping_profile');
      $form_display->removeComponent('title');
      $form_display->buildForm($shipment, $form['shipping']['shipments'][$index], $form_state);
      $form['shipping']['shipments'][$index]['#shipment'] = $shipment;
    }
  }

  /**
   * Determines if the order is shippable.
   *
   * @return bool
   *   Returns TRUE if the order is shippable.
   */
  protected function isOrderShippable() {
    static $is_shippable;
    if (empty($is_shippable)) {
      $is_shippable = FALSE;
      // The order must contain at least one shippable purchasable entity.
      foreach ($this->order->getItems() as $order_item) {
        $purchased_entity = $order_item->getPurchasedEntity();
        if ($purchased_entity && $purchased_entity->hasField('weight')) {
          $is_shippable = TRUE;
        }
      }
    }
    return $is_shippable;
  }

  /**
   * Processes the payment with Amazon Pay.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function doProcessPayment() {
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $this->entityTypeManager->getStorage('commerce_payment_gateway')->load('amazon_pay');
    /** @var \Drupal\commerce_amazon_lpa\Plugin\Commerce\PaymentGateway\AmazonPayInterface $plugin */
    $plugin = $payment_gateway->getPlugin();

    $payment = Payment::create([
      'state' => 'new',
      'amount' => $this->order->getTotalPrice(),
      'payment_gateway' => $payment_gateway->id(),
      'order_id' => $this->order->id(),
    ]);
    $plugin->createPayment($payment);
  }

  /**
   * Gets the shipping profile.
   *
   * The shipping profile is assumed to be the same for all shipments.
   * Therefore, it is taken from the first found shipment, or created from
   * scratch if no shipments were found.
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   *   The shipping profile.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getShippingProfile($form, FormStateInterface $form_state) {
    /** @var \Drupal\profile\Entity\ProfileInterface|\Drupal\profile\Entity\Profile $shipping_profile */
    $shipping_profile = $this->entityTypeManager->getStorage('profile')->create([
      'type' => 'customer',
      'uid' => $this->order->getCustomerId(),
    ]);

    $order_reference_id = $form_state->getValue('amazon_order_reference_id');
    if ($order_reference_id) {
      try {
        $this->order->get('amazon_order_reference')->setValue($order_reference_id);
        $amazon_order_reference = $this->amazonPay->getOrderReference($this->order);
        $destination = $amazon_order_reference['Destination']['PhysicalDestination'];
        $shipping_profile->get('address')->setValue([
          'country_code' => $destination['CountryCode'],
          'administrative_area' => $destination['StateOrRegion'],
          'locality' => $destination['City'],
          'postal_code' => $destination['PostalCode'],
        ]);
      }
      catch (\Exception $e) {
        // Return empty shipping profile.
      }
    }

    $form_state->setRebuild(TRUE);
    return $shipping_profile;
  }

  /**
   * Ajax callback for refreshing the order summary.
   */
  public static function ajaxRefreshSummary(array $form, FormStateInterface $form_state) {
    if (isset($form['sidebar']['order_summary'])) {
      $summary_element = $form['sidebar']['order_summary'];
      return new InsertCommand('[data-drupal-selector="edit-sidebar-order-summary"]', $summary_element);
    }
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#parents'], 0, -1);
    return NestedArray::getValue($form, $parents);
  }

}
