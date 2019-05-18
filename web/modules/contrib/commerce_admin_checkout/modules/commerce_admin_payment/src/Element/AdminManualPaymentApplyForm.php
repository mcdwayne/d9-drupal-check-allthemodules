<?php

namespace Drupal\commerce_admin_payment\Element;

use Drupal\commerce_admin_payment\Plugin\Commerce\PaymentGateway\AdminManualPaymentGatewayInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowWithPanesBase;
use Drupal\commerce_multi_payment\Entity\StagedPaymentInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_price\Price;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\user\Entity\User;

/**
 * Provides a form element for editing assigning an order to another customer
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
 * @FormElement("commerce_admin_payment_apply_form")
 */
class AdminManualPaymentApplyForm extends FormElement {
  
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

      '#title' => t('Apply Payments'),
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
  public static function ajaxAssignOrder(array $form, FormStateInterface $form_state) {
    if (empty($form_state->getErrors())) {
      $values = $form_state->getValue(['commerce_admin_checkout_order_assign', 'form']);
      $build_info = $form_state->getBuildInfo();
      
      /** @var \Drupal\commerce_order\Entity\Order $order */
      $order = $build_info['callback_object']->getOrder();
      if (!empty($values['uid']) && $account = User::load($values['uid'])) {
        $order->setCustomer($account);
        $order->setEmail($account->getEmail());
        $order->save();
      }
    }
    $form_state->setRebuild();
  }

  /**
   * Ajax callback.
   */
  public static function ajaxCreateStagedPayment(array $form, FormStateInterface $form_state) {
    if (empty($form_state->getErrors())) {
      /** @var \Drupal\commerce_multi_payment\MultiplePaymentManagerInterface $multi_payment_manager */
      $multi_payment_manager = \Drupal::service('commerce_multi_payment.manager');
      $add_form_parents = array_slice($form_state->getTriggeringElement()['#parents'], 0, -1);
      $values = $form_state->getValue($add_form_parents);
      if (!empty($values['gateway']) && $gateway = \Drupal::service('commerce_admin_payment.manager')->loadPaymentGateway($values['gateway'])) {
        /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $gateway */
        $build_info = $form_state->getBuildInfo();
        /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
        $order = $build_info['callback_object']->getOrder();
        
        $staged_payment = $multi_payment_manager->createStagedPayment([
          'order_id' => $order->id(),
          'payment_gateway' => $gateway->id(),
          'data' => ['description' => $values['form']['description']],
        ]);
        $amount = new Price($values['form']['amount']['number'], $values['form']['amount']['currency_code']);
        $staged_payment->setAmount($amount);
        $staged_payment->setAmount($multi_payment_manager->getAdjustedPaymentAmount($staged_payment));
        $order->get('staged_multi_payment')->appendItem($staged_payment);
        $order->save();
      }
      
      static::setUserInput($form_state, $add_form_parents, NULL);
      static::setFormValues($form_state, $add_form_parents, NULL);
    }
    $form_state->setRebuild();
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRemoveStagedPayment(array $form, FormStateInterface $form_state) {
    if (empty($form_state->getErrors())) {
      /** @var \Drupal\commerce_multi_payment\MultiplePaymentManagerInterface $multi_payment_manager */
      $multi_payment_manager = \Drupal::service('commerce_multi_payment.manager');
      /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $gateway */
      $build_info = $form_state->getBuildInfo();
      /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
      $order = $build_info['callback_object']->getOrder();
      
      $staged_payment_parents = array_slice($form_state->getTriggeringElement()['#parents'], 0, -1);
      $staged_payment_id = array_pop($staged_payment_parents);
      
      $staged_payment = $multi_payment_manager->loadStagedPayment($staged_payment_id);
      $staged_payment->delete();

      $order->save();
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
      throw new \InvalidArgumentException('The commerce_admin_payment_apply_form element requires the #order_id property.');
    }
    /** @var \Drupal\commerce_multi_payment\MultiplePaymentManagerInterface $multi_payment_manager */
    $multi_payment_manager = \Drupal::service('commerce_multi_payment.manager');
    $order = $multi_payment_manager->loadOrder($element['#order_id']);
    if (!$order instanceof OrderInterface) {
      throw new \InvalidArgumentException('The commerce_admin_payment_apply_form #order_id must be a valid order ID.');
    }

    $id_prefix = implode('-', $element['#parents']);
    // @todo We cannot use unique IDs, or multiple elements on a page currently.
    // @see https://www.drupal.org/node/2675688
    // $wrapper_id = Html::getUniqueId($id_prefix . '-ajax-wrapper');
    $wrapper_id = $id_prefix . '-ajax-wrapper';

    $element = [
        '#tree' => TRUE,
        '#theme' => 'commerce_admin_payment_apply_form',
        '#prefix' => '<div data-drupal-selector="' . $wrapper_id . '" id="' . $wrapper_id . '">',
        '#suffix' => '</div>',
        // Pass the id along to other methods.
        '#wrapper_id' => $wrapper_id,
      ] + $element;

    $staged_payments = $order->get('staged_multi_payment')->referencedEntities();
    if (!empty($staged_payments)) {
      /** @var \Drupal\commerce_multi_payment\Entity\StagedPaymentInterface[] $staged_admin_payments */
      $staged_admin_payments = array_filter($staged_payments, function($staged_payment) {
        return $staged_payment->getPaymentGateway()->getPlugin() instanceof AdminManualPaymentGatewayInterface;
      });
      $element['staged_payments'] = [
        '#type' => 'container',
      ];
      foreach ($staged_admin_payments as $staged_payment) {
        $formatted_amount = [
          '#type' => 'inline_template',
          '#template' => '{{ price|commerce_price_format }}',
          '#context' => [
            'price' => $staged_payment->getAmount(),
          ],
        ];

        $element['staged_payments'][$staged_payment->id()] = [
          '#type' => 'fieldset',
          '#title' => t('@label: @amount', [
            '@label' => $staged_payment->getPaymentGateway()->getPlugin()->multiPaymentDisplayLabel(),
            '@amount' => render($formatted_amount),
          ]),
        ];
        $element['staged_payments'][$staged_payment->id()]['#amount'] = $staged_payment->getAmount();
        $element['staged_payments'][$staged_payment->id()]['#payment_gateway_label'] = $staged_payment->getPaymentGateway()->getPlugin()->multiPaymentDisplayLabel();
        $element['staged_payments'][$staged_payment->id()]['description'] = [
          '#plain_text' => $staged_payment->getData('description'),
        ];
        $element['staged_payments'][$staged_payment->id()]['remove'] = [
          '#type' => 'submit',
          '#value' => t('Remove'),
          '#name' => 'commerce_admin_payment_remove_' . $staged_payment->id(),
          '#limit_validation_errors' => [
            array_merge($element['#parents'], ['staged_payments', $staged_payment->id()]),
          ],
          '#submit' => [
            [get_called_class(), 'ajaxRemoveStagedPayment'],
          ],
          '#ajax' => [
            'callback' => [CheckoutFlowBase::class, 'ajaxRefreshForm'],
          ],
        ];
      }
    }
    
    
    $payment_gateways = \Drupal::service('commerce_admin_payment.manager')->getAdminManualPaymentGateways($order);

    $gateway_options = array_merge(['' => ''], array_map(function(PaymentGateway $gateway) {
      return $gateway->getPlugin()->multiPaymentDisplayLabel();
    }, $payment_gateways));

    $element['add'] = [
      '#type' => 'fieldset',
      '#title' => t('Add Payment'),
    ];
    $element['add']['gateway'] = [
      '#type' => 'select',
      '#title' => t('Payment Type'),
      '#options' => $gateway_options,
      '#ajax' => [
        'callback' => [CheckoutFlowBase::class, 'ajaxRefreshForm'],
      ],
    ];

    $selected_gateway = $form_state->getValue(array_merge($element['#parents'], ['add', 'gateway']), NULL);
    if (!empty($selected_gateway)) {
      $payment_gateway_plugin = $payment_gateways[$selected_gateway]->getPlugin();
      $element['add']['form'] = $payment_gateway_plugin->multiPaymentBuildForm($element, $form_state, $complete_form, $order);

      $element['add']['apply'] = [
        '#type' => 'submit',
        '#value' => t('Apply'),
        '#name' => 'commerce_admin_payment_apply_new',
        '#limit_validation_errors' => [
          array_merge($element['#parents'], ['add']),
        ],
        '#submit' => [
          [get_called_class(), 'ajaxCreateStagedPayment'],
        ],
        '#ajax' => [
          'callback' => [CheckoutFlowBase::class, 'ajaxRefreshForm'],
        ],
      ];
    }

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

  /**
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param array $parents
   * @param mixed|null $value
   */
  public static function setFormValues(FormStateInterface &$form_state, array $parents, $value = NULL) {
    $values = &$form_state->getValues();
    NestedArray::setValue($values, $parents, $value);
  }
}
