<?php

namespace Drupal\commerce_admin_checkout\Element;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowWithPanesBase;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form element for editing an order's items in the cart
 *
 * Usage example:
 *
 * @code
 * $form['store_credit'] = [
 *   '#type' => 'commerce_multi_payment_example_giftcard_form',
 *   '#title' => t('Gift Card'),
 *   '#payment_gateway_id' => $payment_form['#payment_gateway_id'],
 *   '#order_id' => $order_id,
 * ];
 * @endcode
 * The element takes the gift card list from
 *   $order->get('staged_multi_payment'). The order is saved when a gift card
 *   is added or removed.
 *
 * @FormElement("commerce_admin_checkout_order_items_form")
 */
class AdminCheckoutOrderItemsForm extends FormElement {
  
  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#element_ajax' => [],
      // If NULL, the cardinality is unlimited.
      '#cardinality' => 1,
      '#order_id' => NULL,

      '#title' => t('Edit Order'),
      '#description' => NULL,
      '#process' => [
        [$class, 'processForm'],
      ],
      '#element_validate' => [
        [$class, 'validateForm'],
      ],
      '#element_submit' => [
        [$class, 'submitForm'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Ajax callback.
   */
  public static function ajaxAddOrderItem(array $form, FormStateInterface &$form_state) {
    $triggering_parents = $form_state->getTriggeringElement()['#parents'];
    if (empty($form_state->getErrors())) {
      $order_item_parents = array_slice($triggering_parents, 0, -2);
      $order_item_values = $form_state->getValue($order_item_parents);
      $variation = ProductVariation::load($order_item_values['variation']);
      $quantity = $order_item_values['quantity'];
      if ($quantity == '') {
        $quantity = 1;
      }
      if (!empty($variation) && $quantity > 0) {
        $build_info = $form_state->getBuildInfo();
        /** @var \Drupal\commerce_order\Entity\Order $order */
        $order = $build_info['callback_object']->getOrder();
        $order_item = OrderItem::create([
          'type' => $variation->getOrderItemTypeId(),
          'order_id' => $order,
          'purchased_entity' => $variation,
        ]);
        
        $order_item->setQuantity($quantity);
        if (!empty($order_item_values['price']['number'])) {
          $price = new Price($order_item_values['price']['number'], $order_item_values['price']['currency_code']);
        }
        else {
          $price = $variation->getPrice();
        }
        $order_item->setUnitPrice($price, (bool) $order_item_values['overridden_price']);

        // Allow extra fields to be added to the form and saved.
        foreach (array_diff(array_keys($order_item_values), ['overridden_price', 'price', 'variation', 'quantity', 'actions']) as $extra_field) {
          if ($order_item->hasField($extra_field)) {
            $order_item->set($extra_field, $order_item_values[$extra_field]);
          }
          else {
            $order_item->setData($extra_field, $order_item_values[$extra_field]);
          }
        }

        $order_item->save();
        $order->addItem($order_item);
        $order->save();
        static::setUserInput($form_state, $order_item_parents, NULL);
      }
    }
    $form_state->setRebuild();
  }

  /**
   * Ajax callback.
   */
  public static function ajaxUpdateOrderItem(array $form, FormStateInterface &$form_state) {
    $triggering_parents = $form_state->getTriggeringElement()['#parents'];
    if (empty($form_state->getErrors())) {
      if (count($triggering_parents) >= 4) {
        $order_item_id = $triggering_parents[count($triggering_parents) - 3];
        $order_item_parents = array_slice($triggering_parents, 0, -2);
        if (is_numeric($order_item_id)) {
          $order_item = OrderItem::load($order_item_id);
          $order_item_values = $form_state->getValue($order_item_parents);
          if ($order_item_values['quantity'] > 0) {
            $order_item->setQuantity($order_item_values['quantity']);
            $price = new Price($order_item_values['price']['number'], $order_item_values['price']['currency_code']);
            $order_item->set('purchased_entity', $order_item_values['variation']);
            $order_item->setUnitPrice($price, !empty($order_item_values['overridden_price']));
            $order_item->save();
            $order_item->getOrder()->save();
            static::setUserInput($form_state, $order_item_parents, NULL);
          }
          else {
            /** @var \Drupal\commerce_order\Entity\Order $order */
            $order = $order_item->getOrder();
            $order->removeItem($order_item);
            $order->save();
            $order_item->delete();
          }
        }
        
      }
    }
    $form_state->setRebuild();
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRemoveOrderItem(array $form, FormStateInterface $form_state) {
    $triggering_parents = $form_state->getTriggeringElement()['#parents'];
    if (empty($form_state->getErrors())) {
      if (count($triggering_parents) >= 4) {
        $order_item_id = $triggering_parents[count($triggering_parents) - 3];
        if (is_numeric($order_item_id)) {
          $order_item = OrderItem::load($order_item_id);
          /** @var \Drupal\commerce_order\Entity\Order $order */
          $order = $order_item->getOrder();
          $order->removeItem($order_item);
          $order->save();
          $order_item->delete();
        }

      }
    }
    $form_state->setRebuild();
  }
  
  /**
   * Processes the gift card redemption form.
   *
   * @param array $element
   *   The form element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the #order_id property is empty or invalid.
   *
   * @return array
   *   The processed form element.
   */
  public static function processForm(array $element, FormStateInterface $form_state, array &$complete_form) {
    if (empty($element['#order_id'])) {
      throw new \InvalidArgumentException('The commerce_admin_checkout_order_items_form element requires the #order_id property.');
    }
    $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
    $order = $order_storage->load($element['#order_id']);
    if (!$order instanceof OrderInterface) {
      throw new \InvalidArgumentException('The commerce_admin_checkout_order_items_form #order_id must be a valid order ID.');
    }

    $id_prefix = implode('-', $element['#parents']);
    // @todo We cannot use unique IDs, or multiple elements on a page currently.
    // @see https://www.drupal.org/node/2675688
    // $wrapper_id = Html::getUniqueId($id_prefix . '-ajax-wrapper');
    $wrapper_id = $id_prefix . '-ajax-wrapper';
    
    

    $element = [
        '#tree' => TRUE,
        '#theme' => 'commerce_admin_checkout_order_items_form',
        '#prefix' => '<div data-drupal-selector="' . $wrapper_id . '" id="' . $wrapper_id . '">',
        '#suffix' => '</div>',
        // Pass the id along to other methods.
        '#wrapper_id' => $wrapper_id,
      ] + $element;

    
    $element['items'] = [
      '#type' => 'container',
      '#weight' => 10,
    ];
    $allow_override_price = \Drupal::currentUser()->hasPermission('override line item prices during checkout');
    foreach ($order->getItems() as $item) {
      $element['items'][$item->id()]['variation'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'commerce_product_variation',
        '#selection_handler' => 'views',
        '#selection_settings' => [
          'view' => [
            'view_name' => 'commerce_admin_checkout_variations',
            'display_name' => 'entity_reference_1',
          ],
        ],
        '#title' => t('Product Variation'),
        '#required' => TRUE,
        '#default_value' => $item->getPurchasedEntity(),
      ];
      
      $triggering_element = $form_state->getTriggeringElement();
      if (!is_null($triggering_element) && !empty(array_diff($triggering_element['#parents'], ['actions', 'next'])) && !empty(array_diff($triggering_element['#parents'], array_merge($element['#parents'], ['items', $item->id(), 'actions', 'update'])))) {
        $user_input = &$form_state->getUserInput();
        NestedArray::setValue($user_input, array_merge($element['#parents'], ['items', $item->id(), 'quantity']), NULL);
      }
      
      $element['items'][$item->id()]['quantity'] = [
        '#type' => 'number',
        '#title' => t('Quantity'),
        '#required' => TRUE,
        '#default_value' => $item->getQuantity(),
        '#min' => 0,
      ];
      $price_states = [];
      if ($allow_override_price) {
        $element['items'][$item->id()]['overridden_price'] = [
          '#type' => 'checkbox',
          '#title' => t('Override Unit Price'),
          '#default_value' => $item->isUnitPriceOverridden(),
        ];
        $overriden_checkbox_name = reset($element['#parents']) . '[' . implode('][', array_merge(array_slice($element['#parents'], 1), ['items', $item->id(), 'overridden_price'])) . ']';
        $price_states = [
          'disabled' => [
            'input[name="' . $overriden_checkbox_name . '"]' => ['checked' => FALSE],
          ],
        ];
      }
      $element['items'][$item->id()]['price'] = [
        '#type' => 'commerce_price',
        '#title' => t('Unit Price'),
        '#required' => TRUE,
        '#default_value' => $item->getUnitPrice()->toArray(),
        '#states' => !empty($price_states) ? $price_states : [],
      ];
      $element['items'][$item->id()]['actions'] = [
        '#type' => 'container',
        '#weight' => 10,
      ];
      $element['items'][$item->id()]['actions']['update'] = [
        '#type' => 'submit',
        '#value' => t('Update'),
        '#name' => 'commerce_admin_checkout_order_items_update_' . $item->id(),
        '#limit_validation_errors' => [
          array_merge($element['#parents'], ['items', $item->id()]),
        ],
        '#submit' => [
          [get_called_class(), 'ajaxUpdateOrderItem'],
        ],
        '#ajax' => [
          'callback' => [CheckoutFlowBase::class, 'ajaxRefreshForm'],
        ],
      ];
      $element['items'][$item->id()]['actions']['remove'] = [
        '#type' => 'submit',
        '#value' => t('Remove'),
        '#name' => 'commerce_admin_checkout_order_items_remove_' . $item->id(),
        '#limit_validation_errors' => [
          array_merge($element['#parents'], ['items', $item->id()]),
        ],
        '#submit' => [
          [get_called_class(), 'ajaxRemoveOrderItem'],
        ],
        '#ajax' => [
          'callback' => [CheckoutFlowBase::class, 'ajaxRefreshForm'],
        ],
      ];
    }

    $element['items']['new']['variation'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'commerce_product_variation',
      '#selection_handler' => 'views',
      '#selection_settings' => [
        'view' => [
          'view_name' => 'commerce_admin_checkout_variations',
          'display_name' => 'entity_reference_1',
        ],
      ],
      '#title' => t('Product Variation'),
    ];
    $element['items']['new']['quantity'] = [
      '#type' => 'number',
      '#title' => t('Quantity'),
    ];
    $price_states = [];
    if ($allow_override_price) {
      $element['items']['new']['overridden_price'] = [
        '#type' => 'checkbox',
        '#title' => t('Override Unit Price'),
        '#default_value' => FALSE,
      ];
      $overriden_checkbox_name = reset($element['#parents']) . '[' . implode('][', array_merge(array_slice($element['#parents'], 1), ['items', 'new', 'overridden_price'])) . ']';
      $price_states = [
        'disabled' => [
          'input[name="' . $overriden_checkbox_name . '"]' => ['checked' => FALSE],
        ]
      ];
    }
    $element['items']['new']['price'] = [
      '#type' => 'commerce_price',
      '#title' => t('Unit Price'),
      '#placeholder' => '',
      '#states' => !empty($price_states) ? $price_states : [],
    ];
    $element['items']['new']['actions'] = [
      '#type' => 'container',
      '#weight' => 10,
    ];
    $element['items']['new']['actions']['add'] = [
      '#type' => 'submit',
      '#value' => t('Add'),
      '#name' => 'commerce_admin_checkout_order_items_add_new',
      '#limit_validation_errors' => [
        array_merge($element['#parents'], ['items', 'new']),
      ],
      '#submit' => [
        [get_called_class(), 'ajaxAddOrderItem'],
      ],
      '#ajax' => [
        'callback' => [CheckoutFlowBase::class, 'ajaxRefreshForm'],
      ],
    ];

    return $element;
  }
  


  /**
   * Validates the gift card redemption element.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateForm(array &$element, FormStateInterface &$form_state) {
    // Prevent order from going to review if there are no order items.
    $triggering_element = $form_state->getTriggeringElement();
    if (empty($triggering_element['#parents']) || $triggering_element['#parents'][0] != 'commerce_admin_checkout_order_items') {
      $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
      /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
      $order = $order_storage->load($element['#order_id']);
      if (!$order->hasItems()) {
        $form_state->setError($element['items']['new']['variation'], t('This order must contain items to continue checkout.'));
      }
    }
  }

  /**
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param array $parents
   * @param mixed|null $value
   */
  public static function setUserInput(FormStateInterface &$form_state, array $parents, $value = NULL) {
    $user_input = &$form_state->getUserInput();
    NestedArray::setValue($user_input, $parents, $value);
  }

}
