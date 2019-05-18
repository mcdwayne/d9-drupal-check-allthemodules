<?php

namespace Drupal\contacts_events\Plugin\Commerce\CheckoutPane;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\contacts_events\Plugin\Commerce\CheckoutFlow\BookingFlow;
use Drupal\contacts_events\PriceCalculator;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\inline_entity_form\WidgetSubmit;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the tickets pane.
 *
 * @CommerceCheckoutPane(
 *   id = "tickets",
 *   label = @Translation("Tickets"),
 *   default_step = "tickets",
 *   wrapper_element = "container",
 *   review_link = @Translation("Manage tickets")
 * )
 */
class TicketsPane extends CheckoutPaneBase {

  /**
   * The price calculator.
   *
   * @var \Drupal\contacts_events\PriceCalculator
   */
  protected $priceCalculator;

  /**
   * The currency formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
   */
  protected $currencyFormatter;

  /**
   * Constructs a new CheckoutPaneBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface $checkout_flow
   *   The parent checkout flow.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\contacts_events\PriceCalculator $price_calculator
   *   The price calculator service.
   * @param \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter
   *   The currency formatter.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, EntityTypeManagerInterface $entity_type_manager, PriceCalculator $price_calculator, CurrencyFormatterInterface $currency_formatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow, $entity_type_manager);
    $this->priceCalculator = $price_calculator;
    $this->currencyFormatter = $currency_formatter;
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
      $container->get('contacts_events.price_calculator'),
      $container->get('commerce_price.currency_formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    /* @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = $this->entityTypeManager
      ->getStorage('entity_form_display')
      ->load('commerce_order.contacts_booking.booking_tickets');

    $query_params = \Drupal::request()->query->all();
    if (!empty($query_params['op']) && !empty($query_params['id'])) {
      $form_state->set('inline_entity_form_order_item_tickets', [
        'op' => $query_params['op'],
        'id' => $query_params['id'],
      ]);
    }

    $form_display->buildForm($this->order, $pane_form, $form_state);

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    WidgetSubmit::doSubmit($pane_form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneSummary() {
    $content = [];

    $content['tickets'] = [
      '#type' => 'table',
      '#empty' => $this->t('There are no tickets on this booking.'),
      '#header' => [
        'name' => $this->t('Name'),
        'class' => $this->t('Type'),
        'price' => $this->t('Price'),
        'operations' => $this->t('Manage'),
      ],
      '#rows' => [],
    ];

    $format_options = [
      'currency_display' => 'symbol',
      'minimum_fraction_digits' => 2,
    ];

    $route_name = $this->checkoutFlow instanceof BookingFlow ? $this->checkoutFlow::ROUTE_NAME : 'commerce_checkout.form';
    $route_params = [
      'commerce_order' => $this->order->id(),
      'step' => $this->getStepId(),
    ];

    foreach ($this->order->get('order_items') as $order_item_item) {
      /* @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
      $order_item = $order_item_item->entity;
      if (!$order_item || $order_item->bundle() != 'contacts_ticket') {
        continue;
      }

      /* @var \Drupal\contacts_events\Entity\TicketInterface $ticket */
      $ticket = $order_item->getPurchasedEntity();
      $price_mapping = $order_item->get('mapped_price');

      if (!isset($classes)) {
        $price_map = $this->priceCalculator->findPriceMap($order_item);
        $classes = [];
        foreach ($price_map->getClasses() as $class) {
          $classes[$class->id()] = $class->label();
        };
      }

      $price = $order_item->getTotalPrice();
      $formatted_price = $this->currencyFormatter
        ->format($price->getNumber(), $price->getCurrencyCode(), $format_options);

      // @todo: Access checks on operations.
      $operations = [];
      $operations['edit'] = [
        '#type' => 'link',
        '#url' => Url::fromRoute($route_name, $route_params, [
          'query' => [
            'id' => $order_item->id(),
            'op' => 'edit',
          ],
        ]),
        '#title' => $this->t('Edit'),
        '#attributes' => [
          'class' => ['button', 'button--secondary'],
        ],
        '#access' => $order_item->access('update'),
      ];
      $operations['remove'] = [
        '#type' => 'link',
        '#url' => Url::fromRoute($route_name, $route_params, [
          'query' => [
            'id' => $order_item->id(),
            'op' => 'remove',
          ],
        ]),
        '#title' => $this->t('Remove'),
        '#attributes' => [
          'class' => ['button', 'button--secondary'],
        ],
        '#access' => $order_item->access('delete'),
      ];

      $content['tickets']['#rows'][] = [
        'name' => ['data' => $ticket->name->view()],
        'class' => $classes[$price_mapping->class],
        'price' => $formatted_price,
        'operations' => [
          'data' => ['#type' => 'actions'] + $operations,
        ],
      ];
    }

    return $content;
  }

}
