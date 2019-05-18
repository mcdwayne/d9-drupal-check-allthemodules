<?php

namespace Drupal\commerce_admin_checkout\Element;

use Drupal\commerce_admin_checkout\Event\AdminCheckoutEvent;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowWithPanesBase;
use Drupal\commerce_order\Entity\OrderInterface;
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
 * @FormElement("commerce_admin_checkout_order_assign_form")
 */
class AdminCheckoutOrderAssignForm extends FormElement {

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

      '#title' => t('Assign Order'),
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
      if (!empty($values['assign']['uid']) && $account = User::load($values['assign']['uid'])) {
        /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher */
        $event_dispatcher = \Drupal::service('event_dispatcher');
        $event_dispatcher->dispatch(AdminCheckoutEvent::CHECKOUT_ASSIGN, new AdminCheckoutEvent($order, $account, $form, $form_state));
      }
    }
    $form_state->setRebuild();
  }

  /**
   * Ajax callback.
   */
  public static function ajaxCreateCustomerAssignOrder(array $form, FormStateInterface $form_state) {
    if (empty($form_state->getErrors())) {
      $values = $form_state->getValue(['commerce_admin_checkout_order_assign', 'form']);
      if ($values['assign']['customer_type'] == 'new') {
        $user = User::create([
          'name' => $values['assign']['mail'],
          'mail' => $values['assign']['mail'],
          'pass' => ($values['assign']['password']['generate']) ? user_password() : $values['assign']['password']['password_confirm_wrapper']['pass'],
          'status' => TRUE,
        ]);
        $user->save();
        _user_mail_notify('register_admin_created', $user);
        $values['assign']['uid'] = $user->id();
        drupal_set_message(t('Created new user account for %user.', ['%user' => $user->getAccountName()]));
      }
      $form_state->setValue(['commerce_admin_checkout_order_assign', 'form'], $values);
      
      $build_info = $form_state->getBuildInfo();
      /** @var \Drupal\commerce_order\Entity\Order $order */
      $order = $build_info['callback_object']->getOrder();
      if (!empty($values['assign']['uid']) && $account = User::load($values['assign']['uid'])) {
        $order->setCustomer($account);
        $order->setEmail($account->getEmail());
        $order->save();
      }
    }
    static::setUserInput($form_state, ['commerce_admin_checkout_order_assign', 'form'], NULL);
    static::setFormValues($form_state, ['commerce_admin_checkout_order_assign', 'form'], NULL);
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
        '#theme' => 'commerce_admin_checkout_order_assign_form',
        '#prefix' => '<div data-drupal-selector="' . $wrapper_id . '" id="' . $wrapper_id . '">',
        '#suffix' => '</div>',
        // Pass the id along to other methods.
        '#wrapper_id' => $wrapper_id,
      ] + $element;

    $form_open = FALSE;
    $selected_customer_type = $form_state->getValue(array_merge($element['#parents'], ['assign', 'customer_type']));
    if (!empty($selected_customer_type)) {
      $form_open = TRUE;
    }
    else {
      $selected_customer_type = 'existing';
    }
    if ($order->getCustomer()->isAnonymous()) {
      $form_open = TRUE;
    }
    
    $element['#customer'] = $order->getCustomer()->getDisplayName();
    
    $element['assign'] = [
      '#type' => 'container'
    ];
    $element['assign']['customer_type'] = [
      '#type' => 'radios',
      '#title' => t('Order for'),
      '#title_display' => 'invisible',
      '#attributes' => [
        'class' => ['container-inline'],
      ],
      '#required' => TRUE,
      '#options' => [
        'existing' => t('Existing customer'),
        'new' => t('New customer'),
      ],
      '#default_value' => $selected_customer_type,
      '#ajax' => [
        'callback' => [CheckoutFlowBase::class, 'ajaxRefreshForm'],
      ],
    ];
    if ($selected_customer_type == 'existing') {
      $element['assign']['uid'] = [
        '#type' => 'entity_autocomplete',
        '#title' => t('Search'),
        '#attributes' => [
          'class' => ['container-inline'],
        ],
        '#placeholder' => t('Search by username or email address'),
        '#target_type' => 'user',
        '#selection_settings' => [
          'match_operator' => 'CONTAINS',
          'include_anonymous' => FALSE,
        ],
      ];
      $element['assign']['mail'] = [
        '#type' => 'hidden',
        '#default_value' => '',
      ];
      $element['assign']['password'] = [
        '#type' => 'container',
      ];
      $element['assign']['password']['generate'] = [
        '#type' => 'hidden',
        '#value' => 1,
      ];

      $element['assign']['assign'] = [
        '#type' => 'submit',
        '#value' => t('Assign Order to Customer'),
        '#name' => 'commerce_admin_checkout_order_assign',
        '#limit_validation_errors' => [
          array_merge($element['#parents']),
        ],
        '#submit' => [
          [get_called_class(), 'ajaxAssignOrder'],
        ],
        '#ajax' => [
          'callback' => [CheckoutFlowBase::class, 'ajaxRefreshForm'],
        ],
      ];
    }
    else {
      // New customer.
      $element['assign']['uid'] = [
        '#type' => 'value',
        '#value' => 0,
      ];
      $element['assign']['mail'] = [
        '#type' => 'email',
        '#title' => t('Email'),
        '#required' => TRUE,
      ];
      $element['assign']['password'] = [
        '#type' => 'container',
      ];
      $element['assign']['password']['generate'] = [
        '#type' => 'checkbox',
        '#title' => t('Generate password'),
        '#default_value' => TRUE,
      ];
      $generate_checkbox_name = reset($element['#parents']) . '[' . implode('][', array_merge(array_slice($element['#parents'], 1), ['assign', 'password', 'generate'])) . ']';

      // The password_confirm element needs to be wrapped in order for #states
      // to work properly. See https://www.drupal.org/node/1427838.
      $element['assign']['password']['password_confirm_wrapper'] = [
        '#type' => 'container',
        '#states' => [
          'visible' => [
            ':input[name="' . $generate_checkbox_name . '"]' => ['checked' => FALSE],
          ],
        ],
      ];
      // We cannot make this required due to HTML5 validation.
      $element['assign']['password']['password_confirm_wrapper']['pass'] = [
        '#type' => 'password_confirm',
        '#size' => 25,
      ];

      $element['assign']['create_assign'] = [
        '#type' => 'submit',
        '#value' => t('Create Customer Account and Assign Order'),
        '#name' => 'commerce_admin_checkout_order_create_assign',
        '#limit_validation_errors' => [
          array_merge($element['#parents']),
        ],
        '#submit' => [
          [get_called_class(), 'ajaxCreateCustomerAssignOrder'],
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
    $values = $form_state->getValue($element['#parents']);
    if (!empty($values['assign']['customer_type']) && $values['assign']['customer_type'] == 'new') {
      $existing_user = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['mail' => $values['assign']['mail']]);
      if (!empty($existing_user)) {
        $form_state->setError($element['assign']['mail'], t('A user with email address %email already exists.', ['%email' => $values['assign']['mail']]));
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
