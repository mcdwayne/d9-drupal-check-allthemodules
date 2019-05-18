<?php

namespace Drupal\commerce_shipengine\Form;

use Drupal\commerce_shipengine\ShipEngineRateRequest;
use Drupal\commerce_shipengine\ShipEngineLabelRequest;
use Drupal\commerce_shipengine\ShipEngineVoidRequest;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class ShipEngineDebug.
 */
class ShipEngineDebug extends FormBase {

  /**
   * ShipEngineLabelRequest.
   *
   * @var \Drupal\commerce_shipengine\ShipEngineLabelRequest
   */
  protected $ship_engine_label_request;

  /**
   * Constructs a new TestController object.
   */
  public function __construct(ShipEngineLabelRequest $ship_engine_label_request, ShipEngineRateRequest $ship_engine_rate_request, ShipEngineVoidRequest $ship_engine_void_request) {
    $this->ship_engine_label_request = $ship_engine_label_request;
    $this->ship_engine_rate_request = $ship_engine_rate_request;
    $this->ship_engine_void_request = $ship_engine_void_request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_shipengine.label_request'),
      $container->get('commerce_shipengine.rate_request'),
      $container->get('commerce_shipengine.void_request')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_shipengine_debug';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['order_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Order ID'),
    ];

    $form['get_rates'] = [
      '#type' => 'button',
      '#value' => $this->t('Get rates'),
      '#name' => 'get_rates',
    ];

    $form['get_labels'] = [
      '#type' => 'button',
      '#value' => $this->t('Get labels'),
      '#name' => 'get_labels',
    ];

    if ($order_id = $form_state->getValue('order_id')) {
      $order = Order::load($order_id);
      $form_state->setFormState(['order' => $order]);
      $shipments = $order->get('shipments')->referencedEntities();

      if ($triggering_element = $form_state->getTriggeringElement()) {
        switch($triggering_element['#name']) {
          case 'get_labels':
            $form['labels'] = [
              '#type' => 'fieldset',
              '#title' => $this->t('Labels'),
            ];

            $form['label_requests'] = [
              '#type' => 'fieldset',
              '#title' => $this->t('Label requests'),
            ];

            foreach ($shipments as $shipment) {
              $label = $shipment->getData('label');
              if ($label) {
                $label_element = [
                  'link' => Link::fromTextAndUrl($label['url'], Url::fromUri($label['url']))->toRenderable(),
                  'void' => [
                    '#type' => 'submit',
                    '#value' => $this->t('Void label'),
                    '#name' => $label['label_id'],
                    '#submit' => [[$this, 'voidLabel']],
                  ],
                ];
                $form['labels'][] = $label_element;
              }

              $this->ship_engine_label_request->setShipment($shipment);
              $label_request = $this->ship_engine_label_request->getLabelRequest();
              $label_request_element = [
                '#type' => 'fieldset',
                '#title' => $shipment->label(),
                'label_request' => [
                  '#markup' => json_encode($label_request, JSON_PRETTY_PRINT),
                  '#prefix' => '<pre>',
                  '#suffix' => '</pre>',
                ],
                'create_label' => [
                  '#type' => 'submit',
                  '#value' => $this->t('Create label'),
                  '#name' => $shipment->id(),
                  '#submit' => [[$this, 'createLabel']],
                ],
              ];

              $form['label_requests'][] = $label_request_element;
            }

            break;
          case 'get_rates':
            $form['rate_requests'] = [
              '#type' => 'fieldset',
              '#title' => $this->t('Rates'),
            ];

            foreach ($shipments as $shipment) {
              $this->ship_engine_rate_request->setShipment($shipment);
              $config = $shipment->getShippingMethod()->getPlugin()->getConfiguration();
              $this->ship_engine_rate_request->setConfig($config);
              $rate_request = $this->ship_engine_rate_request->getRateRequest();
              if ($rate_request) {
                $rates_element = [
                  '#type' => 'fieldset',
                  '#title' => $shipment->label(),
                  'request' => [
                    '#markup' => json_encode($rate_request, JSON_PRETTY_PRINT),
                    '#prefix' => '<pre>',
                    '#suffix' => '</pre>',
                  ],
                  'rates' => [
                    '#type' => 'fieldset',
                    '#title' => $this->t('Rates'),
                  ],
                ];

                $rates = $this->ship_engine_rate_request->getRates();
                foreach ($rates as $rate) {
                  $label = $rate->getService()->getLabel();
                  $number = $rate->getAmount()->getNumber();
                  $rates_element['rates'][] = [
                    '#markup' => "$label: $$number",
                    '#prefix' => '<pre>',
                    '#suffix' => '</pre>',
                  ];
                }

                $form['rate_requests'][] = $rates_element;
              }
            }
            break;
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Create label.
   */
  public function createLabel(array &$form, FormStateInterface $form_state) {
    $order = $form_state->get('order');
    $shipments = $order->get('shipments')->referencedEntities();
    $shipment_id = $form_state->getTriggeringElement()['#name'];

    foreach ($shipments as $shipment) {
      if ($shipment->id() === $shipment_id) {
        $label = $shipment->getData('label');
        if ($label) {
          drupal_set_message($this->t('Label already exists.'));
        }
        else {
          $this->ship_engine_label_request->setShipment($shipment);
          $label = $this->ship_engine_label_request->createLabel();
          $shipment->setData('label', $label);
          $shipment->save();
        }
      }
    }

    $form_state->setTriggeringElement($form['get_labels']);
    $form_state->setRebuild();
  }

  /**
   * Void label.
   */
  public function voidLabel(array &$form, FormStateInterface $form_state) {
    $order = $form_state->get('order');
    $shipments = $order->get('shipments')->referencedEntities();
    $label_id = $form_state->getTriggeringElement()['#name'];

    foreach ($shipments as $shipment) {
      $label = $shipment->getData('label');
      if ($label['label_id'] === $label_id) {
        $this->ship_engine_void_request->setShipment($shipment);
        $config = $shipment->getShippingMethod()->getPlugin()->getConfiguration();
        $this->ship_engine_void_request->setConfig($config);
        $this->ship_engine_void_request->voidLabel($label_id);

        $shipment->setData('label', []);
        $shipment->save();
        drupal_set_message($this->t("Voided label $label_id."));
      }
    }

    $form_state->setTriggeringElement($form['get_labels']);
    $form_state->setRebuild();
  }

}
